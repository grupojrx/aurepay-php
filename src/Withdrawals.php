<?php

declare(strict_types=1);

namespace AurePay;

/** Saques (Pix Out) — `/v1/withdrawals`. */
final class Withdrawals extends CrudResource
{
    public function __construct(HttpClient $http)
    {
        parent::__construct($http, '/withdrawals');
    }
}
