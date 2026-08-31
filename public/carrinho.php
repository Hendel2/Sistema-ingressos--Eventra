<?php
require_once __DIR__ . '/../src/bootstrap.php';

Auth::requireLogin();

$items = CartService::detailed();
$total = CartService::total();

$pageTitle = 'Carrinho';
include __DIR__ . '/../templates/header.php';
?>

<section class="cart-page">
    <div class="page-head">
        <h1>Seu carrinho</h1>
        <p><?= count($items) ?> item<?= count($items) === 1 ? '' : 's' ?></p>
    </div>

    <?php if (empty($items)): ?>
        <p class="empty-state">Seu carrinho está vazio. <a href="<?= e(base_url('index.php')) ?>">Ver eventos</a></p>
    <?php else: ?>
        <table class="ticket-table">
            <thead>
                <tr><th>Evento</th><th>Ingresso</th><th>Qtd.</th><th>Subtotal</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td data-label="Evento"><?= e($item['event']['title'] ?? '') ?></td>
                    <td data-label="Ingresso"><?= e($item['ticket_type']['name']) ?></td>
                    <td data-label="Qtd.">
                        <form method="post" action="<?= e(base_url('api/carrinho.php')) ?>" class="inline-form">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="ticket_type_id" value="<?= (int) $item['ticket_type']['id'] ?>">
                            <input type="number" name="quantity" min="0" max="<?= (int) $item['ticket_type']['max_per_purchase'] ?>" value="<?= (int) $item['quantity'] ?>" style="width:70px">
                            <button type="submit" class="btn-link">Atualizar</button>
                        </form>
                    </td>
                    <td data-label="Subtotal"><?= e(format_price($item['subtotal'])) ?></td>
                    <td data-label="">
                        <form method="post" action="<?= e(base_url('api/carrinho.php')) ?>" class="inline-form">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="remove">
                            <input type="hidden" name="ticket_type_id" value="<?= (int) $item['ticket_type']['id'] ?>">
                            <button type="submit" class="btn-link btn-link-danger">Remover</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <div class="cart-total">Total: <strong><?= e(format_price($total)) ?></strong></div>

        <form method="post" action="<?= e(base_url('checkout.php')) ?>">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-primary btn-large">Finalizar compra</button>
        </form>
    <?php endif; ?>
</section>

<?php include __DIR__ . '/../templates/footer.php'; ?>
