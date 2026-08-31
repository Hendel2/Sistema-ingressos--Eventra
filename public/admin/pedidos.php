<?php
require_once __DIR__ . '/../../src/bootstrap.php';

Auth::requireAdmin();

$filterEventId = isset($_GET['event_id']) ? (int) $_GET['event_id'] : null;
if ($filterEventId !== null && !EventModel::belongsToOrganizer($filterEventId, (int) Auth::id())) {
    $filterEventId = null;
}

$orders = OrderModel::byOrganizer((int) Auth::id());
$itemsByOrder = [];
foreach ($orders as $order) {
    $items = OrderModel::items((int) $order['id']);
    if ($filterEventId !== null) {
        $items = array_values(array_filter($items, static fn($i) => (int) $i['event_id'] === $filterEventId));
        if (empty($items)) {
            continue;
        }
    }
    $itemsByOrder[$order['id']] = $items;
}

$statusLabels = [
    'pending' => 'Aguardando pagamento',
    'paid' => 'Pago',
    'cancelled' => 'Cancelado',
    'refunded' => 'Reembolsado',
];

$pageTitle = 'Pedidos';
include __DIR__ . '/../../templates/header.php';
?>

<section class="admin-form">
    <h1>Pedidos recebidos</h1>

    <?php if (empty($itemsByOrder)): ?>
        <p class="empty-state">Nenhum pedido encontrado.</p>
    <?php endif; ?>

    <?php foreach ($orders as $order): ?>
        <?php if (!isset($itemsByOrder[$order['id']])) continue; ?>
        <div class="order-card">
            <div class="order-card-header">
                <strong>Pedido #<?= (int) $order['id'] ?></strong> — <?= e($order['customer_name']) ?> (<?= e($order['customer_email']) ?>)
                <span class="badge badge-<?= e($order['status']) ?>"><?= e($statusLabels[$order['status']] ?? $order['status']) ?></span>
            </div>
            <p><?= e(format_datetime($order['created_at'])) ?> — Total: <?= e(format_price((float) $order['total_amount'])) ?></p>
            <ul>
                <?php foreach ($itemsByOrder[$order['id']] as $item): ?>
                    <li><?= (int) $item['quantity'] ?>x <?= e($item['event_title']) ?> — <?= e($item['ticket_type_name']) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endforeach; ?>

    <p><a href="<?= e(base_url('admin/index.php')) ?>">&larr; Voltar ao painel</a></p>
</section>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
