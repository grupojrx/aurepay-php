<?php

declare(strict_types=1);

namespace AurePay;

/** Carteiras com saldo isolado — `/v1/wallets`. */
final class Wallets extends CrudResource
{
    public function __construct(HttpClient $http)
    {
        parent::__construct($http, '/wallets');
    }
}
