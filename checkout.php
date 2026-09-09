<?php

session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$cart = $_SESSION['cart'] ?? [];

if (empty($cart)) {
    header("Location: cart.php");
    exit();
}

$total = 0;
$totalItems = 0;

foreach ($cart as $item) {

    $total += $item['price'] * $item['quantity'];

    $totalItems += $item['quantity'];
}


$userName = $_SESSION['user_name'] ?? '';
$userEmail = $_SESSION['user_email'] ?? '';

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Checkout | Abella Apparel</title>

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

        .checkout-header {
            background: #000;
            height: 92px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 6%;
        }

        .checkout-logo img {
            width: 150px;
            display: block;
        }

        .back-link {
            color: #fff;
            text-decoration: none;
            font-size: 13px;
            letter-spacing: 1px;
            font-weight: bold;
        }

        .back-link:hover {
            color: #c49d4c;
        }

        .checkout-container {
            max-width: 1180px;

            margin: 50px auto;
            padding: 0 25px;
        }

        .checkout-title {
            text-align: center;
            margin-bottom: 45px;
        }

        .checkout-title h1 {
            font-size: 36px;
            letter-spacing: 2px;
            margin-bottom: 10px;
        }

        .checkout-title p {
            color: #666;
            font-size: 14px;
        }

        .checkout-layout {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 35px;
            align-items: start;
        }

        .checkout-form {
            background: #fff;
            padding: 35px;
            border: 1px solid #ddd;
        }

        .section-title {
            font-size: 20px;
            letter-spacing: 1px;
            margin-bottom: 25px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group.full {
            width: 100%;
        }

        label {
            display: block;
            font-size: 12px;
            font-weight: bold;
            letter-spacing: 1px;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        input,
        textarea,
        select {
            width: 100%;
            padding: 14px;
            border: 1px solid #ccc;
            background: #fff;
            font-family: inherit;
            font-size: 14px;
            outline: none;
        }

        input:focus,
        textarea:focus,
        select:focus {
            border-color: #000;
        }

        textarea {
            height: 100px;
            resize: vertical;
        }

        .payment-box {
            margin-top: 10px;
        }

        .payment-option {
            border: 1px solid #ccc;
            padding: 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
        }

        .payment-option input {
            width: auto;
        }

        .payment-option span {
            font-size: 14px;
        }


        .order-summary {
            background: #000;
            color: #fff;
            padding: 30px;
            position: sticky;
            top: 25px;
        }

        .order-summary h2 {
            font-size: 20px;
            letter-spacing: 1px;
            margin-bottom: 25px;
        }

        .order-item {
            display: grid;
            grid-template-columns: 65px 1fr auto;
            gap: 15px;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #333;
        }

        .order-item-image {
            width: 65px;
            height: 75px;
            background: #fff;
            overflow: hidden;
        }

        .order-item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .order-item-name {
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 6px;
        }

        .order-item-quantity {
            font-size: 12px;
            color: #aaa;
        }

        .order-item-price {
            font-size: 13px;
            white-space: nowrap;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            font-size: 14px;
        }

        .summary-row.shipping {
            border-bottom: 1px solid #333;
        }

        .summary-row.total {
            font-size: 19px;
            font-weight: bold;
            padding-top: 20px;
        }

        .summary-row.total span:last-child {
            color: #c49d4c;
        }


        .place-order-button {
            width: 100%;
            border: none;
            background: #c49d4c;
            color: #000;
            padding: 17px;
            margin-top: 25px;
            font-weight: bold;
            letter-spacing: 1px;
            cursor: pointer;
            font-size: 13px;
        }

        .place-order-button:hover {
            background: #fff;
        }

        .secure-note {
            text-align: center;
            color: #aaa;
            font-size: 11px;
            margin-top: 15px;
        }

        @media (max-width: 800px) {

            .checkout-header {
                padding: 0 20px;
            }

            .checkout-logo img {
                width: 120px;
            }

            .checkout-container {
                margin: 30px auto;
            }

            .checkout-title h1 {
                font-size: 28px;
            }

            .checkout-layout {
                grid-template-columns: 1fr;
            }

            .order-summary {
                position: static;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .checkout-form {
                padding: 25px;
            }

        }

    </style>

</head>

<body>

<header class="checkout-header">

    <a
        href="index.php"
        class="checkout-logo"
    >

        <img
            src="assets/header-logo.png"
            alt="Abella Apparel"
        >

    </a>


    <a
        href="cart.php"
        class="back-link"
    >
        ← BACK TO CART
    </a>

</header>

<main class="checkout-container">


    <div class="checkout-title">

        <h1>
            CHECKOUT
        </h1>

        <p>
            Complete your information to place your order.
        </p>

    </div>


    <div class="checkout-layout">

        <form
            class="checkout-form"
            action="place_order.php"
            method="POST"
        >

            <h2 class="section-title">
                CUSTOMER INFORMATION
            </h2>


            <div class="form-row">


                <div class="form-group">

                    <label for="name">
                        Full Name
                    </label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="<?= htmlspecialchars($userName) ?>"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="email">
                        Email Address
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?= htmlspecialchars($userEmail) ?>"
                        required
                    >

                </div>


            </div>


            <div class="form-group full">

                <label for="phone">
                    Phone Number
                </label>

                <input
                    type="tel"
                    id="phone"
                    name="phone"
                    placeholder="09XXXXXXXXX"
                    required
                >

            </div>


            <h2
                class="section-title"
                style="margin-top: 30px;"
            >
                DELIVERY ADDRESS
            </h2>


            <div class="form-group">

                <label for="address">
                    Complete Address
                </label>

                <textarea
                    id="address"
                    name="address"
                    placeholder="House number, street, barangay..."
                    required
                ></textarea>

            </div>


            <div class="form-row">


                <div class="form-group">

                    <label for="city">
                        City / Municipality
                    </label>

                    <input
                        type="text"
                        id="city"
                        name="city"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="postal_code">
                        Postal Code
                    </label>

                    <input
                        type="text"
                        id="postal_code"
                        name="postal_code"
                        required
                    >

                </div>


            </div>


            <h2
                class="section-title"
                style="margin-top: 15px;"
            >
                PAYMENT METHOD
            </h2>


            <div class="payment-box">

                <label class="payment-option">

                    <input
                        type="radio"
                        name="payment_method"
                        value="Cash on Delivery"
                        checked
                    >

                    <span>
                        Cash on Delivery
                    </span>

                </label>

            </div>


            <input
                type="hidden"
                name="total_amount"
                value="<?= $total ?>"
            >


            <button
                type="submit"
                class="place-order-button"
            >
                PLACE ORDER
            </button>

        </form>


        <div class="order-summary">


            <h2>
                YOUR ORDER
            </h2>


            <?php foreach ($cart as $item): ?>


                <?php


                $imagePath = $item['image'];

                if (
                    strpos($imagePath, 'assets/') !== 0 &&
                    strpos($imagePath, 'http://') !== 0 &&
                    strpos($imagePath, 'https://') !== 0
                ) {
                    $imagePath = 'assets/' . $imagePath;
                }

                ?>


                <div class="order-item">


                    <!-- PRODUCT PHOTO -->

                    <div class="order-item-image">

                        <img
                            src="<?= htmlspecialchars($imagePath) ?>"
                            alt="<?= htmlspecialchars($item['name']) ?>"
                        >

                    </div>


                    <div>

                        <div class="order-item-name">

                            <?= htmlspecialchars($item['name']) ?>

                        </div>


                        <div class="order-item-quantity">

                            Qty:
                            <?= $item['quantity'] ?>

                        </div>

                    </div>


                    <!-- PRICE -->

                    <div class="order-item-price">

                        ₱<?= number_format(
                            $item['price'] * $item['quantity'],
                            2
                        ) ?>

                    </div>


                </div>


            <?php endforeach; ?>


            <div style="margin-top: 20px;">


                <div class="summary-row">

                    <span>
                        Items
                    </span>

                    <span>
                        <?= $totalItems ?>
                    </span>

                </div>


                <div class="summary-row">

                    <span>
                        Subtotal
                    </span>

                    <span>
                        ₱<?= number_format($total, 2) ?>
                    </span>

                </div>


                <div class="summary-row shipping">

                    <span>
                        Shipping
                    </span>

                    <span>
                        FREE
                    </span>

                </div>


                <div class="summary-row total">

                    <span>
                        TOTAL
                    </span>

                    <span>
                        ₱<?= number_format($total, 2) ?>
                    </span>

                </div>


            </div>


            <div class="secure-note">

                Your order information is secure.

            </div>


        </div>


    </div>

</main>

</body>

</html>