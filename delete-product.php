<?php
require_once 'includes/db.php';
requireLogin();

$redirect = !empty($_SESSION['is_admin']) ? 'admin/products.php' : 'my-listings.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: $redirect");
    exit();
}

requireCsrf($redirect);

$id  = intval($_POST['id'] ?? 0);
$uid = (int)$_SESSION['user_id'];

if ($id > 0) {
    // Only owner or admin can delete
    if (!empty($_SESSION['is_admin'])) {
        $stmt = $conn->prepare("UPDATE products SET status='removed' WHERE id=?");
        $stmt->bind_param("i", $id);
    } else {
        $stmt = $conn->prepare("UPDATE products SET status='removed' WHERE id=? AND seller_id=?");
        $stmt->bind_param("ii", $id, $uid);
    }
    $stmt->execute();
}

header("Location: $redirect?deleted=1");
exit();
