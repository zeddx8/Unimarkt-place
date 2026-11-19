<?php
require_once 'includes/db.php';
requireLogin();

$product_id  = intval($_GET['product_id'] ?? 0);
$receiver_id = intval($_GET['receiver_id'] ?? 0);
$uid         = $_SESSION['user_id'];

if (!canAccessProductChat($conn, $product_id, (int) $uid, $receiver_id, !empty($_SESSION['is_admin']))) {
    http_response_code(403);
    exit();
}

// Mark as read
$read_stmt = $conn->prepare("UPDATE messages SET is_read=1 WHERE product_id=? AND receiver_id=? AND sender_id=?");
$read_stmt->bind_param("iii", $product_id, $uid, $receiver_id);
$read_stmt->execute();

$mm = $conn->prepare("
    SELECT m.*, u.full_name
    FROM messages m
    JOIN users u ON m.sender_id = u.id
    WHERE m.product_id=?
      AND ((m.sender_id=? AND m.receiver_id=?) OR (m.sender_id=? AND m.receiver_id=?))
    ORDER BY m.created_at ASC
");
$mm->bind_param("iiiii", $product_id, $uid, $receiver_id, $receiver_id, $uid);
$mm->execute();
$msgs = $mm->get_result();

while ($msg = $msgs->fetch_assoc()):
    $is_me = $msg['sender_id'] == $uid;
?>
<div class="d-flex <?= $is_me ? 'justify-content-end' : 'justify-content-start' ?>">
    <div class="msg-bubble <?= $is_me ? 'msg-me' : 'msg-them' ?>">
        <?= nl2br(htmlspecialchars($msg['message'], ENT_QUOTES)) ?>
        <div style="font-size:10px;opacity:.6;margin-top:4px;text-align:right;">
            <?= timeAgo($msg['created_at']) ?>
        </div>
    </div>
</div>
<?php endwhile; ?>
