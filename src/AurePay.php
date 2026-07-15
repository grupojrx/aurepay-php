<?php

declare(strict_types=1);

namespace AurePay;

/**
 * Facade principal da API AurePay (PHP).
 */
final class AurePay
{
    public readonly Deposits $deposits;
    public readonly Withdrawals $withdrawals;
    public readonly Webhooks $webhooks;
    public readonly Company $company;
    public readonly Conversions $conversions;
    public readonly Chargebacks $chargebacks;
    public readonly Wallets $wallets;

    private readonly HttpClient $http;

    /**
     * @param array{apiKey: string, apiSecret: string, baseUrl?: string} $config Credenciais e base URL opcional
     */
    public function __construct(array $config)
    {
        $apiKey = trim((string) ($config['apiKey'] ?? ''));
        $apiSecret = trim((string) ($config['apiSecret'] ?? ''));

        if ($apiKey === '' || $apiSecret === '') {
            throw new AurePayException('apiKey and apiSecret are required.');
        }

        $baseUrl = (string) ($config['baseUrl'] ?? 'https://api.aurepay.com.br/v1');

        $this->http = new HttpClient($apiKey, $apiSecret, $baseUrl);
        $this->deposits = new Deposits($this->http);
        $this->withdrawals = new Withdrawals($this->http);
        $this->webhooks = new Webhooks($this->http);
        $this->company = new Company($this->http);
        $this->conversions = new Conversions($this->http);
        $this->chargebacks = new Chargebacks($this->http);
        $this->wallets = new Wallets($this->http);
    }
}
