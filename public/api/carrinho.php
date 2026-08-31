<?php
require_once __DIR__ . '/../../src/bootstrap.php';

Auth::requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('carrinho.php');
}
verify_csrf();

$action = $_POST['action'] ?? '';

if ($action === 'add') {
    $quantities = $_POST['quantities'] ?? [];
    $added = 0;
    foreach ($quantities as $ticketTypeId => $qty) {
        $ticketTypeId = (int) $ticketTypeId;
        $qty = (int) $qty;
        if ($qty <= 0) {
            continue;
        }
        $ticketType = TicketType::find($ticketTypeId);
        if ($ticketType === null) {
            continue;
        }
        $available = TicketType::availableQuantity($ticketType);
        $qty = min($qty, $available, (int) $ticketType['max_per_purchase']);
        if ($qty > 0) {
            CartService::add($ticketTypeId, $qty);
            $added += $qty;
        }
    }

    if ($added > 0) {
        flash_set('success', 'Ingressos adicionados ao carrinho.');
    } else {
        flash_set('error', 'Selecione ao menos um ingresso para adicionar.');
    }

    $slug = trim($_POST['redirect_slug'] ?? '');
    redirect($slug !== '' ? 'evento.php?slug=' . urlencode($slug) : 'index.php');
}

if ($action === 'update') {
    $ticketTypeId = (int) ($_POST['ticket_type_id'] ?? 0);
    $qty = (int) ($_POST['quantity'] ?? 0);
    $ticketType = TicketType::find($ticketTypeId);
    if ($ticketType !== null) {
        $available = TicketType::availableQuantity($ticketType);
        $qty = min($qty, $available, (int) $ticketType['max_per_purchase']);
        CartService::setQuantity($ticketTypeId, $qty);
    }
    redirect('carrinho.php');
}

if ($action === 'remove') {
    $ticketTypeId = (int) ($_POST['ticket_type_id'] ?? 0);
    CartService::remove($ticketTypeId);
    redirect('carrinho.php');
}

redirect('carrinho.php');
