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
    WHERE is_new = 1
    ORDER BY id ASC
    LIMIT 8
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
    <title>ABELLA APPAREL</title>
    <link
        rel="stylesheet"
        href="style.css?v=<?php echo time(); ?>"
    >
    <style>
        .product {
            position: relative;
        }
        .product-image-wrapper {
            position: relative;
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
        .product-category {
            font-size: 9px;
            color: #777;
            letter-spacing: 1px;
            margin-top: 8px;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        .product-stock {
            font-size: 10px;
            color: #777;
            margin: 8px 0 12px;
            letter-spacing: 0.5px;
        }
        .product-stock.out {
            color: #b00020;
            font-weight: 700;
        }
        .product button:disabled {
            background: #aaa;
            color: #fff;
            cursor: not-allowed;
        }
        .product button:disabled:hover {
            background: #aaa;
            color: #fff;
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
    <section class="hero">
        <div class="hero-copy">
            <img
                class="abella"
                src="assets/abella.png"
                alt=""
            >
            <div class="hero-card">
                <small>
                    NEW COLLECTION
                </small>
                <h1>
                    STREETWEAR<br>
                    THAT DEFINES YOU.
                </h1>
                <div class="line"></div>
                <p>
                    Premium quality. Minimal design.<br>
                    Made to stand out.
                </p>
            </div>
            <a
                href="shop.php"
                class="shop-button"
            >
                SHOP NOW <b>⟶</b>
            </a>
        </div>
        <img
            class="hero-model"
            src="assets/hero-model.jpg"
            alt=""
        >
    </section>
    <section class="benefits">
        <div>
            <span class="benefit-icon">
                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.6"
                >
                    <path d="M1 7h11v9H1z"></path>
                    <path d="M12 10h4l4 3v3h-8z"></path>
                    <circle
                        cx="6"
                        cy="18"
                        r="1.6"
                    ></circle>
                    <circle
                        cx="17"
                        cy="18"
                        r="1.6"
                    ></circle>
                </svg>
            </span>
            <b>
                FAST DELIVERY
            </b>
            <small>
                Delivery nationwide<br>
                to your door
            </small>
        </div>
        <div>
            <span class="benefit-icon">
                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.6"
                >
                    <rect
                        x="4"
                        y="10"
                        width="16"
                        height="10"
                        rx="1.5"
                    ></rect>
                    <path
                        d="M7 10V7a5 5 0 0 1 10 0v3"
                    ></path>
                </svg>
            </span>
            <b>
                SECURE PAYMENT
            </b>
            <small>
                100% secure payment<br>
                guarantee
            </small>
        </div>
        <div>
            <span class="benefit-icon">
                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.6"
                >
                    <circle
                        cx="12"
                        cy="12"
                        r="9"
                    ></circle>
                    <path
                        d="M8 12.5l2.5 2.5L16 9.5"
                    ></path>
                </svg>
            </span>
            <b>
                PREMIUM QUALITY
            </b>
            <small>
                High quality materials<br>
                build to last
            </small>
        </div>
        <div>
            <span class="benefit-icon">
                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.6"
                >
                    <circle
                        cx="12"
                        cy="8"
                        r="5.2"
                    ></circle>
                    <path
                        d="M8.5 12.8L7 21l5-2.4L17 21l-1.5-8.2"
                    ></path>
                </svg>
            </span>
            <b>
                EASY RETURNS
            </b>
            <small>
                30-day easy returns<br>
                or exchange
            </small>
        </div>
    </section>
    <section class="products">
        <h2>
            new arrivals
        </h2>
        <div class="gold-line"></div>
        <div class="product-grid">
            <?php if (!empty($products)): ?>
                <?php foreach ($products as $product): ?>
                    <article class="product">
                        <div class="product-image-wrapper">
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
                                <span class="img-missing">
                                    Photo coming soon
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="product-category">
                            <?= htmlspecialchars($product['category']) ?>
                        </div>
                        <p>
                            <?= htmlspecialchars($product['name']) ?>
                        </p>
                        <strong>
                            ₱<?= number_format(
                                (float)$product['price'],
                                2
                            ) ?>
                        </strong>
                        <?php if ((int)$product['stock'] > 0): ?>
                            <div class="product-stock">
                                <?= (int)$product['stock'] ?>
                                in stock
                            </div>
                        <?php else: ?>
                            <div class="product-stock out">
                                OUT OF STOCK
                            </div>
                        <?php endif; ?>
                        <?php if ((int)$product['stock'] > 0): ?>
                            <button
                                type="button"
                                onclick="addToCart(<?= (int)$product['id'] ?>)"
                            >
                                ADD TO CART
                            </button>
                        <?php else: ?>
                            <button
                                type="button"
                                disabled
                            >
                                OUT OF STOCK
                            </button>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <p>
                    No new arrivals available.
                </p>
            <?php endif; ?>
        </div>
        <a
            href="shop.php"
            class="view-button"
        >
            VIEW ALL PRODUCT
        </a>
    </section>
    <section class="editorial">
        <img
            src="assets/editorial.png"
            alt=""
        >
        <img
            class="editorial1-center"
            src="assets/editorial1.jpg"
            alt=""
        >
        <img
            src="assets/editorial2.jpg"
            alt=""
        >
    </section>
    <section class="newsletter">
        <div>
            <h3>
                GET
                <b class="gold-text">
                    15% OFF
                </b>
                <br>
                <span>
                    YOUR FIRST ORDER
                </span>
            </h3>
            <p>
                Join our newsletter and be the first to know<br>
                about new arrivals and exclusive offers.
            </p>
        </div>
        <div class="email-box">
            <input
                type="email"
                id="newsletter-email"
                class="email-input"
                placeholder="Enter your email address"
            >
            <b onclick="subscribeNewsletter()">
                SUBSCRIBE
            </b>
            <span id="newsletter-message" class="newsletter-message"></span>
        </div>
    </section>
    <section class="instagram">
        <h2>
            FOLLOW US @ABELLA.APPAREL
        </h2>
        <div class="ig-grid">
            <?php
            for ($i = 1; $i <= 6; $i++) {
                $file = 'igg' . $i . '.png';
                if (
                    file_exists(
                        __DIR__ . '/assets/' . $file
                    )
                ) {
                    echo '<img src="assets/' .
                        $file .
                        '" alt="">';
                } else {
                    echo '<a class="ig-follow" href="#">FOLLOW<br>US</a>';
                }
            }
            ?>
        </div>
    </section>
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

function subscribeNewsletter() {
    const isLoggedIn =
        <?= $isLoggedIn ? "true" : "false" ?>;

    if (!isLoggedIn) {
        window.location.href = "login.php";
        return;
    }

    const emailInput =
        document.getElementById("newsletter-email");
    const messageBox =
        document.getElementById("newsletter-message");
    const email = emailInput.value.trim();

    messageBox.textContent = "";
    messageBox.className = "newsletter-message";

    if (email === "") {
        messageBox.textContent =
            "Please enter your email address.";
        messageBox.classList.add("error");
        return;
    }

    const formData = new FormData();
    formData.append("email", email);

    fetch("subscribe.php", {
        method: "POST",
        body: formData
    })
    .then(function (response) {
        return response.json();
    })
    .then(function (data) {
        messageBox.textContent = data.message;
        messageBox.classList.add(
            data.success ? "success" : "error"
        );
        if (data.success) {
            emailInput.value = "";
        }
    })
    .catch(function () {
        messageBox.textContent =
            "Something went wrong. Please try again.";
        messageBox.classList.add("error");
    });
}
</script>
</body>
</html>