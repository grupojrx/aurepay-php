/**
 * Decide se aurepay/sdk (PHP) deve ganhar nova tag/release (Packagist).
 *
 * exit 0  → publicar (criar tag)
 * exit 10 → pular (SDK igual ao latest no Packagist)
 * exit 1  → erro (mudou mas a versão em VERSION já foi tagueada)
 *
 * Env:
 *   SDK_VERSION — opcional; senão lê ../VERSION
 */
import { createHash } from 'node:crypto'
import {
  existsSync,
  mkdtempSync,
  mkdirSync,
  readFileSync,
  readdirSync,
  rmSync,
  statSync,
  writeFileSync
} from 'node:fs'
import { tmpdir } from 'node:os'
import { dirname, join, relative } from 'node:path'
import { fileURLToPath } from 'node:url'
import { execSync } from 'node:child_process'

const root = join(dirname(fileURLToPath(import.meta.url)), '..')
const packageName = 'aurepay/sdk'
const userAgent = 'AurePaySDKPublish (mailto=dev@aurepay.com.br)'

function walkFiles(dir, files = []) {
  for (const name of readdirSync(dir)) {
    const path = join(dir, name)

    if (statSync(path).isDirectory()) {
      walkFiles(path, files)
      continue
    }

    files.push(path)
  }

  return files
}

/**
 * @param {unknown} value
 * @returns {unknown}
 */
function sortKeys(value) {
  if (Array.isArray(value)) {
    return value.map(sortKeys)
  }

  if (value && typeof value === 'object') {
    const object = /** @type {Record<string, unknown>} */ (value)
    const sorted = {}

    for (const key of Object.keys(object).sort()) {
      sorted[key] = sortKeys(object[key])
    }

    return sorted
  }

  return value
}

/**
 * Hash estável: src/ + composer.json (Packagist não usa "version" no composer.json).
 * @param {string} base
 */
function contentHash(base) {
  const hash = createHash('sha256')
  const composerPath = join(base, 'composer.json')

  if (existsSync(composerPath)) {
    const parsed = JSON.parse(readFileSync(composerPath, 'utf8'))
    delete parsed.version
    hash.update('composer.json\0')
    hash.update(JSON.stringify(sortKeys(parsed)))
    hash.update('\0')
  }

  const dirs = ['src', 'generated'].map((name) => join(base, name)).filter((dir) => existsSync(dir))

  if (dirs.length === 0) {
    throw new Error(`src/ or generated/ missing in ${base}`)
  }

  const files = dirs
    .flatMap((dir) => walkFiles(dir))
    .sort((a, b) => relative(base, a).localeCompare(relative(base, b)))

  for (const file of files) {
    const rel = relative(base, file).replaceAll('\\', '/')
    const body = readFileSync(file, 'utf8').replaceAll('\r\n', '\n')
    hash.update(rel)
    hash.update('\0')
    hash.update(body)
    hash.update('\0')
  }

  return hash.digest('hex')
}

function readVersion() {
  if (process.env.SDK_VERSION) {
    return process.env.SDK_VERSION.replace(/^v/, '')
  }

  const versionPath = join(root, 'VERSION')

  if (!existsSync(versionPath)) {
    throw new Error('VERSION file missing (and SDK_VERSION unset)')
  }

  return readFileSync(versionPath, 'utf8').trim().replace(/^v/, '')
}

/**
 * @returns {Promise<{ version: string, distUrl: string } | null>}
 */
async function fetchPackagistLatest() {
  const response = await fetch(`https://repo.packagist.org/p2/${packageName}.json`, {
    headers: { 'User-Agent': userAgent }
  })

  if (response.status === 404) {
    return null
  }

  if (!response.ok) {
    throw new Error(`Packagist HTTP ${response.status}`)
  }

  const data = await response.json()
  const versions = data?.packages?.[packageName]

  if (!Array.isArray(versions) || versions.length === 0) {
    return null
  }

  const latest = versions[0]
  const distUrl = latest?.dist?.url

  if (!distUrl) {
    throw new Error('Packagist latest has no dist.url')
  }

  return {
    version: String(latest.version).replace(/^v/, ''),
    distUrl
  }
}

/**
 * @param {string} url
 * @param {string} destDir
 */
async function downloadAndExtractZip(url, destDir) {
  const response = await fetch(url, {
    headers: { 'User-Agent': userAgent, Accept: 'application/vnd.github+json' },
    redirect: 'follow'
  })

  if (!response.ok) {
    throw new Error(`download HTTP ${response.status}: ${url}`)
  }

  const buffer = Buffer.from(await response.arrayBuffer())
  const zipPath = join(destDir, 'pkg.zip')
  writeFileSync(zipPath, buffer)

  // GitHub zipballs → uma pasta raiz (GNU tar no Linux não abre zip)
  try {
    execSync(`unzip -qo "${zipPath}" -d "${destDir}"`, { stdio: 'pipe' })
  } catch {
    execSync(`tar -xf "${zipPath}" -C "${destDir}"`, { stdio: 'pipe' })
  }

  const entries = readdirSync(destDir).filter((name) => name !== 'pkg.zip')
  const folder = entries.find((name) => statSync(join(destDir, name)).isDirectory())

  if (!folder) {
    throw new Error('zip extract: no root folder')
  }

  return join(destDir, folder)
}

const version = readVersion()
const localHash = contentHash(root)
const remote = await fetchPackagistLatest()

if (!remote) {
  console.log(`no remote package — publish ${packageName}@${version}`)
  process.exit(0)
}

const work = mkdtempSync(join(tmpdir(), 'aurepay-php-cmp-'))

try {
  mkdirSync(work, { recursive: true })
  const remoteRoot = await downloadAndExtractZip(remote.distUrl, work)
  const remoteHash = contentHash(remoteRoot)

  if (localHash === remoteHash) {
    console.log(
      `SDK unchanged vs ${packageName}@${remote.version} — skip publish (${localHash.slice(0, 12)})`
    )
    process.exit(10)
  }

  if (remote.version === version) {
    console.error(
      `SDK source changed, but ${packageName}@${version} already exists on Packagist. Bump sdks/php/VERSION.`
    )
    process.exit(1)
  }

  console.log(
    `SDK changed (${localHash.slice(0, 8)} ≠ ${remoteHash.slice(0, 8)}) — publish v${version}`
  )
  process.exit(0)
} finally {
  rmSync(work, { recursive: true, force: true })
}
