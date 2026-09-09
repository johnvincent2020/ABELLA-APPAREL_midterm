<?php

session_start();
require_once 'config.php';

function abella_initials($name) {

    $name = trim((string)$name);

    if ($name === '') {
        return '?';
    }

    $parts = preg_split('/\s+/', $name);
    $initials = strtoupper(substr($parts[0], 0, 1));

    if (count($parts) > 1) {
        $initials .= strtoupper(substr($parts[count($parts) - 1], 0, 1));
    }

    return $initials;
}

function abella_avatar_color($seed) {

    $palette = [
        '#c49d4c', '#7a8bd8', '#5cb3a4',
        '#d17a9a', '#d1975c', '#8a7fd1',
        '#5ca6d1', '#c46b6b'
    ];

    $hash = 0;

    foreach (str_split((string)$seed) as $char) {
        $hash = (($hash << 5) - $hash) + ord($char);
        $hash &= 0xFFFFFFFF;
    }

    return $palette[abs($hash) % count($palette)];
}

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

        $checkCustomer = $conn->prepare("
            SELECT id
            FROM users
            WHERE id = ?
            AND role = 'user'
            LIMIT 1
        ");

        if ($checkCustomer) {

            $checkCustomer->bind_param("i", $userId);
            $checkCustomer->execute();

            $customerResult = $checkCustomer->get_result();
            $customerExists = $customerResult->num_rows > 0;

            $checkCustomer->close();

            if (!$customerExists) {

                $replyError = 'Customer does not exist.';

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

                    if (!$stmt->execute()) {
                        $replyError = 'Message could not be sent.';
                    }

                    $stmt->close();

                } else {

                    $replyError = 'Unable to prepare message.';
                }
            }

        } else {

            $replyError = 'Unable to verify customer.';
        }
    }

    $selectedUser = $userId;

    if (
        isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
    ) {

        header('Content-Type: application/json; charset=utf-8');

        if ($replyError !== '') {

            echo json_encode([
                'success' => false,
                'message' => $replyError
            ]);

        } else {

            $newMessageId = (int)$conn->insert_id;

            $stmt = $conn->prepare("
                SELECT
                    id,
                    sender,
                    message,
                    is_read,
                    created_at
                FROM messages
                WHERE id = ?
                LIMIT 1
            ");

            $newMessage = null;

            if ($stmt) {

                $stmt->bind_param("i", $newMessageId);
                $stmt->execute();

                $result = $stmt->get_result();
                $newMessage = $result->fetch_assoc();

                $stmt->close();
            }

            echo json_encode([
                'success' => true,
                'message_data' => $newMessage
            ]);
        }

        exit();
    }
}

$customers = [];

$result = $conn->query("
    SELECT
        u.id,
        u.name,
        u.email,
        u.profile_photo,

        (
            SELECT message
            FROM messages m2
            WHERE m2.user_id = u.id
            ORDER BY m2.created_at DESC, m2.id DESC
            LIMIT 1
        ) AS last_message,

        (
            SELECT created_at
            FROM messages m3
            WHERE m3.user_id = u.id
            ORDER BY m3.created_at DESC, m3.id DESC
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
            email,
            profile_photo
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
        ORDER BY created_at ASC, id ASC
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
<!DOCTYPE html>
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
    padding: 20px 20px 14px;
    color: #fff;
    font-size: 15px;
    font-weight: 800;
}

.customer-search {
    padding: 0 16px 14px;
}

.customer-search input {
    width: 100%;
    background: #1c1c1c;
    border: 1px solid #292929;
    border-radius: 20px;
    padding: 10px 16px;
    color: #fff;
    font-size: 12px;
    outline: none;
    transition: 0.2s ease;
}

.customer-search input::placeholder {
    color: #777;
}

.customer-search input:focus {
    border-color: #c49d4c;
}

.customer-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    color: #fff;
    transition: 0.2s ease;
    border-radius: 12px;
    margin: 2px 8px;
    width: calc(100% - 16px);
}

.customer-item:hover {
    background: #181818;
}

.customer-item.active {
    background: #1c1c1c;
}

.avatar {
    flex-shrink: 0;
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #111;
    font-size: 15px;
    font-weight: 800;
    overflow: hidden;
}

.avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.avatar .avatar-fallback {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
}

.avatar.avatar-sm {
    width: 34px;
    height: 34px;
    font-size: 12px;
}

.customer-item-body {
    flex: 1;
    min-width: 0;
}

.customer-item-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 3px;
}

.customer-name {
    font-size: 13px;
    font-weight: 700;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.customer-time {
    color: #777;
    font-size: 9px;
    flex-shrink: 0;
}

.customer-item-bottom {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
}

.last-message {
    color: #999;
    font-size: 11px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.customer-item.has-unread .last-message {
    color: #fff;
    font-weight: 600;
}

.unread-badge {
    flex-shrink: 0;
    min-width: 18px;
    height: 18px;
    padding: 0 5px;
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
    padding: 16px 20px;
    border-bottom: 1px solid #292929;
    background: #111;
    display: flex;
    align-items: center;
    gap: 12px;
}

.conversation-header h2 {
    font-size: 15px;
    margin-bottom: 3px;
}

.conversation-header p {
    color: #777;
    font-size: 11px;
}

.conversation-body {
    flex: 1;
    padding: 20px 25px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    scroll-behavior: smooth;
}

.message-bubble {
    max-width: 65%;
    padding: 10px 16px;
    border-radius: 20px;
    margin-top: 8px;
    animation: bubble-in 0.15s ease-out;
}

.message-bubble.grouped {
    margin-top: 2px;
}

.customer-message {
    align-self: flex-start;
    background: #262626;
    border-bottom-left-radius: 4px;
}

.customer-message.grouped {
    border-top-left-radius: 20px;
}

.admin-message {
    align-self: flex-end;
    background: linear-gradient(135deg, #c49d4c, #a67e34);
    border-bottom-right-radius: 4px;
}

.admin-message.grouped {
    border-top-right-radius: 20px;
}

.message-sender {
    display: none;
}

.message-text {
    color: #fff;
    font-size: 13px;
    line-height: 1.5;
    word-break: break-word;
}

.customer-message .message-text {
    color: #eee;
}

.admin-message .message-text {
    color: #111;
    font-weight: 500;
}

.message-time {
    color: #666;
    font-size: 9px;
    margin-top: 4px;
    text-align: right;
}

.customer-message + .message-time,
.customer-message ~ .message-time {
    text-align: left;
}

.message-time.admin-time {
    align-self: flex-end;
}

.message-time.customer-time-stamp {
    align-self: flex-start;
}

.seen-indicator {
    align-self: flex-end;
    color: #777;
    font-size: 9px;
    margin-top: 3px;
    margin-bottom: 4px;
    letter-spacing: 0.5px;
}

@keyframes bubble-in {
    from {
        opacity: 0;
        transform: translateY(6px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.reply-area {
    padding: 16px 20px;
    border-top: 1px solid #292929;
    background: #111;
}

.reply-form {
    display: flex;
    align-items: flex-end;
    gap: 10px;
    background: #1c1c1c;
    border: 1px solid #292929;
    border-radius: 24px;
    padding: 6px 6px 6px 18px;
    transition: 0.2s ease;
}

.reply-form:focus-within {
    border-color: #c49d4c;
}

.reply-form textarea {
    flex: 1;
    height: 24px;
    max-height: 90px;
    resize: none;
    background: transparent;
    color: #fff;
    border: none;
    padding: 6px 0;
    outline: none;
    font-family:
        Arial,
        Helvetica,
        sans-serif;
    font-size: 13px;
    line-height: 1.4;
}

.reply-button {
    flex-shrink: 0;
    width: 38px;
    height: 38px;
    border-radius: 50%;
    border: none;
    background: #c49d4c;
    color: #111;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: 0.2s ease;
}

.reply-button svg {
    width: 16px;
    height: 16px;
    fill: #111;
    margin-left: -1px;
}

.reply-button:hover {
    background: #fff;
}

.reply-button:disabled {
    opacity: 0.5;
    cursor: default;
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

    .message-bubble {
        max-width: 85%;
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
        <a href="admin_reviews.php">
         Reviews
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

        <a href="javascript:history.back()" class="back-btn">
    BACK
</a>
    </div>

    <div class="messages-container">

        <div class="panel customer-list">

            <div class="customer-list-header">
                Messages
            </div>

            <div class="customer-search">
                <input
                    type="text"
                    id="customerSearch"
                    placeholder="Search customers..."
                    autocomplete="off"
                >
            </div>

            <?php if (!empty($customers)): ?>

                <?php foreach ($customers as $customer): ?>

                    <?php
                        $unread = (int)$customer['unread_count'];
                        $initials = abella_initials($customer['name']);
                        $avatarColor = abella_avatar_color($customer['id']);
                        $lastTime = $customer['last_message_time']
                            ? date('M d', strtotime($customer['last_message_time']))
                            : '';
                    ?>

                    <a
                        href="admin_messages.php?user=<?= (int)$customer['id'] ?>"
                        class="customer-item <?= $selectedUser === (int)$customer['id'] ? 'active' : '' ?> <?= $unread > 0 ? 'has-unread' : '' ?>"
                        data-customer-id="<?= (int)$customer['id'] ?>"
                        data-customer-name="<?= htmlspecialchars(strtolower($customer['name'])) ?>"
                    >

                        <div
                            class="avatar"
                            style="background: <?= $avatarColor ?>;"
                        >
                            <?php if (!empty($customer['profile_photo'])): ?>
                                <img
                                    src="assets/profiles/<?= htmlspecialchars(basename($customer['profile_photo'])) ?>"
                                    alt="<?= htmlspecialchars($customer['name']) ?>"
                                    onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                >
                                <span class="avatar-fallback" style="display:none;">
                                    <?= htmlspecialchars($initials) ?>
                                </span>
                            <?php else: ?>
                                <span class="avatar-fallback">
                                    <?= htmlspecialchars($initials) ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="customer-item-body">

                            <div class="customer-item-top">
                                <div class="customer-name">
                                    <?= htmlspecialchars($customer['name']) ?>
                                </div>
                                <div class="customer-time">
                                    <?= htmlspecialchars($lastTime) ?>
                                </div>
                            </div>

                            <div class="customer-item-bottom">
                                <div class="last-message">
                                    <?= htmlspecialchars($customer['last_message']) ?>
                                </div>

                                <?php if ($unread > 0): ?>
                                    <span class="unread-badge">
                                        <?= $unread ?>
                                    </span>
                                <?php endif; ?>
                            </div>

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

                    <div
                        class="avatar avatar-sm"
                        style="background: <?= abella_avatar_color($selectedCustomer['id']) ?>;"
                    >
                        <?php if (!empty($selectedCustomer['profile_photo'])): ?>
                            <img
                                src="assets/profiles/<?= htmlspecialchars(basename($selectedCustomer['profile_photo'])) ?>"
                                alt="<?= htmlspecialchars($selectedCustomer['name']) ?>"
                                onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                            >
                            <span class="avatar-fallback" style="display:none;">
                                <?= htmlspecialchars(abella_initials($selectedCustomer['name'])) ?>
                            </span>
                        <?php else: ?>
                            <span class="avatar-fallback">
                                <?= htmlspecialchars(abella_initials($selectedCustomer['name'])) ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <div>
                        <h2>
                            <?= htmlspecialchars($selectedCustomer['name']) ?>
                        </h2>

                        <p>
                            <?= htmlspecialchars($selectedCustomer['email']) ?>
                        </p>
                    </div>

                </div>

                <div
                    class="conversation-body"
                    id="conversationBody"
                    data-user-id="<?= $selectedUser ?>"
                >

                    <?php if (!empty($conversation)): ?>

                        <?php
                            $prevSender = null;
                            $lastAdminIndex = null;

                            foreach ($conversation as $i => $msg) {
                                if ($msg['sender'] === 'admin') {
                                    $lastAdminIndex = $i;
                                }
                            }
                        ?>

                        <?php foreach ($conversation as $i => $msg): ?>

                            <?php
                                $isGrouped = $prevSender === $msg['sender'];
                                $prevSender = $msg['sender'];

                                $nextSender = isset($conversation[$i + 1])
                                    ? $conversation[$i + 1]['sender']
                                    : null;

                                $isLastInGroup = $nextSender !== $msg['sender'];
                            ?>

                            <div
                                class="message-bubble <?= $msg['sender'] === 'customer' ? 'customer-message' : 'admin-message' ?> <?= $isGrouped ? 'grouped' : '' ?>"
                                data-message-id="<?= (int)$msg['id'] ?>"
                                data-sender="<?= htmlspecialchars($msg['sender']) ?>"
                                data-read="<?= (int)$msg['is_read'] ?>"
                            >

                                <div class="message-text">
                                    <?= nl2br(htmlspecialchars($msg['message'])) ?>
                                </div>

                            </div>

                            <?php if ($isLastInGroup): ?>
                                <div class="message-time <?= $msg['sender'] === 'admin' ? 'admin-time' : 'customer-time-stamp' ?>">
                                    <?= date(
                                        'M d, h:i A',
                                        strtotime($msg['created_at'])
                                    ) ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($i === $lastAdminIndex && (int)$msg['is_read'] === 1): ?>
                                <div class="seen-indicator" id="seenIndicator">
                                    Seen
                                </div>
                            <?php endif; ?>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <div
                            class="empty"
                            id="emptyConversation"
                        >
                            No messages in this conversation.
                        </div>

                    <?php endif; ?>

                </div>

                <div class="reply-area">

                    <?php if ($replyError): ?>

                        <div class="error-message">
                            <?= htmlspecialchars($replyError) ?>
                        </div>

                    <?php endif; ?>

                    <form
                        method="POST"
                        class="reply-form"
                        id="replyForm"
                    >

                        <input
                            type="hidden"
                            name="user_id"
                            value="<?= $selectedUser ?>"
                        >

                        <textarea
                            name="message"
                            id="replyMessage"
                            placeholder="Write your reply..."
                            required
                        ></textarea>

                        <button
                            type="submit"
                            class="reply-button"
                            id="replyButton"
                            aria-label="Send reply"
                        >
                            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="M2 21l21-9L2 3v7l15 2-15 2z"></path>
                            </svg>
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

<script>
document.addEventListener("DOMContentLoaded", function () {

    const customerSearch =
        document.getElementById("customerSearch");

    if (customerSearch) {

        customerSearch.addEventListener(
            "input",
            function () {

                const query =
                    this.value.trim().toLowerCase();

                document
                    .querySelectorAll(
                        ".customer-item[data-customer-name]"
                    )
                    .forEach(function (item) {

                        const name =
                            item.dataset.customerName || "";

                        item.style.display =
                            name.indexOf(query) !== -1
                                ? "flex"
                                : "none";
                    });

            }
        );
    }

    const conversationBody =
        document.getElementById("conversationBody");

    const replyForm =
        document.getElementById("replyForm");

    const replyMessage =
        document.getElementById("replyMessage");

    const replyButton =
        document.getElementById("replyButton");

    if (!conversationBody) {
        return;
    }

    const currentUserId =
        parseInt(
            conversationBody.dataset.userId || "0",
            10
        );

    let latestMessageId = 0;
    let lastRenderedSender = null;

    const existingMessages =
        conversationBody.querySelectorAll(
            ".message-bubble[data-message-id]"
        );

    existingMessages.forEach(function (message) {

        const id =
            parseInt(
                message.dataset.messageId || "0",
                10
            );

        if (id > latestMessageId) {
            latestMessageId = id;
        }

        lastRenderedSender =
            message.dataset.sender || lastRenderedSender;

    });

    conversationBody.scrollTop =
        conversationBody.scrollHeight;

    function escapeHtml(text) {

        const div =
            document.createElement("div");

        div.textContent =
            text == null ? "" : String(text);

        return div.innerHTML;
    }

    function formatMessageTime(createdAt) {

        if (!createdAt) {
            return "";
        }

        const date =
            new Date(
                createdAt.replace(" ", "T")
            );

        if (isNaN(date.getTime())) {
            return createdAt;
        }

        return date.toLocaleDateString(
            "en-US",
            {
                month: "short",
                day: "2-digit",
                year: "numeric"
            }
        ) + " • " +
        date.toLocaleTimeString(
            "en-US",
            {
                hour: "2-digit",
                minute: "2-digit"
            }
        );
    }

    function addMessage(message, forceScroll) {

        if (!message || !message.id) {
            return;
        }

        const messageId =
            parseInt(message.id, 10);

        if (
            conversationBody.querySelector(
                '[data-message-id="' +
                messageId +
                '"]'
            )
        ) {
            return;
        }

        const emptyConversation =
            document.getElementById(
                "emptyConversation"
            );

        if (emptyConversation) {
            emptyConversation.remove();
        }

        const isGrouped =
            lastRenderedSender === message.sender;

        const oldSeenIndicator =
            document.getElementById("seenIndicator");

        if (oldSeenIndicator) {
            oldSeenIndicator.remove();
        }

        const bubble =
            document.createElement("div");

        bubble.className =
            "message-bubble " +
            (
                message.sender === "customer"
                    ? "customer-message"
                    : "admin-message"
            ) +
            (isGrouped ? " grouped" : "");

        bubble.dataset.messageId =
            messageId;

        bubble.dataset.sender =
            message.sender;

        bubble.dataset.read =
            message.is_read ? "1" : "0";

        bubble.innerHTML =
            '<div class="message-text">' +
                escapeHtml(message.message)
                    .replace(/\n/g, "<br>") +
            '</div>';

        conversationBody.appendChild(
            bubble
        );

        const timeRow =
            document.createElement("div");

        timeRow.className =
            "message-time " +
            (
                message.sender === "admin"
                    ? "admin-time"
                    : "customer-time-stamp"
            );

        timeRow.textContent =
            formatMessageTime(
                message.created_at
            );

        conversationBody.appendChild(
            timeRow
        );

        lastRenderedSender =
            message.sender;

        if (messageId > latestMessageId) {
            latestMessageId = messageId;
        }

        if (forceScroll) {

            conversationBody.scrollTop =
                conversationBody.scrollHeight;
        }
    }

    function pollMessages() {

        if (currentUserId <= 0) {
            return;
        }

        fetch(
            "messages_poll.php?user_id=" +
            encodeURIComponent(currentUserId) +
            "&after_id=" +
            encodeURIComponent(latestMessageId),
            {
                method: "GET",
                cache: "no-store",
                headers: {
                    "X-Requested-With":
                        "XMLHttpRequest"
                }
            }
        )
        .then(function (response) {
            return response.json();
        })
        .then(function (data) {

            if (
                !data ||
                data.success !== true
            ) {
                return;
            }

            const distanceFromBottom =
                conversationBody.scrollHeight -
                conversationBody.scrollTop -
                conversationBody.clientHeight;

            const wasNearBottom =
                distanceFromBottom < 120;

            if (
                Array.isArray(data.messages)
            ) {

                data.messages.forEach(
                    function (message) {

                        addMessage(
                            message,
                            false
                        );

                    }
                );

                if (
                    data.messages.length > 0 &&
                    wasNearBottom
                ) {

                    conversationBody.scrollTop =
                        conversationBody.scrollHeight;
                }
            }

            if (
                Array.isArray(
                    data.customer_read_ids
                )
            ) {

                let highestReadAdminId = 0;

                data.customer_read_ids.forEach(
                    function (id) {

                        const parsedId =
                            parseInt(id, 10);

                        const messageElement =
                            conversationBody.querySelector(
                                '[data-message-id="' +
                                parsedId +
                                '"]'
                            );

                        if (messageElement) {

                            messageElement.dataset.read =
                                "1";

                            if (
                                messageElement.dataset.sender ===
                                    "admin" &&
                                parsedId > highestReadAdminId
                            ) {
                                highestReadAdminId =
                                    parsedId;
                            }
                        }

                    }
                );

                const allAdminBubbles =
                    conversationBody.querySelectorAll(
                        '.message-bubble[data-sender="admin"]'
                    );

                const lastAdminBubble =
                    allAdminBubbles.length
                        ? allAdminBubbles[
                            allAdminBubbles.length - 1
                        ]
                        : null;

                const existingSeen =
                    document.getElementById(
                        "seenIndicator"
                    );

                if (
                    lastAdminBubble &&
                    lastAdminBubble.dataset.read === "1"
                ) {

                    if (!existingSeen) {

                        const seenDiv =
                            document.createElement("div");

                        seenDiv.className =
                            "seen-indicator";

                        seenDiv.id = "seenIndicator";
                        seenDiv.textContent = "Seen";

                        conversationBody.appendChild(
                            seenDiv
                        );
                    }

                } else if (existingSeen) {

                    existingSeen.remove();
                }
            }

            if (
                Array.isArray(data.customers)
            ) {

                data.customers.forEach(
                    function (customer) {

                        const customerItem =
                            document.querySelector(
                                '[data-customer-id="' +
                                parseInt(customer.id, 10) +
                                '"]'
                            );

                        if (!customerItem) {
                            return;
                        }

                        const oldBadge =
                            customerItem.querySelector(
                                ".unread-badge"
                            );

                        const unreadCount =
                            parseInt(
                                customer.unread_count || 0,
                                10
                            );

                        if (unreadCount > 0) {

                            if (oldBadge) {

                                oldBadge.textContent =
                                    unreadCount;

                            } else {

                                const badge =
                                    document.createElement(
                                        "span"
                                    );

                                badge.className =
                                    "unread-badge";

                                badge.textContent =
                                    unreadCount;

                                customerItem.prepend(
                                    badge
                                );
                            }

                        } else if (oldBadge) {

                            oldBadge.remove();
                        }

                        const lastMessage =
                            customerItem.querySelector(
                                ".last-message"
                            );

                        if (
                            lastMessage &&
                            customer.last_message !== null
                        ) {

                            lastMessage.textContent =
                                customer.last_message;
                        }

                    }
                );
            }

        })
        .catch(function () {

        });
    }

    if (replyForm) {

        replyForm.addEventListener(
            "submit",
            function (event) {

                event.preventDefault();

                const message =
                    replyMessage.value.trim();

                if (!message) {
                    return;
                }

                replyButton.disabled = true;

                const formData =
                    new FormData(replyForm);

                fetch(
                    "admin_messages.php?user=" +
                    encodeURIComponent(currentUserId),
                    {
                        method: "POST",
                        body: formData,
                        headers: {
                            "X-Requested-With":
                                "XMLHttpRequest"
                        }
                    }
                )
                .then(function (response) {
                    return response.json();
                })
                .then(function (data) {

                    if (
                        data &&
                        data.success === true
                    ) {

                        if (
                            data.message_data
                        ) {

                            addMessage(
                                data.message_data,
                                true
                            );
                        }

                        replyMessage.value = "";

                        replyMessage.style.height =
                            "24px";

                    } else {

                        alert(
                            data && data.message
                                ? data.message
                                : "Message could not be sent."
                        );
                    }

                })
                .catch(function () {

                    alert(
                        "Message could not be sent."
                    );

                })
                .finally(function () {

                    replyButton.disabled =
                        false;
                });

            }
        );

        replyMessage.addEventListener(
            "keydown",
            function (event) {

                if (
                    event.key === "Enter" &&
                    !event.shiftKey
                ) {

                    event.preventDefault();

                    replyForm.requestSubmit();
                }

            }
        );

        replyMessage.addEventListener(
            "input",
            function () {

                this.style.height =
                    "24px";

                this.style.height =
                    Math.min(
                        this.scrollHeight,
                        90
                    ) + "px";

            }
        );
    }

    pollMessages();

    setInterval(
        pollMessages,
        2000
    );

});
</script>

</body>
</html>