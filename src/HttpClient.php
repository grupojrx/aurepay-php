<?php

declare(strict_types=1);

namespace AurePay;

/** Cliente HTTP autenticado (cURL) com retry em 429. */
final class HttpClient
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $apiSecret,
        private readonly string $baseUrl = 'https://api.aurepay.com.br/v1',
        private readonly int $maxRetries = 2,
    ) {
    }

    /**
     * Executa requisição autenticada e desempacota o envelope `data`.
     *
     * @param array<string, mixed>|null $body
     * @param array<string, string> $extraHeaders
     */
    public function request(
        string $method,
        string $path,
        ?array $body = null,
        array $extraHeaders = [],
    ): mixed {
        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($path, '/');
        $attempt = 0;

        while (true) {
            $attempt++;
            $ch = curl_init($url);

            if ($ch === false) {
                throw new AurePayException('Failed to init cURL.');
            }

            $headers = array_merge([
                'X-Api-Key: ' . $this->apiKey,
                'X-Api-Secret: ' . $this->apiSecret,
                'Accept: application/json',
                'Content-Type: application/json',
            ], array_map(
                static fn (string $k, string $v): string => $k . ': ' . $v,
                array_keys($extraHeaders),
                array_values($extraHeaders),
            ));

            $options = [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => strtoupper($method),
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_HEADER => true,
            ];

            if ($body !== null) {
                $options[CURLOPT_POSTFIELDS] = json_encode($body, JSON_THROW_ON_ERROR);
            }

            curl_setopt_array($ch, $options);
            $raw = curl_exec($ch);

            if ($raw === false) {
                $err = curl_error($ch);
                curl_close($ch);
                throw new AurePayException('HTTP request failed: ' . $err);
            }

            $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            curl_close($ch);

            $headerText = substr((string) $raw, 0, $headerSize);
            $responseBody = substr((string) $raw, $headerSize);
            $decoded = $responseBody === '' ? null : json_decode($responseBody, true);

            if ($status === 429 && $attempt <= $this->maxRetries + 1) {
                $retryAfter = 1;

                if (preg_match('/^Retry-After:\s*(\d+)/mi', $headerText, $m)) {
                    $retryAfter = max(1, (int) $m[1]);
                }

                sleep($retryAfter);
                continue;
            }

            if ($status >= 400) {
                $error = is_array($decoded) ? ($decoded['error'] ?? null) : null;
                $message = is_array($error)
                    ? (string) ($error['message'] ?? 'Request failed.')
                    : 'Request failed.';
                $codeName = is_array($error) ? ($error['code'] ?? null) : null;
                $details = is_array($error) ? ($error['details'] ?? null) : null;

                throw new AurePayException($message, is_string($codeName) ? $codeName : null, $details, $status);
            }

            if (is_array($decoded) && array_key_exists('data', $decoded)) {
                return $decoded['data'];
            }

            return $decoded;
        }
    }
}
