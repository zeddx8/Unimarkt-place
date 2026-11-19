<?php
require_once 'includes/db.php';
requireLogin();

$id  = intval($_GET['id'] ?? 0);
$uid = $_SESSION['user_id'];

if ($id) {
    // Only owner or admin can delete
    if ($_SESSION['is_admin']) {
        $stmt = $conn->prepare("UPDATE products SET status='removed' WHERE id=?");
        $stmt->bind_param("i", $id);
    } else {
        $stmt = $conn->prepare("UPDATE products SET status='removed' WHERE id=? AND seller_id=?");
        $stmt->bind_param("ii", $id, $uid);
    }
    $stmt->execute();
}

$redirect = $_SESSION['is_admin'] ? 'admin/products.php' : 'my-listings.php';
header("Location: $redirect?deleted=1");
exit();
