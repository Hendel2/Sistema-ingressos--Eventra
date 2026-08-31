<?php
require_once __DIR__ . '/../src/bootstrap.php';

if (Auth::check()) {
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim($_POST['email'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    if (Auth::attempt($email, $password)) {
        clear_old();
        redirect(Auth::isAdmin() ? 'admin/index.php' : 'index.php');
    }

    flash_set('error', 'E-mail ou senha inválidos.');
    set_old(['email' => $email]);
    redirect('login.php');
}

$pageTitle = 'Entrar';
$mainClass = 'container--flush';
include __DIR__ . '/../templates/header.php';
?>

<div class="auth-split">
    <aside class="auth-aside">
        <h2 class="auth-aside-title">De volta pra <em>pista</em></h2>

        <ul class="auth-points">
            <li>Seus ingressos ficam salvos na conta, com QR Code pronto pra entrada.</li>
            <li>Histórico completo de pedidos, sempre à mão.</li>
            <li>Organizador entra aqui também — direto pro painel de vendas.</li>
        </ul>

        <p class="auth-aside-foot">Eventra · acesso à conta</p>
    </aside>

    <div class="auth-panel">
        <section class="auth-form">
            <span class="eyebrow">Entrar</span>
            <h1>Acessar conta</h1>
            <form method="post" action="<?= e(base_url('login.php')) ?>">
                <?= csrf_field() ?>
                <label>E-mail
                    <input type="email" name="email" required autofocus value="<?= old('email') ?>">
                </label>
                <label>Senha
                    <input type="password" name="password" required>
                </label>
                <button type="submit" class="btn btn-primary btn-large">Entrar</button>
            </form>
            <p>Ainda não tem conta? <a href="<?= e(base_url('registro.php')) ?>">Cadastre-se</a></p>
        </section>
    </div>
</div>

<?php clear_old(); include __DIR__ . '/../templates/footer.php'; ?>
