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

// Integração de pagamento (Mercado Pago) temporariamente desativada:
// o pedido é confirmado direto, sem cobrança real. O estoque já foi
// reservado acima. Para reativar o pagamento real, volte a chamar
// MercadoPagoService::createPreference() e redirecionar para o checkoutUrl
// em vez do bloco abaixo.
OrderModel::updateStatus($orderId, 'paid');
TicketService::generateForOrder($orderId);
CartService::clear();

flash_set('success', 'Compra confirmada! Seus ingressos já estão em "Minhas Compras".');
redirect('minhas-compras.php');
