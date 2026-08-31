<?php
declare(strict_types=1);

class MercadoPagoService
{
    private static function accessToken(): string
    {
        return $GLOBALS['config']['mercadopago']['access_token'];
    }

    /**
     * Cria uma preferência de pagamento (Checkout Pro) para o pedido informado.
     * @param array<int,array{title:string,quantity:int,unit_price:float}> $items
     */
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
            'payer' => ['email' => $payerEmail],
            'external_reference' => (string) $orderId,
            'back_urls' => [
                'success' => $baseUrl . '/checkout-retorno.php?status=success',
                'failure' => $baseUrl . '/checkout-retorno.php?status=failure',
                'pending' => $baseUrl . '/checkout-retorno.php?status=pending',
            ],
            // 'auto_return' exige um domínio público válido em back_urls.success;
            // em localhost o Mercado Pago rejeita a preferência com esse campo presente.
            // Sem ele, o usuário só precisa clicar em "Voltar ao site" após pagar.
            'notification_url' => $baseUrl . '/api/webhook-mp.php',
        ];

        return self::request('POST', '/checkout/preferences', $payload);
    }

    public static function getPayment(string $paymentId): array
    {
        return self::request('GET', '/v1/payments/' . urlencode($paymentId));
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
