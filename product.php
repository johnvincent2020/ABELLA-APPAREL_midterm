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
        $cartCount += (int)$item['quantity'];
    }
}
$productId = isset($_GET['id'])
    ? (int)$_GET['id']
    : 0;
$product = null;
if ($productId > 0) {
    $stmt = $conn->prepare("
        SELECT
            id,
            name,
            price,
            category,
            image,
            stock,
            is_new,
            created_at
        FROM products
        WHERE id = ?
        LIMIT 1
    ");
    if ($stmt) {
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $product = $result->fetch_assoc();
        }
        $stmt->close();
    }
}
if (!$product) {
    http_response_code(404);
    $pageTitle = "PRODUCT NOT FOUND | ABELLA APPAREL";
} else {
    $pageTitle =
        strtoupper($product['name'])
        . " | ABELLA APPAREL";
}
$relatedProducts = [];
if ($product) {
    $relatedStmt = $conn->prepare("
        SELECT
            id,
            name,
            price,
            category,
            image,
            stock,
            is_new
        FROM products
        WHERE category = ?
        AND id != ?
        ORDER BY id ASC
        LIMIT 4
    ");
    if ($relatedStmt) {
        $relatedStmt->bind_param(
            "si",
            $product['category'],
            $product['id']
        );
        $relatedStmt->execute();
        $relatedResult =
            $relatedStmt->get_result();
        if ($relatedResult) {
            while (
                $row =
                $relatedResult->fetch_assoc()
            ) {
                $relatedProducts[] = $row;
            }
        }
        $relatedStmt->close();
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
    <title>
        <?= htmlspecialchars($pageTitle) ?>
    </title>
    <link
        rel="stylesheet"
        href="style.css?v=<?php echo time(); ?>"
    >
    <style>
        .product-details-page {
            background: #f8f8e9;
            min-height: 100vh;
            padding-bottom: 100px;
        }
        .product-breadcrumb {
            max-width: 1120px;
            margin: 0 auto;
            padding: 30px 25px 0;
            font-size: 10px;
            letter-spacing: 1px;
            color: #777;
        }
        .product-breadcrumb a {
            color: #111;
            text-decoration: none;
        }
        .product-breadcrumb a:hover {
            color: #c49d4c;
        }
        .breadcrumb-separator {
            margin: 0 8px;
            color: #aaa;
        }
        .product-details-container {
            max-width: 1120px;
            margin: 0 auto;
            padding: 35px 25px 0;
        }
        .product-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 70px;
            align-items: start;
        }
        .product-details-image {
            position: relative;
            width: 100%;
            height: 650px;
            background: #ededdf;
            overflow: hidden;
        }
        .product-details-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .details-new-label {
            position: absolute;
            top: 18px;
            left: 18px;
            z-index: 2;
            background: #111;
            color: #fff;
            padding: 9px 13px;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 1.5px;
        }
        .product-details-info {
            padding-top: 15px;
        }
        .details-category {
            color: #999;
            font-size: 10px;
            letter-spacing: 2px;
            font-weight: 600;
            margin-bottom: 15px;
        }
        .product-details-info h1 {
            color: #111;
            font-size: 38px;
            line-height: 1.15;
            letter-spacing: 1px;
            margin: 0 0 18px;
            font-weight: 700;
        }
        .details-price {
            color: #111;
            font-size: 23px;
            font-weight: 700;
            margin-bottom: 25px;
        }
        .details-line {
            width: 100%;
            height: 1px;
            background: #ddd;
            margin-bottom: 25px;
        }
        .details-description {
            color: #666;
            font-size: 13px;
            line-height: 1.8;
            margin-bottom: 28px;
        }
        .details-stock {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 11px;
            color: #555;
            margin-bottom: 25px;
            letter-spacing: .5px;
        }
        .stock-dot {
            width: 7px;
            height: 7px;
            background: #4b7d45;
            border-radius: 50%;
            display: inline-block;
        }
        .details-stock.out {
            color: #b00000;
            font-weight: 700;
        }
        .details-stock.out .stock-dot {
            background: #b00000;
        }
        .quantity-label {
            display: block;
            color: #111;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1.5px;
            margin-bottom: 10px;
        }
        .quantity-wrapper {
            display: flex;
            width: 135px;
            height: 45px;
            border: 1px solid #111;
            margin-bottom: 22px;
        }
        .quantity-btn {
            width: 40px;
            border: none;
            background: #f8f8e9;
            color: #111;
            font-size: 18px;
            cursor: pointer;
            transition: .2s ease;
        }
        .quantity-btn:hover {
            background: #111;
            color: #fff;
        }
        .quantity-btn:disabled {
            color: #aaa;
            cursor: not-allowed;
        }
        .quantity-input {
            width: 55px;
            border: none;
            border-left: 1px solid #ddd;
            border-right: 1px solid #ddd;
            background: #fff;
            color: #111;
            text-align: center;
            font-size: 13px;
            font-weight: 600;
            outline: none;
        }
        .details-buttons {
            display: flex;
            flex-direction: column;
            gap: 10px;
            width: 100%;
        }
        .details-add-cart,
        .details-buy-now {
            width: 100%;
            padding: 16px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1.8px;
            cursor: pointer;
            transition: all .3s ease;
        }
        .details-add-cart {
            border: 1px solid #111;
            background: #111;
            color: #fff;
        }
        .details-add-cart:hover {
            background: #c49d4c;
            border-color: #c49d4c;
            color: #111;
        }
        .details-buy-now {
            border: 1px solid #c49d4c;
            background: #c49d4c;
            color: #111;
        }
        .details-buy-now:hover {
            background: #111;
            border-color: #111;
            color: #fff;
        }
        .details-add-cart:disabled,
        .details-buy-now:disabled {
            background: #999;
            border-color: #999;
            color: #fff;
            cursor: not-allowed;
        }
        .details-add-cart:disabled:hover,
        .details-buy-now:disabled:hover {
            background: #999;
            border-color: #999;
            color: #fff;
        }
        .details-info-box {
            border-top: 1px solid #ddd;
            margin-top: 30px;
            padding-top: 25px;
        }
        .details-info-row {
            display: flex;
            justify-content: space-between;
            padding: 11px 0;
            border-bottom: 1px solid #e4e4d8;
            font-size: 10px;
        }
        .details-info-row span:first-child {
            color: #777;
            letter-spacing: 1px;
        }
        .details-info-row span:last-child {
            color: #111;
            font-weight: 600;
        }
        .back-to-shop {
            display: inline-block;
            margin-top: 30px;
            color: #111;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-decoration: none;
            border-bottom: 1px solid #111;
            padding-bottom: 4px;
        }
        .back-to-shop:hover {
            color: #c49d4c;
            border-color: #c49d4c;
        }
        .related-section {
            max-width: 1120px;
            margin: 100px auto 0;
            padding: 0 25px;
        }
        .related-header {
            text-align: center;
            margin-bottom: 40px;
        }
        .related-header small {
            display: block;
            color: #c49d4c;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 3px;
            margin-bottom: 10px;
        }
        .related-header h2 {
            margin: 0;
            color: #111;
            font-size: 28px;
            letter-spacing: 2px;
        }
        .related-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }
        .related-product {
            position: relative;
        }
        .related-image {
            position: relative;
            width: 100%;
            height: 300px;
            background: #ededdf;
            overflow: hidden;
            margin-bottom: 15px;
        }
        .related-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform .4s ease;
        }
        .related-product:hover .related-image img {
            transform: scale(1.05);
        }
        .related-label {
            position: absolute;
            top: 10px;
            left: 10px;
            background: #111;
            color: #fff;
            padding: 6px 9px;
            font-size: 8px;
            font-weight: 700;
            letter-spacing: 1px;
            z-index: 2;
        }
        .related-category {
            font-size: 8px;
            color: #999;
            letter-spacing: 1.5px;
            margin-bottom: 6px;
        }
        .related-product h3 {
            margin: 0 0 6px;
            font-size: 13px;
            color: #111;
            font-weight: 600;
        }
        .related-price {
            font-size: 13px;
            font-weight: 700;
            color: #111;
        }
        .related-link {
            display: block;
            color: inherit;
            text-decoration: none;
        }
        .product-not-found {
            max-width: 700px;
            margin: 0 auto;
            padding: 150px 25px;
            text-align: center;
        }
        .product-not-found h1 {
            color: #111;
            font-size: 35px;
            letter-spacing: 2px;
            margin-bottom: 15px;
        }
        .product-not-found p {
            color: #777;
            font-size: 13px;
            margin-bottom: 30px;
        }
        .not-found-btn {
            display: inline-block;
            background: #111;
            color: #fff;
            text-decoration: none;
            padding: 14px 25px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1.5px;
        }
        .not-found-btn:hover {
            background: #c49d4c;
            color: #111;
        }
        .cart-icon {
            position: relative;
        }
        .cart-count {
            position: absolute;
            top: -8px;
            right: -9px;
            min-width: 15px;
            height: 15px;
            padding: 0 4px;
            background: #c49d4c;
            color: #000;
            border-radius: 50%;
            font-size: 9px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
        }
        @media (max-width: 900px) {
            .product-details {
                grid-template-columns: 1fr 1fr;
                gap: 35px;
            }
            .product-details-image {
                height: 500px;
            }
            .product-details-info h1 {
                font-size: 30px;
            }
            .related-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (max-width: 700px) {
            .product-details {
                grid-template-columns: 1fr;
            }
            .product-details-image {
                height: 500px;
            }
            .product-details-info {
                padding-top: 0;
            }
            .related-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 25px 12px;

            }
            .related-image {

                height: 250px;

            }
        }
        @media (max-width: 450px) {
            .product-breadcrumb {
                padding-left: 15px;
                padding-right: 15px;
            }
            .product-details-container {
                padding-left: 15px;
                padding-right: 15px;
            }
            .product-details-image {
                height: 400px;
            }
            .product-details-info h1 {
                font-size: 26px;
            }
            .details-price {
                font-size: 20px;
            }
            .related-section {
                padding-left: 15px;
                padding-right: 15px;
            }
            .related-grid {
                grid-template-columns: 1fr 1fr;
            }
            .related-image {
                height: 210px;
            }
        }
    </style>
</head>
<body>
<div class="site">
    <header class="header">
        <div class="header-inner">
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
    <main class="product-details-page">
        <?php if (!$product): ?>
            <section class="product-not-found">
                <h1>
                    PRODUCT NOT FOUND
                </h1>
                <p>
                    Sorry, the product you are looking for
                    does not exist.
                </p>
                <a
                    href="shop.php"
                    class="not-found-btn"
                >
                    BACK TO SHOP
                </a>
            </section>
        <?php else: ?>
            <div class="product-breadcrumb">
                <a href="index.php">
                    HOME
                </a>
                <span class="breadcrumb-separator">
                    /
                </span>
                <a href="shop.php">
                    SHOP
                </a>
                <span class="breadcrumb-separator">
                    /
                </span>
                <span>
                    <?= htmlspecialchars($product['name']) ?>
                </span>
            </div>
            <section class="product-details-container">
                <div class="product-details">
                    <div class="product-details-image">
                        <?php if ((int)$product['is_new'] === 1): ?>
                            <span class="details-new-label">
                                NEW
                            </span>
                        <?php endif; ?>
                        <?php if (
                            file_exists(
                                __DIR__
                                . '/assets/'
                                . $product['image']
                            )
                        ): ?>
                            <img
                                src="assets/<?= htmlspecialchars($product['image']) ?>"
                                alt="<?= htmlspecialchars($product['name']) ?>"
                            >
                        <?php else: ?>
                            <div
                                style="
                                    display:flex;
                                    align-items:center;
                                    justify-content:center;
                                    height:100%;
                                    color:#777;
                                    font-size:12px;
                                "
                            >
                                Photo coming soon
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="product-details-info">
                        <div class="details-category">
                            <?= htmlspecialchars(
                                $product['category']
                            ) ?>
                        </div>
                        <h1>
                            <?= htmlspecialchars(
                                $product['name']
                            ) ?>
                        </h1>
                        <div class="details-price">
                            ₱<?= number_format(
                                (float)$product['price'],
                                2
                            ) ?>
                        </div>
                        <div class="details-line"></div>
                        <p class="details-description">
                            Premium Abella Apparel streetwear
                            designed with a clean and modern
                            aesthetic. Built for everyday wear
                            while keeping the Abella style.
                        </p>
                        <?php if ((int)$product['stock'] > 0): ?>
                            <div class="details-stock">
                                <span class="stock-dot"></span>
                                <?= (int)$product['stock'] ?>
                                available
                            </div>
                        <?php else: ?>
                            <div class="details-stock out">
                                <span class="stock-dot"></span>
                                OUT OF STOCK
                            </div>
                        <?php endif; ?>
                        <?php if ((int)$product['stock'] > 0): ?>
                            <label
                                class="quantity-label"
                                for="quantity"
                            >
                                QUANTITY
                            </label>
                            <div class="quantity-wrapper">
                                <button
                                    type="button"
                                    class="quantity-btn"
                                    onclick="decreaseQuantity()"
                                    id="minusButton"
                                >
                                    −
                                </button>
                                <input
                                    type="number"
                                    id="quantity"
                                    class="quantity-input"
                                    value="1"
                                    min="1"
                                    max="<?= (int)$product['stock'] ?>"
                                    readonly
                                >
                                <button
                                    type="button"
                                    class="quantity-btn"
                                    onclick="increaseQuantity()"
                                    id="plusButton"
                                >
                                    +
                                </button>
                            </div>
                            <div class="details-buttons">
                                <button
                                    type="button"
                                    class="details-add-cart"
                                    onclick="addToCart()"
                                >
                                    ADD TO CART
                                </button>
                                <button
                                    type="button"
                                    class="details-buy-now"
                                    onclick="buyNow()"
                                >
                                    BUY NOW
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="details-buttons">
                                <button
                                    type="button"
                                    class="details-add-cart"
                                    disabled
                                >
                                    OUT OF STOCK
                                </button>
                                <button
                                    type="button"
                                    class="details-buy-now"
                                    disabled
                                >
                                    OUT OF STOCK
                                </button>
                            </div>
                        <?php endif; ?>
                        <div class="details-info-box">
                            <div class="details-info-row">
                                <span>
                                    CATEGORY
                                </span>
                                <span>
                                    <?= htmlspecialchars(
                                        $product['category']
                                    ) ?>
                                </span>
                            </div>
                            <div class="details-info-row">
                                <span>
                                    PRODUCT ID
                                </span>
                                <span>
                                    #<?= (int)$product['id'] ?>
                                </span>
                            </div>
                            <div class="details-info-row">
                                <span>
                                    AVAILABILITY
                                </span>
                                <span>
                                    <?php if (
                                        (int)$product['stock'] > 0
                                    ): ?>
                                        IN STOCK
                                    <?php else: ?>
                                        OUT OF STOCK
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                        <a
                            href="shop.php"
                            class="back-to-shop"
                        >
                            ← BACK TO SHOP
                        </a>
                    </div>
                </div>
            </section>
            <?php if (count($relatedProducts) > 0): ?>
                <section class="related-section">
                    <div class="related-header">
                        <small>
                            YOU MAY ALSO LIKE
                        </small>
                        <h2>
                            RELATED PRODUCTS
                        </h2>
                    </div>
                    <div class="related-grid">
                        <?php foreach (
                            $relatedProducts
                            as $related
                        ): ?>
                            <article class="related-product">
                                <a
                                    href="product.php?id=<?= (int)$related['id'] ?>"
                                    class="related-link"
                                >
                                    <div class="related-image">
                                        <?php if (
                                            (int)$related['is_new'] === 1
                                        ): ?>
                                            <span class="related-label">
                                                NEW
                                            </span>
                                        <?php endif; ?>
                                        <?php if (
                                            file_exists(
                                                __DIR__
                                                . '/assets/'
                                                . $related['image']
                                            )
                                        ): ?>
                                            <img
                                                src="assets/<?= htmlspecialchars($related['image']) ?>"
                                                alt="<?= htmlspecialchars($related['name']) ?>"
                                            >
                                        <?php else: ?>
                                            <div
                                                style="
                                                    display:flex;
                                                    align-items:center;
                                                    justify-content:center;
                                                    height:100%;
                                                    color:#777;
                                                    font-size:10px;
                                                "
                                            >
                                                Photo coming soon
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="related-category">
                                        <?= htmlspecialchars(
                                            $related['category']
                                        ) ?>
                                    </div>
                                    <h3>
                                        <?= htmlspecialchars(
                                            $related['name']
                                        ) ?>
                                    </h3>
                                    <div class="related-price">
                                        ₱<?= number_format(
                                            (float)$related['price'],
                                            2
                                        ) ?>
                                    </div>
                                </a>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
        <?php endif; ?>
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
<script>
const productId =
    <?= $product ? (int)$product['id'] : 0 ?>;
const maxStock =
    <?= $product ? (int)$product['stock'] : 0 ?>;
function updateQuantityButtons() {
    const quantityInput =
        document.getElementById("quantity");
    const minusButton =
        document.getElementById("minusButton");
    const plusButton =
        document.getElementById("plusButton");
    if (
        !quantityInput
        ||
        !minusButton
        ||
        !plusButton
    ) {
        return;
    }
    const quantity =
        parseInt(quantityInput.value) || 1;
    minusButton.disabled =
        quantity <= 1;
    plusButton.disabled =
        quantity >= maxStock;
}
function increaseQuantity() {
    const quantityInput =
        document.getElementById("quantity");
    if (!quantityInput) {
        return;
    }
    let quantity =
        parseInt(quantityInput.value) || 1;
    if (quantity < maxStock) {
        quantity++;
        quantityInput.value =
            quantity;
    }
    updateQuantityButtons();
}
function decreaseQuantity() {
    const quantityInput =
        document.getElementById("quantity");
    if (!quantityInput) {
        return;
    }
    let quantity =
        parseInt(quantityInput.value) || 1;
    if (quantity > 1) {
        quantity--;
        quantityInput.value =
            quantity;
    }
    updateQuantityButtons();
}
function addToCart() {
    const quantityInput =
        document.getElementById("quantity");
    if (!quantityInput) {
        return;
    }
    const quantity =
        parseInt(quantityInput.value) || 1;
    if (quantity < 1) {
        return;
    }
    if (quantity > maxStock) {
        alert(
            "The selected quantity is greater than the available stock."
        );
        return;
    }
    const form =
    document.createElement("form");
    form.method = "POST";
    form.action = "cart.php";
    const productInput =
    document.createElement("input");
    productInput.type = "hidden";
    productInput.name = "product_id";
    productInput.value = productId;
    const cartInput =
    document.createElement("input");
    cartInput.type = "hidden";
    cartInput.name = "add_to_cart";
    cartInput.value = "1";
    const quantityInputHidden =
    document.createElement("input");
    quantityInputHidden.type = "hidden";
    quantityInputHidden.name = "quantity";
    quantityInputHidden.value = quantity;
    form.appendChild(productInput);
    form.appendChild(cartInput);
    form.appendChild(quantityInputHidden);
    document.body.appendChild(form);
    form.submit();
}
function buyNow() {
    const quantityInput =
        document.getElementById("quantity");
    if (!quantityInput) {
        return;
    }
    const quantity =
        parseInt(quantityInput.value) || 1;
    if (quantity < 1) {
        return;
    }
    if (quantity > maxStock) {
        alert(
            "The selected quantity is greater than the available stock."
        );
        return;
    }
    const form =
    document.createElement("form");
    form.method = "POST";
    form.action = "cart.php";
    const productInput =
    document.createElement("input");
    productInput.type = "hidden";
    productInput.name = "product_id";
    productInput.value = productId;
    const cartInput =
    document.createElement("input");
    cartInput.type = "hidden";
    cartInput.name = "add_to_cart";
    cartInput.value = "1";
    const quantityInputHidden =
    document.createElement("input");
    quantityInputHidden.type = "hidden";
    quantityInputHidden.name = "quantity";
    quantityInputHidden.value = quantity;
    const buyNowInput =
    document.createElement("input");
    buyNowInput.type = "hidden";
    buyNowInput.name = "buy_now";
    buyNowInput.value = "1";
    form.appendChild(productInput);
    form.appendChild(cartInput);
    form.appendChild(quantityInputHidden);
    form.appendChild(buyNowInput);
    document.body.appendChild(form);
    form.submit();
}
document.addEventListener(
    "DOMContentLoaded",
    function() {
        updateQuantityButtons();
    }
);
</script>
</body>
</html>