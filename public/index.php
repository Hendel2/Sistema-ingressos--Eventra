<?php
require_once __DIR__ . '/../src/bootstrap.php';

$search = trim($_GET['q'] ?? '');
$category = trim($_GET['categoria'] ?? '');
$city = trim($_GET['cidade'] ?? '');

$events = EventModel::allPublished($search ?: null, $category ?: null, $city ?: null);
$categories = EventModel::categories();
$isFiltered = $search !== '' || $category !== '' || $city !== '';

// Com dois ou mais eventos o primeiro vira destaque e sai da grade, evitando
// aparecer duas vezes. Fora disso a coluna recebe a chamada para organizadores,
// para nunca sobrar espaço morto ao lado da manchete.
$featured = (!$isFiltered && count($events) >= 2) ? $events[0] : null;
$gridEvents = $featured !== null ? array_slice($events, 1) : $events;

$pageTitle = 'Eventos';
include __DIR__ . '/../templates/header.php';
?>

<section class="hero">
    <div class="hero-main">
        <span class="eyebrow"><?= count($events) ?> evento<?= count($events) === 1 ? '' : 's' ?> com ingresso à venda</span>
        <h1>Seu próximo <em>rolê</em> começa aqui</h1>
        <p class="hero-sub">Shows, festas e festivais com ingresso digital, QR Code na hora e check-in direto na porta.</p>
    </div>

    <aside class="hero-feature">
        <?php if ($featured !== null): ?>
            <a class="feature-card" href="<?= e(base_url('evento.php?slug=' . urlencode($featured['slug']))) ?>">
                <div class="feature-cover" style="background-image:url('<?= e(cover_url($featured['cover_image'], 'https://placehold.co/600x420/111111/cccccc?text=EVENTO')) ?>')"></div>
                <div class="feature-body">
                    <span class="eyebrow">Próximo evento</span>
                    <h3><?= e($featured['title']) ?></h3>
                    <p class="feature-meta">
                        <?= e(format_datetime($featured['starts_at'])) ?><?= city_label($featured) !== '' ? ' · ' . e(city_label($featured)) : '' ?>
                    </p>
                    <span class="feature-cta">Ver ingressos</span>
                </div>
            </a>
        <?php else: ?>
            <div class="promo-panel">
                <span class="eyebrow">Para organizadores</span>
                <h3>Venda os ingressos do seu evento</h3>
                <p>Cadastre o evento, defina os lotes e acompanhe vendas, receita e check-in num painel só.</p>
                <a class="btn btn-secondary" href="<?= e(base_url(Auth::check() && Auth::isAdmin() ? 'admin/evento-form.php' : 'registro.php')) ?>">
                    <?= Auth::check() && Auth::isAdmin() ? 'Criar evento' : 'Começar a vender' ?>
                </a>
            </div>
        <?php endif; ?>
    </aside>

    <form class="search-form" method="get" action="<?= e(base_url('index.php')) ?>">
        <input type="text" name="q" placeholder="Buscar evento, artista, festa..." value="<?= e($search) ?>">
        <select name="categoria">
            <option value="">Todas as categorias</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= e($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="text" name="cidade" placeholder="Cidade" value="<?= e($city) ?>">
        <button type="submit" class="btn btn-primary">Buscar</button>
    </form>
</section>

<div class="page-head">
    <h2><?= $isFiltered ? 'Resultados da busca' : 'Em cartaz' ?></h2>
    <?php if ($isFiltered): ?>
        <p><?= count($events) ?> encontrado<?= count($events) === 1 ? '' : 's' ?> · <a href="<?= e(base_url('index.php')) ?>">limpar filtros</a></p>
    <?php endif; ?>
</div>

<section class="event-grid">
    <?php if (empty($gridEvents)): ?>
        <p class="empty-state">
            Nenhum evento encontrado com esses filtros.
            <?php if ($isFiltered): ?><br><a href="<?= e(base_url('index.php')) ?>">Limpar busca</a><?php endif; ?>
        </p>
    <?php endif; ?>
    <?php foreach ($gridEvents as $event): ?>
        <a class="event-card" href="<?= e(base_url('evento.php?slug=' . urlencode($event['slug']))) ?>">
            <div class="event-card-cover" style="background-image:url('<?= e(cover_url($event['cover_image'], 'https://placehold.co/600x340/111111/cccccc?text=EVENTO')) ?>')"></div>
            <div class="event-card-body">
                <span class="event-card-category"><?= e($event['category']) ?></span>
                <h3><?= e($event['title']) ?></h3>
                <p class="event-card-meta"><?= e(format_datetime($event['starts_at'])) ?></p>
                <?php if (city_label($event) !== ''): ?>
                    <p class="event-card-meta event-card-city"><?= e(city_label($event)) ?></p>
                <?php endif; ?>
            </div>
        </a>
    <?php endforeach; ?>
</section>

<?php include __DIR__ . '/../templates/footer.php'; ?>
