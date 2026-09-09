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

$checkStmt = $conn->prepare("
    SELECT
        id,
        discount_code,
        discount_used
    FROM subscribers
    WHERE email = ?
    LIMIT 1
");

if (!$checkStmt) {
    echo json_encode([
        'success' => false,
        'message' => 'Something went wrong. Please try again.'
    ]);
    exit();
}

$checkStmt->bind_param("s", $email);
$checkStmt->execute();

$checkResult = $checkStmt->get_result();

if ($checkResult && $checkResult->num_rows > 0) {
    $existingSubscriber = $checkResult->fetch_assoc();
    $checkStmt->close();

    if (!empty($existingSubscriber['discount_code'])) {
        echo json_encode([
            'success' => false,
            'message' => 'This email is already subscribed.',
            'discount_code' => $existingSubscriber['discount_code']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'This email is already subscribed.'
        ]);
    }

    exit();
}

$checkStmt->close();

$discountCode = 'ABELLA15-' . strtoupper(
    substr(bin2hex(random_bytes(5)), 0, 8)
);

$insertStmt = $conn->prepare("
    INSERT INTO subscribers
    (
        email,
        discount_code,
        discount_percent,
        discount_used
    )
    VALUES (?, ?, 15.00, 0)
");

if (!$insertStmt) {
    echo json_encode([
        'success' => false,
        'message' => 'Something went wrong. Please try again.'
    ]);
    exit();
}

$insertStmt->bind_param(
    "ss",
    $email,
    $discountCode
);

if ($insertStmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Thanks for subscribing! You received 15% off your first order.',
        'discount_code' => $discountCode,
        'discount_percent' => 15
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Something went wrong. Please try again.'
    ]);
}

$insertStmt->close();
$conn->close();
?>