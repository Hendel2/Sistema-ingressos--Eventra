<?php
require_once __DIR__ . '/../../src/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

// Endpoint interno do cadastro de eventos: só organizadores autenticados.
if (!Auth::check() || !Auth::isAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Acesso negado.']);
    exit;
}

$uf = strtoupper(trim($_GET['uf'] ?? ''));
if (!LocationService::isValidState($uf)) {
    http_response_code(400);
    echo json_encode(['error' => 'Estado inválido.']);
    exit;
}

$cities = LocationService::cities($uf);
if ($cities === []) {
    http_response_code(503);
    echo json_encode(['error' => 'Não foi possível carregar as cidades agora.']);
    exit;
}

echo json_encode(['uf' => $uf, 'cities' => $cities], JSON_UNESCAPED_UNICODE);
