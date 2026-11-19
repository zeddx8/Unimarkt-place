<?php
require_once 'includes/db.php';
requireLogin();

$id  = intval($_GET['id'] ?? 0);
$uid = $_SESSION['user_id'];

if ($id) {
    $stmt = $conn->prepare("UPDATE products SET status='sold' WHERE id=? AND seller_id=?");
    $stmt->bind_param("ii", $id, $uid);
    $stmt->execute();
}

header('Location: my-listings.php?marked=1');
exit();
