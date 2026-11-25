<?php
require_once 'includes/db.php';
header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Authentication required.']);
    exit();
}

$pid = intval($_GET['product_id'] ?? 0);
$uid = (int)$_SESSION['user_id'];

if ($pid < 1) {
    http_response_code(400);
    echo json_encode(['status' => 'error']);
    exit();
}

$chk = $conn->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ? LIMIT 1");
$chk->bind_param("ii", $uid, $pid);
$chk->execute();
$exists = $chk->get_result()->num_rows > 0;

if ($exists) {
    $del = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
    $del->bind_param("ii", $uid, $pid);
    if ($del->execute()) {
        echo json_encode(['status' => 'removed']);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error']);
    }
} else {
    $ins = $conn->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
    $ins->bind_param("ii", $uid, $pid);
    if ($ins->execute()) {
        echo json_encode(['status' => 'added']);
    } else {
        http_response_code(422);
        echo json_encode(['status' => 'error']);
    }
}
