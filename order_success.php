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
            background: #f3f1e7;
            color: #111;
        }
        .success-header {
            height: 82px;
            background: #000;
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 1px solid #242424;
        }
        .success-logo img {
            width: 142px;
            display: block;
        }
        .success-container {
            min-height: calc(100vh - 82px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 58px 20px;
        }
        .success-box {
            width: 100%;
            max-width: 720px;
            background: #fff;
            border: 1px solid #deddd5;
            border-top: 4px solid #c49d4c;
            padding: 50px 64px 42px;
            text-align: center;
            box-shadow: 0 18px 50px rgba(0, 0, 0, 0.08);
        }
        .success-icon {
            width: 72px;
            height: 72px;
            margin: 0 auto 22px;
            border-radius: 50%;
            background: #c49d4c;
            color: #000;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            font-weight: bold;
            box-shadow: 0 0 0 8px #f6f1e4;
        }
        .success-kicker {
            color: #9a7838;
            font-size: 10px;
            font-weight: bold;
            letter-spacing: 2.5px;
            margin-bottom: 12px;
        }
        .success-box h1 {
            font-size: 31px;
            letter-spacing: 3px;
            margin-bottom: 14px;
        }
        .success-message {
            color: #666;
            font-size: 14px;
            line-height: 1.8;
            margin: 0 auto 28px;
            max-width: 500px;
        }
        .order-number {
            background: #f7f5ec;
            border: 1px solid #e4dfcf;
            padding: 19px 18px;
            margin: 26px 0 18px;
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
            font-size: 21px;
            letter-spacing: 1.5px;
        }
        .confirmation-steps {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0;
            margin: 24px 0 28px;
            border-top: 1px solid #e8e5db;
            border-bottom: 1px solid #e8e5db;
            padding: 17px 0;
        }
        .confirmation-step {
            position: relative;
            color: #999;
            font-size: 9px;
            font-weight: bold;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .confirmation-step:not(:last-child)::after {
            content: '';
            position: absolute;
            right: 0;
            top: 2px;
            height: 24px;
            border-right: 1px solid #e1ddd1;
        }
        .confirmation-step.active {
            color: #9a7838;
        }
        .confirmation-step span {
            display: block;
            width: 8px;
            height: 8px;
            margin: 0 auto 8px;
            border-radius: 50%;
            background: #c49d4c;
        }
        .payment-note {
            font-size: 13px;
            color: #555;
            line-height: 1.6;
            margin-bottom: 31px;
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
            min-width: 178px;
            padding: 15px 22px;
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
            margin-top: 30px;
            font-size: 11px;
            color: #999;
            letter-spacing: 0.5px;
        }
        @media (max-width: 600px) {
            .success-box {
                padding: 42px 22px 34px;
            }
            .success-box h1 {
                font-size: 24px;
                letter-spacing: 2px;
            }
            .success-message {
                font-size: 13px;
            }
            .confirmation-steps {
                margin-left: -4px;
                margin-right: -4px;
            }
            .confirmation-step {
                font-size: 8px;
                letter-spacing: .6px;
            }
            .success-buttons {
                flex-direction: column;
                gap: 10px;
            }
            .button {
                width: 100%;
            }
            .order-number strong {
                font-size: 18px;
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
        <div class="success-kicker">
            THANK YOU FOR YOUR ORDER
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
        <div class="confirmation-steps" aria-label="Order progress">
            <div class="confirmation-step active">
                <span></span>
                Order placed
            </div>
            <div class="confirmation-step">
                <span></span>
                Processing
            </div>
            <div class="confirmation-step">
                <span></span>
                Delivery
            </div>
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