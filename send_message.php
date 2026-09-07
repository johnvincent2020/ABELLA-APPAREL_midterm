<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

/* Make sure customer is logged in */
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode([
        'success' => false,
        'message' => 'You must be logged in.'
    ]);
    exit();
}

/* Get customer ID */
$sender_id = $_SESSION['user_id'] ?? 0;

/* Get admin ID */
$admin = $conn->query("
    SELECT id 
    FROM users 
    WHERE role = 'admin' 
    LIMIT 1
");

if (!$admin || $admin->num_rows === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Admin account not found.'
    ]);
    exit();
}

$admin_data = $admin->fetch_assoc();
$receiver_id = $admin_data['id'];

/* Get message */
$message = trim($_POST['message'] ?? '');

if ($message === '') {
    echo json_encode([
        'success' => false,
        'message' => 'Please enter a message.'
    ]);
    exit();
}

/* Insert message */
$stmt = $conn->prepare("
    INSERT INTO messages 
    (sender_id, receiver_id, message, is_read)
    VALUES (?, ?, ?, 0)
");

$stmt->bind_param(
    "iis",
    $sender_id,
    $receiver_id,
    $message
);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Message sent successfully.'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to send message.'
    ]);
}

$stmt->close();
$conn->close();
?>