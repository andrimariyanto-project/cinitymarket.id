<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
$user = auth(); $db = Database::getInstance();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/index.php');
verifyCsrf();
$productId = (int)($_POST['product_id'] ?? 0);
$qty = max(1, (int)($_POST['qty'] ?? 1));
$product = $db->fetchOne("SELECT id, stock FROM products WHERE id=? AND status='active'", [$productId]);
if (!$product || $product['stock'] < $qty) { setFlash('error','Stok tidak mencukupi'); redirect($_SERVER['HTTP_REFERER'] ?? '/index.php'); }
// Add to cart then go to checkout
$existing = $db->fetchOne("SELECT id FROM cart WHERE user_id=? AND product_id=?", [$user['id'], $productId]);
if ($existing) $db->update('cart', ['quantity' => min($existing['quantity'] + $qty, $product['stock'])], 'id=?', [$existing['id']]);
else $db->insert('cart', ['user_id' => $user['id'], 'product_id' => $productId, 'quantity' => $qty]);
redirect('/buyer/checkout.php');
