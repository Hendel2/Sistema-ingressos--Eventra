<?php
declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function base_url(string $path = ''): string
{
    $base = rtrim($GLOBALS['config']['app']['base_url'], '/');
    return $base . '/' . ltrim($path, '/');
}

function redirect(string $path): never
{
    $url = str_starts_with($path, 'http') ? $path : base_url($path);
    header('Location: ' . $url);
    exit;
}

function format_price(float $value): string
{
    return 'R$ ' . number_format($value, 2, ',', '.');
}

function price_label(float $value): string
{
    return $value <= 0 ? 'Grátis' : format_price($value);
}

function format_datetime(string $datetime): string
{
    $date = new DateTime($datetime);
    return $date->format('d/m/Y \à\s H:i');
}

function flash_set(string $key, string $message): void
{
    $_SESSION['flash'][$key] = $message;
}

function flash_get(string $key): ?string
{
    if (!empty($_SESSION['flash'][$key])) {
        $message = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $message;
    }
    return null;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || $token === '' || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('Token de segurança inválido ou expirado. Volte e tente novamente.');
    }
}

function generate_code(int $bytes = 8): string
{
    return strtoupper(bin2hex(random_bytes($bytes)));
}

function slugify(string $text): string
{
    $text = preg_replace('~[^\pL\d]+~u', '-', $text) ?? $text;
    $transliterated = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
    $text = $transliterated !== false ? $transliterated : $text;
    $text = preg_replace('~[^-\w]+~', '', $text) ?? $text;
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text) ?? $text;
    $text = strtolower($text);
    return $text !== '' ? $text : 'evento';
}

function set_old(array $data): void
{
    $_SESSION['old'] = $data;
}

function old(string $key, string $default = ''): string
{
    $value = $_SESSION['old'][$key] ?? $default;
    return e((string) $value);
}

function clear_old(): void
{
    unset($_SESSION['old']);
}

function event_categories(): array
{
    return [
        'Show / Música',
        'Festa / Balada',
        'Festival',
        'Teatro',
        'Comédia / Stand-up',
        'Cinema',
        'Exposição / Arte',
        'Feira / Mercado',
        'Congresso / Palestra',
        'Workshop / Curso',
        'Esporte',
        'Gastronomia',
        'Infantil',
        'Religioso',
        'Beneficente',
    ];
}


function event_statuses(bool $isNew = false): array
{
    $all = [
        'draft' => 'Rascunho',
        'published' => 'Publicado',
        'cancelled' => 'Cancelado',
        'finished' => 'Finalizado',
    ];
    return $isNew ? ['draft' => $all['draft'], 'published' => $all['published']] : $all;
}


function cover_url(?string $value, string $fallback = ''): string
{
    $value = trim((string) $value);
    if ($value === '') {
        return $fallback;
    }
    if (preg_match('#^https?://#i', $value)) {
        return $value;
    }
    return base_url(ltrim($value, '/'));
}


function city_label(array $event): string
{
    $city = trim((string) ($event['city'] ?? ''));
    $state = trim((string) ($event['state'] ?? ''));
    if ($city === '') {
        return $state;
    }
    return $state !== '' ? $city . ' - ' . $state : $city;
}


function store_cover_upload(array $file, ?string &$error = null): ?string
{
    $error = null;

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
        $error = 'A imagem excede o tamanho máximo aceito pelo servidor.';
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'Falha ao enviar a imagem. Tente novamente.';
        return null;
    }
    if ($file['size'] > 4 * 1024 * 1024) {
        $error = 'A imagem deve ter no máximo 4 MB.';
        return null;
    }

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!isset($allowed[$mime]) || @getimagesize($file['tmp_name']) === false) {
        $error = 'Formato inválido. Envie uma imagem JPG, PNG, WEBP ou GIF.';
        return null;
    }

    $dir = PUBLIC_DIR . '/assets/uploads';
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        $error = 'Não foi possível criar a pasta de imagens no servidor.';
        return null;
    }

    $name = bin2hex(random_bytes(10)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
        $error = 'Não foi possível salvar a imagem no servidor.';
        return null;
    }

    return 'assets/uploads/' . $name;
}

function delete_cover_upload(?string $path): void
{
    $path = trim((string) $path);
    if ($path === '' || preg_match('#^https?://#i', $path)) {
        return;
    }
    
    if (strpos($path, 'assets/uploads/') !== 0 || strpos($path, '..') !== false) {
        return;
    }
    $full = PUBLIC_DIR . '/' . $path;
    if (is_file($full)) {
        @unlink($full);
    }
}
