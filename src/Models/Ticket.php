<?php
declare(strict_types=1);

class Ticket
{
    public static function create(int $orderItemId, int $eventId, int $ticketTypeId, int $userId, string $code): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO tickets (order_item_id, event_id, ticket_type_id, user_id, code)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$orderItemId, $eventId, $ticketTypeId, $userId, $code]);
        return (int) Database::pdo()->lastInsertId();
    }

    public static function byOrder(int $orderId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT t.*, e.title AS event_title, e.starts_at, e.venue_name, tt.name AS ticket_type_name
             FROM tickets t
             JOIN order_items oi ON oi.id = t.order_item_id
             JOIN events e ON e.id = t.event_id
             JOIN ticket_types tt ON tt.id = t.ticket_type_id
             WHERE oi.order_id = ?'
        );
        $stmt->execute([$orderId]);
        return $stmt->fetchAll();
    }


    public static function findByCode(string $code): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT t.*, e.title AS event_title, e.starts_at, e.venue_name, e.organizer_id, tt.name AS ticket_type_name, u.name AS holder_name
             FROM tickets t
             JOIN events e ON e.id = t.event_id
             JOIN ticket_types tt ON tt.id = t.ticket_type_id
             JOIN users u ON u.id = t.user_id
             WHERE t.code = ?'
        );
        $stmt->execute([$code]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findByCodeForUser(string $code, int $userId): ?array
    {
        $ticket = self::findByCode($code);
        if ($ticket !== null && (int) $ticket['user_id'] === $userId) {
            return $ticket;
        }
        return null;
    }

    public static function markUsed(int $id): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE tickets SET status = "used", used_at = NOW() WHERE id = ? AND status = "valid"'
        );
        $stmt->execute([$id]);
    }
}
