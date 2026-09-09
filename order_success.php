<?php
session_start();
if (
    !isset($_SESSION['logged_in']) ||
    $_SESSION['logged_in'] !== true
) {
    header("Location: login.php");
    exit();
}
$orderId = $_SESSION['last_order_id'] ?? null;
if (!$orderId) {
    header("Location: index.php");
    exit();
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
    <title>Order Confirmed | Abella Apparel</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f8f8e9;
            color: #111;
        }
        .success-header {
            height: 92px;
            background: #000;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .success-logo img {
            width: 150px;
            display: block;
        }
        .success-container {
            min-height: calc(100vh - 92px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 50px 20px;
        }
        .success-box {
            width: 100%;
            max-width: 650px;
            background: #fff;
            border: 1px solid #ddd;
            padding: 55px 45px;
            text-align: center;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.05);
        }
        .success-icon {
            width: 75px;
            height: 75px;
            margin: 0 auto 25px;
            border-radius: 50%;
            background: #c49d4c;
            color: #000;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 38px;
            font-weight: bold;
        }
        .success-box h1 {
            font-size: 32px;
            letter-spacing: 2px;
            margin-bottom: 15px;
        }
        .success-message {
            color: #666;
            font-size: 15px;
            line-height: 1.7;
            margin-bottom: 25px;
        }
        .order-number {
            background: #f5f5e8;
            border: 1px solid #ddd;
            padding: 18px;
            margin: 25px 0;
        }
        .order-number span {
            display: block;
            font-size: 11px;
            letter-spacing: 1.5px;
            color: #777;
            margin-bottom: 8px;
            text-transform: uppercase;
        }
        .order-number strong {
            font-size: 22px;
            letter-spacing: 1px;
        }
        .payment-note {
            font-size: 13px;
            color: #555;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        .payment-note strong {
            color: #111;
        }
        .success-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        .button {
            display: inline-block;
            text-decoration: none;
            padding: 15px 25px;
            font-size: 12px;
            font-weight: bold;
            letter-spacing: 1px;
            transition: 0.2s;
        }
        .shop-button {
            background: #000;
            color: #fff;
        }
        .shop-button:hover {
            background: #c49d4c;
            color: #000;
        }
        .account-button {
            background: #c49d4c;
            color: #000;
        }
        .account-button:hover {
            background: #000;
            color: #fff;
        }
        .footer-note {
            margin-top: 35px;
            font-size: 11px;
            color: #999;
            letter-spacing: 0.5px;
        }
        @media (max-width: 600px) {
            .success-box {
                padding: 40px 25px;
            }
            .success-box h1 {
                font-size: 26px;
            }
            .success-buttons {
                flex-direction: column;
            }
            .button {
                width: 100%;
            }
        }
    </style>
</head>
<body>
<header class="success-header">
    <a
        href="index.php"
        class="success-logo"
    >
        <img
            src="assets/header-logo.png"
            alt="Abella Apparel"
        >
    </a>
</header>
<main class="success-container">
    <div class="success-box">
        <div class="success-icon">
            ✓
        </div>
        <h1>
            ORDER CONFIRMED
        </h1>
        <p class="success-message">
            Thank you for shopping with
            <strong>Abella Apparel</strong>!
            <br>
            Your order has been successfully placed
            and is now being processed.
        </p>
        <div class="order-number">
            <span>
                Order Number
            </span>
            <strong>
                ABELLA-<?= date('Y') ?>-<?= str_pad($orderId, 4, '0', STR_PAD_LEFT) ?>
            </strong>
        </div>
        <p class="payment-note">
            Payment Method:
            <strong>Cash on Delivery</strong>
            <br>
            Please prepare the exact amount when
            your order arrives.
        </p>
        <div class="success-buttons">
            <a
                href="index.php"
                class="button shop-button"
            >
                CONTINUE SHOPPING
            </a>
            <a
                href="user_page.php"
                class="button account-button"
            >
                MY ACCOUNT
            </a>
        </div>
        <p class="footer-note">
            Thank you for choosing Abella Apparel.
        </p>
    </div>
</main>
</body>
</html>