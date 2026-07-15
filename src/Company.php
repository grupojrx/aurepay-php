<?php

declare(strict_types=1);

namespace AurePay;

/** Empresa autenticada e saldo. */
final class Company extends Resource
{
    /** Dados da empresa (GET /company). */
    public function get(): mixed
    {
        return $this->http->request('GET', '/company');
    }

    /** Saldo disponível (GET /company/balance). */
    public function balance(): mixed
    {
        return $this->http->request('GET', '/company/balance');
    }
}
