<?php
declare(strict_types=1);

class Auth
{
    public static function attempt(string $email, string $password): bool
    {
        $user = User::findByEmail($email);
        if ($user && password_verify($password, $user['password_hash'])) {
            self::login($user);
            return true;
        }
        return false;
    }

    public static function register(string $name, string $email, string $password, string $role = 'client'): bool
    {
        if (User::findByEmail($email) !== null) {
            return false;
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $id = User::create($name, $email, $hash, $role);
        self::login(['id' => $id, 'name' => $name, 'role' => $role]);
        return true;
    }

    private static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie('PHPSESSID', '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    public static function check(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public static function id(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    public static function name(): ?string
    {
        return $_SESSION['user_name'] ?? null;
    }

    public static function role(): ?string
    {
        return $_SESSION['user_role'] ?? null;
    }

    public static function isAdmin(): bool
    {
        return self::role() === 'admin';
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            flash_set('error', 'Faça login para continuar.');
            redirect('login.php');
        }
    }

    public static function requireAdmin(): void
    {
        self::requireLogin();
        if (!self::isAdmin()) {
            http_response_code(403);
            die('Acesso restrito a organizadores de eventos.');
        }
    }
}
