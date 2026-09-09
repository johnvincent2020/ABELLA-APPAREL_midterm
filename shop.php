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
    SELECT *
    FROM products
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
    <title>SHOP | ABELLA APPAREL</title>
    <link
        rel="stylesheet"
        href="style.css?v=<?php echo time(); ?>"
    >
    <style>
        .shop-page {
            background: #f8f8e9;
            min-height: 100vh;
            padding-bottom: 90px;
        }
        .shop-hero {
            min-height: 330px;
            background: #111;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            overflow: hidden;
        }
        .shop-hero::before {
            content: "ABELLA";
            position: absolute;
            font-size: 180px;
            font-weight: 900;
            letter-spacing: 15px;
            color: rgba(255,255,255,0.025);
            white-space: nowrap;
        }
        .shop-hero-content {
            position: relative;
            z-index: 2;
        }
        .shop-hero-content small {
            color: #c49d4c;
            font-size: 11px;
            letter-spacing: 4px;
            font-weight: 700;
        }
        .shop-hero-content h1 {
            color: #fff;
            font-size: 55px;
            margin: 12px 0;
            letter-spacing: 5px;
            font-weight: 800;
        }
        .shop-hero-content p {
            color: #aaa;
            font-size: 13px;
            letter-spacing: 1px;
        }
        .shop-content {
            max-width: 1120px;
            margin: 0 auto;
            padding: 55px 25px 0;
        }
        .shop-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 35px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 25px;
        }
        .shop-top h2 {
            margin: 0;
            font-size: 22px;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #111;
        }
        .shop-top p {
            margin: 6px 0 0;
            color: #777;
            font-size: 12px;
        }
        .product-total {
            font-size: 12px;
            color: #777;
        }
        .shop-categories {
            display: flex;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 45px;
        }
        .category-btn {
            border: 1px solid #111;
            background: transparent;
            color: #111;
            padding: 11px 22px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1.5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .category-btn:hover,
        .category-btn.active {
            background: #111;
            color: #fff;
        }
        .shop-product-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 42px 20px;
        }
        .shop-product {
            position: relative;
            text-align: left;
        }
        .shop-product-link {
            display: block;
            color: inherit;
            text-decoration: none;
        }
        .shop-product-link:hover {
            color: inherit;
        }
        .shop-product-link h3 {
            transition: color 0.3s ease;
        }
        .shop-product-link:hover h3 {
            color: #c49d4c;
        }
        .product-image-box {
            position: relative;
            width: 100%;
            height: 350px;
            background: #ededdf;
            overflow: hidden;
            margin-bottom: 17px;
        }
        .product-image-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform 0.5s ease;
        }
        .shop-product:hover .product-image-box img {
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
        .shop-product h3 {
            margin: 0 0 7px;
            font-size: 14px;
            color: #111;
            font-weight: 600;
        }
        .shop-product-price {
            font-size: 14px;
            font-weight: 700;
            color: #111;
            margin-bottom: 14px;
        }
        .stock-status {
            font-size: 9px;
            color: #777;
            margin-bottom: 10px;
            letter-spacing: .5px;
        }
        .stock-status.out {
            color: #b00000;
            font-weight: bold;
        }
        .shop-add-cart {
            width: 100%;
            border: 1px solid #111;
            background: #111;
            color: #fff;
            padding: 12px;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 1.5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .shop-add-cart:hover {
            background: #c49d4c;
            border-color: #c49d4c;
            color: #111;
        }
        .shop-add-cart:disabled {
            background: #999;
            border-color: #999;
            color: #fff;
            cursor: not-allowed;
        }
        .shop-add-cart:disabled:hover {
            background: #999;
            border-color: #999;
            color: #fff;
        }
        .filter-empty {
            display: none;
            text-align: center;
            padding: 70px 20px;
            color: #777;
            grid-column: 1 / -1;
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
        @media (max-width: 950px) {
            .shop-product-grid {
                grid-template-columns: repeat(3, 1fr);
            }
            .product-image-box {
                height: 300px;
            }
        }
        @media (max-width: 700px) {
            .shop-hero {
                min-height: 260px;
            }
            .shop-hero-content h1 {
                font-size: 38px;
            }
            .shop-hero::before {
                font-size: 100px;
            }
            .shop-top {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            .shop-product-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 30px 12px;
            }
            .product-image-box {
                height: 260px;
            }
        }
        @media (max-width: 450px) {
            .shop-content {
                padding-left: 15px;
                padding-right: 15px;
            }
            .shop-hero-content h1 {
                font-size: 30px;
            }
            .category-btn {
                padding: 9px 13px;
                font-size: 8px;
            }
            .product-image-box {
                height: 220px;
            }
            .shop-product h3 {
                font-size: 12px;
            }
            .shop-product-price {
                font-size: 12px;
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
    <main class="shop-page">
        <section class="shop-hero">
            <div class="shop-hero-content">
                <small>
                    ABELLA APPAREL
                </small>
                <h1>
                    SHOP ALL
                </h1>
                <p>
                    PREMIUM STREETWEAR. MINIMAL DESIGN.
                </p>
            </div>
        </section>
        <section class="shop-content">
            <div class="shop-top">
                <div>
                    <h2>
                        All Products
                    </h2>
                    <p>
                        Explore the latest Abella collection.
                    </p>
                </div>
                <div class="product-total">
                    <?= count($products) ?>
                    PRODUCTS
                </div>
            </div>
            <div class="shop-categories">
                <button
                    class="category-btn active"
                    data-category="ALL"
                    onclick="filterProducts('ALL', this)"
                >
                    ALL
                </button>
                <button
                    class="category-btn"
                    data-category="T-SHIRTS"
                    onclick="filterProducts('T-SHIRTS', this)"
                >
                    T-SHIRTS
                </button>
                <button
                    class="category-btn"
                    data-category="HOODIES"
                    onclick="filterProducts('HOODIES', this)"
                >
                    HOODIES
                </button>
                <button
                    class="category-btn"
                    data-category="PANTS"
                    onclick="filterProducts('PANTS', this)"
                >
                    PANTS
                </button>
                <button
                    class="category-btn"
                    data-category="ACCESSORIES"
                    onclick="filterProducts('ACCESSORIES', this)"
                >
                    ACCESSORIES
                </button>
            </div>
            <div class="shop-product-grid">
                <?php foreach ($products as $product): ?>
                    <article
                        class="shop-product"
                        data-category="<?= htmlspecialchars($product['category']) ?>"
                    >
                        <a
                            href="product.php?id=<?= (int)$product['id'] ?>"
                            class="shop-product-link"
                        >
                            <div class="product-image-box">
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
                                    <span>
                                        Photo coming soon
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="product-category">
                                <?= htmlspecialchars($product['category']) ?>
                            </div>
                            <h3>
                                <?= htmlspecialchars($product['name']) ?>
                            </h3>
                        </a>
                        <div class="shop-product-price">
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
                                class="shop-add-cart"
                                onclick="addToCart(<?= (int)$product['id'] ?>)"
                            >
                                ADD TO CART
                            </button>
                        <?php else: ?>
                            <button
                                type="button"
                                class="shop-add-cart"
                                disabled
                            >
                                OUT OF STOCK
                            </button>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
                <div
                    class="filter-empty"
                    id="filterEmpty"
                >
                    <h3>
                        No products found
                    </h3>
                    <p>
                        There are no products in this category yet.
                    </p>
                </div>
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
function filterProducts(category, button) {
    const products =
    document.querySelectorAll(".shop-product");
    const buttons =
    document.querySelectorAll(".category-btn");
    const emptyMessage =
    document.getElementById("filterEmpty");
    let visibleProducts = 0;
    buttons.forEach(function(btn) {
    btn.classList.remove("active");
    });
    button.classList.add("active");
    products.forEach(function(product) {
        const productCategory =
            product.getAttribute("data-category");
        if (
            category === "ALL"
            ||
            productCategory === category
        ) {
            product.style.display = "";
            visibleProducts++;
        } else {
            product.style.display = "none";
        }
    });
    if (visibleProducts === 0) {
        emptyMessage.style.display = "block";
    } else {
        emptyMessage.style.display = "none";
    }
}
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