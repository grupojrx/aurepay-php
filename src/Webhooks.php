<?php

declare(strict_types=1);

namespace AurePay;

/** Webhooks da conta — `/v1/webhooks`. */
final class Webhooks extends CrudResource
{
    public function __construct(HttpClient $http)
    {
        parent::__construct($http, '/webhooks');
    }
}
