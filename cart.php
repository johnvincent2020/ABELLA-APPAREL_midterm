<?php

session_start();

require_once 'config.php';


$isLoggedIn = isset($_SESSION['logged_in'])
    && $_SESSION['logged_in'] === true;

$isUser = $isLoggedIn
    && isset($_SESSION['user_role'])
    && $_SESSION['user_role'] === 'user';


$cartCount = 0;

if (isset($_SESSION['cart'])) {

    foreach ($_SESSION['cart'] as $item) {

        $cartCount += (int)($item['quantity'] ?? 0);

    }

}


if (isset($_POST['add_to_cart'])) {




    if (!$isLoggedIn) {

        header("Location: login.php");
        exit();

    }


    $productId = isset($_POST['product_id'])
        ? (int)$_POST['product_id']
        : 0;

    $productName = trim($_POST['product_name'] ?? '');

    $requestedQuantity = isset($_POST['quantity'])
        ? (int)$_POST['quantity']
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

        $_SESSION['cart_message'] =
            "Product not found.";

        header("Location: cart.php");
        exit();

    }


    $stock = (int)$product['stock'];


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


    foreach ($_SESSION['cart'] as &$item) {

        if (
            isset($item['product_id'])
            &&
            (int)$item['product_id'] === (int)$product['id']
        ) {

            $currentQuantity =
                (int)$item['quantity'];

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

            break;

        }

    }

    unset($item);


    if (!$found) {

        $_SESSION['cart'][] = [

            'product_id' =>
                (int)$product['id'],

            'name' =>
                $product['name'],

            'price' =>
                (float)$product['price'],

            'image' =>
                $product['image'],

            'quantity' =>
                $requestedQuantity

        ];

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

    $index = (int)($_POST['index'] ?? -1);


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

    $index = (int)($_POST['index'] ?? -1);


    if (isset($_SESSION['cart'][$index])) {

        $_SESSION['cart'][$index]['quantity']--;


        if (
            $_SESSION['cart'][$index]['quantity']
            <= 0
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

    $index = (int)($_POST['index'] ?? -1);


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
        SHOPPING CART | ABELLA APPAREL
    </title>


    <link
        rel="stylesheet"
        href="style.css?v=<?php echo time(); ?>"
    >


    <style>


        .cart-page {

            background: #f8f8e9;

            min-height: 100vh;

            padding-bottom: 90px;

        }


        .cart-hero {

            min-height: 250px;

            background: #111;

            position: relative;

            display: flex;

            align-items: center;

            justify-content: center;

            text-align: center;

            overflow: hidden;

        }


        .cart-hero::before {

            content: "CART";

            position: absolute;

            font-size: 150px;

            font-weight: 900;

            letter-spacing: 12px;

            color: rgba(255,255,255,0.025);

            white-space: nowrap;

        }


        .cart-hero-content {

            position: relative;

            z-index: 2;

        }


        .cart-hero-content small {

            color: #c49d4c;

            font-size: 11px;

            letter-spacing: 4px;

            font-weight: 700;

        }


        .cart-hero-content h1 {

            color: #fff;

            font-size: 50px;

            margin: 12px 0;

            letter-spacing: 5px;

            font-weight: 800;

        }


        .cart-hero-content p {

            color: #aaa;

            font-size: 13px;

            letter-spacing: 1px;

        }


        .cart-container {

            max-width: 1180px;

            margin: 0 auto;

            padding: 55px 25px 0;

        }


        .cart-message {

            background: #111;

            color: #fff;

            padding: 15px 20px;

            margin-bottom: 25px;

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

            background: #111;

            color: #fff;

            padding: 15px 30px;

            text-decoration: none;

            font-size: 12px;

            font-weight: bold;

            letter-spacing: 1px;

            transition: all .3s ease;

        }


        .shop-button:hover {

            background: #c49d4c;

            color: #111;

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

            color: #111;

            cursor: pointer;

            font-size: 16px;

            transition: all .2s ease;

        }


        .quantity-box button:hover {

            background: #000;

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

            transition: all .3s ease;

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

            transition: all .3s ease;

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

            .cart-hero {

                min-height: 220px;

            }


            .cart-hero-content h1 {

                font-size: 38px;

            }


            .cart-hero::before {

                font-size: 90px;

            }


            .cart-container {

                padding:

                    35px
                    15px
                    0;

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


        @media (max-width: 450px) {

            .cart-hero-content h1 {

                font-size: 30px;

            }


            .cart-item {

                grid-template-columns:
                    70px
                    1fr;

            }


            .cart-item-image {

                width: 70px;

                height: 90px;

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

            <div class="icons">

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

                <a
                    href="<?= $isUser
                        ? 'user_page.php'
                        : 'login.php' ?>"
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

    <main class="cart-page">

        <section class="cart-hero">

            <div class="cart-hero-content">

                <small>
                    ABELLA APPAREL
                </small>

                <h1>
                    SHOPPING CART
                </h1>

                <p>
                    REVIEW YOUR ITEMS BEFORE CHECKOUT.
                </p>

            </div>

        </section>

        <section class="cart-container">


            <!-- MESSAGE -->

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
                            strpos($imagePath, 'http://') !== 0
                            &&
                            strpos($imagePath, 'https://') !== 0
                        ) {

                            if (
                                strpos($imagePath, 'assets/') !== 0
                            ) {

                                $imagePath =
                                    'assets/' . $imagePath;

                            }

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
                                    onerror="this.style.display='none';"
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
                                            (int)$item['quantity']
                                                >= $stock
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


                    <!-- ACTIONS -->

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
                            href="<?= $isLoggedIn ? 'checkout.php' : 'login.php' ?>"
                            class="checkout-button"
                        >
                            CHECKOUT
                        </a>


                    </div>


                </div>


            <?php endif; ?>


        </section>


    </main>

    <footer class="footer">

        <div class="footer-main">


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


                    <a
                        class="social-icon ig"
                        href="#"
                        aria-label="Instagram"
                    >

                        <svg
                            width="15"
                            height="15"
                            viewBox="0 0 24 24"
                        >

                            <rect
                                x="3"
                                y="3"
                                width="18"
                                height="18"
                                rx="5"
                            ></rect>

                            <circle
                                class="dot"
                                cx="12"
                                cy="12"
                                r="4"
                            ></circle>

                            <circle
                                class="dot"
                                cx="17.3"
                                cy="6.7"
                                r="0.6"
                            ></circle>

                        </svg>

                    </a>


                    <a
                        class="social-icon tiktok"
                        href="#"
                        aria-label="TikTok"
                    >

                        <svg
                            width="15"
                            height="15"
                            viewBox="0 0 24 24"
                        >

                            <path
                                d="M15.5 3c.4 2.2 1.8 3.6 4 3.9v2.7c-1.4 0-2.8-.4-4-1.2v6.1c0 3.2-2.6 5.5-5.6 5.5S4.3 17.7 4.3 14.5c0-3 2.4-5.4 5.5-5.5v2.8c-1.4.1-2.5 1.2-2.5 2.7 0 1.5 1.2 2.7 2.7 2.7s2.8-1.1 2.8-2.7V3h2.7z"
                            ></path>

                        </svg>

                    </a>


                </div>

            </div>


            <div>

                <h4>
                    SHOP
                </h4>

                <p>
                    All Products<br>
                    New Arrivals<br>
                    Hoodies<br>
                    T-Shirts<br>
                    Pants<br>
                    Accessories
                </p>

            </div>


            <div>

                <h4>
                    COMPANY
                </h4>

                <p>
                    About Us<br>
                    Our Story<br>
                    Size Guide<br>
                    Care Guide<br>
                    Contact Us
                </p>

            </div>


            <div>

                <h4>
                    HELP
                </h4>

                <p>
                    FAQ<br>
                    Shipping Info<br>
                    Payment Methods<br>
                    Track Order
                </p>

            </div>


            <div>

                <h4>
                    LEGAL
                </h4>

                <p>
                    Privacy Policy<br>
                    Terms & Conditions
                </p>

            </div>


        </div>


        <div class="copyright">

            <span>
                © 2026 ABELLA APPAREL.
                All rights reserved.
            </span>

            <span>
                Designed with passion
            </span>

        </div>


    </footer>


</div>


</body>

</html>