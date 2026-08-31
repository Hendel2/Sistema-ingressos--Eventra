<?php
declare(strict_types=1);

class EventModel
{
    public static function allPublished(?string $search = null, ?string $category = null, ?string $city = null): array
    {
        $sql = 'SELECT * FROM events WHERE status = "published" AND starts_at >= NOW()';
        $params = [];

        if ($search !== null && $search !== '') {
            $sql .= ' AND (title LIKE ? OR description LIKE ?)';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }
        if ($category !== null && $category !== '') {
            $sql .= ' AND category = ?';
            $params[] = $category;
        }
        if ($city !== null && $city !== '') {
            $sql .= ' AND city = ?';
            $params[] = $city;
        }

        $sql .= ' ORDER BY starts_at ASC';

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function categories(): array
    {
        $stmt = Database::pdo()->query(
            'SELECT DISTINCT category FROM events WHERE status = "published" ORDER BY category ASC'
        );
        return array_column($stmt->fetchAll(), 'category');
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM events WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findPublishedBySlug(string $slug): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM events WHERE slug = ? AND status = "published"');
        $stmt->execute([$slug]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function byOrganizer(int $organizerId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM events WHERE organizer_id = ? ORDER BY starts_at DESC'
        );
        $stmt->execute([$organizerId]);
        return $stmt->fetchAll();
    }

    /** Ingressos vendidos e receita (apenas pedidos pagos) por evento do organizador. */
    public static function statsByOrganizer(int $organizerId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT e.id, e.title, e.starts_at, e.status,
                COALESCE(SUM(CASE WHEN o.status = "paid" THEN oi.quantity ELSE 0 END), 0) AS tickets_sold,
                COALESCE(SUM(CASE WHEN o.status = "paid" THEN oi.quantity * oi.unit_price ELSE 0 END), 0) AS revenue
             FROM events e
             LEFT JOIN ticket_types tt ON tt.event_id = e.id
             LEFT JOIN order_items oi ON oi.ticket_type_id = tt.id
             LEFT JOIN orders o ON o.id = oi.order_id
             WHERE e.organizer_id = ?
             GROUP BY e.id, e.title, e.starts_at, e.status
             ORDER BY e.starts_at DESC'
        );
        $stmt->execute([$organizerId]);
        return $stmt->fetchAll();
    }

    public static function belongsToOrganizer(int $eventId, int $organizerId): bool
    {
        $stmt = Database::pdo()->prepare('SELECT id FROM events WHERE id = ? AND organizer_id = ?');
        $stmt->execute([$eventId, $organizerId]);
        return (bool) $stmt->fetch();
    }

    public static function uniqueSlug(string $title): string
    {
        $base = slugify($title);
        $slug = $base;
        $i = 2;
        while (self::slugExists($slug)) {
            $slug = $base . '-' . $i;
            $i++;
        }
        return $slug;
    }

    private static function slugExists(string $slug): bool
    {
        $stmt = Database::pdo()->prepare('SELECT id FROM events WHERE slug = ?');
        $stmt->execute([$slug]);
        return (bool) $stmt->fetch();
    }

    public static function create(array $data): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO events
                (organizer_id, title, slug, description, category, venue_name, address, state, city, starts_at, ends_at, cover_image, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['organizer_id'],
            $data['title'],
            $data['slug'],
            $data['description'],
            $data['category'],
            $data['venue_name'],
            $data['address'],
            $data['state'],
            $data['city'],
            $data['starts_at'],
            $data['ends_at'],
            $data['cover_image'],
            $data['status'],
        ]);
        return (int) Database::pdo()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE events SET
                title = ?, description = ?, category = ?, venue_name = ?, address = ?, state = ?, city = ?,
                starts_at = ?, ends_at = ?, cover_image = ?, status = ?
             WHERE id = ?'
        );
        $stmt->execute([
            $data['title'],
            $data['description'],
            $data['category'],
            $data['venue_name'],
            $data['address'],
            $data['state'],
            $data['city'],
            $data['starts_at'],
            $data['ends_at'],
            $data['cover_image'],
            $data['status'],
            $id,
        ]);
    }
}
