<?php
require_once __DIR__ . '/../../src/bootstrap.php';

Auth::requireAdmin();

$eventId = (int) ($_GET['event_id'] ?? 0);
$event = EventModel::find($eventId);
if ($event === null || !EventModel::belongsToOrganizer($eventId, (int) Auth::id())) {
    http_response_code(404);
    die('Evento não encontrado.');
}

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : null;
$editing = null;
if ($editId !== null) {
    $editing = TicketType::find($editId);
    if ($editing === null || (int) $editing['event_id'] !== $eventId) {
        $editing = null;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? 'create';

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $tt = TicketType::find($id);
        if ($tt !== null && (int) $tt['event_id'] === $eventId) {
            TicketType::delete($id);
            flash_set('success', 'Tipo de ingresso removido.');
        }
        redirect('admin/ingressos-form.php?event_id=' . $eventId);
    }

    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = (float) str_replace(',', '.', $_POST['price'] ?? '0');
    $quantityTotal = max(0, (int) ($_POST['quantity_total'] ?? 0));
    $maxPerPurchase = max(1, (int) ($_POST['max_per_purchase'] ?? 10));
    $saleStart = trim($_POST['sale_start'] ?? '');
    $saleEnd = trim($_POST['sale_end'] ?? '');

    $errors = [];
    if ($name === '') {
        $errors[] = 'Informe o nome do tipo de ingresso.';
    }
    if ($price < 0) {
        $errors[] = 'O preço não pode ser negativo.';
    }

    if (empty($errors)) {
        $data = [
            'event_id' => $eventId,
            'name' => $name,
            'description' => $description,
            'price' => $price,
            'quantity_total' => $quantityTotal,
            'max_per_purchase' => $maxPerPurchase,
            'sale_start' => $saleStart !== '' ? date('Y-m-d H:i:s', strtotime($saleStart)) : null,
            'sale_end' => $saleEnd !== '' ? date('Y-m-d H:i:s', strtotime($saleEnd)) : null,
        ];

        $editingId = (int) ($_POST['id'] ?? 0);
        if ($action === 'update' && $editingId > 0) {
            $tt = TicketType::find($editingId);
            if ($tt !== null && (int) $tt['event_id'] === $eventId) {
                TicketType::update($editingId, $data);
                flash_set('success', 'Tipo de ingresso atualizado.');
            }
        } else {
            TicketType::create($data);
            flash_set('success', 'Tipo de ingresso adicionado.');
        }

        redirect('admin/ingressos-form.php?event_id=' . $eventId);
    }

    flash_set('error', implode(' ', $errors));
}

$ticketTypes = TicketType::byEvent($eventId);

$pageTitle = 'Ingressos — ' . $event['title'];
include __DIR__ . '/../../templates/header.php';
?>

<section class="admin-form">
    <h1>Ingressos de "<?= e($event['title']) ?>"</h1>

    <table class="ticket-table">
        <thead>
            <tr><th>Nome</th><th>Preço</th><th>Total</th><th>Vendidos</th><th></th></tr>
        </thead>
        <tbody>
        <?php if (empty($ticketTypes)): ?>
            <tr><td colspan="5">Nenhum tipo de ingresso cadastrado ainda.</td></tr>
        <?php endif; ?>
        <?php foreach ($ticketTypes as $tt): ?>
            <tr>
                <td><?= e($tt['name']) ?></td>
                <td><?= e(format_price((float) $tt['price'])) ?></td>
                <td><?= (int) $tt['quantity_total'] ?></td>
                <td><?= (int) $tt['quantity_sold'] ?></td>
                <td>
                    <a href="<?= e(base_url('admin/ingressos-form.php?event_id=' . $eventId . '&edit=' . (int) $tt['id'])) ?>">Editar</a>
                    <?php if ((int) $tt['quantity_sold'] === 0): ?>
                        · <form method="post" class="inline-form" onsubmit="return confirm('Remover este tipo de ingresso?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int) $tt['id'] ?>">
                            <button type="submit" class="btn-link btn-link-danger">Remover</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <h2><?= $editing !== null ? 'Editar tipo de ingresso' : 'Adicionar tipo de ingresso' ?></h2>
    <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="<?= $editing !== null ? 'update' : 'create' ?>">
        <?php if ($editing !== null): ?>
            <input type="hidden" name="id" value="<?= (int) $editing['id'] ?>">
        <?php endif; ?>
        <label>Nome
            <input type="text" name="name" required value="<?= e($editing['name'] ?? '') ?>">
        </label>
        <label>Descrição
            <input type="text" name="description" value="<?= e($editing['description'] ?? '') ?>">
        </label>
        <label>Preço (R$)
            <input type="text" name="price" required value="<?= $editing ? number_format((float) $editing['price'], 2, '.', '') : '' ?>">
        </label>
        <label>Quantidade total
            <input type="number" name="quantity_total" min="0" required value="<?= (int) ($editing['quantity_total'] ?? 0) ?>">
        </label>
        <label>Máximo por compra
            <input type="number" name="max_per_purchase" min="1" required value="<?= (int) ($editing['max_per_purchase'] ?? 10) ?>">
        </label>
        <button type="submit" class="btn btn-primary"><?= $editing !== null ? 'Salvar alterações' : 'Adicionar' ?></button>
        <?php if ($editing !== null): ?>
            <a href="<?= e(base_url('admin/ingressos-form.php?event_id=' . $eventId)) ?>">Cancelar edição</a>
        <?php endif; ?>
    </form>

    <p><a href="<?= e(base_url('admin/index.php')) ?>">&larr; Voltar ao painel</a></p>
</section>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
