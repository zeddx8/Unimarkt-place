<?php
require_once 'includes/db.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: my-listings.php');
    exit();
}

requireCsrf('my-listings.php');

$id  = intval($_POST['id'] ?? 0);
$uid = (int)$_SESSION['user_id'];

if ($id > 0) {
    $stmt = $conn->prepare("UPDATE products SET status='sold' WHERE id=? AND seller_id=?");
    $stmt->bind_param("ii", $id, $uid);
    $stmt->execute();
}

header('Location: my-listings.php?marked=1');
exit();
