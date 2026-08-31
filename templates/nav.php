<nav class="navbar">
    <div class="navbar-inner">
        <a class="brand" href="<?= e(base_url('index.php')) ?>"><span class="brand-word">Even<span class="brand-accent">tra</span></span></a>

        <button id="navToggle" type="button" class="nav-toggle" aria-label="Abrir menu" aria-expanded="false" aria-controls="navLinks">☰</button>

        <div class="nav-links" id="navLinks">
            <a href="<?= e(base_url('index.php')) ?>">Eventos</a>
            <?php if (Auth::check()): ?>
                <a href="<?= e(base_url('minhas-compras.php')) ?>">Minhas Compras</a>
            <?php endif; ?>
            <?php if (Auth::check() && Auth::isAdmin()): ?>
                <a href="<?= e(base_url('admin/index.php')) ?>">Painel</a>
                <a href="<?= e(base_url('admin/checkin.php')) ?>">Check-in</a>
            <?php endif; ?>

            <?php $cartCount = Auth::check() ? CartService::count() : 0; ?>
            <a class="nav-cart" href="<?= e(base_url('carrinho.php')) ?>"
               aria-label="<?= $cartCount > 0 ? 'Carrinho com ' . (int) $cartCount . ' ingresso(s)' : 'Carrinho vazio' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="square" aria-hidden="true">
                    <path d="M3 4h2.2l2.2 11h10L20 7H6.4"></path>
                    <circle cx="9.5" cy="19" r="1.4" fill="currentColor" stroke="none"></circle>
                    <circle cx="17" cy="19" r="1.4" fill="currentColor" stroke="none"></circle>
                </svg>
                <span>Carrinho</span>
                <?php if ($cartCount > 0): ?><span class="cart-count"><?= (int) $cartCount ?></span><?php endif; ?>
            </a>

            <?php if (Auth::check()): ?>
                <span class="nav-user"><?= e(Auth::name() ?? '') ?></span>
                <a href="<?= e(base_url('logout.php')) ?>">Sair</a>
            <?php else: ?>
                <a href="<?= e(base_url('login.php')) ?>">Entrar</a>
                <a href="<?= e(base_url('registro.php')) ?>" class="btn-nav">Criar conta</a>
            <?php endif; ?>
            <button id="themeToggle" type="button" class="theme-toggle" title="Alternar tema" aria-label="Alternar tema claro/escuro">◐</button>
        </div>
    </div>
</nav>
