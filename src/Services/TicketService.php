<?php
declare(strict_types=1);

class TicketService
{
    
    public static function confirmPayment(string $paymentId): ?array
    {
        $payment = MercadoPagoService::getPayment($paymentId);
        $orderId = (int) ($payment['external_reference'] ?? 0);
        if ($orderId === 0) {
            return null;
        }

        $order = OrderModel::find($orderId);
        if ($order === null || $order['status'] !== 'pending') {
            return $order;
        }

        if ($payment['status'] === 'approved') {
            OrderModel::setPaymentId($orderId, $paymentId);
            OrderModel::updateStatus($orderId, 'paid');
            self::generateForOrder($orderId);
        } elseif (in_array($payment['status'], ['cancelled', 'rejected'], true)) {
            self::releaseOrderStock($orderId);
            OrderModel::updateStatus($orderId, 'cancelled');
        }

        return OrderModel::find($orderId);
    }

    
    public static function reconcilePendingOrder(int $orderId): ?array
    {
        $order = OrderModel::find($orderId);
        if ($order === null || $order['status'] !== 'pending') {
            return null;
        }

        foreach (MercadoPagoService::paymentsForOrder($orderId) as $payment) {
            $status = $payment['status'] ?? '';
            $paymentId = (string) ($payment['id'] ?? '');
            if ($paymentId === '' || !in_array($status, ['approved', 'cancelled', 'rejected'], true)) {
                continue;
            }

            return self::confirmPayment($paymentId);
        }

        return null;
    }

    
    public static function generateForOrder(int $orderId): void
    {
        $order = OrderModel::find($orderId);
        if ($order === null || $order['status'] !== 'paid') {
            return;
        }

        if (count(Ticket::byOrder($orderId)) > 0) {
            return;
        }

        foreach (OrderModel::items($orderId) as $item) {
            for ($i = 0; $i < (int) $item['quantity']; $i++) {
                Ticket::create(
                    (int) $item['id'],
                    (int) $item['event_id'],
                    (int) $item['ticket_type_id'],
                    (int) $order['user_id'],
                    generate_code()
                );
            }
        }
    }

    
    public static function releaseOrderStock(int $orderId): void
    {
        foreach (OrderModel::items($orderId) as $item) {
            TicketType::releaseStock((int) $item['ticket_type_id'], (int) $item['quantity']);
        }
    }
}
