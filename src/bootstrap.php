<?php
declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));

// Onde ficam os arquivos servidos pela web. No XAMPP local eles estão em
// "public/". Em hospedagem compartilhada (InfinityFree) o projeto inteiro
// mora dentro de "htdocs" e os arquivos públicos ficam na própria raiz —
// não dá para guardar nada acima dela. Detectado aqui para o resto do
// código não precisar saber a diferença.
define('PUBLIC_DIR', is_dir(APP_ROOT . '/public') ? APP_ROOT . '/public' : APP_ROOT);

$configPath = APP_ROOT . '/config/config.php';
if (!file_exists($configPath)) {
    die(
        'Arquivo de configuração não encontrado. ' .
        'Copie config/config.example.php para config/config.php e preencha suas credenciais.'
    );
}

$config = require $configPath;
$GLOBALS['config'] = $config;

date_default_timezone_set($config['app']['timezone'] ?? 'America/Sao_Paulo');

require_once APP_ROOT . '/src/Helpers.php';
require_once APP_ROOT . '/src/Database.php';
require_once APP_ROOT . '/src/Auth.php';
require_once APP_ROOT . '/src/Models/User.php';
require_once APP_ROOT . '/src/Models/EventModel.php';
require_once APP_ROOT . '/src/Models/TicketType.php';
require_once APP_ROOT . '/src/Models/OrderModel.php';
require_once APP_ROOT . '/src/Models/Ticket.php';
require_once APP_ROOT . '/src/Services/CartService.php';
require_once APP_ROOT . '/src/Services/MercadoPagoService.php';
require_once APP_ROOT . '/src/Services/TicketService.php';
require_once APP_ROOT . '/src/Services/LocationService.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

Database::init($config['db']);
