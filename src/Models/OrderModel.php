<?php
declare(strict_types=1);

class OrderModel
{
    public static function create(int $userId, float $total): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO orders (user_id, total_amount, status) VALUES (?, ?, "pending")'
        );
        $stmt->execute([$userId, $total]);
        return (int) Database::pdo()->lastInsertId();
    }

    public static function addItem(int $orderId, int $ticketTypeId, int $qty, float $unitPrice): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO order_items (order_id, ticket_type_id, quantity, unit_price) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$orderId, $ticketTypeId, $qty, $unitPrice]);
        return (int) Database::pdo()->lastInsertId();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM orders WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findForUser(int $id, int $userId): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM orders WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Itens do pedido com nome do ingresso e do evento (para exibição e para geração de tickets). */
    public static function items(int $orderId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT oi.*, tt.name AS ticket_type_name, tt.event_id, e.title AS event_title
             FROM order_items oi
             JOIN ticket_types tt ON tt.id = oi.ticket_type_id
             JOIN events e ON e.id = tt.event_id
             WHERE oi.order_id = ?'
        );
        $stmt->execute([$orderId]);
        return $stmt->fetchAll();
    }

    public static function byUser(int $userId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /** Pedidos pagos que contêm ao menos um ingresso de eventos do organizador informado. */
    public static function byOrganizer(int $organizerId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT DISTINCT o.*, u.name AS customer_name, u.email AS customer_email
             FROM orders o
             JOIN users u ON u.id = o.user_id
             JOIN order_items oi ON oi.order_id = o.id
             JOIN ticket_types tt ON tt.id = oi.ticket_type_id
             JOIN events e ON e.id = tt.event_id
             WHERE e.organizer_id = ?
             ORDER BY o.created_at DESC'
        );
        $stmt->execute([$organizerId]);
        return $stmt->fetchAll();
    }

    public static function updateStatus(int $id, string $status): void
    {
        $paidAt = $status === 'paid' ? ', paid_at = NOW()' : '';
        $stmt = Database::pdo()->prepare("UPDATE orders SET status = ?{$paidAt} WHERE id = ?");
        $stmt->execute([$status, $id]);
    }

    public static function setPreferenceId(int $id, string $preferenceId): void
    {
        $stmt = Database::pdo()->prepare('UPDATE orders SET mp_preference_id = ? WHERE id = ?');
        $stmt->execute([$preferenceId, $id]);
    }

    public static function setPaymentId(int $id, string $paymentId): void
    {
        $stmt = Database::pdo()->prepare('UPDATE orders SET mp_payment_id = ? WHERE id = ?');
        $stmt->execute([$paymentId, $id]);
    }

    public static function delete(int $id): void
    {
        $stmt = Database::pdo()->prepare('DELETE FROM orders WHERE id = ?');
        $stmt->execute([$id]);
    }
}
