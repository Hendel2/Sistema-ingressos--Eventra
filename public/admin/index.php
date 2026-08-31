<?php
require_once __DIR__ . '/../../src/bootstrap.php';

Auth::requireAdmin();

$stats = EventModel::statsByOrganizer((int) Auth::id());
$totalRevenue = array_sum(array_column($stats, 'revenue'));
$totalTickets = array_sum(array_column($stats, 'tickets_sold'));

$statusLabels = event_statuses();

$pageTitle = 'Painel do Organizador';
include __DIR__ . '/../../templates/header.php';
?>

<section class="admin-dashboard">
    <div class="admin-header">
        <h1>Painel do Organizador</h1>
        <a href="<?= e(base_url('admin/evento-form.php')) ?>" class="btn btn-primary">+ Novo evento</a>
    </div>

    <div class="stat-cards">
        <div class="stat-card">
            <span class="stat-label">Receita total (pedidos pagos)</span>
            <span class="stat-value"><?= e(format_price((float) $totalRevenue)) ?></span>
        </div>
        <div class="stat-card">
            <span class="stat-label">Ingressos vendidos</span>
            <span class="stat-value"><?= (int) $totalTickets ?></span>
        </div>
        <div class="stat-card">
            <span class="stat-label">Eventos cadastrados</span>
            <span class="stat-value"><?= count($stats) ?></span>
        </div>
    </div>

    <h2>Meus eventos</h2>
    <?php if (empty($stats)): ?>
        <p class="empty-state">Você ainda não cadastrou eventos.</p>
    <?php else: ?>
        <table class="ticket-table">
            <thead>
                <tr><th>Evento</th><th>Data</th><th>Status</th><th>Ingressos vendidos</th><th>Receita</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($stats as $s): ?>
                <tr>
                    <td><?= e($s['title']) ?></td>
                    <td><?= e(format_datetime($s['starts_at'])) ?></td>
                    <td>
                        <span class="badge badge-<?= e($s['status']) ?>"><?= e($statusLabels[$s['status']] ?? $s['status']) ?></span>
                        <?php // Publicado + data passada = sumiu da vitrine. Sem esse aviso o
                              // organizador não tem pista de por que o evento não aparece. ?>
                        <?php if ($s['status'] === 'published' && strtotime($s['starts_at']) < time()): ?>
                            <span class="badge badge-past" title="A data de início já passou, então este evento saiu da listagem pública.">Fora da vitrine</span>
                        <?php endif; ?>
                    </td>
                    <td><?= (int) $s['tickets_sold'] ?></td>
                    <td><?= e(format_price((float) $s['revenue'])) ?></td>
                    <td>
                        <a href="<?= e(base_url('admin/evento-form.php?id=' . (int) $s['id'])) ?>">Editar</a> ·
                        <a href="<?= e(base_url('admin/ingressos-form.php?event_id=' . (int) $s['id'])) ?>">Ingressos</a> ·
                        <a href="<?= e(base_url('admin/pedidos.php?event_id=' . (int) $s['id'])) ?>">Pedidos</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
