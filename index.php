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

$hasUnreadMessages = false;

if ($isUser && isset($_SESSION['user_id'])) {
    $userId = (int)$_SESSION['user_id'];

    $unreadStmt = $conn->prepare("
        SELECT COUNT(*) AS unread_count
        FROM messages
        WHERE user_id = ?
        AND sender = 'admin'
        AND is_read = 0
    ");

    if ($unreadStmt) {
        $unreadStmt->bind_param("i", $userId);
        $unreadStmt->execute();
        $unreadRow = $unreadStmt->get_result()->fetch_assoc();
        $hasUnreadMessages = ((int)($unreadRow['unread_count'] ?? 0)) > 0;
        $unreadStmt->close();
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


$reviews = [];

$reviewResult = $conn->query("
    SELECT
        r.rating,
        r.review,
        r.created_at,
        u.name,
        u.profile_photo,
        p.id AS product_id,
        p.name AS product_name,
        p.image AS product_image
    FROM reviews r
    LEFT JOIN users u
        ON r.user_id = u.id
    LEFT JOIN products p
        ON r.product_id = p.id
    WHERE r.rating BETWEEN 1 AND 5
    ORDER BY r.created_at DESC
    LIMIT 12
");

if ($reviewResult) {
    while ($row = $reviewResult->fetch_assoc()) {
        $reviews[] = $row;
    }
}


$productRatings = [];

$ratingResult = $conn->query("
    SELECT
        p.id AS product_id,
        p.name AS product_name,
        p.image AS product_image,

        COUNT(r.id) AS total_reviews,

        AVG(r.rating) AS average_rating,

        SUM(
            CASE
                WHEN r.rating = 5 THEN 1
                ELSE 0
            END
        ) AS five_star,

        SUM(
            CASE
                WHEN r.rating = 4 THEN 1
                ELSE 0
            END
        ) AS four_star,

        SUM(
            CASE
                WHEN r.rating = 3 THEN 1
                ELSE 0
            END
        ) AS three_star,

        SUM(
            CASE
                WHEN r.rating = 2 THEN 1
                ELSE 0
            END
        ) AS two_star,

        SUM(
            CASE
                WHEN r.rating = 1 THEN 1
                ELSE 0
            END
        ) AS one_star

    FROM reviews r

    INNER JOIN products p
        ON r.product_id = p.id

    WHERE r.rating BETWEEN 1 AND 5

    GROUP BY
        p.id,
        p.name,
        p.image

    ORDER BY
        total_reviews DESC,
        average_rating DESC
");

if ($ratingResult) {

    while ($row = $ratingResult->fetch_assoc()) {

        $total = (int)$row['total_reviews'];

        if ($total > 0) {

            $row['five_percent'] =
                round(
                    ((int)$row['five_star'] / $total) * 100
                );

            $row['four_percent'] =
                round(
                    ((int)$row['four_star'] / $total) * 100
                );

            $row['three_percent'] =
                round(
                    ((int)$row['three_star'] / $total) * 100
                );

            $row['two_percent'] =
                round(
                    ((int)$row['two_star'] / $total) * 100
                );

            $row['one_percent'] =
                round(
                    ((int)$row['one_star'] / $total) * 100
                );

        } else {

            $row['five_percent'] = 0;
            $row['four_percent'] = 0;
            $row['three_percent'] = 0;
            $row['two_percent'] = 0;
            $row['one_percent'] = 0;

        }

        $productRatings[] = $row;
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

        .reviews-section {
            padding: 100px 7%;
            background: #f7f7f7;
            color: #111;
        }

        .reviews-heading {
            text-align: center;
            margin-bottom: 45px;
        }

        .reviews-heading h2 {
            margin: 0;
            font-size: clamp(32px, 4vw, 52px);
            font-weight: 800;
            letter-spacing: -2px;
            text-transform: uppercase;
        }

        .reviews-heading h2 span {
            color: #c49d4c;
        }

        .reviews-heading .gold-line {
            width: 55px;
            height: 2px;
            background: #c49d4c;
            margin: 20px auto;
        }

        .reviews-heading p {
            max-width: 600px;
            margin: 0 auto;
            color: #777;
            font-size: 13px;
            line-height: 1.8;
        }

        .product-rating-wrapper {
            max-width: 1250px;
            margin: 0 auto 40px;
            position: relative;
            overflow: hidden;
        }

        .product-rating-list {
            width: 100%;
            display: flex;
            justify-content: center;
            gap: 14px;
            overflow-x: auto;
            overflow-y: hidden;
            scroll-behavior: smooth;
            scroll-snap-type: x mandatory;
            scrollbar-width: thin;
            scrollbar-color: #c49d4c #e5e5e5;
            padding: 2px 2px 9px;
            box-sizing: border-box;
            overscroll-behavior-x: contain;
            -webkit-overflow-scrolling: touch;
        }

        /*
         * When there are too many cards to fit,
         * move them to the left so the new cards
         * continue naturally on the right side.
         */
        .product-rating-list.is-overflowing {
            justify-content: flex-start;
        }

        .product-rating-list::-webkit-scrollbar {
            height: 4px;
        }

        .product-rating-list::-webkit-scrollbar-track {
            background: #e5e5e5;
        }

        .product-rating-list::-webkit-scrollbar-thumb {
            background: #c49d4c;
        }

        .product-rating-card {
            flex: 0 0 300px;
            width: 300px;
            min-width: 300px;
            box-sizing: border-box;
            display: flex;
            gap: 14px;
            background: #fff;
            border: 1px solid #e5e5e5;
            padding: 14px;
            scroll-snap-align: start;
        }

        .product-rating-image {
            width: 82px;
            min-width: 82px;
            height: 82px;
            background: #eee;
            border: 1px solid #e5e5e5;
            overflow: hidden;
        }

        .product-rating-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .product-rating-no-image {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
            font-size: 7px;
            letter-spacing: .8px;
        }

        .product-rating-content {
            flex: 1;
            min-width: 0;
        }

        .product-rating-name {
            color: #111;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .8px;
            text-transform: uppercase;
            margin-bottom: 3px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .product-rating-top {
            display: flex;
            align-items: center;
            gap: 7px;
            flex-wrap: wrap;
        }

        .product-rating-average {
            color: #111;
            font-size: 20px;
            font-weight: 800;
        }

        .product-rating-average span {
            color: #999;
            font-size: 8px;
            font-weight: 500;
        }

        .product-rating-stars {
            color: #c49d4c;
            font-size: 10px;
            letter-spacing: 1px;
        }

        .product-rating-total {
            color: #999;
            font-size: 7px;
            letter-spacing: .7px;
            margin: 2px 0 7px;
        }

        .rating-breakdown {
            width: 100%;
        }

        .rating-row {
            display: grid;
            grid-template-columns: 42px 1fr 29px;
            align-items: center;
            gap: 5px;
            margin-bottom: 3px;
        }

        .rating-row > span {
            color: #c49d4c;
            font-size: 7px;
            letter-spacing: 0;
            white-space: nowrap;
        }

        .rating-row b {
            color: #777;
            font-size: 7px;
            text-align: right;
        }

        .rating-bar {
            height: 4px;
            background: #e5e5e5;
            overflow: hidden;
        }

        .rating-fill {
            height: 100%;
            background: #c49d4c;
        }

        .product-rating-navigation {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 7px;
            margin-top: 10px;
        }

        .product-rating-nav-btn {
            width: 30px;
            height: 30px;
            padding: 0;
            border: 1px solid #c49d4c;
            border-radius: 50%;
            background: transparent;
            color: #111;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            line-height: 1;
            transition: all .2s ease;
        }

        .product-rating-nav-btn:hover {
            background: #c49d4c;
            color: #111;
        }


        .review-product-name {
            color: #c49d4c;
            font-size: 8px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .reviews-slider {
            max-width: 1250px;
            margin: 0 auto;
            overflow: hidden;
            position: relative;
        }

        .reviews-track {
            display: flex;
            justify-content: center;
            gap: 22px;
            transition: transform 0.45s ease;
            will-change: transform;
        }

       
        .reviews-track.is-overflowing {
            justify-content: flex-start;
        }

        .review-card {
            flex: 0 0 calc((100% - 110px) / 6);
            box-sizing: border-box;
            background: #fff;
            border: 1px solid #e5e5e5;
            padding: 18px;
            min-height: 190px;
            display: flex;
            flex-direction: column;
        }

        .review-profile {
            display: flex;
            align-items: center;
            gap: 9px;
            margin-bottom: 12px;
        }

        .review-profile-image {
            width: 38px;
            height: 38px;
            min-width: 38px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #c49d4c;
            background: #eee;
            display: block;
        }

        .review-profile-placeholder {
            width: 38px;
            height: 38px;
            min-width: 38px;
            border-radius: 50%;
            background: #111;
            color: #c49d4c;
            border: 2px solid #c49d4c;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .review-profile-info {
            min-width: 0;
        }

        .review-profile-info strong {
            display: block;
            color: #111;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: .7px;
            text-transform: uppercase;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .review-date {
            display: block;
            margin-top: 3px;
            color: #999;
            font-size: 7px;
            letter-spacing: .7px;
            text-transform: uppercase;
        }

        .review-stars {
            color: #c49d4c;
            font-size: 13px;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }

        .review-message {
            color: #555;
            font-size: 10px;
            line-height: 1.6;
            flex: 1;
            word-break: break-word;
        }

        .review-message.empty {
            color: #999;
            font-style: italic;
        }

        .review-navigation {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 22px;
        }

        .review-nav-btn {
            width: 34px;
            height: 34px;
            padding: 0;
            border-radius: 50%;
            border: 1px solid #c49d4c;
            background: transparent;
            color: #111;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            line-height: 1;
            transition: all .2s ease;
        }

        .review-nav-btn:hover {
            background: #c49d4c;
            color: #111;
        }

        .review-dots {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }

        .review-dot {
            width: 5px;
            height: 5px;
            border-radius: 50%;
            background: #ccc;
            transition: all .2s ease;
            cursor: pointer;
        }

        .review-dot.active {
            width: 16px;
            border-radius: 8px;
            background: #c49d4c;
        }

        .reviews-empty {
            max-width: 700px;
            margin: 0 auto;
            text-align: center;
            color: #888;
            font-size: 13px;
            line-height: 1.8;
        }

        @media (max-width: 900px) {

            .review-card {
                flex: 0 0 calc((100% - 110px) / 6);
                padding: 14px;
                min-height: 175px;
            }

            .review-profile-image,
            .review-profile-placeholder {
                width: 34px;
                height: 34px;
                min-width: 34px;
            }

            .review-profile-info strong {
                font-size: 8px;
            }

            .review-date {
                font-size: 6px;
            }

            .review-stars {
                font-size: 12px;
            }

            .review-message {
                font-size: 9px;
                line-height: 1.5;
            }

        }

        @media (max-width: 600px) {

            .reviews-section {
                padding: 60px 15px;
            }

            .reviews-heading {
                margin-bottom: 30px;
            }

            .product-rating-wrapper {
                margin-bottom: 30px;
            }

            .product-rating-list {
                justify-content: flex-start;
            }

            .product-rating-card {
                flex: 0 0 280px;
                width: 280px;
                min-width: 280px;
                gap: 10px;
                padding: 10px;
            }

            .product-rating-image {
                width: 65px;
                min-width: 65px;
                height: 65px;
            }

            .product-rating-name {
                font-size: 9px;
            }

            .product-rating-average {
                font-size: 17px;
            }

            .product-rating-stars {
                font-size: 9px;
            }

            .rating-row {
                grid-template-columns: 39px 1fr 26px;
                gap: 4px;
            }

            .product-rating-nav-btn {
                width: 28px;
                height: 28px;
                font-size: 13px;
            }

            .review-card {
                flex: 0 0 calc((100% - 66px) / 4);
                padding: 12px;
                min-height: 155px;
            }

            .review-profile {
                gap: 6px;
                margin-bottom: 9px;
            }

            .review-profile-image,
            .review-profile-placeholder {
                width: 28px;
                height: 28px;
                min-width: 28px;
            }

            .review-profile-placeholder {
                font-size: 11px;
            }

            .review-profile-info strong {
                font-size: 7px;
                letter-spacing: .4px;
            }

            .review-date {
                font-size: 5px;
                letter-spacing: .4px;
            }

            .review-stars {
                font-size: 9px;
                letter-spacing: .5px;
                margin-bottom: 7px;
            }

            .review-message {
                font-size: 8px;
                line-height: 1.4;
            }

            .review-navigation {
                margin-top: 18px;
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

                <a href="contact.php" style="position: relative;">
                    CONTACT
                    <span
                        id="contact-badge"
                        style="
                            position: absolute;
                            top: -4px;
                            right: -10px;
                            width: 8px;
                            height: 8px;
                            background: #e63946;
                            border-radius: 50%;
                            display: <?= $hasUnreadMessages ? 'inline-block' : 'none' ?>;
                        "
                    ></span>
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
                                    src="assets/<?= htmlspecialchars(
                                        $product['image'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                    alt="<?= htmlspecialchars(
                                        $product['name'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                >

                            <?php else: ?>

                                <span class="img-missing">
                                    Photo coming soon
                                </span>

                            <?php endif; ?>

                        </div>

                        <div class="product-category">

                            <?= htmlspecialchars(
                                $product['category'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>

                        </div>

                        <p>

                            <?= htmlspecialchars(
                                $product['name'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>

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
                                onclick="addToCart(<?= (int)$product['id'] ?>, this)"
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


    <section class="reviews-section">

        <div class="reviews-heading">

            <h2>
                CUSTOMER <span>REVIEWS</span>
            </h2>

            <div class="gold-line"></div>

            <p>
                Real feedback from customers who have received
                and experienced Abella Apparel.
            </p>

        </div>


        <?php if (!empty($productRatings)): ?>

            <div class="product-rating-wrapper">

                <div
                    class="product-rating-list"
                    id="productRatingList"
                >

                    <?php foreach ($productRatings as $ratingProduct): ?>

                        <?php

                        $ratingProductImage =
                            basename(
                                (string)(
                                    $ratingProduct['product_image'] ?? ''
                                )
                            );

                        $ratingProductName =
                            (string)(
                                $ratingProduct['product_name'] ?? ''
                            );

                        $averageRating =
                            (float)(
                                $ratingProduct['average_rating'] ?? 0
                            );

                        $averageRounded =
                            (int)round($averageRating);

                        if ($averageRounded < 0) {
                            $averageRounded = 0;
                        }

                        if ($averageRounded > 5) {
                            $averageRounded = 5;
                        }

                        ?>

                        <div class="product-rating-card">

                            <div class="product-rating-image">

                                <?php if (
                                    $ratingProductImage !== '' &&
                                    file_exists(
                                        __DIR__ .
                                        '/assets/' .
                                        $ratingProductImage
                                    )
                                ): ?>

                                    <img
                                        src="assets/<?= htmlspecialchars(
                                            $ratingProductImage,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                        alt="<?= htmlspecialchars(
                                            $ratingProductName,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                    >

                                <?php else: ?>

                                    <div class="product-rating-no-image">
                                        NO IMAGE
                                    </div>

                                <?php endif; ?>

                            </div>

                            <div class="product-rating-content">

                                <div class="product-rating-name">

                                    <?= htmlspecialchars(
                                        $ratingProductName,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </div>

                                <div class="product-rating-top">

                                    <div class="product-rating-average">

                                        <?= number_format(
                                            $averageRating,
                                            1
                                        ) ?>

                                        <span>/ 5</span>

                                    </div>

                                    <div class="product-rating-stars">

                                        <?= str_repeat(
                                            '★',
                                            $averageRounded
                                        ) ?><?= str_repeat(
                                            '☆',
                                            5 - $averageRounded
                                        ) ?>

                                    </div>

                                </div>

                                <div class="product-rating-total">

                                    <?= (int)$ratingProduct['total_reviews'] ?>

                                    <?= (int)$ratingProduct['total_reviews'] === 1
                                        ? 'REVIEW'
                                        : 'REVIEWS' ?>

                                </div>

                                <div class="rating-breakdown">

                                    <div class="rating-row">

                                        <span>★★★★★</span>

                                        <div class="rating-bar">

                                            <div
                                                class="rating-fill"
                                                style="width:<?= (int)$ratingProduct['five_percent'] ?>%;"
                                            ></div>

                                        </div>

                                        <b>
                                            <?= (int)$ratingProduct['five_percent'] ?>%
                                        </b>

                                    </div>

                                    <div class="rating-row">

                                        <span>★★★★☆</span>

                                        <div class="rating-bar">

                                            <div
                                                class="rating-fill"
                                                style="width:<?= (int)$ratingProduct['four_percent'] ?>%;"
                                            ></div>

                                        </div>

                                        <b>
                                            <?= (int)$ratingProduct['four_percent'] ?>%
                                        </b>

                                    </div>

                                    <div class="rating-row">

                                        <span>★★★☆☆</span>

                                        <div class="rating-bar">

                                            <div
                                                class="rating-fill"
                                                style="width:<?= (int)$ratingProduct['three_percent'] ?>%;"
                                            ></div>

                                        </div>

                                        <b>
                                            <?= (int)$ratingProduct['three_percent'] ?>%
                                        </b>

                                    </div>

                                    <div class="rating-row">

                                        <span>★★☆☆☆</span>

                                        <div class="rating-bar">

                                            <div
                                                class="rating-fill"
                                                style="width:<?= (int)$ratingProduct['two_percent'] ?>%;"
                                            ></div>

                                        </div>

                                        <b>
                                            <?= (int)$ratingProduct['two_percent'] ?>%
                                        </b>

                                    </div>

                                    <div class="rating-row">

                                        <span>★☆☆☆☆</span>

                                        <div class="rating-bar">

                                            <div
                                                class="rating-fill"
                                                style="width:<?= (int)$ratingProduct['one_percent'] ?>%;"
                                            ></div>

                                        </div>

                                        <b>
                                            <?= (int)$ratingProduct['one_percent'] ?>%
                                        </b>

                                    </div>

                                </div>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

                <?php if (count($productRatings) > 1): ?>

                    <div class="product-rating-navigation">

                        <button
                            type="button"
                            class="product-rating-nav-btn"
                            id="productRatingPrev"
                            aria-label="Previous product ratings"
                        >
                            ←
                        </button>

                        <button
                            type="button"
                            class="product-rating-nav-btn"
                            id="productRatingNext"
                            aria-label="Next product ratings"
                        >
                            →
                        </button>

                    </div>

                <?php endif; ?>

            </div>

        <?php endif; ?>


        <?php if (!empty($reviews)): ?>

            <?php

            $reviewCount = count($reviews);

            $hasSlider = $reviewCount > 3;

            ?>

            <div
                class="reviews-slider"
                id="reviewsSlider"
                data-slider="<?= $hasSlider ? 'true' : 'false' ?>"
            >

                <div
                    class="reviews-track"
                    id="reviewsTrack"
                >

                    <?php foreach ($reviews as $review): ?>

                        <?php

                        $profilePhoto =
                            trim(
                                (string)(
                                    $review['profile_photo'] ?? ''
                                )
                            );

                        $customerName =
                            trim(
                                (string)(
                                    $review['name'] ?? ''
                                )
                            );

                        if ($customerName === '') {
                            $customerName =
                                'Abella Customer';
                        }

                        $initial =
                            strtoupper(
                                substr(
                                    $customerName,
                                    0,
                                    1
                                )
                            );

                        $profileSrc = '';

                        if ($profilePhoto !== '') {

                            if (
                                strpos(
                                    $profilePhoto,
                                    'http://'
                                ) === 0 ||

                                strpos(
                                    $profilePhoto,
                                    'https://'
                                ) === 0 ||

                                strpos(
                                    $profilePhoto,
                                    '/'
                                ) === 0
                            ) {

                                $profileSrc =
                                    $profilePhoto;

                            } else {

                                $profileSrc =
                                    'assets/profiles/' .
                                    basename(
                                        $profilePhoto
                                    );

                            }

                        }

                        ?>

                        <article class="review-card">

                            <?php if (
                                !empty(
                                    $review['product_name']
                                )
                            ): ?>

                                <div class="review-product-name">

                                    <?= htmlspecialchars(
                                        $review['product_name'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </div>

                            <?php endif; ?>

                            <div class="review-profile">

                                <?php if ($profileSrc !== ''): ?>

                                    <img
                                        class="review-profile-image"
                                        src="<?= htmlspecialchars(
                                            $profileSrc,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                        alt="<?= htmlspecialchars(
                                            $customerName,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                        onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                    >

                                    <div
                                        class="review-profile-placeholder"
                                        style="display:none;"
                                    >

                                        <?= htmlspecialchars(
                                            $initial,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </div>

                                <?php else: ?>

                                    <div class="review-profile-placeholder">

                                        <?= htmlspecialchars(
                                            $initial,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </div>

                                <?php endif; ?>

                                <div class="review-profile-info">

                                    <strong>

                                        <?= htmlspecialchars(
                                            $customerName,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </strong>

                                    <span class="review-date">

                                        <?= date(
                                            'M d, Y',
                                            strtotime(
                                                $review['created_at']
                                            )
                                        ) ?>

                                    </span>

                                </div>

                            </div>

                            <div class="review-stars">

                                <?= str_repeat(
                                    '★',
                                    (int)$review['rating']
                                ) ?><?= str_repeat(
                                    '☆',
                                    5 -
                                    (int)$review['rating']
                                ) ?>

                            </div>

                            <?php if (
                                trim(
                                    (string)$review['review']
                                ) !== ''
                            ): ?>

                                <div class="review-message">

                                    <?= htmlspecialchars(
                                        $review['review'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </div>

                            <?php else: ?>

                                <div class="review-message empty">

                                    Customer left a rating
                                    without a written review.

                                </div>

                            <?php endif; ?>

                        </article>

                    <?php endforeach; ?>

                </div>

            </div>

            <?php if ($hasSlider): ?>

                <div class="review-navigation">

                    <button
                        type="button"
                        class="review-nav-btn"
                        id="reviewPrev"
                        aria-label="Previous reviews"
                    >
                        ←
                    </button>

                    <div
                        class="review-dots"
                        id="reviewDots"
                    ></div>

                    <button
                        type="button"
                        class="review-nav-btn"
                        id="reviewNext"
                        aria-label="Next reviews"
                    >
                        →
                    </button>

                </div>

            <?php endif; ?>

        <?php else: ?>

            <div class="reviews-empty">

                No customer reviews yet. Be the first to share your
                Abella Apparel experience.

            </div>

        <?php endif; ?>

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

            <span
                id="newsletter-message"
                class="newsletter-message"
            ></span>

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
                        htmlspecialchars(
                            $file,
                            ENT_QUOTES,
                            'UTF-8'
                        ) .
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

function addToCart(productId, btn) {
    if (btn && btn.disabled) {
        return;
    }
    if (btn) {
        btn.disabled = true;
    }

    const formData = new FormData();
    formData.append("product_id", productId);
    formData.append("add_to_cart", "1");

    fetch("cart.php", {
        method: "POST",
        credentials: "same-origin",
        headers: { "X-Requested-With": "XMLHttpRequest" },
        body: formData
    })
    .then(function (res) { return res.json(); })
    .then(function (data) {
        if (data.requires_login) {
            window.location.href = "login.php";
            return;
        }
        if (data.success) {
            const card = btn ? btn.closest("article") : null;
            const img = card ? card.querySelector("img") : null;
            flyToCart(img);
            updateCartBadge(data.cart_count);
        } else {
            alert(data.message || "Could not add this item to your cart.");
        }
    })
    .catch(function () {
        alert("Something went wrong. Please try again.");
    })
    .finally(function () {
        if (btn) {
            btn.disabled = false;
        }
    });
}

function flyToCart(sourceImg) {
    const cartIcon = document.querySelector(".cart-icon");
    if (!sourceImg || !cartIcon) {
        return;
    }

    const startRect = sourceImg.getBoundingClientRect();
    const endRect = cartIcon.getBoundingClientRect();

    const flyer = sourceImg.cloneNode(true);
    flyer.classList.add("fly-to-cart-clone");
    flyer.style.top = startRect.top + "px";
    flyer.style.left = startRect.left + "px";
    flyer.style.width = startRect.width + "px";
    flyer.style.height = startRect.height + "px";
    document.body.appendChild(flyer);

    const startCenterX = startRect.left + startRect.width / 2;
    const startCenterY = startRect.top + startRect.height / 2;
    const endCenterX = endRect.left + endRect.width / 2;
    const endCenterY = endRect.top + endRect.height / 2;

    requestAnimationFrame(function () {
        requestAnimationFrame(function () {
            const translateX = endCenterX - startCenterX;
            const translateY = endCenterY - startCenterY;
            flyer.style.width = "22px";
            flyer.style.height = "22px";
            flyer.style.opacity = "0.35";
            flyer.style.transform =
                "translate(" + translateX + "px, " + translateY + "px) scale(0.3)";
        });
    });

    setTimeout(function () {
        flyer.remove();
        cartIcon.classList.add("cart-bump");
        setTimeout(function () {
            cartIcon.classList.remove("cart-bump");
        }, 350);
    }, 750);
}

function updateCartBadge(count) {
    const cartIcon = document.querySelector(".cart-icon");
    if (!cartIcon) {
        return;
    }
    let badge = cartIcon.querySelector(".cart-count");
    if (count > 0) {
        if (!badge) {
            badge = document.createElement("span");
            badge.className = "cart-count";
            cartIcon.appendChild(badge);
        }
        badge.textContent = count;
    } else if (badge) {
        badge.remove();
    }
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

    const email =
        emailInput.value.trim();

    messageBox.textContent = "";

    messageBox.className =
        "newsletter-message";

    if (email === "") {

        messageBox.textContent =
            "Please enter your email address.";

        messageBox.classList.add("error");

        return;
    }

    const formData =
        new FormData();

    formData.append("email", email);

    fetch("subscribe.php", {

        method: "POST",

        body: formData

    })

    .then(function (response) {

        return response.json();

    })

    .then(function (data) {

        messageBox.textContent =
            data.message;

        messageBox.classList.add(
            data.success
                ? "success"
                : "error"
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


const productRatingList =
    document.getElementById("productRatingList");

const productRatingPrev =
    document.getElementById("productRatingPrev");

const productRatingNext =
    document.getElementById("productRatingNext");

if (productRatingList) {

    function updateProductRatingAlignment() {

        /*
         * If all cards fit inside the available width,
         * keep them centered.
         *
         * If they don't fit, move them to the left
         * so additional cards continue on the right.
         */
        const isOverflowing =
            productRatingList.scrollWidth >
            productRatingList.clientWidth + 2;

        productRatingList.classList.toggle(
            "is-overflowing",
            isOverflowing
        );

    }

    function getProductRatingStep() {

        const card =
            productRatingList.querySelector(
                ".product-rating-card"
            );

        if (!card) {
            return 0;
        }

        const cardWidth =
            card.getBoundingClientRect().width;

        const listStyle =
            window.getComputedStyle(
                productRatingList
            );

        const gap =
            parseFloat(listStyle.gap) || 0;

        return cardWidth + gap;
    }

    if (productRatingPrev) {

        productRatingPrev.addEventListener(
            "click",
            function () {

                productRatingList.scrollBy({

                    left:
                        -getProductRatingStep(),

                    behavior:
                        "smooth"

                });

            }
        );

    }

    if (productRatingNext) {

        productRatingNext.addEventListener(
            "click",
            function () {

                productRatingList.scrollBy({

                    left:
                        getProductRatingStep(),

                    behavior:
                        "smooth"

                });

            }
        );

    }


    productRatingList.addEventListener(
        "wheel",
        function (event) {

            if (
                Math.abs(event.deltaY) >
                Math.abs(event.deltaX)
            ) {

                event.preventDefault();

                productRatingList.scrollLeft +=
                    event.deltaY;

            }

        },
        {
            passive: false
        }
    );

    window.addEventListener(
        "resize",
        function () {

            updateProductRatingAlignment();

        }
    );

   
    window.addEventListener(
        "load",
        function () {

            updateProductRatingAlignment();

        }
    );

    updateProductRatingAlignment();

}



const reviewsSlider =
    document.getElementById("reviewsSlider");

const reviewsTrack =
    document.getElementById("reviewsTrack");

const reviewPrev =
    document.getElementById("reviewPrev");

const reviewNext =
    document.getElementById("reviewNext");

const reviewDots =
    document.getElementById("reviewDots");

if (
    reviewsSlider &&
    reviewsTrack
) {

    const reviewCards =
        reviewsTrack.querySelectorAll(
            ".review-card"
        );

    let currentReview = 0;

    function getVisibleReviews() {

        if (window.innerWidth <= 600) {
            return 4;
        }

        return 6;
    }

    function getMaxReview() {

        return Math.max(
            0,
            reviewCards.length -
            getVisibleReviews()
        );

    }

    function getReviewStep() {

        if (!reviewCards.length) {
            return 0;
        }

        const cardWidth =
            reviewCards[0]
                .getBoundingClientRect()
                .width;

        const trackStyle =
            window.getComputedStyle(
                reviewsTrack
            );

        const gap =
            parseFloat(
                trackStyle.gap
            ) || 0;

        return cardWidth + gap;

    }

    function updateReviewAlignment() {

        const visible =
            getVisibleReviews();

        const isOverflowing =
            reviewCards.length >
            visible;

        reviewsTrack.classList.toggle(
            "is-overflowing",
            isOverflowing
        );

       
        if (!isOverflowing) {

            currentReview = 0;

            reviewsTrack.style.transform =
                "translateX(0)";

        }

    }

    function createReviewDots() {

        if (!reviewDots) {
            return;
        }

        reviewDots.innerHTML = "";

        const maxReview =
            getMaxReview();

        for (
            let i = 0;
            i <= maxReview;
            i++
        ) {

            const dot =
                document.createElement("span");

            dot.className =
                "review-dot";

            if (
                i === currentReview
            ) {

                dot.classList.add(
                    "active"
                );

            }

            dot.addEventListener(
                "click",
                function () {

                    currentReview = i;

                    updateReviews();

                }
            );

            reviewDots.appendChild(dot);

        }

    }

    function updateReviews() {

        const maxReview =
            getMaxReview();

      
        if (
            reviewCards.length <=
            getVisibleReviews()
        ) {

            currentReview = 0;

            reviewsTrack.style.transform =
                "translateX(0)";

            reviewsTrack.classList.remove(
                "is-overflowing"
            );

            if (reviewDots) {

                const dots =
                    reviewDots.querySelectorAll(
                        ".review-dot"
                    );

                dots.forEach(
                    function (dot) {

                        dot.classList.remove(
                            "active"
                        );

                    }
                );

            }

            return;

        }

        reviewsTrack.classList.add(
            "is-overflowing"
        );

        if (
            currentReview >
            maxReview
        ) {

            currentReview =
                maxReview;

        }

        const step =
            getReviewStep();

        reviewsTrack.style.transform =
            "translateX(-" +
            (
                currentReview *
                step
            ) +
            "px)";

        if (reviewDots) {

            const dots =
                reviewDots.querySelectorAll(
                    ".review-dot"
                );

            dots.forEach(
                function (
                    dot,
                    index
                ) {

                    dot.classList.toggle(
                        "active",
                        index === currentReview
                    );

                }
            );

        }

    }

    if (reviewPrev) {

        reviewPrev.addEventListener(
            "click",
            function () {

                const maxReview =
                    getMaxReview();

                if (maxReview <= 0) {
                    return;
                }

                if (
                    currentReview > 0
                ) {

                    currentReview--;

                } else {

                    currentReview =
                        maxReview;

                }

                updateReviews();

            }
        );

    }

    if (reviewNext) {

        reviewNext.addEventListener(
            "click",
            function () {

                const maxReview =
                    getMaxReview();

                if (maxReview <= 0) {
                    return;
                }

                if (
                    currentReview <
                    maxReview
                ) {

                    currentReview++;

                } else {

                    currentReview = 0;

                }

                updateReviews();

            }
        );

    }

    window.addEventListener(
        "resize",
        function () {

            updateReviewAlignment();

            createReviewDots();

            updateReviews();

        }
    );

    window.addEventListener(
        "load",
        function () {

            updateReviewAlignment();

            createReviewDots();

            updateReviews();

        }
    );

    updateReviewAlignment();

    createReviewDots();

    updateReviews();

}

</script>

<script>
(function () {
    var contactBadge = document.getElementById('contact-badge');

    if (!contactBadge) {
        return;
    }

    function checkUnreadMessages() {
        fetch('check_messages.php', { credentials: 'same-origin' })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                contactBadge.style.display = data.hasUnreadMessages
                    ? 'inline-block'
                    : 'none';
            })
            .catch(function () {});
    }

    setInterval(checkUnreadMessages, 10000);
})();
</script>
</body>

</html>