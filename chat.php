<?php
require_once 'includes/db.php';
requireLogin();
$page_title = 'Messages';
$uid = $_SESSION['user_id'];

// Handle sending message
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id  = intval($_POST['product_id'] ?? 0);
    $receiver_id = intval($_POST['receiver_id'] ?? ($_POST['seller_id'] ?? 0));
    $message     = is_string($_POST['message'] ?? null) ? trim($_POST['message']) : '';
    $request_token = is_string($_POST['request_token'] ?? null) ? trim($_POST['request_token']) : '';
    $is_ajax     = (isset($_POST['ajax']) && $_POST['ajax'] === '1')
                   || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

    $_SESSION['sent_chat_tokens'] = $_SESSION['sent_chat_tokens'] ?? [];
    $_SESSION['sent_chat_tokens'] = array_filter(
        $_SESSION['sent_chat_tokens'],
        static function ($sent_at) { return $sent_at > time() - 3600; }
    );
    $valid_token = preg_match('/^[a-f0-9]{32}$/', $request_token) === 1;
    $already_sent = $valid_token && isset($_SESSION['sent_chat_tokens'][$request_token]);
    $sent = false;

    if (!$already_sent && $message !== '' && $product_id > 0 && $receiver_id > 0 && $receiver_id !== (int)$uid) {
        if (canAccessProductChat($conn, $product_id, (int) $uid, $receiver_id, !empty($_SESSION['is_admin']))) {
            $stmt = $conn->prepare("INSERT INTO messages (product_id, sender_id, receiver_id, message) VALUES (?,?,?,?)");
            if ($stmt) {
                $stmt->bind_param("iiis", $product_id, $uid, $receiver_id, $message);
                $sent = $stmt->execute();
                if ($sent && $valid_token) {
                    $_SESSION['sent_chat_tokens'][$request_token] = time();
                }
            }
        }
    }

    if ($is_ajax) {
        header('Content-Type: application/json; charset=utf-8');
        if (!$sent) {
            http_response_code(422);
            echo json_encode(['status' => 'error', 'message' => 'Could not send message. Check conversation and try again.']);
        } else {
            $new_token = bin2hex(random_bytes(16));
            echo json_encode([
                'status' => 'ok',
                'request_token' => $new_token,
                'message' => [
                    'id' => $conn->insert_id,
                    'product_id' => $product_id,
                    'sender_id' => $uid,
                    'receiver_id' => $receiver_id,
                    'message' => $message,
                    'created_at' => date('Y-m-d H:i:s'),
                    'time_ago' => 'Just now'
                ]
            ]);
        }
        exit();
    }

    header("Location: chat.php?product_id=$product_id&seller_id=$receiver_id");
    exit();
}

// Get active chat params
$active_product_id  = intval($_GET['product_id'] ?? 0);
$active_receiver_id = intval($_GET['seller_id'] ?? ($_GET['receiver_id'] ?? ($_GET['user_id'] ?? 0)));
$chat_request_token = bin2hex(random_bytes(16));

// Get all conversation threads for this user
$threads_sql = "
    SELECT
        p.id AS product_id, p.title, p.image,
        c.icon AS cat_icon,
        threads.other_user_id,
        u.full_name AS other_name, u.avatar AS other_avatar,
        last_message.message AS last_msg,
        last_message.created_at AS last_time,
        (SELECT COUNT(*) FROM messages unread
         WHERE unread.product_id=p.id
           AND unread.sender_id=threads.other_user_id
           AND unread.receiver_id=?
           AND unread.is_read=0) AS unread_cnt
    FROM (
        SELECT product_id,
               CASE WHEN sender_id=? THEN receiver_id ELSE sender_id END AS other_user_id,
               MAX(id) AS last_message_id
        FROM messages
        WHERE sender_id=? OR receiver_id=?
        GROUP BY product_id, other_user_id
    ) threads
    JOIN products p ON threads.product_id = p.id
    JOIN categories c ON p.category_id = c.id
    JOIN users u ON u.id = threads.other_user_id
    JOIN messages last_message ON last_message.id=threads.last_message_id
    ORDER BY last_message.created_at DESC, last_message.id DESC
";
$t = $conn->prepare($threads_sql);
$t->bind_param("iiii", $uid, $uid, $uid, $uid);
$t->execute();
$threads = $t->get_result();

// Load messages for active thread
$messages_data = [];
$active_product = null;
$active_receiver = null;

if ($active_product_id && $active_receiver_id) {
    if (!canAccessProductChat($conn, $active_product_id, (int) $uid, $active_receiver_id, !empty($_SESSION['is_admin']))) {
        header('Location: chat.php');
        exit();
    }

    $read = $conn->prepare("UPDATE messages SET is_read=1 WHERE product_id=? AND receiver_id=? AND sender_id=?");
    $read->bind_param("iii", $active_product_id, $uid, $active_receiver_id);
    $read->execute();

    $mp = $conn->prepare("SELECT p.*, c.name AS cat_name, c.icon AS cat_icon FROM products p JOIN categories c ON p.category_id=c.id WHERE p.id=?");
    $mp->bind_param("i", $active_product_id);
    $mp->execute();
    $active_product = $mp->get_result()->fetch_assoc();

    $mu = $conn->prepare("SELECT * FROM users WHERE id=?");
    $mu->bind_param("i", $active_receiver_id);
    $mu->execute();
    $active_receiver = $mu->get_result()->fetch_assoc();

    $mm = $conn->prepare("SELECT m.*, u.full_name FROM messages m JOIN users u ON m.sender_id=u.id WHERE m.product_id=? AND ((m.sender_id=? AND m.receiver_id=?) OR (m.sender_id=? AND m.receiver_id=?)) ORDER BY m.created_at ASC");
    $mm->bind_param("iiiii", $active_product_id, $uid, $active_receiver_id, $active_receiver_id, $uid);
    $mm->execute();
    $messages_data = $mm->get_result()->fetch_all(MYSQLI_ASSOC);
}

include 'includes/header.php';
?>

<div class="container-fluid py-4" style="max-width:1100px;">
    <h4 class="section-title mb-4"><i class="bi bi-chat-dots me-2" style="color:var(--emerald);"></i>Messages & Inquiries</h4>

    <div class="um-card overflow-hidden">
        <div class="row g-0" style="min-height:600px;">

            <!-- ── Thread List ── -->
            <div class="col-md-4 chat-sidebar">
                <?php if ($threads->num_rows === 0): ?>
                <div class="empty-state py-5 text-center">
                    <div class="empty-icon mb-2"><i class="bi bi-chat-square-dots fs-1 text-muted"></i></div>
                    <p class="text-muted">No messages yet</p>
                    <a href="dashboard.php" class="btn btn-orange btn-sm px-4">Browse Items</a>
                </div>
                <?php else: ?>
                <?php while ($thread = $threads->fetch_assoc()): ?>
                <?php $is_active = ($thread['product_id']==$active_product_id && $thread['other_user_id']==$active_receiver_id); ?>
                <a href="chat.php?product_id=<?= $thread['product_id'] ?>&seller_id=<?= $thread['other_user_id'] ?>"
                   class="chat-thread text-decoration-none <?= $is_active ? 'active' : '' ?>">
                    <div class="seller-avatar" style="width:42px;height:42px;font-size:14px;flex-shrink:0;">
                        <?php if ($thread['other_avatar']): ?>
                            <img src="<?= getProductImageUrl($thread['other_avatar']) ?>" alt="" onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name=<?= urlencode($thread['other_name']) ?>&background=0F172A&color=fff';">
                        <?php else: ?>
                            <?= getAvatar($thread['other_name']) ?>
                        <?php endif; ?>
                    </div>
                    <div style="min-width:0;flex:1;">
                        <div class="d-flex justify-content-between align-items-start">
                            <span class="fw-700" style="font-size:14px;"><?= sanitize($thread['other_name']) ?></span>
                            <span style="font-size:10px;color:var(--muted);"><?= timeAgo($thread['last_time']) ?></span>
                        </div>
                        <div style="font-size:12px;color:var(--muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                            <?= $thread['cat_icon'] ?> <?= sanitize($thread['title']) ?>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-1">
                            <span style="font-size:12px;color:var(--muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:150px;">
                                <?= sanitize($thread['last_msg']) ?>
                            </span>
                            <?php if ($thread['unread_cnt'] > 0): ?>
                            <span class="badge-dot" style="position:static;width:18px;height:18px;font-size:10px;"><?= $thread['unread_cnt'] ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
                <?php endwhile; ?>
                <?php endif; ?>
            </div>

            <!-- ── Chat Window ── -->
            <div class="col-md-8 d-flex flex-column" style="border-left:1.5px solid var(--border);">
                <?php if ($active_product && $active_receiver): ?>

                    <!-- Chat Header -->
                    <div class="d-flex align-items-center gap-3 p-3" style="border-bottom:1.5px solid var(--border);">
                        <div class="seller-avatar" style="width:42px;height:42px;font-size:14px;">
                            <?php if (!empty($active_receiver['avatar'])): ?>
                                <img src="<?= getProductImageUrl($active_receiver['avatar']) ?>" alt="" onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name=<?= urlencode($active_receiver['full_name']) ?>&background=0F172A&color=fff';">
                            <?php else: ?>
                                <?= getAvatar($active_receiver['full_name']) ?>
                            <?php endif; ?>
                        </div>
                        <div>
                            <div class="fw-700"><?= sanitize($active_receiver['full_name']) ?></div>
                            <div style="font-size:12px;color:var(--muted);">
                                <?= $active_product['cat_icon'] ?> <?= sanitize($active_product['title']) ?> ·
                                <strong style="color:var(--emerald);"><?= formatPrice($active_product['price']) ?></strong>
                            </div>
                        </div>
                        <a href="product.php?id=<?= $active_product['id'] ?>" class="ms-auto btn btn-outline-success btn-sm" style="border-radius:8px;">
                            View Product
                        </a>
                    </div>

                    <!-- Messages -->
                    <div class="chat-messages" id="chatMessages">
                        <?php if (empty($messages_data)): ?>
                        <div class="text-center text-muted py-4" style="font-size:14px;">
                            Start the conversation about <strong><?= sanitize($active_product['title']) ?></strong>
                        </div>
                        <?php else: ?>
                        <?php foreach ($messages_data as $msg): ?>
                        <div class="d-flex <?= $msg['sender_id']==$uid ? 'justify-content-end' : 'justify-content-start' ?>">
                            <div class="msg-bubble <?= $msg['sender_id']==$uid ? 'msg-me' : 'msg-them' ?>">
                                <?= nl2br(sanitize($msg['message'])) ?>
                                <div style="font-size:10px;opacity:.6;margin-top:4px;text-align:right;"><?= timeAgo($msg['created_at']) ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Input -->
                    <div class="p-3" style="border-top:1.5px solid var(--border);">
                        <form id="chatMsgForm" method="POST"
                              data-product="<?= $active_product_id ?>"
                              data-receiver="<?= $active_receiver_id ?>"
                              data-chat-endpoint="chat.php"
                              data-fetch-endpoint="chat-fetch.php"
                              class="d-flex gap-2">
                            <input type="hidden" name="product_id"  value="<?= $active_product_id ?>">
                            <input type="hidden" name="receiver_id" value="<?= $active_receiver_id ?>">
                            <input type="hidden" name="request_token" value="<?= $chat_request_token ?>">
                            <input type="text" name="message" class="form-control um-input" placeholder="Type a message..." autocomplete="off" required>
                            <button type="submit" class="btn btn-orange px-4">
                                <i class="bi bi-send"></i>
                            </button>
                        </form>
                    </div>

                <?php else: ?>
                    <div class="empty-state flex-fill d-flex flex-column align-items-center justify-content-center">
                        <div class="empty-icon mb-2"><i class="bi bi-chat-square-dots fs-1 text-muted"></i></div>
                        <p class="text-muted">Select a conversation to start chatting</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
