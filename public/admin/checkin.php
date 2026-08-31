<?php
require_once __DIR__ . '/../../src/bootstrap.php';

Auth::requireAdmin();

$code = trim($_GET['codigo'] ?? $_POST['codigo'] ?? '');
$ticket = null;
$notOwned = false;

if ($code !== '') {
    $ticket = Ticket::findByCode($code);
    if ($ticket !== null && (int) $ticket['organizer_id'] !== (int) Auth::id()) {
        $notOwned = true;
        $ticket = null;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'checkin') {
    verify_csrf();
    if ($ticket !== null && $ticket['status'] === 'valid') {
        Ticket::markUsed((int) $ticket['id']);
        flash_set('success', 'Check-in confirmado para ' . $ticket['holder_name'] . '.');
        redirect('admin/checkin.php');
    }
}

$statusLabels = ['valid' => 'Válido', 'used' => 'Já utilizado', 'cancelled' => 'Cancelado'];

$pageTitle = 'Check-in';
include __DIR__ . '/../../templates/header.php';
?>

<section class="admin-form">
    <h1>Check-in de ingressos</h1>
    <form method="get" class="checkin-form">
        <label>Código do ingresso
            <input type="text" name="codigo" required autofocus value="<?= e($code) ?>" placeholder="Digite ou escaneie o código">
        </label>
        <button type="submit" class="btn btn-primary">Buscar</button>
    </form>

    <?php if ($code !== '' && $ticket === null): ?>
        <p class="alert alert-error"><?= $notOwned ? 'Este ingresso não pertence a um evento seu.' : 'Ingresso não encontrado.' ?></p>
    <?php elseif ($ticket !== null): ?>
        <div class="order-card">
            <p><strong><?= e($ticket['holder_name']) ?></strong></p>
            <p><?= e($ticket['event_title']) ?> — <?= e($ticket['ticket_type_name']) ?></p>
            <p><?= e(format_datetime($ticket['starts_at'])) ?></p>
            <span class="badge badge-<?= e($ticket['status']) ?>"><?= e($statusLabels[$ticket['status']] ?? $ticket['status']) ?></span>

            <?php if ($ticket['status'] === 'valid'): ?>
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="checkin">
                    <input type="hidden" name="codigo" value="<?= e($ticket['code']) ?>">
                    <button type="submit" class="btn btn-primary">Confirmar entrada</button>
                </form>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
