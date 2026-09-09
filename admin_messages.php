<script>
document.addEventListener("DOMContentLoaded", function () {

    const conversationBody =
        document.querySelector(".conversation-body");

    if (conversationBody) {

        conversationBody.scrollTop =
            conversationBody.scrollHeight;

    }

});
</script>
<?php

session_start();
require_once 'config.php';
if (
    !isset($_SESSION['logged_in']) ||
    $_SESSION['logged_in'] !== true ||
    !isset($_SESSION['user_role']) ||
    $_SESSION['user_role'] !== 'admin'
) {
    header("Location: login_register.php");
    exit();
}

$selectedUser = isset($_GET['user'])
    ? (int)$_GET['user']
    : 0;

$replyError = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = (int)($_POST['user_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');

    if ($userId <= 0) {
        $replyError = 'Invalid customer.';
    } elseif ($message === '') {
        $replyError = 'Please enter your message.';
    } else {
        $stmt = $conn->prepare("
            INSERT INTO messages
            (user_id, sender, message, is_read)
            VALUES (?, 'admin', ?, 0)
        ");
        if ($stmt) {
            $stmt->bind_param(
                "is",
                $userId,
                $message
            );
            if ($stmt->execute()) {
                $stmt->close();
                header(
                    "Location: admin_messages.php?user=" .
                    $userId
                );
                exit();
            } else {
                $replyError =
                    'Message could not be sent.';
                $stmt->close();
            }
        } else {
            $replyError =
                'Unable to prepare message.';
        }
    }
    $selectedUser = $userId;
}

$customers = [];

$result = $conn->query("
    SELECT
        u.id,
        u.name,
        u.email,

        (
            SELECT message
            FROM messages m2
            WHERE m2.user_id = u.id
            ORDER BY m2.created_at DESC
            LIMIT 1
        ) AS last_message,

        (
            SELECT created_at
            FROM messages m3
            WHERE m3.user_id = u.id
            ORDER BY m3.created_at DESC
            LIMIT 1
        ) AS last_message_time,

        (
            SELECT COUNT(*)
            FROM messages m4
            WHERE m4.user_id = u.id
            AND m4.sender = 'customer'
            AND m4.is_read = 0
        ) AS unread_count

    FROM users u

    WHERE u.role = 'user'

    AND EXISTS (
        SELECT 1
        FROM messages m
        WHERE m.user_id = u.id
    )

    ORDER BY last_message_time DESC
");


if ($result) {

    while ($row = $result->fetch_assoc()) {

        $customers[] = $row;
    }
}

if ($selectedUser > 0) {

    $stmt = $conn->prepare("
        UPDATE messages
        SET is_read = 1
        WHERE user_id = ?
        AND sender = 'customer'
        AND is_read = 0
    ");

    if ($stmt) {

        $stmt->bind_param(
            "i",
            $selectedUser
        );

        $stmt->execute();
        $stmt->close();
    }
}

$selectedCustomer = null;

if ($selectedUser > 0) {

    $stmt = $conn->prepare("
        SELECT
            id,
            name,
            email
        FROM users
        WHERE id = ?
        AND role = 'user'
        LIMIT 1
    ");


    if ($stmt) {

        $stmt->bind_param(
            "i",
            $selectedUser
        );

        $stmt->execute();

        $result = $stmt->get_result();

        $selectedCustomer =
            $result->fetch_assoc();

        $stmt->close();
    }
}
$conversation = [];

if ($selectedCustomer) {

    $stmt = $conn->prepare("
        SELECT
            id,
            sender,
            message,
            is_read,
            created_at
        FROM messages
        WHERE user_id = ?
        ORDER BY created_at ASC
    ");
    if ($stmt) {
        $stmt->bind_param(
            "i",
            $selectedUser
        );
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $conversation[] = $row;
        }
        $stmt->close();
    }
}

?>
<!DOCTYPE html
<html lang="en">
<head>
<meta charset="UTF-8">
<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>
<title>
    Messages - Abella Apparel Admin
</title>
<style>
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}
body {
    font-family:
        Arial,
        Helvetica,
        sans-serif;
    background: #000;
    color: #fff;
}
a {
    text-decoration: none;
}
.sidebar {
    width: 250px;
    background: #111;
    color: #fff;
    padding: 30px 20px;
    position: fixed;
    left: 0;
    top: 0;
    bottom: 0;
    z-index: 20;
    border-right: 1px solid #292929;
}
.brand {
    padding: 0 12px 35px;
    display: flex;
    align-items: center;
    height: 70px;
}
.brand img {
    display: block;
    width: 150px;
    height: auto;
    max-height: 60px;
    object-fit: contain;
    object-position: left center;
}
.menu-title {
    color: #888;
    font-size: 10px;
    letter-spacing: 2px;
    margin: 0 12px 12px;
}
.nav-menu {
    display: flex;
    flex-direction: column;
    gap: 5px;
}
.nav-menu a {
    display: flex;
    align-items: center;
    gap: 0;
    padding: 13px 12px;
    color: #bbb;
    font-size: 13px;
    transition: 0.2s ease;
}
.nav-menu a:hover {
    background: #1d1d1d;
    color: #fff;
}
.nav-menu a.active {
    background: #c49d4c;
    color: #111;
    font-weight: 700;
}
.logout-link {
    position: absolute;
    left: 20px;
    right: 20px;
    bottom: 25px;
}
.logout-link a {
    display: block;
    padding: 13px;
    text-align: center;
    border: 1px solid #444;
    color: #aaa;
    font-size: 12px;
}
.logout-link a:hover {
    border-color: #c49d4c;
    color: #c49d4c;
}
.main {
    margin-left: 250px;
    min-height: 100vh;
    background: #000;
    color: #fff;
    padding: 30px;
}
.topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}
.page-title h1 {
    font-size: 28px;
    font-weight: 800;
}
.page-title p {
    color: #888;
    font-size: 13px;
    margin-top: 5px;
}
.back-btn {
    display: inline-block;
    padding: 10px 16px;
    border: 1px solid #333;
    background: #111;
    color: #fff;
    font-size: 12px;
    transition: 0.2s ease;
}
.back-btn:hover {
    border-color: #c49d4c;
    color: #c49d4c;
}
.messages-container {
    display: grid;
    grid-template-columns: 320px 1fr;
    gap: 20px;
    height: calc(100vh - 130px);
    min-height: 500px;
}
.panel {
    background: #111;
    border: 1px solid #292929;
    overflow: hidden;
}
.customer-list {
    height: 100%;
    overflow-y: auto;
}
.customer-list-header {
    padding: 20px;
    border-bottom: 1px solid #292929;
    color: #c49d4c;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 1.5px;
}
.customer-item {
    display: block;
    padding: 18px;
    border-bottom: 1px solid #292929;
    color: #fff;
    transition: 0.2s ease;
}
.customer-item:hover {
    background: #181818;
}
.customer-item.active {
    background: #1c1c1c;
    border-left: 3px solid #c49d4c;
}
.customer-name {
    font-size: 13px;
    font-weight: 700;
    margin-bottom: 5px;
}
.customer-email {
    font-size: 10px;
    color: #777;
    margin-bottom: 10px;
}
.last-message {
    color: #999;
    font-size: 11px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.unread-badge {
    float: right;
    min-width: 20px;
    height: 20px;
    padding: 0 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #c49d4c;
    color: #111;
    font-size: 9px;
    font-weight: 700;
    border-radius: 50%;
}
.conversation {
    height: 100%;
    display: flex;
    flex-direction: column;
}
.conversation-header {
    padding: 20px;
    border-bottom: 1px solid #292929;
    background: #111;
}
.conversation-header h2 {
    font-size: 17px;
    margin-bottom: 5px;
}
.conversation-header p {
    color: #777;
    font-size: 11px;
}
.conversation-body {
    flex: 1;
    padding: 25px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 15px;
}
.message-bubble {
    max-width: 75%;
    padding: 14px 16px;
    border: 1px solid #292929;
}
.customer-message {
    align-self: flex-start;
    background: #181818;
}
.admin-message {
    align-self: flex-end;
    background: #000;
    border-color: #c49d4c;
}
.message-sender {
    color: #c49d4c;
    font-size: 9px;
    font-weight: 700;
    letter-spacing: 1.5px;
    margin-bottom: 7px;
}
.message-text {
    color: #ddd;
    font-size: 12px;
    line-height: 1.7;
    word-break: break-word;
}
.message-time {
    color: #555;
    font-size: 9px;
    margin-top: 8px;
}
.reply-area {
    padding: 18px;
    border-top: 1px solid #292929;
    background: #111;
}
.reply-form {
    display: flex;
    gap: 10px;
}
.reply-form textarea {
    flex: 1;
    height: 70px;
    resize: none;
    background: #000;
    color: #fff;
    border: 1px solid #292929;
    padding: 12px;
    outline: none;
    font-family:
        Arial,
        Helvetica,
        sans-serif;
    font-size: 12px;
}
.reply-form textarea:focus {
    border-color: #c49d4c;
}
.reply-button {
    width: 110px;
    border: none;
    background: #c49d4c;
    color: #111;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 1px;
    cursor: pointer;
}
.reply-button:hover {
    background: #fff;
}
.error-message {
    color: #d88b8b;
    font-size: 11px;
    margin-bottom: 10px;
}
.empty {
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #777;
    font-size: 12px;
    text-align: center;
    padding: 30px;
}
@media (max-width: 900px) {
    .main {
        margin-left: 0;
    }
    .sidebar {
        position: relative;
        width: 100%;
        height: auto;
    }
    .messages-container {
        grid-template-columns: 1fr;
        height: auto;
    }
    .customer-list {
        max-height: 300px;
    }
}
@media (max-width: 600px) {
    .main {
        padding: 20px;
    }
    .topbar {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    .reply-form {
        flex-direction: column;
    }
    .reply-button {
        width: 100%;
        height: 45px;
    }
    .message-bubble {
        max-width: 90%;
    }
}
</style>
</head>
<body>
<aside class="sidebar">
    <div class="brand">
        <img
            src="assets/header-logo.png"
            alt="Abella Apparel"
        >
    </div>
    <div class="menu-title">
        MAIN MENU
    </div>
    <nav class="nav-menu">
        <a href="admin_page.php">
            Dashboard
        </a>
        <a href="admin_orders.php">
            Orders
        </a>
        <a href="admin_customers.php">
            Customers
        </a>
        <a href="admin_products.php">
            Products
        </a>
        <a href="admin_stock.php">
            Stock
        </a>
        <a
            href="admin_messages.php"
            class="active"
        >
            Messages
        </a>
        <a href="admin_subscribers.php">
            Subscribers
        </a>
        <a
            href="index.php"
            target="_blank"
        >
            View Store
        </a>
    </nav>
    <div class="logout-link">
        <a href="logout.php">
            LOGOUT
        </a>
    </div>
</aside>
<main class="main">
    <div class="topbar">
        <div class="page-title">
            <h1>
                Messages
            </h1>
            <p>
                Manage your customer conversations.
            </p>
        </div>
        <a
            href="admin_page.php"
            class="back-btn"
        >
            BACK TO DASHBOARD
        </a>
    </div>
    <div class="messages-container">
        <div class="panel customer-list">
            <div class="customer-list-header">
                CUSTOMER MESSAGES
            </div>
            <?php if (!empty($customers)): ?>
                <?php foreach (
                    $customers
                    as $customer
                ): ?>
                    <a
                        href="admin_messages.php?user=<?=
                            (int)$customer['id']
                        ?>"
                        class="customer-item
                        <?=
                            $selectedUser ===
                            (int)$customer['id']
                            ? 'active'
                            : ''
                        ?>"
                    >
                        <?php if (
                            (int)$customer['unread_count'] > 0
                        ): ?>
                            <span class="unread-badge">
                                <?=
                                    (int)
                                    $customer['unread_count']
                                ?>
                            </span>
                        <?php endif; ?>
                        <div class="customer-name">
                            <?= htmlspecialchars(
                                $customer['name']
                            ) ?>
                        </div>
                        <div class="customer-email">
                            <?= htmlspecialchars(
                                $customer['email']
                            ) ?>
                        </div>
                        <div class="last-message">
                            <?= htmlspecialchars(
                                $customer['last_message']
                            ) ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty">
                    No customer messages yet.
                </div>
            <?php endif; ?>
        </div>
        <div class="panel conversation">
            <?php if ($selectedCustomer): ?>
                <div class="conversation-header">
                    <h2>
                        <?= htmlspecialchars(
                            $selectedCustomer['name']
                        ) ?>
                    </h2>
                    <p>
                        <?= htmlspecialchars(
                            $selectedCustomer['email']
                        ) ?>
                    </p>
                </div>
                <div class="conversation-body">
                    <?php if (!empty($conversation)): ?>
                        <?php foreach (
                            $conversation
                            as $msg
                        ): ?>
                            <div
                                class="message-bubble
                                <?= $msg['sender'] === 'customer'
                                    ? 'customer-message'
                                    : 'admin-message' ?>"
                            >
                                <div class="message-sender">
                                    <?= $msg['sender'] === 'customer'
                                        ? 'CUSTOMER'
                                        : 'ABELLA APPAREL' ?>
                                </div>
                                <div class="message-text">
                                    <?= nl2br(
                                        htmlspecialchars(
                                            $msg['message']
                                        )
                                    ) ?>
                                </div>
                                <div class="message-time">
                                    <?= date(
                                        'M d, Y • h:i A',
                                        strtotime(
                                            $msg['created_at']
                                        )
                                    ) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty">
                            No messages in this conversation.
                        </div>
                    <?php endif; ?>
                </div>
                <div class="reply-area">
                    <?php if ($replyError): ?>
                        <div class="error-message">
                            <?= htmlspecialchars(
                                $replyError
                            ) ?>
                        </div>
                    <?php endif; ?>
                    <form
                        method="POST"
                        class="reply-form"
                    >
                        <input
                            type="hidden"
                            name="user_id"
                            value="<?= $selectedUser ?>"
                        >
                        <textarea
                            name="message"
                            placeholder="Write your reply..."
                            required
                        ></textarea>
                        <button
                            type="submit"
                            class="reply-button"
                        >
                            SEND REPLY
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <div class="empty">
                    Select a customer to view their conversation.
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>
</body>
</html>