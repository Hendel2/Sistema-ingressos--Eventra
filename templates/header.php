<?php
/** @var string $pageTitle */
$pageTitle = $pageTitle ?? 'Eventra';
$mainClass = $mainClass ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="color-scheme" content="dark light">
<meta name="theme-color" content="#08080a">
<title><?= e($pageTitle) ?> · Eventra</title>
<script>
// Escuro é o padrão da marca; só sai dele se a pessoa escolher.
(function () {
    var stored = localStorage.getItem('theme');
    document.documentElement.setAttribute('data-theme', stored === 'light' ? 'light' : 'dark');
})();
</script>
<link rel="icon" type="image/svg+xml" href="<?= e(base_url('assets/favicon.svg')) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Archivo+Black&family=Space+Grotesk:wght@400;500;600;700&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(base_url('assets/css/style.css')) ?>">
</head>
<body>
<?php include __DIR__ . '/nav.php'; ?>

<div class="ticker" aria-hidden="true">
    <div class="ticker-track">
        <?php for ($i = 0; $i < 2; $i++): ?>
            <span>★ Ingressos com QR Code</span>
            <span>★ Check-in na porta</span>
            <span>★ Sem taxa escondida</span>
            <span>★ Compre em 1 minuto</span>
            <span>★ Line-up sempre atualizado</span>
        <?php endfor; ?>
    </div>
</div>

<main class="container <?= e($mainClass) ?>">
    <?php if ($msg = flash_get('success')): ?>
        <div class="alert alert-success"><?= e($msg) ?></div>
    <?php endif; ?>
    <?php if ($msg = flash_get('error')): ?>
        <div class="alert alert-error"><?= e($msg) ?></div>
    <?php endif; ?>
