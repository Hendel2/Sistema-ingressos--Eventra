<?php
require_once __DIR__ . '/../src/bootstrap.php';

if (Auth::check()) {
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');
    $wantsOrganizer = isset($_POST['is_organizer']);

    $errors = [];
    if ($name === '' || mb_strlen($name) < 2) {
        $errors[] = 'Informe seu nome completo.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'E-mail inválido.';
    }
    if (mb_strlen($password) < 6) {
        $errors[] = 'A senha deve ter ao menos 6 caracteres.';
    }
    if ($password !== $passwordConfirm) {
        $errors[] = 'As senhas não coincidem.';
    }

    if (empty($errors)) {
        $role = $wantsOrganizer ? 'admin' : 'client';
        if (Auth::register($name, $email, $password, $role)) {
            clear_old();
            redirect($role === 'admin' ? 'admin/index.php' : 'index.php');
        }
        $errors[] = 'Este e-mail já está cadastrado.';
    }

    flash_set('error', implode(' ', $errors));
    set_old(['name' => $name, 'email' => $email]);
    redirect('registro.php');
}

$pageTitle = 'Criar conta';
$mainClass = 'container--flush';
include __DIR__ . '/../templates/header.php';
?>

<div class="auth-split">
    <aside class="auth-aside">
        <h2 class="auth-aside-title">Sua entrada começa <em>aqui</em></h2>

        <ul class="auth-points">
            <li>Compre em menos de um minuto e receba o ingresso na hora.</li>
            <li>QR Code digital — sem papel, sem fila no guichê.</li>
            <li>Vai produzir um evento? Marque a opção de organizador e venda pelo Eventra.</li>
        </ul>

        <p class="auth-aside-foot">Eventra · nova conta</p>
    </aside>

    <div class="auth-panel">
        <section class="auth-form">
            <span class="eyebrow">Cadastro</span>
            <h1>Criar conta</h1>
            <form method="post" action="<?= e(base_url('registro.php')) ?>">
                <?= csrf_field() ?>
                <div class="form-grid">
                    <label class="form-full">Nome completo
                        <input type="text" name="name" required autofocus value="<?= old('name') ?>">
                    </label>
                    <label class="form-full">E-mail
                        <input type="email" name="email" required value="<?= old('email') ?>">
                    </label>
                    <label>Senha
                        <input type="password" name="password" required minlength="6">
                    </label>
                    <label>Confirmar senha
                        <input type="password" name="password_confirm" required minlength="6">
                    </label>
                </div>
                <label class="checkbox-label">
                    <input type="checkbox" name="is_organizer" value="1">
                    Quero cadastrar e vender ingressos para meus próprios eventos
                </label>
                <button type="submit" class="btn btn-primary btn-large">Criar conta</button>
            </form>
            <p>Já tem conta? <a href="<?= e(base_url('login.php')) ?>">Entrar</a></p>
        </section>
    </div>
</div>

<?php clear_old(); include __DIR__ . '/../templates/footer.php'; ?>
