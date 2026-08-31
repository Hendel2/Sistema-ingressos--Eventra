<?php
require_once __DIR__ . '/../src/bootstrap.php';

Auth::requireLogin();

$status = $_GET['status'] ?? '';
$paymentId = $_GET['payment_id'] ?? $_GET['collection_id'] ?? null;

$order = null;
if ($paymentId !== null) {
    try {
        $order = TicketService::confirmPayment((string) $paymentId);
    } catch (Throwable $e) {
        error_log('Erro ao confirmar pagamento no retorno: ' . $e->getMessage());
    }
}

$pageTitle = 'Retorno do pagamento';
include __DIR__ . '/../templates/header.php';
?>

<section class="checkout-return">
    <?php if ($status === 'success' || ($order && $order['status'] === 'paid')): ?>
        <h1>✅ Pagamento aprovado!</h1>
        <p>Seus ingressos já estão disponíveis na página "Minhas Compras".</p>
    <?php elseif ($status === 'pending' || ($order && $order['status'] === 'pending')): ?>
        <h1>⏳ Pagamento em análise</h1>
        <p>Assim que o Mercado Pago confirmar o pagamento, seus ingressos aparecerão em "Minhas Compras".</p>
    <?php else: ?>
        <h1>❌ Pagamento não concluído</h1>
        <p>O pagamento foi cancelado ou recusado. Você pode tentar novamente pelo carrinho.</p>
    <?php endif; ?>

    <a class="btn btn-primary" href="<?= e(base_url('minhas-compras.php')) ?>">Minhas Compras</a>
</section>

<?php include __DIR__ . '/../templates/footer.php'; ?>
