<?php



return [
    'db' => [
        'host' => '127.0.0.1',
        'name' => 'ingressos_app',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4',
    ],
    'app' => [
        
        'base_url' => 'http://localhost/ingressos-app/public',
        'timezone' => 'America/Sao_Paulo',
    ],
    'mercadopago' => [
        
        
        'access_token' => 'TEST-0000000000000000-000000-00000000000000000000000000000000-000000000',
        'public_key' => 'TEST-00000000-0000-0000-0000-000000000000',
        
        
        'sandbox' => false,
    ],
];
