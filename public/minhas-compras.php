<?php
require_once __DIR__ . '/../src/bootstrap.php';

Auth::requireLogin();

$orders = OrderModel::byUser((int) Auth::id());





$reconciled = false;
foreach ($orders as $order) {
    if ($order['status'] !== 'pending') {
        continue;
    }
    try {
        if (TicketService::reconcilePendingOrder((int) $order['id']) !== null) {
            $reconciled = true;
        }
    } catch (Throwable $e) {
        error_log('Falha ao conferir o pedido ' . $order['id'] . ': ' . $e->getMessage());
    }
}
if ($reconciled) {
    $orders = OrderModel::byUser((int) Auth::id());
}

$itemsByOrder = [];
$ticketsByOrder = [];
foreach ($orders as $order) {
    $itemsByOrder[$order['id']] = OrderModel::items((int) $order['id']);
    if ($order['status'] === 'paid') {
        $ticketsByOrder[$order['id']] = Ticket::byOrder((int) $order['id']);
    }
}

$statusLabels = [
    'pending' => 'Aguardando pagamento',
    'paid' => 'Pago',
    'cancelled' => 'Cancelado',
    'refunded' => 'Reembolsado',
];

$pageTitle = 'Minhas Compras';
include __DIR__ . '/../templates/header.php';
?>

<section class="orders-page">
    <div class="page-head">
        <h1>Minhas Compras</h1>
        <p><?= count($orders) ?> pedido<?= count($orders) === 1 ? '' : 's' ?></p>
    </div>

    <?php if (empty($orders)): ?>
        <p class="empty-state">Você ainda não fez nenhuma compra. <a href="<?= e(base_url('index.php')) ?>">Ver eventos</a></p>
    <?php endif; ?>

    <?php foreach ($orders as $order): ?>
        <div class="order-card">
            <div class="order-card-header">
                <strong>Pedido #<?= (int) $order['id'] ?></strong>
                <span class="badge badge-<?= e($order['status']) ?>"><?= e($statusLabels[$order['status']] ?? $order['status']) ?></span>
            </div>
            <p><?= e(format_datetime($order['created_at'])) ?> — Total: <?= e(price_label((float) $order['total_amount'])) ?></p>
            <ul>
                <?php foreach ($itemsByOrder[$order['id']] as $item): ?>
                    <li><?= (int) $item['quantity'] ?>x <?= e($item['event_title']) ?> — <?= e($item['ticket_type_name']) ?></li>
                <?php endforeach; ?>
            </ul>

            <?php if ($order['status'] === 'paid' && !empty($ticketsByOrder[$order['id']])): ?>
                <div class="order-tickets">
                    <?php foreach ($ticketsByOrder[$order['id']] as $ticket): ?>
                        <a class="btn btn-secondary" href="<?= e(base_url('ingresso.php?codigo=' . urlencode($ticket['code']))) ?>">
                            Ver ingresso — <?= e($ticket['ticket_type_name']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php elseif ($order['status'] === 'pending'): ?>
                <p><em>Pagamento ainda não confirmado. Se você fechou a tela do Mercado Pago antes de pagar, retome por aqui.</em></p>
                <a class="btn btn-primary" href="<?= e(base_url('pagar.php?pedido=' . (int) $order['id'])) ?>">
                    Pagar pedido #<?= (int) $order['id'] ?>
                </a>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</section>

<?php include __DIR__ . '/../templates/footer.php'; ?>
