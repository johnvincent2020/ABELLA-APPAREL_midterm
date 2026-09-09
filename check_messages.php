<?php

session_start();

require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

$isLoggedIn = isset($_SESSION['logged_in'])
    && $_SESSION['logged_in'] === true;

$isUser = $isLoggedIn
    && isset($_SESSION['user_role'])
    && $_SESSION['user_role'] === 'user';

$hasUnreadMessages = false;

if ($isUser && isset($_SESSION['user_id'])) {

    $userId = (int)$_SESSION['user_id'];

    $stmt = $conn->prepare("
        SELECT COUNT(*) AS unread_count
        FROM messages
        WHERE user_id = ?
        AND sender = 'admin'
        AND is_read = 0
    ");

    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $hasUnreadMessages = ((int)($row['unread_count'] ?? 0)) > 0;
        $stmt->close();
    }
}

echo json_encode([
    'hasUnreadMessages' => $hasUnreadMessages
]);