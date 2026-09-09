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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >
    <title>About Us | ABELLA APPAREL</title>
    <link
        rel="stylesheet"
        href="style.css?v=<?php echo time(); ?>"
    >
    <style>
        .about-page {
            background: #000;
            color: #fff;
        }
        .about-hero {
            min-height: 480px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 100px 30px;
            background:
                linear-gradient(
                    rgba(0,0,0,0.65),
                    rgba(0,0,0,0.85)
                ),
                url("assets/editorial.png");
            background-size: cover;
            background-position: center;
            position: relative;
        }
        .about-hero-content {
            max-width: 850px;
            margin: auto;
        }
        .about-small {
            color: #c49d4c;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 4px;
            margin-bottom: 18px;
        }
        .about-hero h1 {
            font-size: 52px;
            line-height: 1.05;
            font-weight: 800;
            letter-spacing: 1px;
            margin-bottom: 20px;
        }
        .about-hero h1 span {
            color: #c49d4c;
        }
        .about-line {
            width: 55px;
            height: 2px;
            background: #c49d4c;
            margin: 0 auto 22px;
        }
        .about-hero p {
            color: #bbb;
            font-size: 14px;
            line-height: 1.8;
            max-width: 650px;
            margin: auto;
        }
        .about-story {
            max-width: 1180px;
            margin: 0 auto;
            padding: 90px 30px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 70px;
            align-items: center;
        }
        .about-story-image {
            width: 100%;
            height: 500px;
            overflow: hidden;
            background: #111;
        }
        .about-story-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .about-story-content {
            max-width: 520px;
        }
        .about-label {
            color: #c49d4c;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 3px;
            margin-bottom: 12px;
        }
        .about-story h2 {
            font-size: 36px;
            line-height: 1.15;
            margin-bottom: 20px;
            font-weight: 800;
        }
        .about-story h2 span {
            color: #c49d4c;
        }
        .about-story .gold-line {
            width: 45px;
            height: 2px;
            background: #c49d4c;
            margin-bottom: 25px;
        }
        .about-story p {
            color: #999;
            font-size: 13px;
            line-height: 1.9;
            margin-bottom: 18px;
        }
        .about-mission {
            background: #111;
            border-top: 1px solid #292929;
            border-bottom: 1px solid #292929;
            padding: 85px 30px;
            text-align: center;
        }
        .about-mission-inner {
            max-width: 900px;
            margin: auto;
        }
        .about-mission h2 {
            font-size: 34px;
            font-weight: 800;
            margin-bottom: 18px;
        }
        .about-mission h2 span {
            color: #c49d4c;
        }
        .about-mission .gold-line {
            width: 45px;
            height: 2px;
            background: #c49d4c;
            margin: 0 auto 25px;
        }
        .about-mission p {
            color: #999;
            font-size: 14px;
            line-height: 1.9;
        }
        .about-values {
            max-width: 1180px;
            margin: auto;
            padding: 90px 30px;
        }
        .about-values-title {
            text-align: center;
            margin-bottom: 50px;
        }
        .about-values-title .about-label {
            margin-bottom: 10px;
        }
        .about-values-title h2 {
            font-size: 34px;
            font-weight: 800;
        }
        .about-values-title h2 span {
            color: #c49d4c;
        }
        .about-values-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
        }
        .about-value {
            background: #111;
            border: 1px solid #292929;
            padding: 35px 28px;
            text-align: center;
            transition: 0.2s ease;
        }
        .about-value:hover {
            border-color: #c49d4c;
            transform: translateY(-3px);
        }
        .about-value-number {
            color: #c49d4c;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 2px;
            margin-bottom: 18px;
        }
        .about-value h3 {
            font-size: 17px;
            margin-bottom: 14px;
            font-weight: 700;
        }
        .about-value p {
            color: #888;
            font-size: 12px;
            line-height: 1.8;
        }
        .about-cta {
            padding: 90px 30px;
            text-align: center;
            background: #000;
        }
        .about-cta h2 {
            font-size: 38px;
            font-weight: 800;
            margin-bottom: 15px;
        }
        .about-cta h2 span {
            color: #c49d4c;
        }
        .about-cta p {
            color: #888;
            font-size: 13px;
            margin-bottom: 30px;
        }
        .about-shop-button {
            display: inline-block;
            padding: 14px 28px;
            background: #c49d4c;
            color: #111;
            text-decoration: none;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1.5px;
            transition: 0.2s ease;
        }
        .about-shop-button:hover {
            background: #fff;
            color: #111;
        }
        @media (max-width: 900px) {
            .about-story {
                grid-template-columns: 1fr;
                gap: 40px;
            }
            .about-story-content {
                max-width: none;
            }
            .about-values-grid {
                grid-template-columns: 1fr;
            }
            .about-hero h1 {
                font-size: 42px;
            }
        }
        @media (max-width: 600px) {
            .about-hero {
                min-height: 400px;
                padding: 70px 20px;
            }
            .about-hero h1 {
                font-size: 34px;
            }
            .about-story {
                padding: 65px 20px;
            }
            .about-story-image {
                height: 350px;
            }
            .about-story h2 {
                font-size: 29px;
            }
            .about-mission {
                padding: 65px 20px;
            }
            .about-values {
                padding: 65px 20px;
            }
            .about-cta {
                padding: 65px 20px;
            }
            .about-cta h2 {
                font-size: 30px;
            }
        }
    </style>
</head>
<body>
<div class="site about-page">
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
    <section class="about-hero">
        <div class="about-hero-content">
            <div class="about-small">
                ABELLA APPAREL
            </div>
            <h1>
                MORE THAN<br>
                <span>JUST CLOTHING.</span>
            </h1>
            <div class="about-line"></div>
            <p>
                We create premium streetwear for people
                who believe style is more than what you wear.
                It is a reflection of who you are.
            </p>
        </div>
    </section>
    <section class="about-story">
        <div class="about-story-image">
            <img
                src="assets/editorial1.jpg"
                alt="Abella Apparel Story"
            >
        </div>
        <div class="about-story-content">
            <div class="about-label">
                OUR STORY
            </div>
            <h2>
                BORN FROM<br>
                <span>PASSION.</span>
            </h2>
            <div class="gold-line"></div>
            <p>
                Abella Apparel was created from a passion
                for fashion, creativity, and individuality.
                We believe clothing should allow people
                to express themselves without limits.
            </p>
            <p>
                Our approach is simple: clean designs,
                premium quality, and timeless streetwear
                that can become part of your everyday life.
            </p>
            <p>
                From the first idea to every piece we create,
                our goal is to build a brand that represents
                confidence, culture, and individuality.
            </p>
        </div>
    </section>
    <section class="about-mission">
        <div class="about-mission-inner">
            <div class="about-label">
                OUR MISSION
            </div>
            <h2>
                DESIGNED TO <span>STAND OUT.</span>
            </h2>
            <div class="gold-line"></div>
            <p>
                Our mission is to create streetwear that combines
                minimal design with premium quality. We want every
                Abella Apparel piece to give you confidence,
                comfort, and the freedom to express your own style.
            </p>
        </div>
    </section>
    <section class="about-values">
        <div class="about-values-title">
            <div class="about-label">
                WHAT WE BELIEVE
            </div>
            <h2>
                OUR <span>VALUES</span>
            </h2>
        </div>
        <div class="about-values-grid">
            <div class="about-value">
                <div class="about-value-number">
                    01
                </div>
                <h3>
                    QUALITY
                </h3>
                <p>
                    We focus on quality materials and
                    thoughtful designs made to become
                    a lasting part of your wardrobe.
                </p>
            </div>
            <div class="about-value">
                <div class="about-value-number">
                    02
                </div>
                <h3>
                    INDIVIDUALITY
                </h3>
                <p>
                    Your style is your identity. Our designs
                    are made to help you express yourself
                    with confidence.
                </p>
            </div>
            <div class="about-value">
                <div class="about-value-number">
                    03
                </div>
                <h3>
                    CULTURE
                </h3>
                <p>
                    Abella Apparel is inspired by street
                    culture, creativity, and the people
                    who continue to shape it.
                </p>
            </div>
        </div>
    </section>
    <section class="about-cta">
        <h2>
            WEAR YOUR <span>IDENTITY.</span>
        </h2>
        <p>
            Discover the latest Abella Apparel collection.
        </p>
        <a
            href="shop.php"
            class="about-shop-button"
        >
            SHOP THE COLLECTION
        </a>
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
</body>
</html>