<?php
session_start();
require_once 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode([
        'success' => false,
        'message' => 'Please log in to subscribe.',
        'requires_login' => true
    ]);
    exit();
}

$email = trim($_POST['email'] ?? '');

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'message' => 'Please enter a valid email address.'
    ]);
    exit();
}

$checkStmt = $conn->prepare(
    "SELECT id FROM subscribers WHERE email = ?"
);
$checkStmt->bind_param("s", $email);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows > 0) {
    $checkStmt->close();
    echo json_encode([
        'success' => false,
        'message' => 'This email is already subscribed.'
    ]);
    exit();
}
$checkStmt->close();

$stmt = $conn->prepare(
    "INSERT INTO subscribers (email) VALUES (?)"
);
$stmt->bind_param("s", $email);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Thanks for subscribing! Check your inbox for 15% off.'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Something went wrong. Please try again.'
    ]);
}

$stmt->close();
$conn->close();
?>