<?php

session_start();

require_once '../login_register/config.php';


if (isset($_POST['add_to_cart'])) {

    $productId = isset($_POST['product_id'])
        ? (int) $_POST['product_id']
        : 0;

    $productName = trim($_POST['product_name'] ?? '');

    
    $requestedQuantity = isset($_POST['quantity'])
        ? (int) $_POST['quantity']
        : 1;

    if ($requestedQuantity < 1) {
        $requestedQuantity = 1;
    }

    
    $buyNow = isset($_POST['buy_now'])
        && $_POST['buy_now'] == '1';

    if ($productId > 0) {

        $stmt = $conn->prepare("
            SELECT id, name, price, image, stock
            FROM products
            WHERE id = ?
            LIMIT 1
        ");

        $stmt->bind_param("i", $productId);

    } else {

        $stmt = $conn->prepare("
            SELECT id, name, price, image, stock
            FROM products
            WHERE name = ?
            LIMIT 1
        ");

        $stmt->bind_param("s", $productName);
    }


    $stmt->execute();

    $result = $stmt->get_result();

    $product = $result->fetch_assoc();

    $stmt->close();


    if (!$product) {

        $_SESSION['cart_message'] = "Product not found.";

        header("Location: cart.php");

        exit();
    }


    $stock = (int) $product['stock'];


    if ($stock <= 0) {

        $_SESSION['cart_message'] =
            $product['name'] . " is out of stock.";

        header("Location: cart.php");

        exit();
    }

    if ($requestedQuantity > $stock) {

        $_SESSION['cart_message'] =
            "Only "
            . $stock
            . " unit(s) of "
            . $product['name']
            . " are available.";

        header("Location: cart.php");

        exit();
    }

    if (!isset($_SESSION['cart'])) {

        $_SESSION['cart'] = [];
    }


    $found = false;

    $cartIndex = -1;


    foreach ($_SESSION['cart'] as $index => &$item) {

        if (
            isset($item['product_id'])
            &&
            (int)$item['product_id'] === (int)$product['id']
        ) {

            $currentQuantity = (int)$item['quantity'];

            $newQuantity =
                $currentQuantity + $requestedQuantity;

            if ($newQuantity > $stock) {

                $_SESSION['cart_message'] =
                    "You can only have up to "
                    . $stock
                    . " unit(s) of "
                    . $product['name']
                    . " in your cart.";

                $found = true;

                break;
            }


            $item['quantity'] = $newQuantity;

            $found = true;

            $cartIndex = $index;

            break;
        }
    }


    unset($item);

    if (!$found) {

        $_SESSION['cart'][] = [

            'product_id' => (int)$product['id'],

            'name' => $product['name'],

            'price' => (float)$product['price'],

            'image' => $product['image'],

            'quantity' => $requestedQuantity
        ];

        $cartIndex =
            count($_SESSION['cart']) - 1;
    }

    if (
        isset($_SESSION['cart_message'])
        &&
        $buyNow
    ) {

        header("Location: cart.php");

        exit();
    }

    if ($buyNow) {

        header("Location: checkout.php");

        exit();
    }


    header("Location: cart.php");

    exit();
}


if (isset($_POST['increase'])) {

    $index = (int) ($_POST['index'] ?? -1);


    if (isset($_SESSION['cart'][$index])) {

        $item = $_SESSION['cart'][$index];


        
        $stock = 0;


        if (isset($item['product_id'])) {

            $productId =
                (int)$item['product_id'];

            $stmt = $conn->prepare("
                SELECT stock
                FROM products
                WHERE id = ?
                LIMIT 1
            ");

            $stmt->bind_param(
                "i",
                $productId
            );

            $stmt->execute();

            $result =
                $stmt->get_result();

            $product =
                $result->fetch_assoc();

            $stmt->close();


            if ($product) {

                $stock =
                    (int)$product['stock'];
            }
        }



        if (
            $stock > 0
            &&
            (int)$_SESSION['cart'][$index]['quantity']
                < $stock
        ) {

            $_SESSION['cart'][$index]['quantity']++;

        } else {

            $_SESSION['cart_message'] =
                "You have reached the available stock.";
        }
    }


    header("Location: cart.php");

    exit();
}


if (isset($_POST['decrease'])) {

    $index = (int) ($_POST['index'] ?? -1);


    if (isset($_SESSION['cart'][$index])) {

        $_SESSION['cart'][$index]['quantity']--;


        if (
            $_SESSION['cart'][$index]['quantity'] <= 0
        ) {

            unset($_SESSION['cart'][$index]);

            $_SESSION['cart'] =
                array_values($_SESSION['cart']);
        }
    }


    header("Location: cart.php");

    exit();
}



if (isset($_POST['remove'])) {

    $index = (int) ($_POST['index'] ?? -1);


    if (isset($_SESSION['cart'][$index])) {

        unset($_SESSION['cart'][$index]);

        $_SESSION['cart'] =
            array_values($_SESSION['cart']);
    }


    header("Location: cart.php");

    exit();
}



if (isset($_POST['clear_cart'])) {

    $_SESSION['cart'] = [];

    header("Location: cart.php");

    exit();
}


$cart = $_SESSION['cart'] ?? [];



foreach ($cart as $index => &$item) {

    $product = null;




    if (isset($item['product_id'])) {

        $productId =
            (int)$item['product_id'];

        $stmt = $conn->prepare("
            SELECT id, name, price, image, stock
            FROM products
            WHERE id = ?
            LIMIT 1
        ");

        $stmt->bind_param(
            "i",
            $productId
        );

    } else {


        $productName =
            $item['name'] ?? '';

        $stmt = $conn->prepare("
            SELECT id, name, price, image, stock
            FROM products
            WHERE name = ?
            LIMIT 1
        ");

        $stmt->bind_param(
            "s",
            $productName
        );
    }


    $stmt->execute();

    $result =
        $stmt->get_result();

    $product =
        $result->fetch_assoc();

    $stmt->close();


    if ($product) {

        $item['product_id'] =
            (int)$product['id'];

        $item['name'] =
            $product['name'];

        $item['price'] =
            (float)$product['price'];

        $item['image'] =
            $product['image'];

        $item['stock'] =
            (int)$product['stock'];



        if (
            $item['stock'] > 0
            &&
            (int)$item['quantity']
                > $item['stock']
        ) {

            $item['quantity'] =
                $item['stock'];
        }
    }
}

unset($item);


foreach ($cart as $index => $item) {

    if (
        isset($item['stock'])
        &&
        (int)$item['stock'] <= 0
    ) {

    }
}


$_SESSION['cart'] = $cart;



$total = 0;

$totalItems = 0;


foreach ($cart as $item) {

    $total +=
        (float)$item['price']
        *
        (int)$item['quantity'];

    $totalItems +=
        (int)$item['quantity'];
}



$cartMessage =
    $_SESSION['cart_message'] ?? '';

unset($_SESSION['cart_message']);

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
        Shopping Cart | Abella Apparel
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
            background: #f8f8e9;
            color: #111;
        }

        .cart-header {

            background: #000;
            color: #fff;
            height: 92px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 6%;
        }

        .cart-header h1 {

            font-size: 22px;
            letter-spacing: 2px;
        }


        .continue-shopping {
            color: #fff;
            text-decoration: none;
            font-size: 12px;
            font-weight: bold;
            letter-spacing: 1px;
        }


        .continue-shopping:hover {

            color: #c49d4c;
        }

        .cart-container {

            max-width: 1180px;

            margin: 50px auto;

            padding: 0 25px;
        }

        .cart-message {

            background: #111;

            color: #fff;

            padding: 15px 20px;

            margin-bottom: 20px;

            font-size: 13px;

            border-left: 4px solid #c49d4c;
        }

        .empty-cart {
            background: #fff;
            border: 1px solid #ddd;
            text-align: center;
            padding: 80px 20px;
        }


        .empty-cart h2 {
            font-size: 28px;
            letter-spacing: 1px;
            margin-bottom: 15px;
        }


        .empty-cart p {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }

        .shop-button {
            display: inline-block;
            background: #000;
            color: #fff;
            padding: 15px 30px;
            text-decoration: none;
            font-size: 12px;
            font-weight: bold;
            letter-spacing: 1px;
        }


        .shop-button:hover {

            background: #c49d4c;

            color: #000;
        }

        .cart-items {
            background: #fff;
            border: 1px solid #ddd;
        }


        .cart-item {
            display: grid;
            grid-template-columns:
                120px
                1fr
                120px
                150px;
            gap: 25px;
            align-items: center;
            padding: 25px;
            border-bottom: 1px solid #ddd;
        }


        .cart-item:last-child {

            border-bottom: none;
        }

        .cart-item-image {
            width: 120px;
            height: 140px;
            background: #f4f4f4;
            overflow: hidden;
        }


        .cart-item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .cart-item-info h3 {
            font-size: 16px;
            margin-bottom: 8px;
        }

        .cart-item-info p {
            font-size: 13px;
            color: #666;
        }

        .cart-item-price {
            font-size: 14px;
            font-weight: bold;
            margin-top: 10px;
        }


        .stock-info {
            font-size: 10px;
            color: #777;
            margin-top: 7px;
            letter-spacing: .5px;
        }

        .stock-info.out {
            color: #b00000;
            font-weight: bold;
        }

        .quantity-box {
            display: flex;
            align-items: center;
            border: 1px solid #ccc;
            width: fit-content;
        }

        .quantity-box form {
            margin: 0;
            padding: 0;
        }


        .quantity-box button {
            width: 35px;
            height: 35px;
            border: none;
            background: #fff;
            cursor: pointer;
            font-size: 16px;
        }

        .quantity-box button:hover {
            background: #000
            color: #fff;
        }

        .quantity-box button:disabled {
            cursor: not-allowed;
            opacity: .4;
        }

        .quantity-box button:disabled:hover {
            background: #fff;
            color: #111;
        }

        .quantity-box span {
            width: 35px;
            text-align: center;
            font-size: 13px;
        }

        .cart-item-subtotal {

            text-align: right;
        }

        .cart-item-subtotal strong {
            display: block;
            font-size: 15px;
            margin-bottom: 12px;
        }


        .remove-button {
            border: none;
            background: transparent;
            color: #888;
            font-size: 11px;
            cursor: pointer;
            text-decoration: underline;
        }

        .remove-button:hover {

            color: #000;
        }

        .cart-bottom {

            display: grid;

            grid-template-columns:
                1fr
                350px;

            gap: 40px;

            margin-top: 30px;

            align-items: start;
        }


        .cart-actions {

            display: flex;

            gap: 15px;

            flex-wrap: wrap;
        }


        .action-button {

            border: none;

            background: #000;

            color: #fff;

            padding: 14px 22px;

            font-size: 11px;

            font-weight: bold;

            letter-spacing: 1px;

            cursor: pointer;
        }


        .action-button:hover {

            background: #c49d4c;

            color: #000;
        }


        .summary {

            background: #000;

            color: #fff;

            padding: 30px;
        }


        .summary h2 {

            font-size: 20px;

            letter-spacing: 1px;

            margin-bottom: 20px;
        }


        .summary-row {

            display: flex;

            justify-content: space-between;

            padding: 12px 0;

            font-size: 14px;

            border-bottom: 1px solid #333;
        }


        .summary-row.total {

            border-bottom: none;

            font-size: 20px;

            font-weight: bold;

            padding-top: 20px;
        }


        .summary-row.total span:last-child {

            color: #c49d4c;
        }

        .checkout-button {

            display: block;

            width: 100%;

            background: #c49d4c;

            color: #000;

            text-align: center;

            text-decoration: none;

            padding: 17px;

            margin-top: 20px;

            font-size: 12px;

            font-weight: bold;

            letter-spacing: 1px;
        }


        .checkout-button:hover {

            background: #fff;
        }

        @media (max-width: 900px) {

            .cart-item {

                grid-template-columns:
                    100px
                    1fr;

                gap: 20px;
            }


            .cart-item-image {

                width: 100px;

                height: 120px;
            }


            .quantity-box {

                margin-top: 10px;
            }


            .cart-item-subtotal {

                text-align: left;
            }


            .cart-bottom {

                grid-template-columns: 1fr;
            }

        }


        @media (max-width: 600px) {

            .cart-header {

                padding: 0 20px;
            }


            .cart-header h1 {

                font-size: 18px;
            }


            .cart-container {

                margin: 30px auto;

                padding: 0 15px;
            }


            .cart-item {

                grid-template-columns:
                    80px
                    1fr;

                padding: 18px;

                gap: 15px;
            }


            .cart-item-image {

                width: 80px;

                height: 100px;
            }


            .cart-item-info h3 {

                font-size: 14px;
            }


            .summary {

                padding: 25px;
            }

        }

    </style>

</head>


<body>


<header class="cart-header">

    <h1>
        SHOPPING CART
    </h1>


    <a
        href="index.php"
        class="continue-shopping"
    >
        ← CONTINUE SHOPPING
    </a>

</header>


<main class="cart-container">


<?php if (!empty($cartMessage)): ?>

    <div class="cart-message">

        <?= htmlspecialchars($cartMessage) ?>

    </div>

<?php endif; ?>


<?php if (empty($cart)): ?>


    <div class="empty-cart">

        <h2>
            YOUR CART IS EMPTY
        </h2>


        <p>
            You haven't added anything to your cart yet.
        </p>


        <a
            href="shop.php"
            class="shop-button"
        >
            SHOP NOW
        </a>

    </div>


<?php else: ?>


    <div class="cart-items">


        <?php foreach ($cart as $index => $item): ?>


            <?php


            $imagePath =
                $item['image'] ?? '';


            if (
                strpos($imagePath, 'assets/') !== 0
                &&
                strpos($imagePath, 'http://') !== 0
                &&
                strpos($imagePath, 'https://') !== 0
            ) {

                $imagePath =
                    'assets/' . $imagePath;
            }


            $stock =
                isset($item['stock'])
                ? (int)$item['stock']
                : 0;

            ?>


            <div class="cart-item">

                <div class="cart-item-image">

                    <img
                        src="<?= htmlspecialchars($imagePath) ?>"
                        alt="<?= htmlspecialchars($item['name']) ?>"
                    >

                </div>

                <div class="cart-item-info">

                    <h3>

                        <?= htmlspecialchars(
                            $item['name']
                        ) ?>

                    </h3>


                    <p>
                        Abella Apparel
                    </p>


                    <div class="cart-item-price">

                        ₱<?= number_format(
                            (float)$item['price'],
                            2
                        ) ?>

                    </div>


                    <?php if ($stock > 0): ?>

                        <div class="stock-info">

                            <?= $stock ?>
                            available

                        </div>

                    <?php else: ?>

                        <div class="stock-info out">

                            OUT OF STOCK

                        </div>

                    <?php endif; ?>


                </div>

                <div class="quantity-box">

                    <form method="POST">

                        <input
                            type="hidden"
                            name="index"
                            value="<?= $index ?>"
                        >


                        <button
                            type="submit"
                            name="decrease"
                        >
                            −
                        </button>

                    </form>


                    <span>

                        <?= (int)$item['quantity'] ?>

                    </span>

                    <form method="POST">

                        <input
                            type="hidden"
                            name="index"
                            value="<?= $index ?>"
                        >


                        <button
                            type="submit"
                            name="increase"
                            <?= (
                                $stock <= 0
                                ||
                                (int)$item['quantity'] >= $stock
                            )
                                ? 'disabled'
                                : ''
                            ?>
                        >
                            +
                        </button>

                    </form>


                </div>

                <div class="cart-item-subtotal">

                    <strong>

                        ₱<?= number_format(
                            (float)$item['price']
                            *
                            (int)$item['quantity'],
                            2
                        ) ?>

                    </strong>


                    <form method="POST">

                        <input
                            type="hidden"
                            name="index"
                            value="<?= $index ?>"
                        >


                        <button
                            type="submit"
                            name="remove"
                            class="remove-button"
                        >
                            REMOVE
                        </button>

                    </form>

                </div>


            </div>


        <?php endforeach; ?>


    </div>


    <div class="cart-bottom">



        <div class="cart-actions">


            <a
                href="shop.php"
                class="action-button"
                style="text-decoration:none;"
            >
                CONTINUE SHOPPING
            </a>


            <form method="POST">

                <button
                    type="submit"
                    name="clear_cart"
                    class="action-button"
                >
                    CLEAR CART
                </button>

            </form>


        </div>

        <div class="summary">


            <h2>
                ORDER SUMMARY
            </h2>


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

                    ₱<?= number_format(
                        $total,
                        2
                    ) ?>

                </span>

            </div>


            <div class="summary-row">

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

                    ₱<?= number_format(
                        $total,
                        2
                    ) ?>

                </span>

            </div>


            <a
                href="checkout.php"
                class="checkout-button"
            >
                CHECKOUT
            </a>


        </div>


    </div>


<?php endif; ?>


</main>


</body>

</html>