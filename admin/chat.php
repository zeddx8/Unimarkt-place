<?php
require_once '../includes/db.php';
requireAdmin();

$admin_id = (int) $_SESSION['user_id'];
$product_id = (int) ($_GET['product_id'] ?? 0);
$customer_id = (int) ($_GET['user_id'] ?? 0);
$product_stmt = $conn->prepare("
    SELECT p.*, c.name AS cat_name, c.icon AS cat_icon
    FROM products p
    JOIN categories c ON p.category_id=c.id
    WHERE p.id=?
");
$product_stmt->bind_param("i", $product_id);
$product_stmt->execute();
$product = $product_stmt->get_result()->fetch_assoc();

$customer_stmt = $conn->prepare("SELECT id, full_name, email, avatar FROM users WHERE id=? AND is_admin=0");
$customer_stmt->bind_param("i", $customer_id);
$customer_stmt->execute();
$customer = $customer_stmt->get_result()->fetch_assoc();

$can_open_chat = false;
if ($product && $customer) {
    $history_stmt = $conn->prepare("
        SELECT id FROM messages
        WHERE product_id=? AND
            ((sender_id=? AND receiver_id=?) OR (sender_id=? AND receiver_id=?))
        LIMIT 1
    ");
    $history_stmt->bind_param("iiiii", $product_id, $customer_id, $admin_id, $admin_id, $customer_id);
    $history_stmt->execute();
    $can_open_chat = $history_stmt->get_result()->num_rows > 0
        || (int) $product['seller_id'] === $customer_id;

    if (!$can_open_chat) {
        $participant_stmt = $conn->prepare("
            SELECT id FROM messages
            WHERE product_id=? AND (sender_id=? OR receiver_id=?)
            LIMIT 1
        ");
        $participant_stmt->bind_param("iii", $product_id, $customer_id, $customer_id);
        $participant_stmt->execute();
        $can_open_chat = $participant_stmt->get_result()->num_rows > 0;
    }
}

if ($can_open_chat) {
    $read_stmt = $conn->prepare("
        UPDATE messages SET is_read=1
        WHERE product_id=? AND sender_id=? AND receiver_id=?
    ");
    $read_stmt->bind_param("iii", $product_id, $customer_id, $admin_id);
    $read_stmt->execute();

    $messages_stmt = $conn->prepare("
        SELECT m.*, u.full_name
        FROM messages m
        JOIN users u ON u.id=m.sender_id
        WHERE m.product_id=? AND
            ((m.sender_id=? AND m.receiver_id=?) OR (m.sender_id=? AND m.receiver_id=?))
        ORDER BY m.created_at ASC
    ");
    $messages_stmt->bind_param("iiiii", $product_id, $admin_id, $customer_id, $customer_id, $admin_id);
    $messages_stmt->execute();
    $messages = $messages_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$request_token = bin2hex(random_bytes(16));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Customer Inquiry Chat — UniMarket Admin</title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23EA580C'%3E%3Cpath d='M16 6V4a4 4 0 0 0-8 0v2H4a1 1 0 0 0-1 1v13a3 3 0 0 0 3 3h12a3 3 0 0 0 3-3V7a1 1 0 0 0-1-1h-4zm-6-2a2 2 0 1 1 4 0v2h-4V4zm9 16a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V8h3v2a1 1 0 1 0 2 0V8h4v2a1 1 0 1 0 2 0V8h3v12z'/%3E%3C/svg%3E">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
</head>
<body style="background:#FAFAF9;">
<div class="d-flex flex-column flex-md-row" style="min-height:100vh;">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="flex-fill p-3 p-md-4">
        <a href="messages.php" class="text-decoration-none text-muted d-inline-flex align-items-center gap-2 mb-3">
            <i class="bi bi-arrow-left"></i> All messages
        </a>
        <h4 style="font-weight:800;color:var(--brand-slate);margin-bottom:4px;">Customer Inquiry Chat</h4>
        <p class="text-muted mb-4" style="font-size:13px;">Reply to customers about their marketplace conversations.</p>

        <div class="um-card overflow-hidden">
            <?php if (!$can_open_chat): ?>
            <div class="empty-state py-5 text-center">
                <div class="empty-icon mb-3">
                    <i class="bi bi-chat-square-dots text-muted" style="font-size:48px;"></i>
                </div>
                <h5 class="fw-bold text-dark">Conversation Not Found</h5>
                <p class="text-muted">This customer conversation could not be located or has expired.</p>
                <a href="messages.php" class="btn btn-orange btn-sm px-4">Back to Messages</a>
            </div>
            <?php else: ?>
            <div class="d-flex align-items-center gap-3 p-3" style="border-bottom:1.5px solid var(--border-stone);">
                <div class="seller-avatar" style="width:42px;height:42px;font-size:14px;">
                    <?php if ($customer['avatar']): ?>
                        <img src="<?= htmlspecialchars(getAdminImageUrl($customer['avatar'])) ?>" alt="" onerror="this.onerror=null; this.style.display='none';">
                    <?php else: ?>
                        <?= getAvatar($customer['full_name']) ?>
                    <?php endif; ?>
                </div>
                <div class="min-w-0">
                    <div class="fw-700"><?= sanitize($customer['full_name']) ?></div>
                    <div style="font-size:12px;color:var(--text-muted);" class="d-flex align-items-center gap-1">
                        <i class="bi <?= htmlspecialchars($product['cat_icon']) ?> text-primary"></i>
                        <span><?= sanitize($product['title']) ?></span>
                    </div>
                </div>
                <a href="../product.php?id=<?= $product_id ?>" class="ms-auto btn btn-outline-secondary btn-sm" target="_blank">
                    <i class="bi bi-box-arrow-up-right me-1"></i> View Item
                </a>
            </div>

            <div class="chat-messages p-3" id="chatMessages" style="height:440px;overflow-y:auto;">
                <?php if (!$messages): ?>
                <div class="text-center text-muted py-4" style="font-size:14px;">
                    <i class="bi bi-chat-text d-block mb-2 fs-3 text-muted"></i>
                    Send a message to start chatting with this customer.
                </div>
                <?php else: ?>
                    <?php foreach ($messages as $message): ?>
                    <div class="d-flex <?= (int) $message['sender_id'] === $admin_id ? 'justify-content-end' : 'justify-content-start' ?>">
                        <div class="msg-bubble <?= (int) $message['sender_id'] === $admin_id ? 'msg-me' : 'msg-them' ?>">
                            <?= nl2br(sanitize($message['message'])) ?>
                            <div style="font-size:10px;opacity:.7;margin-top:4px;text-align:right;"><?= timeAgo($message['created_at']) ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="p-3" style="border-top:1.5px solid var(--border-stone);">
                <form id="chatMsgForm" method="POST"
                      data-product="<?= $product_id ?>"
                      data-receiver="<?= $customer_id ?>"
                      data-chat-endpoint="../chat.php"
                      data-fetch-endpoint="../chat-fetch.php"
                      class="d-flex gap-2">
                    <input type="hidden" name="product_id" value="<?= $product_id ?>">
                    <input type="hidden" name="receiver_id" value="<?= $customer_id ?>">
                    <input type="hidden" name="request_token" value="<?= $request_token ?>">
                    <input type="text" name="message" class="form-control um-input" placeholder="Write a message..." autocomplete="off" required>
                    <button type="submit" class="btn btn-orange px-4" aria-label="Send message"><i class="bi bi-send"></i></button>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/main.js"></script>
</body>
</html>
