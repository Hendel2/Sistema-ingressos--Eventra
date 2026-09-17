<?php
require_once __DIR__ . '/../../src/bootstrap.php';

$type = $_GET['type'] ?? $_GET['topic'] ?? null;
$paymentId = $_GET['data_id'] ?? $_GET['id'] ?? null;

if ($paymentId === null) {
    $raw = file_get_contents('php://input');
    $body = json_decode($raw, true);
    if (is_array($body)) {
        $paymentId = $body['data']['id'] ?? null;
        $type = $type ?? ($body['type'] ?? null);
    }
}

http_response_code(200);
header('Content-Type: text/plain');

if ($type === 'payment' && $paymentId !== null) {
    try {
        TicketService::confirmPayment((string) $paymentId);
    } catch (Throwable $e) {
        error_log('Erro no webhook do Mercado Pago: ' . $e->getMessage());
    }
}

echo 'ok';
