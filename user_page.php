<?php

session_start();

require_once 'config.php';


if (
    !isset($_SESSION['logged_in']) ||
    $_SESSION['logged_in'] !== true ||
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['user_role']) ||
    $_SESSION['user_role'] !== 'user'
) {
    header("Location: index.php");
    exit();
}

$userId = (int) $_SESSION['user_id'];



$cartCount = 0;

if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += (int) ($item['quantity'] ?? 0);
    }
}




$user = [
    'name' => '',
    'email' => '',
    'profile_photo' => ''
];

$stmt = $conn->prepare("
    SELECT name, email, profile_photo
    FROM users
    WHERE id = ?
    LIMIT 1
");

$stmt->bind_param("i", $userId);
$stmt->execute();

$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $user = $result->fetch_assoc();
}

$stmt->close();



$isSubscribed = false;

if (!empty($user['email'])) {

    $subStmt = $conn->prepare("
        SELECT id
        FROM subscribers
        WHERE email = ?
        LIMIT 1
    ");

    $subStmt->bind_param("s", $user['email']);
    $subStmt->execute();

    $subResult = $subStmt->get_result();

    $isSubscribed = $subResult->num_rows > 0;

    $subStmt->close();
}

$flashSuccess = '';
$flashError = '';

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['update_account'])
) {

    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if ($name === '' || $email === '') {

        $flashError = 'Please fill in all required fields.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $flashError = 'Please enter a valid email address.';

    } else {

        $newProfilePhoto = $user['profile_photo'];

        if (
            isset($_FILES['profile_photo']) &&
            $_FILES['profile_photo']['error'] !== UPLOAD_ERR_NO_FILE
        ) {

            if ($_FILES['profile_photo']['error'] !== UPLOAD_ERR_OK) {

                $flashError =
                    'There was an error uploading your profile photo.';

            } elseif ($_FILES['profile_photo']['size'] > 2 * 1024 * 1024) {

                $flashError =
                    'Profile photo must be 2MB or smaller.';

            } else {

                $allowedTypes = [
                    'image/jpeg',
                    'image/png',
                    'image/webp'
                ];

                $fileType = mime_content_type(
                    $_FILES['profile_photo']['tmp_name']
                );

                if (!in_array($fileType, $allowedTypes, true)) {

                    $flashError =
                        'Only JPG, PNG, and WEBP images are allowed.';

                } else {

                    $uploadDir =
                        'assets/profiles/';

                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    $extension = strtolower(
                        pathinfo(
                            $_FILES['profile_photo']['name'],
                            PATHINFO_EXTENSION
                        )
                    );

                    $fileName =
                        'profile_' .
                        $userId .
                        '_' .
                        time() .
                        '.' .
                        $extension;

                    $targetPath =
                        $uploadDir . $fileName;

                    if (
                        move_uploaded_file(
                            $_FILES['profile_photo']['tmp_name'],
                            $targetPath
                        )
                    ) {

                        /* Delete previous profile photo */

                        if (!empty($user['profile_photo'])) {

                            $oldFile =
                                'assets/profiles/' .
                                basename($user['profile_photo']);

                            if (file_exists($oldFile)) {
                                unlink($oldFile);
                            }
                        }

                        $newProfilePhoto = $fileName;

                    } else {

                        $flashError =
                            'Unable to save the profile photo.';
                    }
                }
            }
        }


        if ($flashError === '') {

            $stmt = $conn->prepare("
                UPDATE users
                SET name = ?, email = ?, profile_photo = ?
                WHERE id = ?
            ");

            $stmt->bind_param(
                "sssi",
                $name,
                $email,
                $newProfilePhoto,
                $userId
            );

            if ($stmt->execute()) {

                $_SESSION['user_name']  = $name;
                $_SESSION['user_email'] = $email;

                $user['name'] = $name;
                $user['email'] = $email;
                $user['profile_photo'] = $newProfilePhoto;

                $flashSuccess =
                    'Account updated successfully.';

            } else {

                $flashError =
                    'Unable to update your account.';
            }

            $stmt->close();
        }
    }
}


$profilePhotoPath = '';

if (!empty($user['profile_photo'])) {

    $profilePhotoPath =
        'assets/profiles/' .
        basename($user['profile_photo']);
}

$orders = [];

$stmt = $conn->prepare("
    SELECT
        id,
        customer_name,
        email,
        phone,
        address,
        city,
        postal_code,
        payment_method,
        total_amount,
        status,
        created_at
    FROM orders
    WHERE user_id = ?
    ORDER BY created_at DESC
");

$stmt->bind_param("i", $userId);
$stmt->execute();

$result = $stmt->get_result();

if ($result) {

    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
}

$stmt->close();


function getUserOrderItems($conn, $orderId)
{
    $items = [];

    $stmt = $conn->prepare("
        SELECT
            oi.product_name,
            oi.product_price,
            oi.quantity,
            oi.subtotal,
            p.image
        FROM order_items oi
        LEFT JOIN products p
            ON oi.product_name = p.name
        WHERE oi.order_id = ?
    ");

    $stmt->bind_param("i", $orderId);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result) {

        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
    }

    $stmt->close();

    return $items;
}


$totalOrders = count($orders);
$totalSpent = 0;
$pendingOrders = 0;
$processingOrders = 0;
$shippedOrders = 0;
$deliveredOrders = 0;
$lastOrderDate = null;

foreach ($orders as $order) {

    $totalSpent += (float) $order['total_amount'];

    switch (strtolower($order['status'])) {

        case 'pending':
            $pendingOrders++;
            break;

        case 'processing':
            $processingOrders++;
            break;

        case 'shipped':
            $shippedOrders++;
            break;

        case 'delivered':
            $deliveredOrders++;
            break;
    }

    if ($lastOrderDate === null) {
        $lastOrderDate = $order['created_at'];
    }
}

function e($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}


function statusClass($status)
{
    switch (strtolower($status)) {

        case 'delivered':
            return 'status-delivered';

        case 'shipped':
            return 'status-shipped';

        case 'processing':
            return 'status-processing';

        default:
            return 'status-pending';
    }
}


function statusNumber($status)
{
    switch (strtolower($status)) {

        case 'pending':
            return 1;

        case 'processing':
            return 2;

        case 'shipped':
            return 3;

        case 'delivered':
            return 4;

        default:
            return 1;
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

    <title>My Account - Abella Apparel</title>

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="style.css?v=<?php echo time(); ?>"
    >


<style>

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

html {
    scroll-behavior: smooth;
    background: #000;
}

body {
    background: #000;
    color: #fff;
    font-family: Arial, Helvetica, sans-serif;
    overflow-x: hidden;
}

a {
    color: inherit;
    text-decoration: none;
}
.orders-heading h2 {
    color: #000;
}

.orders-heading h2 span {
    color: #c49d4c;
}
button,
input {
    font-family: inherit;
}


.account-hero {
    position: relative;
    min-height: 410px;
    display: flex;
    align-items: center;
    padding: 80px 7%;

    background:
        linear-gradient(
            90deg,
            rgba(0,0,0,.96) 0%,
            rgba(0,0,0,.80) 45%,
            rgba(0,0,0,.45) 100%
        ),
        url('assets/editorial.png')
        center / cover no-repeat;

    border-bottom: 1px solid #1d1d1d;
}

.account-hero-content {
    max-width: 850px;
}

.account-small {
    margin-bottom: 18px;
    color: #c49d4c;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 3px;
}

.account-hero h1 {
    font-size: clamp(48px, 7vw, 82px);
    line-height: .92;
    font-weight: 800;
    letter-spacing: -3px;
    text-transform: uppercase;
}

.account-hero h1 span {
    color: #c49d4c;
}

.account-line {
    width: 65px;
    height: 1px;
    margin: 28px 0;
    background: #c49d4c;
}

.account-hero p {
    max-width: 560px;
    color: #888;
    font-size: 13px;
    line-height: 1.8;
}

.account-main {
    width: 100%;
    max-width: 1250px;
    margin: auto;
    padding: 85px 35px;
}

.profile-section {
    display: grid;
    grid-template-columns: 340px 1fr;
    gap: 25px;
    margin-bottom: 70px;
}


.profile-card {
    position: relative;
    background: #111;
    border: 1px solid #292929;
    padding: 35px;
    min-height: 380px;
    overflow: hidden;
    text-align: center;

    display: flex;
    flex-direction: column;
    align-items: center;
}

.profile-card::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 2px;
    background: #c49d4c;
}

.profile-label {
    color: #c49d4c;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 2px;
    margin-bottom: 25px;
}

.profile-photo-wrapper {
    width: 105px;
    height: 105px;
    margin-bottom: 22px;
    padding: 3px;
    border: 1px solid #c49d4c;
    border-radius: 50%;
}

.profile-photo,
.profile-placeholder {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
    background: #181818;
}

.profile-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;

    color: #c49d4c;

    font-family: "Playfair Display", serif;
    font-size: 38px;
}

.profile-name {
    font-size: 23px;
    font-weight: 700;
    text-transform: uppercase;
    margin-bottom: 7px;
    text-align: center;
}

.profile-email {
    color: #777;
    font-size: 12px;
    margin-bottom: 12px;
    word-break: break-word;
    text-align: center;
}

.newsletter-badge {
    display: inline-block;
    margin: 0 auto 24px;
    padding: 6px 14px;
    border: 1px solid #333;
    color: #888;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.5px;
    text-transform: uppercase;
}

.newsletter-badge.subscribed {
    border-color: #c49d4c;
    color: #c49d4c;
}

.edit-profile-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;

    padding: 12px 19px;

    border: 1px solid #444;
    background: transparent;
    color: #fff;

    font-size: 10px;
    font-weight: 700;
    letter-spacing: 1.2px;

    cursor: pointer;

    transition:
        background .25s ease,
        border-color .25s ease,
        color .25s ease;
}

.edit-profile-btn:hover {
    background: #c49d4c;
    border-color: #c49d4c;
    color: #000;
}

.overview {
    background: #111;
    border: 1px solid #292929;
    padding: 35px;
}

.section-label {
    color: #c49d4c;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 2px;
    margin-bottom: 15px;
}

.overview h2 {
    font-size: 32px;
    font-weight: 800;
    text-transform: uppercase;
    line-height: 1;
}

.overview h2 span {
    color: #c49d4c;
}

.gold-line {
    width: 55px;
    height: 1px;
    background: #c49d4c;
    margin: 22px 0;
}

.overview-text {
    color: #888;
    font-size: 13px;
    line-height: 1.8;
    max-width: 650px;
}

.quick-links {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
    margin-top: 35px;
}

.quick-link {
    display: flex;
    align-items: center;
    justify-content: space-between;

    padding: 17px 18px;

    background: #181818;
    border: 1px solid #292929;

    color: #ddd;

    font-size: 10px;
    font-weight: 700;
    letter-spacing: 1px;

    transition:
        border-color .25s ease,
        color .25s ease,
        background .25s ease;
}

.quick-link span:last-child {
    color: #c49d4c;
    font-size: 15px;
}

.quick-link:hover {
    background: #1c1c1c;
    border-color: #c49d4c;
    color: #c49d4c;
}

.flash-message {
    margin-bottom: 25px;
    padding: 15px 18px;
    border: 1px solid;
    font-size: 12px;
}

.flash-success {
    background: #102718;
    border-color: #315f3b;
    color: #7edb8a;
}

.flash-error {
    background: #291414;
    border-color: #5c2828;
    color: #ff7777;
}

.account-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 14px;
    margin-bottom: 70px;
}

.account-stat {
    background: #111;
    border: 1px solid #292929;
    padding: 27px;
    min-height: 120px;

    transition:
        border-color .25s ease;
}

.account-stat:hover {
    border-color: #4b3b20;
}

.stat-title {
    color: #777;
    font-size: 9px;
    font-weight: 700;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    margin-bottom: 15px;
}

.stat-number {
    color: #fff;
    font-size: 28px;
    font-weight: 700;
}

.stat-number.gold {
    color: #c49d4c;
}

.orders-heading {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;

    gap: 20px;
    margin-bottom: 28px;
}

.orders-heading h2 {
    font-size: 35px;
    line-height: 1;
    font-weight: 800;
    text-transform: uppercase;
}

.orders-heading h2 span {
    color: #c49d4c;
}

.orders-count {
    color: #777;
    font-size: 10px;
    letter-spacing: 1px;
}

.orders-container {
    display: flex;
    flex-direction: column;
    gap: 20px;
}


.order-card {
    background: #111;
    border: 1px solid #292929;
    overflow: hidden;
}

.order-top {
    display: flex;
    align-items: center;
    justify-content: space-between;

    gap: 20px;

    padding: 20px 23px;

    background: #151515;
    border-bottom: 1px solid #292929;
}

.order-number {
    color: #fff;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: .8px;
}

.order-date {
    margin-top: 5px;
    color: #777;
    font-size: 10px;
}


.order-status {
    display: inline-flex;
    align-items: center;

    padding: 7px 11px;

    border: 1px solid;

    font-size: 9px;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;
}

.status-pending {
    color: #c49d4c;
    border-color: #5b461e;
    background: #241d10;
}

.status-processing {
    color: #c49d4c;
    border-color: #5b461e;
    background: #241d10;
}

.status-shipped {
    color: #d6b86e;
    border-color: #665323;
    background: #282211;
}

.status-delivered {
    color: #7edb8a;
    border-color: #315f3b;
    background: #102718;
}


.order-progress {
    padding: 23px;
    border-bottom: 1px solid #292929;
}

.progress-line {
    position: relative;

    display: grid;
    grid-template-columns: repeat(4, 1fr);

    gap: 10px;
}

.progress-line::before {
    content: "";

    position: absolute;

    top: 7px;
    left: 8%;
    right: 8%;

    height: 1px;

    background: #333;
}

.progress-step {
    position: relative;
    text-align: center;
    z-index: 1;
}

.progress-dot {
    width: 15px;
    height: 15px;

    margin: 0 auto 9px;

    border: 1px solid #444;
    background: #111;

    border-radius: 50%;
}

.progress-step.active .progress-dot {
    border-color: #c49d4c;
    background: #c49d4c;
}

.progress-step.current .progress-dot {
    box-shadow: 0 0 0 4px rgba(196,157,76,.10);
}

.progress-label {
    color: #555;
    font-size: 8px;
    font-weight: 600;
    letter-spacing: .7px;
    text-transform: uppercase;
}

.progress-step.active .progress-label {
    color: #c49d4c;
}


.order-details {
    display: grid;
    grid-template-columns: repeat(3, 1fr);

    gap: 20px;

    padding: 22px;

    border-bottom: 1px solid #292929;
}

.detail-title {
    color: #666;
    font-size: 8px;
    font-weight: 700;
    letter-spacing: 1.3px;
    margin-bottom: 7px;
}

.detail-value {
    color: #ddd;
    font-size: 11px;
    line-height: 1.5;
    word-break: break-word;
}


.products-section {
    padding: 22px;
    border-bottom: 1px solid #292929;
}

.products-title {
    color: #c49d4c;
    font-size: 9px;
    font-weight: 700;
    letter-spacing: 1.5px;
    margin-bottom: 15px;
}

.product-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.product-item {
    display: flex;
    align-items: center;

    gap: 15px;

    padding: 12px;

    background: #181818;
    border: 1px solid #292929;
}

.product-image {
    width: 65px;
    height: 65px;

    flex-shrink: 0;

    object-fit: cover;

    background: #222;
    border: 1px solid #333;
}

.product-no-image {
    width: 65px;
    height: 65px;

    flex-shrink: 0;

    display: flex;
    align-items: center;
    justify-content: center;

    background: #222;
    border: 1px solid #333;

    color: #555;
    font-size: 8px;
}

.product-info {
    flex: 1;
    min-width: 0;
}

.product-name {
    color: #fff;
    font-size: 12px;
    font-weight: 700;
    margin-bottom: 6px;
}

.product-meta {
    color: #777;
    font-size: 10px;
}

.product-subtotal {
    color: #c49d4c;
    font-size: 12px;
    font-weight: 700;
    white-space: nowrap;
}

.order-bottom {
    display: flex;
    align-items: center;
    justify-content: space-between;

    gap: 20px;

    padding: 21px 23px;
}

.payment-info {
    color: #777;
    font-size: 10px;
}

.payment-info strong {
    color: #ddd;
    font-weight: 500;
}

.order-total {
    color: #fff;
    font-size: 18px;
    font-weight: 700;
}

.order-total span {
    color: #c49d4c;
}


.empty-orders {
    padding: 75px 25px;

    background: #111;
    border: 1px solid #292929;

    text-align: center;
}

.empty-mark {
    color: #c49d4c;

    font-family: "Playfair Display", serif;

    font-size: 42px;

    margin-bottom: 15px;
}

.empty-orders h3 {
    font-size: 20px;
    text-transform: uppercase;
    margin-bottom: 10px;
}

.empty-orders p {
    color: #777;
    font-size: 12px;
    margin-bottom: 25px;
}

.shop-btn {
    display: inline-flex;

    padding: 13px 22px;

    background: #c49d4c;
    border: 1px solid #c49d4c;

    color: #000;

    font-size: 10px;
    font-weight: 800;
    letter-spacing: 1px;

    transition:
        background .25s ease,
        color .25s ease;
}

.shop-btn:hover {
    background: transparent;
    color: #c49d4c;
}


.account-banner {
    padding: 85px 25px;

    background: #111;

    border-top: 1px solid #1e1e1e;
    border-bottom: 1px solid #1e1e1e;

    text-align: center;
}

.account-banner-inner {
    max-width: 800px;
    margin: auto;
}

.account-banner .section-label {
    margin-bottom: 17px;
}

.account-banner h2 {
    font-size: 36px;
    font-weight: 800;
    margin-bottom: 15px;
}

.account-banner h2 span {
    color: #c49d4c;
}

.account-banner p {
    max-width: 650px;

    margin: auto;

    color: #777;

    font-size: 12px;
    line-height: 1.8;
}

.footer {
    background: #000;
    border-top: 1px solid #1d1d1d;
}

.footer-main {
    width: 100%;
    max-width: 1250px;

    margin: auto;

    padding: 65px 35px;

    display: grid;

    grid-template-columns: 2fr repeat(4, 1fr);

    gap: 45px;
}

.footer-brand img {
    width: 145px;
    margin-bottom: 18px;
}

.footer-brand p {
    color: #777;
    font-size: 11px;
    line-height: 1.8;
}

.social {
    display: flex;
    gap: 10px;
    margin-top: 22px;
}

.social-icon {
    width: 31px;
    height: 31px;

    display: flex;
    align-items: center;
    justify-content: center;

    border: 1px solid #333;

    color: #888;

    transition:
        border-color .25s ease,
        color .25s ease;
}

.social-icon:hover {
    border-color: #c49d4c;
    color: #c49d4c;
}

.social-icon svg {
    width: 14px;
    height: 14px;
    fill: currentColor;
}

.footer-column h4 {
    color: #c49d4c;

    font-size: 9px;
    font-weight: 700;
    letter-spacing: 1.5px;

    margin-bottom: 20px;
}

.footer-column a {
    display: block;

    color: #777;

    font-size: 10px;

    margin-bottom: 12px;

    transition: color .25s ease;
}

.footer-column a:hover {
    color: #fff;
}

.footer-bottom {
    border-top: 1px solid #1d1d1d;

    padding: 20px 35px;

    text-align: center;

    color: #555;

    font-size: 9px;
    letter-spacing: .7px;
}


.modal-overlay {
    position: fixed;
    inset: 0;

    display: none;

    align-items: center;
    justify-content: center;

    padding: 25px;

    background: rgba(0,0,0,.86);

    z-index: 9999;
}

.modal-overlay.show {
    display: flex;
}

.account-modal {
    width: 100%;
    max-width: 500px;

    background: #111;

    border: 1px solid #333;

    box-shadow: 0 25px 70px rgba(0,0,0,.7);
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;

    padding: 21px 24px;

    border-bottom: 1px solid #292929;
}

.modal-header h2 {
    font-size: 17px;
    text-transform: uppercase;
}

.modal-close {
    width: 30px;
    height: 30px;

    display: flex;
    align-items: center;
    justify-content: center;

    background: transparent;

    border: 1px solid #333;

    color: #aaa;

    cursor: pointer;

    font-size: 16px;

    transition:
        border-color .2s ease,
        color .2s ease;
}

.modal-close:hover {
    border-color: #c49d4c;
    color: #c49d4c;
}

.modal-body {
    padding: 27px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;

    color: #888;

    font-size: 9px;
    font-weight: 700;
    letter-spacing: 1.2px;

    margin-bottom: 8px;
}

.form-group input {
    width: 100%;
    height: 44px;

    padding: 0 13px;

    background: #080808;

    border: 1px solid #333;

    color: #fff;

    font-size: 12px;

    outline: none;

    transition: border-color .2s ease;
}

.form-group input:focus {
    border-color: #c49d4c;
}

.form-group small {
    display: block;

    color: #555;

    font-size: 9px;

    margin-top: 7px;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;

    gap: 10px;

    padding: 20px 27px;

    border-top: 1px solid #292929;
}

.cancel-btn,
.save-btn {
    padding: 12px 20px;

    font-size: 10px;
    font-weight: 700;
    letter-spacing: 1px;

    cursor: pointer;
}

.cancel-btn {
    background: transparent;

    border: 1px solid #333;

    color: #aaa;
}

.cancel-btn:hover {
    color: #fff;
    border-color: #555;
}

.save-btn {
    background: #c49d4c;

    border: 1px solid #c49d4c;

    color: #000;
}

.save-btn:hover {
    background: #fff;
    border-color: #fff;
}


@media (max-width: 1000px) {

    .profile-section {
        grid-template-columns: 1fr;
    }

    .profile-card {
        min-height: auto;
    }

    .footer-main {
        grid-template-columns: repeat(2, 1fr);
    }

    .footer-brand {
        grid-column: 1 / -1;
    }
}


@media (max-width: 760px) {

    .account-hero {
        min-height: 360px;
        padding: 70px 25px;
    }

    .account-hero h1 {
        font-size: 48px;
        letter-spacing: -2px;
    }

    .account-main {
        padding: 60px 20px;
    }

    .account-stats {
        grid-template-columns: 1fr;
    }

    .quick-links {
        grid-template-columns: 1fr;
    }

    .orders-heading {
        align-items: flex-start;
        flex-direction: column;
    }

    .order-top {
        align-items: flex-start;
        flex-direction: column;
    }

    .order-details {
        grid-template-columns: 1fr 1fr;
    }

    .order-bottom {
        align-items: flex-start;
        flex-direction: column;
    }

    .account-banner {
        padding: 65px 20px;
    }

    .account-banner h2 {
        font-size: 29px;
    }

    .footer-main {
        grid-template-columns: 1fr 1fr;
        padding: 50px 20px;
    }
}


@media (max-width: 500px) {

    .account-hero {
        min-height: 330px;
    }

    .account-hero h1 {
        font-size: 40px;
    }

    .account-main {
        padding: 50px 15px;
    }

    .profile-card,
    .overview {
        padding: 25px;
    }

    .overview h2 {
        font-size: 27px;
    }

    .order-details {
        grid-template-columns: 1fr;
    }

    .product-item {
        align-items: flex-start;
    }

    .product-subtotal {
        margin-left: auto;
    }

    .progress-label {
        font-size: 7px;
    }

    .footer-main {
        grid-template-columns: 1fr;
        gap: 30px;
    }

    .footer-brand {
        grid-column: auto;
    }

    .modal-overlay {
        padding: 15px;
    }

    .modal-body {
        padding: 22px;
    }

    .modal-footer {
        padding: 18px 22px;
    }
}

</style>

</head>


<body>

<div class="site">


<header class="header">

    <div class="header-inner">

        <!-- LOGO -->

        <a href="index.php">

            <img
                class="logo"
                src="assets/header-logo.png"
                alt="Abella Apparel"
            >

        </a>


        <!-- NAVIGATION -->

        <nav class="nav">

            <a href="index.php">
                HOME
            </a>

            <a href="shop.php">
                SHOP
            </a>

            <a href="hoodies.php">
                HOODIES
            </a>

            <a href="tshirts.php">
                T-SHIRTS
            </a>

            <a href="about.php">
                ABOUT
            </a>

            <a href="contact.php">
                CONTACT
            </a>

        </nav>


        <!-- ICONS -->

        <div class="icons">

            <!-- SEARCH -->

            <a
                href="search.php"
                class="icon"
                aria-label="Search"
            >

                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                >

                    <circle
                        cx="11"
                        cy="11"
                        r="7"
                    ></circle>

                    <line
                        x1="21"
                        y1="21"
                        x2="16.65"
                        y2="16.65"
                    ></line>

                </svg>

            </a>


            <!-- ACCOUNT -->

            <a
                href="user_page.php"
                class="icon"
                aria-label="Account"
            >

                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.5"
                >

                    <circle
                        cx="12"
                        cy="8"
                        r="4"
                    ></circle>

                    <path
                        d="M4 21c0-4.4 3.6-7 8-7s8 2.6 8 7"
                    ></path>

                </svg>

            </a>


            <!-- CART -->

            <a
                href="cart.php"
                class="icon cart-icon"
                aria-label="Cart"
            >

                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                >

                    <path
                        d="M3 4h2l2.4 12.2a2 2 0 0 0 2 1.8h7.2a2 2 0 0 0 2-1.6L21 8H6"
                    ></path>

                    <circle
                        cx="10"
                        cy="21"
                        r="1.3"
                        fill="currentColor"
                        stroke="none"
                    ></circle>

                    <circle
                        cx="17"
                        cy="21"
                        r="1.3"
                        fill="currentColor"
                        stroke="none"
                    ></circle>

                </svg>


                <?php if ($cartCount > 0): ?>

                    <span class="cart-count">
                        <?= $cartCount ?>
                    </span>

                <?php endif; ?>

            </a>

        </div>

    </div>

</header>


<section class="account-hero">

    <div class="account-hero-content">

        <div class="account-small">
            ABELLA APPAREL
        </div>

        <h1>
            MY<br>
            <span>ACCOUNT.</span>
        </h1>

        <div class="account-line"></div>

        <p>
            Manage your profile, view your orders,
            and keep track of your Abella Apparel journey.
        </p>

    </div>

</section>


<main class="account-main">


<?php if ($flashSuccess !== ''): ?>

    <div class="flash-message flash-success">
        <?= e($flashSuccess) ?>
    </div>

<?php endif; ?>


<?php if ($flashError !== ''): ?>

    <div class="flash-message flash-error">
        <?= e($flashError) ?>
    </div>

<?php endif; ?>

<section class="profile-section">


    <!-- PROFILE CARD -->

    <div class="profile-card">

        <div class="profile-label">
            YOUR PROFILE
        </div>


        <div class="profile-photo-wrapper">

            <?php if ($profilePhotoPath !== ''): ?>

                <img
                    class="profile-photo"
                    src="<?= e($profilePhotoPath) ?>"
                    alt="Profile Photo"
                    onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                >

                <div
                    class="profile-placeholder"
                    style="display:none;"
                >
                    <?= e(strtoupper(substr($user['name'], 0, 1))) ?>
                </div>

            <?php else: ?>

                <div class="profile-placeholder">

                    <?= e(
                        strtoupper(
                            substr(
                                $user['name'] !== ''
                                    ? $user['name']
                                    : 'A',
                                0,
                                1
                            )
                        )
                    ) ?>

                </div>

            <?php endif; ?>

        </div>


        <div class="profile-name">
            <?= e($user['name']) ?>
        </div>

        <div class="profile-email">
            <?= e($user['email']) ?>
        </div>


        <?php if ($isSubscribed): ?>

            <div class="newsletter-badge subscribed">
                ✓ Subscribed to @abellaapparel
            </div>

        <?php else: ?>

            <div class="newsletter-badge">
                Not Subscribed to @abellaapparel
            </div>

        <?php endif; ?>


        <button
            type="button"
            class="edit-profile-btn"
            onclick="openAccountModal()"
        >
            EDIT PROFILE
        </button>

    </div>


    <!-- OVERVIEW -->

    <div class="overview">

        <div class="section-label">
            ACCOUNT OVERVIEW
        </div>

        <h2>
            WELCOME BACK,<br>
            <span><?= e(strtoupper($user['name'])) ?>.</span>
        </h2>

        <div class="gold-line"></div>

        <p class="overview-text">
            Thank you for being part of Abella Apparel.
            From your account you can manage your personal
            information and keep track of every order you place.
        </p>


        <div class="quick-links">

            <a
                href="shop.php"
                class="quick-link"
            >

                <span>
                    CONTINUE SHOPPING
                </span>

                <span>
                    →
                </span>

            </a>


            <a
                href="#orders"
                class="quick-link"
            >

                <span>
                    VIEW MY ORDERS
                </span>

                <span>
                    ↓
                </span>

            </a>


            <button
                type="button"
                class="quick-link"
                onclick="openAccountModal()"
                style="width:100%;text-align:left;cursor:pointer;"
            >

                <span>
                    EDIT ACCOUNT
                </span>

                <span>
                    →
                </span>

            </button>


            <a
                href="logout.php"
                class="quick-link"
            >

                <span>
                    LOG OUT
                </span>

                <span>
                    →
                </span>

            </a>

        </div>

    </div>

</section>

<section class="account-stats">


    <div class="account-stat">

        <div class="stat-title">
            Total Orders
        </div>

        <div class="stat-number">
            <?= $totalOrders ?>
        </div>

    </div>


    <div class="account-stat">

        <div class="stat-title">
            Total Spent
        </div>

        <div class="stat-number gold">
            ₱<?= number_format($totalSpent, 2) ?>
        </div>

    </div>


    <div class="account-stat">

        <div class="stat-title">
            Last Order
        </div>

        <div class="stat-number">

            <?php if ($lastOrderDate): ?>

                <?= date('M d, Y', strtotime($lastOrderDate)) ?>

            <?php else: ?>

                —

            <?php endif; ?>

        </div>

    </div>

</section>

<section id="orders">


    <div class="orders-heading">

        <div>

            <div class="section-label">
                YOUR PURCHASES
            </div>

            <h2>
                ORDER <span>HISTORY.</span>
            </h2>

        </div>


        <div class="orders-count">

            <?= $totalOrders ?>

            <?= $totalOrders === 1 ? 'ORDER' : 'ORDERS' ?>

        </div>

    </div>


<?php if (empty($orders)): ?>


    <div class="empty-orders">

        <div class="empty-mark">
            A
        </div>

        <h3>
            NO ORDERS YET
        </h3>

        <p>
            Your Abella Apparel journey starts here.
        </p>

        <a
            href="shop.php"
            class="shop-btn"
        >
            START SHOPPING
        </a>

    </div>


<?php else: ?>


    <div class="orders-container">


    <?php foreach ($orders as $order): ?>

        <?php

            $items = getUserOrderItems(
                $conn,
                (int) $order['id']
            );

            $currentStep = statusNumber(
                $order['status']
            );

        ?>


        <article class="order-card">


            <!-- ORDER TOP -->

            <div class="order-top">

                <div>

                    <div class="order-number">
                        ORDER #<?= e($order['id']) ?>
                    </div>

                    <div class="order-date">

                        <?= date(
                            'F d, Y • h:i A',
                            strtotime($order['created_at'])
                        ) ?>

                    </div>

                </div>


                <div
                    class="order-status <?= statusClass($order['status']) ?>"
                >
                    <?= e($order['status']) ?>
                </div>

            </div>


            <!-- PROGRESS -->

            <div class="order-progress">

                <div class="progress-line">


                    <div
                        class="progress-step
                        <?= $currentStep >= 1 ? 'active' : '' ?>
                        <?= $currentStep === 1 ? 'current' : '' ?>"
                    >

                        <div class="progress-dot"></div>

                        <div class="progress-label">
                            Pending
                        </div>

                    </div>


                    <div
                        class="progress-step
                        <?= $currentStep >= 2 ? 'active' : '' ?>
                        <?= $currentStep === 2 ? 'current' : '' ?>"
                    >

                        <div class="progress-dot"></div>

                        <div class="progress-label">
                            Processing
                        </div>

                    </div>


                    <div
                        class="progress-step
                        <?= $currentStep >= 3 ? 'active' : '' ?>
                        <?= $currentStep === 3 ? 'current' : '' ?>"
                    >

                        <div class="progress-dot"></div>

                        <div class="progress-label">
                            Shipped
                        </div>

                    </div>


                    <div
                        class="progress-step
                        <?= $currentStep >= 4 ? 'active' : '' ?>
                        <?= $currentStep === 4 ? 'current' : '' ?>"
                    >

                        <div class="progress-dot"></div>

                        <div class="progress-label">
                            Delivered
                        </div>

                    </div>


                </div>

            </div>


            <!-- CUSTOMER DETAILS -->

            <div class="order-details">


                <div>

                    <div class="detail-title">
                        CUSTOMER
                    </div>

                    <div class="detail-value">
                        <?= e($order['customer_name']) ?>
                    </div>

                </div>


                <div>

                    <div class="detail-title">
                        EMAIL
                    </div>

                    <div class="detail-value">
                        <?= e($order['email']) ?>
                    </div>

                </div>


                <div>

                    <div class="detail-title">
                        PHONE
                    </div>

                    <div class="detail-value">
                        <?= e($order['phone']) ?>
                    </div>

                </div>


                <div>

                    <div class="detail-title">
                        DELIVERY ADDRESS
                    </div>

                    <div class="detail-value">

                        <?= e($order['address']) ?>

                        <?php if (!empty($order['city'])): ?>

                            , <?= e($order['city']) ?>

                        <?php endif; ?>

                        <?php if (!empty($order['postal_code'])): ?>

                            <?= e($order['postal_code']) ?>

                        <?php endif; ?>

                    </div>

                </div>


                <div>

                    <div class="detail-title">
                        PAYMENT
                    </div>

                    <div class="detail-value">
                        <?= e($order['payment_method']) ?>
                    </div>

                </div>


                <div>

                    <div class="detail-title">
                        ORDER STATUS
                    </div>

                    <div class="detail-value">
                        <?= e($order['status']) ?>
                    </div>

                </div>


            </div>


            <!-- PRODUCTS -->

            <div class="products-section">

                <div class="products-title">
                    ITEMS IN THIS ORDER
                </div>


                <div class="product-list">


                <?php foreach ($items as $item): ?>


                    <div class="product-item">


                        <?php

                        $productImage = '';

                        if (!empty($item['image'])) {

                            $productImage =
                                'assets/' .
                                basename($item['image']);
                        }

                        ?>


                        <?php if ($productImage !== ''): ?>

                            <img
                                class="product-image"
                                src="<?= e($productImage) ?>"
                                alt="<?= e($item['product_name']) ?>"
                                onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                            >

                            <div
                                class="product-no-image"
                                style="display:none;"
                            >
                                NO IMAGE
                            </div>

                        <?php else: ?>

                            <div class="product-no-image">
                                NO IMAGE
                            </div>

                        <?php endif; ?>


                        <div class="product-info">

                            <div class="product-name">
                                <?= e($item['product_name']) ?>
                            </div>

                            <div class="product-meta">

                                Qty:
                                <?= (int) $item['quantity'] ?>

                                &nbsp; • &nbsp;

                                ₱<?= number_format(
                                    (float) $item['product_price'],
                                    2
                                ) ?>

                                each

                            </div>

                        </div>


                        <div class="product-subtotal">

                            ₱<?= number_format(
                                (float) $item['subtotal'],
                                2
                            ) ?>

                        </div>


                    </div>


                <?php endforeach; ?>


                </div>

            </div>


            <!-- ORDER BOTTOM -->

            <div class="order-bottom">

                <div class="payment-info">

                    Paid via

                    <strong>
                        <?= e($order['payment_method']) ?>
                    </strong>

                </div>


                <div class="order-total">

                    TOTAL:

                    <span>

                        ₱<?= number_format(
                            (float) $order['total_amount'],
                            2
                        ) ?>

                    </span>

                </div>

            </div>


        </article>


    <?php endforeach; ?>


    </div>


<?php endif; ?>


</section>


</main>


<section class="account-banner">

    <div class="account-banner-inner">

        <div class="section-label">
            ABELLA APPAREL
        </div>

        <h2>
            WEAR YOUR <span>IDENTITY.</span>
        </h2>

        <p>
            Thank you for being part of the Abella Apparel
            journey. We create with passion and design
            for those who are not afraid to stand out.
        </p>

    </div>

</section>

<footer class="footer">

    <div class="footer-main">


        <!-- BRAND -->

        <div class="footer-brand">

            <img
                src="assets/footer.png"
                alt="Abella Apparel"
            >

            <p>
                Premium streetwear inspired by<br>
                passion, designed for the culture.
            </p>


            <div class="social">


                <!-- FACEBOOK -->

                <a
                    class="social-icon"
                    href="#"
                    aria-label="Facebook"
                >

                    <svg viewBox="0 0 24 24">

                        <path
                            d="M15 8.5h2V5.3c-.35-.05-1.5-.15-2.85-.15-2.8 0-4.7 1.7-4.7 4.85v2.5H6.5V16h2.95v8h3.4v-8h2.85l.45-3.5h-3.3V10c0-1 .3-1.5 1.65-1.5z"
                        ></path>

                    </svg>

                </a>


                <!-- INSTAGRAM -->

                <a
                    class="social-icon"
                    href="#"
                    aria-label="Instagram"
                >

                    <svg viewBox="0 0 24 24">

                        <rect
                            x="3"
                            y="3"
                            width="18"
                            height="18"
                            rx="5"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.7"
                        ></rect>

                        <circle
                            cx="12"
                            cy="12"
                            r="4"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.7"
                        ></circle>

                        <circle
                            cx="17.5"
                            cy="6.5"
                            r="1"
                            fill="currentColor"
                        ></circle>

                    </svg>

                </a>


                <!-- TIKTOK -->

                <a
                    class="social-icon"
                    href="#"
                    aria-label="TikTok"
                >

                    <svg viewBox="0 0 24 24">

                        <path
                            d="M15 4c.5 2.5 2 4 4.5 4.2v3.1c-1.8-.1-3.3-.6-4.5-1.5v6.2c0 3.8-2.4 5.8-5.5 5.8-3.1 0-5.5-2.2-5.5-5.4 0-3.4 2.6-5.7 6.1-5.5v3.1c-1.6-.1-2.8.8-2.8 2.3 0 1.4 1 2.4 2.3 2.4 1.5 0 2.5-.9 2.5-3V4H15z"
                        ></path>

                    </svg>

                </a>


            </div>

        </div>


        <!-- SHOP -->

        <div class="footer-column">

            <h4>
                SHOP
            </h4>

            <a href="shop.php">
                ALL PRODUCTS
            </a>

            <a href="hoodies.php">
                HOODIES
            </a>

            <a href="tshirts.php">
                T-SHIRTS
            </a>

        </div>


        <!-- COMPANY -->

        <div class="footer-column">

            <h4>
                COMPANY
            </h4>

            <a href="about.php">
                ABOUT US
            </a>

            <a href="contact.php">
                CONTACT
            </a>

            <a href="user_page.php">
                MY ACCOUNT
            </a>

        </div>


        <!-- HELP -->

        <div class="footer-column">

            <h4>
                HELP
            </h4>

            <a href="contact.php">
                CUSTOMER SERVICE
            </a>

            <a href="contact.php">
                SHIPPING
            </a>

            <a href="contact.php">
                RETURNS
            </a>

        </div>


        <!-- LEGAL -->

        <div class="footer-column">

            <h4>
                LEGAL
            </h4>

            <a href="#">
                PRIVACY POLICY
            </a>

            <a href="#">
                TERMS & CONDITIONS
            </a>

        </div>


    </div>


    <div class="footer-bottom">

        © <?= date('Y') ?> ABELLA APPAREL.
        ALL RIGHTS RESERVED.

    </div>

</footer>


<div
    class="modal-overlay"
    id="accountModal"
    onclick="closeAccountModal(event)"
>

    <div
        class="account-modal"
        onclick="event.stopPropagation()"
    >


        <div class="modal-header">

            <h2>
                Edit Profile
            </h2>

            <button
                type="button"
                class="modal-close"
                onclick="closeAccountModal()"
            >
                ×
            </button>

        </div>


        <form
            method="POST"
            enctype="multipart/form-data"
        >


            <div class="modal-body">


                <div class="form-group">

                    <label for="name">
                        FULL NAME
                    </label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="<?= e($user['name']) ?>"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="email">
                        EMAIL ADDRESS
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?= e($user['email']) ?>"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="profile_photo">
                        PROFILE PHOTO
                    </label>

                    <input
                        type="file"
                        id="profile_photo"
                        name="profile_photo"
                        accept=".jpg,.jpeg,.png,.webp"
                    >

                    <small>
                        JPG, PNG or WEBP • Maximum 2MB
                    </small>

                </div>


            </div>


            <div class="modal-footer">

                <button
                    type="button"
                    class="cancel-btn"
                    onclick="closeAccountModal()"
                >
                    CANCEL
                </button>

                <button
                    type="submit"
                    name="update_account"
                    class="save-btn"
                >
                    SAVE CHANGES
                </button>

            </div>


        </form>


    </div>

</div>


</div>


<script>

function openAccountModal() {

    const modal =
        document.getElementById('accountModal');

    if (!modal) return;

    modal.classList.add('show');

    document.body.style.overflow = 'hidden';
}


function closeAccountModal(event) {

    if (
        event &&
        event.target !== event.currentTarget
    ) {
        return;
    }

    const modal =
        document.getElementById('accountModal');

    if (!modal) return;

    modal.classList.remove('show');

    document.body.style.overflow = '';
}


document.addEventListener(
    'keydown',
    function(event) {

        if (event.key === 'Escape') {
            closeAccountModal();
        }

    }
);


setTimeout(function() {

    const success =
        document.querySelector('.flash-success');

    if (success) {

        success.style.transition =
            'opacity .35s ease';

        success.style.opacity = '0';

        setTimeout(function() {

            success.remove();

        }, 350);
    }

}, 3500);

</script>


</body>
</html>