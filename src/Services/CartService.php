<?php
declare(strict_types=1);

class CartService
{
    private const SESSION_KEY = 'cart';

    
    public static function items(): array
    {
        return $_SESSION[self::SESSION_KEY] ?? [];
    }

    public static function add(int $ticketTypeId, int $qty): void
    {
        if ($qty <= 0) {
            return;
        }
        $items = self::items();
        $items[$ticketTypeId] = ($items[$ticketTypeId] ?? 0) + $qty;
        $_SESSION[self::SESSION_KEY] = $items;
    }

    public static function setQuantity(int $ticketTypeId, int $qty): void
    {
        $items = self::items();
        if ($qty <= 0) {
            unset($items[$ticketTypeId]);
        } else {
            $items[$ticketTypeId] = $qty;
        }
        $_SESSION[self::SESSION_KEY] = $items;
    }

    public static function remove(int $ticketTypeId): void
    {
        $items = self::items();
        unset($items[$ticketTypeId]);
        $_SESSION[self::SESSION_KEY] = $items;
    }

    public static function clear(): void
    {
        unset($_SESSION[self::SESSION_KEY]);
    }

    public static function count(): int
    {
        return array_sum(self::items());
    }

    public static function detailed(): array
    {
        $detailed = [];
        foreach (self::items() as $ticketTypeId => $qty) {
            $ticketType = TicketType::find($ticketTypeId);
            if ($ticketType === null) {
                continue;
            }
            $event = EventModel::find((int) $ticketType['event_id']);
            $detailed[] = [
                'ticket_type' => $ticketType,
                'event' => $event,
                'quantity' => $qty,
                'subtotal' => $qty * (float) $ticketType['price'],
            ];
        }
        return $detailed;
    }

    public static function total(): float
    {
        return array_sum(array_column(self::detailed(), 'subtotal'));
    }
}
