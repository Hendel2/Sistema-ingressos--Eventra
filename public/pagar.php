<?php
require_once __DIR__ . '/../src/bootstrap.php';

Auth::requireLogin();

$orderId = (int) ($_GET['pedido'] ?? 0);
$order = OrderModel::find($orderId);

if ($order === null || (int) $order['user_id'] !== (int) Auth::id()) {
    flash_set('error', 'Pedido não encontrado.');
    redirect('minhas-compras.php');
}

if ($order['status'] !== 'pending') {
    redirect('minhas-compras.php');
}

try {
    if (TicketService::reconcilePendingOrder($orderId) !== null) {
        redirect('minhas-compras.php');
    }
} catch (Throwable $e) {
    error_log('Falha ao conferir o pedido ' . $orderId . ' antes de repagar: ' . $e->getMessage());
}

$items = [];
foreach (OrderModel::items($orderId) as $item) {
    $price = (float) $item['unit_price'];
    if ($price <= 0) {
        continue;
    }
    $items[] = [
        'title' => $item['ticket_type_name'] . ' - ' . $item['event_title'],
        'quantity' => (int) $item['quantity'],
        'unit_price' => $price,
    ];
}

if ($items === []) {
    OrderModel::updateStatus($orderId, 'paid');
    TicketService::generateForOrder($orderId);
    flash_set('success', 'Reserva confirmada! Seus ingressos já estão em "Minhas Compras".');
    redirect('minhas-compras.php');
}

try {
    $preference = MercadoPagoService::createPreference($orderId, $items, '');
} catch (Throwable $e) {
    error_log('Erro ao recriar preferência do pedido ' . $orderId . ': ' . $e->getMessage());
    flash_set('error', 'Não foi possível abrir o pagamento. Tente novamente em instantes.');
    redirect('minhas-compras.php');
}

$checkoutUrl = MercadoPagoService::checkoutUrl($preference);
if ($checkoutUrl === null) {
    flash_set('error', 'Não foi possível abrir o pagamento. Tente novamente em instantes.');
    redirect('minhas-compras.php');
}

OrderModel::setPreferenceId($orderId, (string) ($preference['id'] ?? ''));
redirect($checkoutUrl);
