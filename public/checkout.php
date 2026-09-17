<?php
require_once __DIR__ . '/../src/bootstrap.php';

Auth::requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('carrinho.php');
}
verify_csrf();

$items = CartService::detailed();
if (empty($items)) {
    flash_set('error', 'Seu carrinho está vazio.');
    redirect('carrinho.php');
}

$pdo = Database::pdo();

$pdo->beginTransaction();
try {
    $total = array_sum(array_column($items, 'subtotal'));
    $orderId = OrderModel::create((int) Auth::id(), $total);

    $reserved = [];
    $outOfStock = null;
    foreach ($items as $item) {
        $ticketTypeId = (int) $item['ticket_type']['id'];
        $qty = (int) $item['quantity'];
        if (!TicketType::reserveStock($ticketTypeId, $qty)) {
            $outOfStock = $item['ticket_type']['name'];
            break;
        }
        $reserved[] = ['id' => $ticketTypeId, 'qty' => $qty];
        OrderModel::addItem($orderId, $ticketTypeId, $qty, (float) $item['ticket_type']['price']);
    }

    if ($outOfStock !== null) {
        foreach ($reserved as $r) {
            TicketType::releaseStock($r['id'], $r['qty']);
        }
        $pdo->rollBack();
        flash_set('error', "Estoque insuficiente para \"{$outOfStock}\". Ajuste a quantidade no carrinho.");
        redirect('carrinho.php');
    }

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    error_log('Erro ao criar pedido: ' . $e->getMessage());
    flash_set('error', 'Não foi possível processar seu pedido. Tente novamente.');
    redirect('carrinho.php');
}




$buyer = User::findById((int) Auth::id());



$payableItems = [];
foreach ($items as $item) {
    $price = (float) $item['ticket_type']['price'];
    if ($price <= 0) {
        continue;
    }
    $payableItems[] = [
        'title' => $item['ticket_type']['name'] . ' - ' . ($item['event']['title'] ?? 'Evento'),
        'quantity' => (int) $item['quantity'],
        'unit_price' => $price,
    ];
}


if ($payableItems === []) {
    OrderModel::updateStatus($orderId, 'paid');
    TicketService::generateForOrder($orderId);
    CartService::clear();
    flash_set('success', 'Reserva confirmada! Seus ingressos já estão em "Minhas Compras".');
    redirect('minhas-compras.php');
}

try {
    $preference = MercadoPagoService::createPreference(
        $orderId,
        $payableItems,
        (string) ($buyer['email'] ?? '')
    );
} catch (Throwable $e) {
    
    error_log('Erro ao criar preferência no Mercado Pago: ' . $e->getMessage());
    TicketService::releaseOrderStock($orderId);
    OrderModel::updateStatus($orderId, 'cancelled');
    flash_set('error', 'Não foi possível iniciar o pagamento. Tente novamente em instantes.');
    redirect('carrinho.php');
}

$checkoutUrl = MercadoPagoService::checkoutUrl($preference);
if ($checkoutUrl === null) {
    error_log('Mercado Pago não devolveu init_point para o pedido ' . $orderId);
    TicketService::releaseOrderStock($orderId);
    OrderModel::updateStatus($orderId, 'cancelled');
    flash_set('error', 'Não foi possível iniciar o pagamento. Tente novamente em instantes.');
    redirect('carrinho.php');
}

OrderModel::setPreferenceId($orderId, (string) ($preference['id'] ?? ''));
CartService::clear();

redirect($checkoutUrl);
