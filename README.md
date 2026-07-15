# aurepay/sdk

SDK oficial da API AurePay para PHP.

## Instalação

```bash
composer require aurepay/sdk
```

## Uso

```php
<?php

use AurePay\AurePay;

$aure = new AurePay([
    'apiKey' => getenv('AUREPAY_API_KEY'),
    'apiSecret' => getenv('AUREPAY_API_SECRET'),
]);

$aure->deposits->create([
    'amount' => 10000,
    'method' => 'pix',
]);

$aure->webhooks->list();
$aure->company->balance();
```

Docs: https://api.aurepay.com.br/docs/sdks  
OpenAPI: https://api.aurepay.com.br/openapi.yaml
