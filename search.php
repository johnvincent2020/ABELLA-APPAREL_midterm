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
$search = isset($_GET['search'])
    ? trim($_GET['search'])
    : '';
$products = [];
if ($search === '') {
    $stmt = $conn->prepare("
        SELECT
            id,
            name,
            price,
            category,
            image,
            stock,
            is_new
        FROM products
        ORDER BY id ASC
    ");
} else {
    $searchTerm = "%" . $search . "%";
    $stmt = $conn->prepare("
        SELECT
            id,
            name,
            price,
            category,
            image,
            stock,
            is_new
        FROM products
        WHERE name LIKE ?
           OR category LIKE ?
        ORDER BY id ASC
    ");
    $stmt->bind_param(
        "ss",
        $searchTerm,
        $searchTerm
    );
}
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    $stmt->close();
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
    <title>Search | ABELLA APPAREL</title>
    <link
        rel="stylesheet"
        href="style.css?v=<?php echo time(); ?>"
    >
    <style>
        .search-page {
            background: #f8f8e9;
            min-height: 700px;
            padding: 70px 60px 100px;
        }
        .search-title {
            text-align: center;
            margin-bottom: 35px;
        }
        .search-title h1 {
            margin: 0;
            font-size: 34px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 3px;
            color: #111;
        }
        .search-title p {
            margin-top: 10px;
            font-size: 13px;
            color: #777;
            letter-spacing: 1px;
        }
        .search-form {
            max-width: 700px;
            margin: 0 auto 60px;
            display: flex;
            border: 1px solid #111;
            background: #fff;
            height: 52px;
        }
        .search-form input {
            flex: 1;
            border: none;
            outline: none;
            padding: 0 18px;
            font-size: 14px;
            color: #111;
            background: #fff;
        }
        .search-form input::placeholder {
            color: #999;
        }
        .search-form button {
            width: 130px;
            border: none;
            background: #111;
            color: #fff;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1px;
            cursor: pointer;
            transition: 0.3s;
        }
        .search-form button:hover {
            background: #c49d4c;
            color: #111;
        }
        .search-result-title {
            text-align: center;
            margin-bottom: 35px;
            font-size: 15px;
            color: #333;
        }
        .search-result-title strong {
            color: #111;
        }
        .search-product-grid {
            display: grid;
            grid-template-columns:
            repeat(4, 1fr);
            gap: 35px 22px;
            max-width: 1060px;
            margin: 0 auto;
        }
        .search-product {
            text-align: center;
            background: transparent;
            position: relative;
        }
        .search-product-image {
            position: relative;
            width: 100%;
            height: 330px;
            background: #eee;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            margin-bottom: 16px;
        }
        .product-label {
            position: absolute;
            top: 12px;
            left: 12px;
            z-index: 2;
            background: #c49d4c;
            color: #111;
            padding: 6px 10px;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 1px;
        }
        .search-product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform 0.3s ease;
        }
        .search-product:hover img {
            transform: scale(1.03);
        }
        .product-category {
            font-size: 9px;
            color: #777;
            letter-spacing: 1px;
            margin-bottom: 6px;
            text-transform: uppercase;
        }
        .search-product h3 {
            margin: 0 0 8px;
            font-size: 14px;
            font-weight: 600;
            color: #111;
        }
        .search-product .price {
            font-size: 14px;
            font-weight: 700;
            color: #111;
            margin-bottom: 8px;
        }
        .stock-status {
            font-size: 10px;
            color: #777;
            margin-bottom: 15px;
            letter-spacing: 0.5px;
        }
        .stock-status.out {
            color: #b00020;
            font-weight: 700;
        }
        .search-add-cart {
            border: none;
            background: #111;
            color: #fff;
            padding: 11px 22px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1px;
            cursor: pointer;
            transition: 0.3s;
        }
        .search-add-cart:hover {
            background: #c49d4c;
            color: #111;
        }
        .search-add-cart:disabled {
            background: #aaa;
            border-color: #aaa;
            color: #fff;
            cursor: not-allowed;
        }
        .search-add-cart:disabled:hover {
            background: #aaa;
            color: #fff;
        }
        .no-results {
            text-align: center;
            padding: 70px 20px;
            color: #555;
        }
        .no-results h2 {
            font-size: 22px;
            margin-bottom: 10px;
            color: #111;
        }
        .no-results p {
            font-size: 13px;
            margin-bottom: 25px;
        }
        .back-shop {
            display: inline-block;
            background: #111;
            color: #fff;
            text-decoration: none;
            padding: 13px 25px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1px;
            transition: 0.3s;
        }
        .back-shop:hover {
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
            .search-product-grid {
                grid-template-columns:
                    repeat(2, 1fr);
            }
        }
        @media (max-width: 600px) {
            .search-page {
                padding: 50px 20px 70px;
            }
            .search-title h1 {
                font-size: 26px;
            }
            .search-form {
                height: 48px;
            }
            .search-form button {
                width: 100px;
            }
            .search-product-grid {
                grid-template-columns:
                    repeat(2, 1fr);
                gap: 25px 12px;
            }
            .search-product-image {
                height: 230px;
            }
            .search-product h3 {
                font-size: 12px;
            }
            .search-add-cart {
                padding: 9px 13px;
                font-size: 9px;
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
    <main class="search-page">
        <div class="search-title">
            <h1>
                Search
            </h1>
            <p>
                Find your favorite Abella Apparel products.
            </p>
        </div>
        <form
            class="search-form"
            method="GET"
            action="search.php"
        >
            <input
                type="text"
                name="search"
                placeholder="Search products..."
                value="<?= htmlspecialchars($search) ?>"
                autocomplete="off"
            >
            <button type="submit">
                SEARCH
            </button>
        </form>
        <?php if ($search !== ''): ?>
            <div class="search-result-title">
                Search results for:
                <strong>
                    "<?= htmlspecialchars($search) ?>"
                </strong>
            </div>
        <?php else: ?>
            <div class="search-result-title">
                All Products
            </div>
        <?php endif; ?>
        <?php if (!empty($products)): ?>
            <div class="search-product-grid">
                <?php foreach ($products as $product): ?>
                    <article class="search-product">
                        <div class="search-product-image">
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
                        <div class="price">
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
                                class="search-add-cart"
                                onclick="addToCart(<?= (int)$product['id'] ?>)"
                            >
                                ADD TO CART
                            </button>
                        <?php else: ?>
                            <button
                                type="button"
                                class="search-add-cart"
                                disabled
                            >
                                OUT OF STOCK
                            </button>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-results">
                <h2>
                    No Products Found
                </h2>
                <p>
                    We couldn't find a product matching
                    "<?= htmlspecialchars($search) ?>".
                </p>
                <a
                    href="shop.php"
                    class="back-shop"
                >
                    VIEW ALL PRODUCTS
                </a>
            </div>
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