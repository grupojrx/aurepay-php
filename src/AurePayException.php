<?php

declare(strict_types=1);

namespace AurePay;

use Exception;

/** Erro tipado da API AurePay. */
final class AurePayException extends Exception
{
    public function __construct(
        string $message,
        public readonly ?string $codeName = null,
        public readonly mixed $details = null,
        public readonly int $statusCode = 0,
        ?Exception $previous = null,
    ) {
        parent::__construct($message, $statusCode, $previous);
    }
}
