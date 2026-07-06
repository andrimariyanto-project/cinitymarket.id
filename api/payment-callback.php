<?php
// Tripay Payment Callback Handler
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/tripay.php';

$payload = file_get_contents('php://input');
$data = json_decode($payload, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid payload']);
    exit;
}

$tripay = new Tripay();
if (!$tripay->validateCallback($data)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid signature']);
    exit;
}

$db = Database::getInstance();
$merchantRef = $data['merchant_ref'] ?? '';
$tripayRef   = $data['reference'] ?? '';
$status      = $data['status'] ?? '';

$order = $db->fetchOne("SELECT * FROM orders WHERE order_number=?", [$merchantRef]);
if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Order not found']);
    exit;
}

if ($status === 'PAID') {
    $db->beginTransaction();
    try {
        // Update payment
        $db->update('payments', [
            'status'          => 'PAID',
            'paid_at'         => date('Y-m-d H:i:s'),
            'callback_data'   => json_encode($data)
        ], 'tripay_reference=?', [$tripayRef]);

        // Update order
        $db->update('orders', [
            'status'  => 'paid',
            'paid_at' => date('Y-m-d H:i:s')
        ], 'id=?', [$order['id']]);

        // Notify seller
        $seller = $db->fetchOne("SELECT user_id FROM seller_profiles WHERE id=?", [$order['seller_id']]);
        if ($seller) {
            sendNotification($seller['user_id'], 'Pembayaran Diterima!',
                'Pesanan #' . $order['order_number'] . ' sudah dibayar. Silakan konfirmasi.',
                'payment', '/seller/order-detail.php?id=' . $order['id']);
        }
        // Notify buyer
        sendNotification($order['buyer_id'], 'Pembayaran Berhasil!',
            'Pembayaran pesanan #' . $order['order_number'] . ' berhasil dikonfirmasi.',
            'payment', '/buyer/order-detail.php?order=' . $order['order_number']);

        $db->commit();
    } catch (Exception $e) {
        $db->rollback();
        error_log('Callback error: ' . $e->getMessage());
    }
} elseif (in_array($status, ['FAILED', 'EXPIRED'])) {
    $db->update('payments', ['status' => $status, 'callback_data' => json_encode($data)], 'tripay_reference=?', [$tripayRef]);
    $db->update('orders', ['status' => 'cancelled', 'cancelled_at' => date('Y-m-d H:i:s'), 'cancel_reason' => 'Pembayaran ' . strtolower($status)], 'id=? AND status IN (?,?)', [$order['id'], 'pending', 'payment_pending']);
}

echo json_encode(['success' => true]);
