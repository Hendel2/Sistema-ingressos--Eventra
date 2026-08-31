<?php
// Copie este arquivo para "config.php" e preencha com seus dados reais.
// O arquivo config.php NÃO deve ser compartilhado publicamente (contém segredos).

return [
    'db' => [
        'host' => '127.0.0.1',
        'name' => 'ingressos_app',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4',
    ],
    'app' => [
        // URL base onde a pasta "public" está acessível
        'base_url' => 'http://localhost/ingressos-app/public',
        'timezone' => 'America/Sao_Paulo',
    ],
    'mercadopago' => [
        // Chaves de TESTE (sandbox) da sua conta Mercado Pago
        // https://www.mercadopago.com.br/developers/panel/app
        'access_token' => 'TEST-0000000000000000-000000-00000000000000000000000000000000-000000000',
        'public_key' => 'TEST-00000000-0000-0000-0000-000000000000',
    ],
];
