<?php
require_once __DIR__ . '/../src/bootstrap.php';

$slug = trim($_GET['slug'] ?? '');
$event = $slug !== '' ? EventModel::findPublishedBySlug($slug) : null;

if ($event === null) {
    http_response_code(404);
    $pageTitle = 'Evento não encontrado';
    include __DIR__ . '/../templates/header.php';
    echo '<p class="empty-state">Evento não encontrado ou não está mais disponível.</p>';
    include __DIR__ . '/../templates/footer.php';
    exit;
}

$ticketTypes = TicketType::byEvent((int) $event['id']);

$local = trim(($event['venue_name'] ?? '') . (!empty($event['address']) ? ' — ' . $event['address'] : ''));

$pageTitle = $event['title'];
include __DIR__ . '/../templates/header.php';
?>

<article class="event-hero">
    <div class="event-hero-cover" style="background-image:url('<?= e(cover_url($event['cover_image'], 'https://placehold.co/1600x600/111111/cccccc?text=EVENTO')) ?>')"></div>
    <div class="event-hero-inner">
        <span class="event-card-category"><?= e($event['category']) ?></span>
        <h1><?= e($event['title']) ?></h1>
    </div>
</article>

<div class="event-layout">
    <div class="event-main">
        <dl class="event-facts">
            <div>
                <dt>Data e hora</dt>
                <dd><?= e(format_datetime($event['starts_at'])) ?></dd>
            </div>
            <?php if ($local !== ''): ?>
                <div>
                    <dt>Local</dt>
                    <dd><?= e($local) ?></dd>
                </div>
            <?php endif; ?>
            <?php if (city_label($event) !== ''): ?>
                <div>
                    <dt>Cidade</dt>
                    <dd><?= e(city_label($event)) ?></dd>
                </div>
            <?php endif; ?>
        </dl>

        <?php if (!empty($event['description'])): ?>
            <h2>Sobre o evento</h2>
            <p class="event-detail-description"><?= nl2br(e($event['description'])) ?></p>
        <?php endif; ?>
    </div>

    <aside class="event-aside">
        <?php if (empty($ticketTypes)): ?>
            <div class="buy-panel">
                <h2>Ingressos</h2>
                <p class="buy-panel-note">Nenhum ingresso disponível no momento.</p>
            </div>
        <?php else: ?>
            <form method="post" action="<?= e(base_url('api/carrinho.php')) ?>" class="buy-panel">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="redirect_slug" value="<?= e($event['slug']) ?>">

                <h2>Ingressos</h2>
                <p class="buy-panel-note">Escolha a quantidade</p>

                <?php foreach ($ticketTypes as $tt): ?>
                    <?php $available = TicketType::availableQuantity($tt); ?>
                    <div class="buy-row">
                        <span class="buy-row-name"><?= e($tt['name']) ?></span>
                        <span class="buy-row-price"><?= e(price_label((float) $tt['price'])) ?></span>
                        <?php if (!empty($tt['description'])): ?>
                            <span class="buy-row-desc"><?= e($tt['description']) ?></span>
                        <?php endif; ?>
                        <div class="buy-row-foot">
                            <span class="buy-row-stock <?= $available > 0 ? '' : 'is-out' ?>">
                                <?= $available > 0 ? (int) $available . ' restantes' : 'Esgotado' ?>
                            </span>
                            <?php if ($available > 0): ?>
                                <select name="quantities[<?= (int) $tt['id'] ?>]" aria-label="Quantidade de <?= e($tt['name']) ?>">
                                    <option value="0">0</option>
                                    <?php $max = min($available, (int) $tt['max_per_purchase']); ?>
                                    <?php for ($i = 1; $i <= $max; $i++): ?>
                                        <option value="<?= $i ?>"><?= $i ?></option>
                                    <?php endfor; ?>
                                </select>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <button type="submit" class="btn btn-primary">Adicionar ao carrinho</button>
            </form>
        <?php endif; ?>
    </aside>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>
