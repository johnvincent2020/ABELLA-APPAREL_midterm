<?php

session_start();
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

if (
    !isset($_SESSION['logged_in']) ||
    $_SESSION['logged_in'] !== true ||
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['user_role'])
) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized.'
    ]);
    exit;
}

$userRole = $_SESSION['user_role'];
$userId = (int)$_SESSION['user_id'];

if ($userRole === 'user') {

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

    if (!$stmt) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error.'
        ]);
        exit;
    }

    $stmt->bind_param("i", $userId);
    $stmt->execute();

    $result = $stmt->get_result();

    $messages = [];

    while ($row = $result->fetch_assoc()) {
        $messages[] = [
            'id' => (int)$row['id'],
            'sender' => $row['sender'],
            'message' => $row['message'],
            'is_read' => (int)$row['is_read'],
            'created_at' => $row['created_at'],
            'time' => date(
                'M d • h:i A',
                strtotime($row['created_at'])
            )
        ];
    }

    $stmt->close();

    $readStmt = $conn->prepare("
        UPDATE messages
        SET is_read = 1
        WHERE user_id = ?
        AND sender = 'admin'
        AND is_read = 0
    ");

    if ($readStmt) {
        $readStmt->bind_param("i", $userId);
        $readStmt->execute();
        $readStmt->close();
    }

    echo json_encode([
        'success' => true,
        'messages' => $messages
    ]);

    $conn->close();
    exit;
}

if ($userRole === 'admin') {

    $selectedUser = (int)($_GET['user'] ?? 0);

    if ($selectedUser <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid customer.'
        ]);
        exit;
    }

    $customerStmt = $conn->prepare("
        SELECT id
        FROM users
        WHERE id = ?
        AND role = 'user'
        LIMIT 1
    ");

    if (!$customerStmt) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error.'
        ]);
        exit;
    }

    $customerStmt->bind_param("i", $selectedUser);
    $customerStmt->execute();

    $customerResult = $customerStmt->get_result();

    if ($customerResult->num_rows === 0) {
        $customerStmt->close();

        echo json_encode([
            'success' => false,
            'message' => 'Customer not found.'
        ]);
        exit;
    }

    $customerStmt->close();

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

    if (!$stmt) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error.'
        ]);
        exit;
    }

    $stmt->bind_param("i", $selectedUser);
    $stmt->execute();

    $result = $stmt->get_result();

    $messages = [];

    while ($row = $result->fetch_assoc()) {
        $messages[] = [
            'id' => (int)$row['id'],
            'sender' => $row['sender'],
            'message' => $row['message'],
            'is_read' => (int)$row['is_read'],
            'created_at' => $row['created_at'],
            'time' => date(
                'M d, Y • h:i A',
                strtotime($row['created_at'])
            )
        ];
    }

    $stmt->close();

    $readStmt = $conn->prepare("
        UPDATE messages
        SET is_read = 1
        WHERE user_id = ?
        AND sender = 'customer'
        AND is_read = 0
    ");

    if ($readStmt) {
        $readStmt->bind_param("i", $selectedUser);
        $readStmt->execute();
        $readStmt->close();
    }

    $customers = [];

    $customerList = $conn->query("
        SELECT
            u.id,
            u.name,
            u.email,

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

    if ($customerList) {
        while ($row = $customerList->fetch_assoc()) {
            $customers[] = [
                'id' => (int)$row['id'],
                'name' => $row['name'],
                'email' => $row['email'],
                'last_message' => $row['last_message'],
                'last_message_time' => $row['last_message_time'],
                'unread_count' => (int)$row['unread_count']
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'messages' => $messages,
        'customers' => $customers
    ]);

    $conn->close();
    exit;
}

echo json_encode([
    'success' => false,
    'message' => 'Invalid role.'
]);

$conn->close();
?>