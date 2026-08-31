<?php
require_once __DIR__ . '/../src/bootstrap.php';

Auth::requireLogin();

$code = trim($_GET['codigo'] ?? '');
$ticket = $code !== '' ? Ticket::findByCodeForUser($code, (int) Auth::id()) : null;

if ($ticket === null) {
    http_response_code(404);
    $pageTitle = 'Ingresso não encontrado';
    include __DIR__ . '/../templates/header.php';
    echo '<p class="empty-state">Ingresso não encontrado.</p>';
    include __DIR__ . '/../templates/footer.php';
    exit;
}

$qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=260x260&data=' . urlencode($ticket['code']);

$statusLabels = [
    'valid' => 'Válido',
    'used' => 'Utilizado',
    'cancelled' => 'Cancelado',
];

$pageTitle = 'Ingresso';
include __DIR__ . '/../templates/header.php';
?>

<section class="ticket-view">
    <div class="ticket-card">
        <h1><?= e($ticket['event_title']) ?></h1>
        <p><?= e(format_datetime($ticket['starts_at'])) ?></p>
        <p>📍 <?= e($ticket['venue_name'] ?? '') ?></p>
        <p><strong><?= e($ticket['ticket_type_name']) ?></strong></p>

        <img src="<?= e($qrUrl) ?>" alt="QR Code do ingresso" class="ticket-qr">

        <p class="ticket-code">Código: <strong><?= e($ticket['code']) ?></strong></p>
        <span class="badge badge-<?= e($ticket['status']) ?>"><?= e($statusLabels[$ticket['status']] ?? $ticket['status']) ?></span>

        <p class="ticket-note">Apresente este QR Code na entrada do evento.</p>
    </div>
</section>

<?php include __DIR__ . '/../templates/footer.php'; ?>
