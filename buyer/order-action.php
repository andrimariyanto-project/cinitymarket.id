<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
$user = auth();
$db   = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/buyer/orders.php');
verifyCsrf();

$action  = $_POST['action']   ?? '';
$orderId = (int)($_POST['order_id'] ?? 0);
$order   = $db->fetchOne("SELECT * FROM orders WHERE id=? AND buyer_id=?", [$orderId, $user['id']]);

if (!$order) { setFlash('error', 'Pesanan tidak ditemukan'); redirect('/buyer/orders.php'); }

if ($action === 'confirm_received' && $order['status'] === 'shipped') {
    $now = date('Y-m-d H:i:s');
    $db->update('orders', ['status'=>'completed','delivered_at'=>$now,'completed_at'=>$now], 'id=?', [$orderId]);
    $sellerUser = $db->fetchOne("SELECT user_id FROM seller_profiles WHERE id=?", [$order['seller_id']]);
    if ($sellerUser) {
        sendNotification($sellerUser['user_id'], 'Pesanan Selesai ✅',
            'Pembeli konfirmasi pesanan #'.$order['order_number'].' telah diterima.',
            'order', '/seller/order-detail.php?id='.$orderId);
    }
    setFlash('success', 'Terima kasih! Pesanan selesai.');

} elseif ($action === 'cancel' && in_array($order['status'], ['pending','payment_pending'])) {
    $db->update('orders', ['status'=>'cancelled','cancelled_at'=>date('Y-m-d H:i:s'),'cancel_reason'=>'Dibatalkan oleh pembeli'], 'id=?', [$orderId]);
    // Restore stock
    $items = $db->fetchAll("SELECT * FROM order_items WHERE order_id=?", [$orderId]);
    foreach ($items as $it) {
        $db->query("UPDATE products SET stock = stock + ? WHERE id = ?", [$it['quantity'], $it['product_id']]);
    }
    $sellerUser = $db->fetchOne("SELECT user_id FROM seller_profiles WHERE id=?", [$order['seller_id']]);
    if ($sellerUser) {
        sendNotification($sellerUser['user_id'], 'Pesanan Dibatalkan',
            'Pembeli membatalkan pesanan #'.$order['order_number'].'.',
            'order', '/seller/order-detail.php?id='.$orderId);
    }
    setFlash('info', 'Pesanan dibatalkan.');

} else {
    setFlash('error', 'Aksi tidak valid');
}

redirect('/buyer/orders.php');
