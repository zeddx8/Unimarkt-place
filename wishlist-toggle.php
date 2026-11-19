<?php
require_once 'includes/db.php';
requireLogin();
header('Content-Type: application/json');

$pid = intval($_GET['product_id'] ?? 0);
$uid = $_SESSION['user_id'];

if (!$pid) { echo json_encode(['status'=>'error']); exit(); }

$exists = $conn->query("SELECT id FROM wishlist WHERE user_id=$uid AND product_id=$pid")->num_rows;
if ($exists) {
    $conn->query("DELETE FROM wishlist WHERE user_id=$uid AND product_id=$pid");
    echo json_encode(['status'=>'removed']);
} else {
    $conn->query("INSERT INTO wishlist (user_id, product_id) VALUES ($uid, $pid)");
    echo json_encode(['status'=>'added']);
}
