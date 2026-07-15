<?php

declare(strict_types=1);

namespace AurePay;

/** Infrações / MED — `/v1/chargebacks`. */
final class Chargebacks extends Resource
{
    /**
     * Lista infrações (GET).
     *
     * @param array<string, mixed> $query
     */
    public function list(array $query = []): mixed
    {
        $path = '/chargebacks';

        if ($query !== []) {
            $path .= '?' . http_build_query($query);
        }

        return $this->http->request('GET', $path);
    }

    /** Consulta por ID (GET). */
    public function get(string $id): mixed
    {
        return $this->http->request('GET', '/chargebacks/' . rawurlencode($id));
    }
}
