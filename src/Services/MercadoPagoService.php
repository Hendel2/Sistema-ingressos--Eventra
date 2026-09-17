<?php
declare(strict_types=1);

class MercadoPagoService
{
    private static function accessToken(): string
    {
        return $GLOBALS['config']['mercadopago']['access_token'];
    }

    
    public static function createPreference(int $orderId, array $items, string $payerEmail): array
    {
        $baseUrl = rtrim($GLOBALS['config']['app']['base_url'], '/');

        $payload = [
            'items' => array_map(static function (array $item): array {
                return [
                    'title' => $item['title'],
                    'quantity' => $item['quantity'],
                    'unit_price' => (float) $item['unit_price'],
                    'currency_id' => 'BRL',
                ];
            }, $items),
            'external_reference' => (string) $orderId,
            'notification_url' => $baseUrl . '/api/webhook-mp.php',
        ];

        
        
        
        $isPublic = str_starts_with($baseUrl, 'https://');
        if ($isPublic) {
            $payload['back_urls'] = [
                'success' => $baseUrl . '/checkout-retorno.php?status=success',
                'failure' => $baseUrl . '/checkout-retorno.php?status=failure',
                'pending' => $baseUrl . '/checkout-retorno.php?status=pending',
            ];
            
            $payload['auto_return'] = 'approved';
        }

        
        
        $payload['payment_methods'] = [
            'excluded_payment_types' => [
                ['id' => 'ticket'],
                ['id' => 'atm'],
            ],
        ];

        return self::request('POST', '/checkout/preferences', $payload);
    }

    
    public static function checkoutUrl(array $preference): ?string
    {
        $sandbox = (bool) ($GLOBALS['config']['mercadopago']['sandbox'] ?? false);
        $url = $sandbox
            ? ($preference['sandbox_init_point'] ?? null)
            : ($preference['init_point'] ?? null);

        return $url ?: ($preference['init_point'] ?? $preference['sandbox_init_point'] ?? null);
    }

    public static function getPayment(string $paymentId): array
    {
        return self::request('GET', '/v1/payments/' . urlencode($paymentId));
    }

    
    public static function paymentsForOrder(int $orderId): array
    {
        $data = self::request(
            'GET',
            '/v1/payments/search?sort=date_created&criteria=desc&external_reference=' . $orderId
        );

        return is_array($data['results'] ?? null) ? $data['results'] : [];
    }

    private static function request(string $method, string $path, ?array $body = null): array
    {
        $ch = curl_init('https://api.mercadopago.com' . $path);
        $headers = [
            'Authorization: Bearer ' . self::accessToken(),
            'Content-Type: application/json',
        ];

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 15,
        ]);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_THROW_ON_ERROR));
        }

        $response = curl_exec($ch);
        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException('Erro ao comunicar com o Mercado Pago: ' . $error);
        }

        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode((string) $response, true);
        if (!is_array($data)) {
            $data = [];
        }

        if ($status >= 400) {
            $message = $data['message'] ?? $response;
            throw new RuntimeException("Mercado Pago retornou erro HTTP {$status}: {$message}");
        }

        return $data;
    }
}
