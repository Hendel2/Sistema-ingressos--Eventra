</main>
<footer class="site-footer">
    <div class="footer-inner">
        <div class="footer-brand">
            <span class="footer-mark">Even<span>tra</span></span>
            <p>Ingresso digital, QR Code na hora<br>e check-in direto na porta.</p>
        </div>

        <nav class="footer-cols">
            <div class="footer-col">
                <h4>Explorar</h4>
                <a href="<?= e(base_url('index.php')) ?>">Todos os eventos</a>
                <?php if (Auth::check()): ?>
                    <a href="<?= e(base_url('minhas-compras.php')) ?>">Minhas compras</a>
                <?php endif; ?>
            </div>
            <div class="footer-col">
                <h4>Organizadores</h4>
                <?php if (Auth::check() && Auth::isAdmin()): ?>
                    <a href="<?= e(base_url('admin/index.php')) ?>">Painel</a>
                    <a href="<?= e(base_url('admin/checkin.php')) ?>">Check-in</a>
                <?php else: ?>
                    <a href="<?= e(base_url('registro.php')) ?>">Vender ingressos</a>
                <?php endif; ?>
            </div>
        </nav>
    </div>

    <div class="footer-bar">
        <p>&copy; <?= date('Y') ?> Eventra</p>
        <p>Projeto de estudo · checkout sem pagamento real</p>
    </div>
</footer>
<script src="<?= e(base_url('assets/js/main.js')) ?>"></script>
</body>
</html>
