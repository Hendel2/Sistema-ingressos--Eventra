<?php
declare(strict_types=1);

class TicketType
{
    public static function byEvent(int $eventId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM ticket_types WHERE event_id = ? ORDER BY price ASC'
        );
        $stmt->execute([$eventId]);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM ticket_types WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(array $data): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO ticket_types
                (event_id, name, description, price, quantity_total, max_per_purchase, sale_start, sale_end)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['event_id'],
            $data['name'],
            $data['description'],
            $data['price'],
            $data['quantity_total'],
            $data['max_per_purchase'],
            $data['sale_start'],
            $data['sale_end'],
        ]);
        return (int) Database::pdo()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE ticket_types SET
                name = ?, description = ?, price = ?, quantity_total = ?, max_per_purchase = ?,
                sale_start = ?, sale_end = ?
             WHERE id = ?'
        );
        $stmt->execute([
            $data['name'],
            $data['description'],
            $data['price'],
            $data['quantity_total'],
            $data['max_per_purchase'],
            $data['sale_start'],
            $data['sale_end'],
            $id,
        ]);
    }

    public static function delete(int $id): void
    {
        $stmt = Database::pdo()->prepare('DELETE FROM ticket_types WHERE id = ? AND quantity_sold = 0');
        $stmt->execute([$id]);
    }

    public static function availableQuantity(array $ticketType): int
    {
        return max(0, (int) $ticketType['quantity_total'] - (int) $ticketType['quantity_sold']);
    }

    public static function reserveStock(int $id, int $qty): bool
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE ticket_types
             SET quantity_sold = quantity_sold + ?
             WHERE id = ? AND (quantity_total - quantity_sold) >= ?'
        );
        $stmt->execute([$qty, $id, $qty]);
        return $stmt->rowCount() > 0;
    }

    public static function releaseStock(int $id, int $qty): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE ticket_types SET quantity_sold = GREATEST(0, quantity_sold - ?) WHERE id = ?'
        );
        $stmt->execute([$qty, $id]);
    }
}
