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
$products = [];
$result = $conn->query("
    SELECT
        id,
        name,
        price,
        category,
        image,
        stock,
        is_new
    FROM products
    WHERE category = 'T-SHIRTS'
    ORDER BY id ASC
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
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
    <title>T-SHIRTS | ABELLA APPAREL</title>
    <link
        rel="stylesheet"
        href="style.css?v=<?php echo time(); ?>"
    >
    <style>
        .tshirts-page {
            background: #f8f8e9;
            min-height: 100vh;
            padding-bottom: 90px;
        }
        .tshirts-hero {
            min-height: 330px;
            background: #111;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            overflow: hidden;
        }
        .tshirts-hero::before {
            content: "T-SHIRTS";
            position: absolute;
            font-size: 145px;
            font-weight: 900;
            letter-spacing: 10px;
            color: rgba(255,255,255,0.025);
            white-space: nowrap;
        }
        .tshirts-hero-content {
            position: relative;
            z-index: 2;
        }
        .tshirts-hero-content small {
            color: #c49d4c;
            font-size: 11px;
            letter-spacing: 4px;
            font-weight: 700;
        }
        .tshirts-hero-content h1 {
            color: #fff;
            font-size: 55px;
            margin: 12px 0;
            letter-spacing: 5px;
            font-weight: 800;
        }
        .tshirts-hero-content p {
            color: #aaa;
            font-size: 13px;
            letter-spacing: 1px;
        }
        .tshirts-content {
            max-width: 1120px;
            margin: 0 auto;
            padding: 55px 25px 0;
        }
        .tshirts-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 25px;
        }
        .tshirts-top h2 {
            margin: 0;
            font-size: 22px;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #111;
        }
        .tshirts-top p {
            margin: 6px 0 0;
            color: #777;
            font-size: 12px;
        }
        .product-total {
            font-size: 12px;
            color: #777;
        }
        .tshirt-product-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 50px 30px;
            max-width: 850px;
            margin: 0 auto;
        }
        .tshirt-product {
            position: relative;
            text-align: left;
        }
        .tshirt-image-box {
            position: relative;
            width: 100%;
            height: 500px;
            background: #ededdf;
            overflow: hidden;
            margin-bottom: 17px;
        }
        .tshirt-image-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform 0.5s ease;
        }
        .tshirt-product:hover
        .tshirt-image-box img {
            transform: scale(1.05);
        }
        .product-label {
            position: absolute;
            top: 12px;
            left: 12px;
            z-index: 2;
            background: #111;
            color: #fff;
            padding: 7px 10px;
            font-size: 8px;
            font-weight: 700;
            letter-spacing: 1px;
        }
        .product-category {
            font-size: 9px;
            color: #999;
            letter-spacing: 1.5px;
            margin-bottom: 7px;
        }
        .tshirt-product h3 {
            margin: 0 0 7px;
            font-size: 15px;
            color: #111;
            font-weight: 600;
        }
        .tshirt-price {
            font-size: 14px;
            font-weight: 700;
            color: #111;
            margin-bottom: 8px;
        }
        .stock-status {
            font-size: 10px;
            color: #777;
            margin-bottom: 12px;
            letter-spacing: 0.5px;
        }
        .stock-status.out {
            color: #b00020;
            font-weight: 700;
        }
        .tshirt-add-cart {
            width: 100%;
            border: 1px solid #111;
            background: #111;
            color: #fff;
            padding: 13px;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 1.5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .tshirt-add-cart:hover {
            background: #c49d4c;
            border-color: #c49d4c;
            color: #111;
        }
        .tshirt-add-cart:disabled {
            background: #aaa;
            border-color: #aaa;
            color: #fff;
            cursor: not-allowed;
        }
        .tshirt-add-cart:disabled:hover {
            background: #aaa;
            border-color: #aaa;
            color: #fff;
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
        @media (max-width: 700px) {
            .tshirts-hero {
                min-height: 260px;
            }
            .tshirts-hero-content h1 {
                font-size: 38px;
            }
            .tshirts-hero::before {
                font-size: 85px;
            }
            .tshirts-top {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            .tshirt-product-grid {
                grid-template-columns: 1fr;
                gap: 35px;
                max-width: 600px;
            }
            .tshirt-image-box {
                height: 500px;
            }
        }
        @media (max-width: 450px) {
            .tshirts-content {
                padding-left: 15px;
                padding-right: 15px;
            }
            .tshirts-hero-content h1 {
                font-size: 30px;
            }
            .tshirt-image-box {
                height: 390px;
            }
            .tshirt-product h3 {
                font-size: 13px;
            }
            .tshirt-price {
                font-size: 13px;
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
    <main class="tshirts-page">
        <section class="tshirts-hero">
            <div class="tshirts-hero-content">
                <small>
                    ABELLA APPAREL
                </small>
                <h1>
                    T-SHIRTS
                </h1>
                <p>
                    EVERYDAY ESSENTIALS. MADE FOR THE CULTURE.
                </p>
            </div>
        </section>
        <section class="tshirts-content">
            <div class="tshirts-top">
                <div>
                    <h2>
                        T-Shirt Collection
                    </h2>
                    <p>
                        Discover our essential Abella T-Shirts.
                    </p>
                </div>
                <div class="product-total">
                    <?= count($products) ?>
                    PRODUCTS
                </div>
            </div>
            <div class="tshirt-product-grid">
                <?php foreach ($products as $product): ?>
                    <article class="tshirt-product">
                        <div class="tshirt-image-box">
                            <?php if ((int)$product['is_new'] === 1): ?>
                                <span class="product-label">
                                    NEW
                                </span>
                            <?php endif; ?>
                            <?php if (
                                file_exists(
                                    __DIR__ . '/assets/' . $product['image']
                                )
                            ): ?>
                                <img
                                    src="assets/<?= htmlspecialchars($product['image']) ?>"
                                    alt="<?= htmlspecialchars($product['name']) ?>"
                                >
                            <?php else: ?>
                                <div
                                    style="
                                        height:100%;
                                        display:flex;
                                        align-items:center;
                                        justify-content:center;
                                        color:#777;
                                        font-size:12px;
                                    "
                                >
                                    Photo coming soon
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="product-category">
                            T-SHIRTS
                        </div>
                        <h3>
                            <?= htmlspecialchars($product['name']) ?>
                        </h3>
                        <div class="tshirt-price">
                            ₱<?= number_format(
                                (float)$product['price'],
                                2
                            ) ?>
                        </div>
                        <?php if ((int)$product['stock'] > 0): ?>
                            <div class="stock-status">
                                <?= (int)$product['stock'] ?>
                                in stock
                            </div>
                        <?php else: ?>
                            <div class="stock-status out">
                                OUT OF STOCK
                            </div>
                        <?php endif; ?>
                        <?php if ((int)$product['stock'] > 0): ?>
                            <button
                                type="button"
                                class="tshirt-add-cart"
                                onclick="addToCart(<?= (int)$product['id'] ?>)"
                            >
                                ADD TO CART
                            </button>
                        <?php else: ?>
                            <button
                                type="button"
                                class="tshirt-add-cart"
                                disabled
                            >
                                OUT OF STOCK
                            </button>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
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
<script>
function addToCart(productId) {
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
    form.appendChild(productInput);
    form.appendChild(cartInput);
    document.body.appendChild(form);
    form.submit();
}
</script>
</body>
</html>