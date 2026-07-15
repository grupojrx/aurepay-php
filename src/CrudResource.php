<?php

declare(strict_types=1);

namespace AurePay;

/** Recurso CRUD genérico (list/create/get/update/delete). */
final class CrudResource extends Resource
{
    public function __construct(
        HttpClient $http,
        private readonly string $basePath,
    ) {
        parent::__construct($http);
    }

    /**
     * Lista recursos (GET).
     *
     * @param array<string, mixed> $query
     */
    public function list(array $query = []): mixed
    {
        $path = $this->basePath;

        if ($query !== []) {
            $path .= '?' . http_build_query($query);
        }

        return $this->http->request('GET', $path);
    }

    /**
     * Cria recurso (POST); opcional `Idempotency-Key`.
     *
     * @param array<string, mixed> $payload
     */
    public function create(array $payload, ?string $idempotencyKey = null): mixed
    {
        $headers = $idempotencyKey !== null ? ['Idempotency-Key' => $idempotencyKey] : [];

        return $this->http->request('POST', $this->basePath, $payload, $headers);
    }

    /** Consulta por ID (GET). */
    public function get(string $id): mixed
    {
        return $this->http->request('GET', $this->basePath . '/' . rawurlencode($id));
    }

    /**
     * Atualiza por ID (PUT).
     *
     * @param array<string, mixed> $payload
     */
    public function update(string $id, array $payload): mixed
    {
        return $this->http->request('PUT', $this->basePath . '/' . rawurlencode($id), $payload);
    }

    /** Remove por ID (DELETE). */
    public function delete(string $id): mixed
    {
        return $this->http->request('DELETE', $this->basePath . '/' . rawurlencode($id));
    }
}
