<?php

session_start();

require_once 'config.php';

$isLoggedIn = isset($_SESSION['logged_in'])
    && $_SESSION['logged_in'] === true;

$isUser = $isLoggedIn
    && isset($_SESSION['user_role'])
    && $_SESSION['user_role'] === 'user';


$cartCount = 0;

if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {

    foreach ($_SESSION['cart'] as $item) {

        $cartCount += (int)($item['quantity'] ?? 0);
    }
}


$formMessage = '';
$postMessage = '';
$messages = [];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $isAjax =
        isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';


    if (!$isUser || !isset($_SESSION['user_id'])) {

        if ($isAjax) {

            header('Content-Type: application/json; charset=utf-8');

            echo json_encode([
                'success' => false,
                'message' => 'Please log in to send a message.'
            ]);

            exit;
        }

        $formMessage = 'Please log in to send a message.';

    } else {

        $userId = (int)$_SESSION['user_id'];

        $message = trim($_POST['message'] ?? '');

        $postMessage = $message;


        if ($message === '') {

            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false,
                    'message' => 'Please enter a message.'
                ]);

                exit;
            }

            $formMessage = 'Please enter a message.';



        } elseif (strlen($message) > 2000) {

            if ($isAjax) {

                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false,
                    'message' =>
                        'Your message is too long. Please keep it under 2000 characters.'
                ]);

                exit;
            }

            $formMessage =
                'Your message is too long. Please keep it under 2000 characters.';

        } else {

            $stmt = $conn->prepare("
                INSERT INTO messages
                (user_id, sender, message, is_read)
                VALUES (?, 'customer', ?, 0)
            ");


            if ($stmt) {
                $stmt->bind_param(
                    "is",
                    $userId,
                    $message
                );

                if ($stmt->execute()) {

                    $newMessageId = $stmt->insert_id;

                    $stmt->close();

                    if ($isAjax) {

                        header(
                            'Content-Type: application/json; charset=utf-8'
                        );

                        echo json_encode([
                            'success' => true,
                            'id' => $newMessageId,
                            'message' => $message,
                            'time' => date('M d • h:i A')
                        ]);

                        exit;
                    }

                    header("Location: contact.php");

                    exit;


                } else {

                    $stmt->close();


                    if ($isAjax) {

                        header(
                            'Content-Type: application/json; charset=utf-8'
                        );

                        echo json_encode([
                            'success' => false,
                            'message' =>
                                'Sorry, your message could not be sent. Please try again.'
                        ]);

                        exit;
                    }

                    $formMessage =
                        'Sorry, your message could not be sent. Please try again.';
                }


            } else {

                if ($isAjax) {

                    header(
                        'Content-Type: application/json; charset=utf-8'
                    );

                    echo json_encode([
                        'success' => false,
                        'message' =>
                            'Sorry, your message could not be sent. Please try again.'
                    ]);

                    exit;
                }

                $formMessage =
                    'Sorry, your message could not be sent. Please try again.';
            }
        }
    }
}

if ($isUser && isset($_SESSION['user_id'])) {

    $userId = (int)$_SESSION['user_id'];

    $stmt = $conn->prepare("
        SELECT
            id,
            sender,
            message,
            is_read,
            created_at
        FROM messages
        WHERE user_id = ?
        ORDER BY created_at ASC, id ASC
    ");


    if ($stmt) {

        $stmt->bind_param(
            "i",
            $userId
        );

        $stmt->execute();

        $result = $stmt->get_result();


        while ($row = $result->fetch_assoc()) {

            $messages[] = $row;
        }


        $stmt->close();
    }
}

if ($isUser && isset($_SESSION['user_id'])) {

    $userId = (int)$_SESSION['user_id'];

    $readStmt = $conn->prepare("
        UPDATE messages
        SET is_read = 1
        WHERE user_id = ?
        AND sender = 'admin'
        AND is_read = 0
    ");


    if ($readStmt) {

        $readStmt->bind_param(
            "i",
            $userId
        );

        $readStmt->execute();

        $readStmt->close();
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
    <title>Contact Us | ABELLA APPAREL</title>
    <link
        rel="stylesheet"
        href="style.css?v=<?php echo time(); ?>"
    >
    <style>

        .contact-page {
            background: #000;
            color: #fff;
            min-height: 100vh;
        }

        .contact-hero {
            min-height: 430px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 90px 30px;
            background:
                linear-gradient(
                    rgba(0,0,0,0.65),
                    rgba(0,0,0,0.88)
                ),
                url("assets/editorial.png");
            background-size: cover;
            background-position: center;
            position: relative;
        }

        .contact-hero-content {
            max-width: 850px;
            margin: auto;
        }

        .contact-small {
            color: #c49d4c;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 4px;
            margin-bottom: 18px;
        }

        .contact-hero h1 {
            font-size: 52px;
            line-height: 1.05;
            font-weight: 800;
            letter-spacing: 1px;
            margin-bottom: 20px;
        }

        .contact-hero h1 span {
            color: #c49d4c;
        }

        .contact-line {
            width: 55px;
            height: 2px;
            background: #c49d4c;
            margin: 0 auto 22px;
        }

        .contact-hero p {
            color: #bbb;
            font-size: 14px;
            line-height: 1.8;
            max-width: 650px;
            margin: auto;
        }

        .contact-main {
            max-width: 1180px;
            margin: 0 auto;
            padding: 90px 30px;
            display: grid;
            grid-template-columns: 0.8fr 1.2fr;
            gap: 70px;
            align-items: start;
        }

        .contact-info {
            max-width: 480px;
        }

        .contact-label {
            color: #c49d4c;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 3px;
            margin-bottom: 12px;
        }

        .contact-info h2 {
            font-size: 36px;
            line-height: 1.15;
            margin-bottom: 20px;
            font-weight: 800;
        }

        .contact-info h2 span {
            color: #c49d4c;
        }

        .contact-gold-line {
            width: 45px;
            height: 2px;
            background: #c49d4c;
            margin-bottom: 25px;
        }

        .contact-info > p {
            color: #999;
            font-size: 13px;
            line-height: 1.9;
            margin-bottom: 35px;
        }

        .contact-details {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        .contact-detail {
            display: flex;
            align-items: flex-start;
            gap: 18px;
        }

        .contact-detail-icon {
            width: 42px;
            height: 42px;
            min-width: 42px;
            border: 1px solid #292929;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #c49d4c;
        }

        .contact-detail-icon svg {
            width: 18px;
            height: 18px;
        }

        .contact-detail-content h3 {
            font-size: 12px;
            letter-spacing: 1.5px;
            margin-bottom: 6px;
            font-weight: 700;
        }

        .contact-detail-content p {
            color: #888;
            font-size: 12px;
            line-height: 1.7;
            margin: 0;
        }

        .contact-form-wrapper {
            background: #111;
            border: 1px solid #292929;
            padding: 0;
            overflow: hidden;
        }

        .chat-header {
            height: 76px;
            padding: 0 22px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #292929;
            background: #111;
        }

        .chat-header-left {
            display: flex;
            align-items: center;
            gap: 13px;
        }

        .chat-avatar {
            width: 42px;
            height: 42px;
            min-width: 42px;
            border-radius: 50%;
            background: #000;
            border: 1px solid #c49d4c;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #c49d4c;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1px;
        }

        .chat-header-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .chat-header-name {
            color: #fff;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1px;
        }

        .chat-header-status {
            color: #777;
            font-size: 9px;
            letter-spacing: 0.8px;
        }

        .chat-online {
            display: inline-block;
            width: 6px;
            height: 6px;
            background: #c49d4c;
            border-radius: 50%;
            margin-right: 4px;
        }

        .chat-header-icon {
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
        }

        .chat-header-icon svg {
            width: 17px;
            height: 17px;
        }

        .contact-form-title {
            padding: 25px 22px 10px;
            margin: 0;
        }

        .contact-form-title h2 {
            font-size: 20px;
            font-weight: 800;
            margin-bottom: 7px;
        }

        .contact-form-title h2 span {
            color: #c49d4c;
        }

        .contact-form-title p {
            color: #666;
            font-size: 10px;
            line-height: 1.7;
            margin: 0;
        }

        .contact-message {
            padding: 11px 15px;
            margin: 15px 22px;
            font-size: 10px;
            line-height: 1.6;
        }

        .contact-message.error {
            background: rgba(180,60,60,0.10);
            border: 1px solid #7b3d3d;
            color: #d88b8b;
        }

        .chat-box {
            margin: 12px 22px 0;
            height: 430px;
            background: #080808;
            border: 1px solid #222;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 22px 15px;
            display: flex;
            flex-direction: column;
            gap: 15px;
            scroll-behavior: smooth;
        }

        .chat-box::-webkit-scrollbar {
            width: 5px;
        }

        .chat-box::-webkit-scrollbar-track {
            background: #080808;
        }

        .chat-box::-webkit-scrollbar-thumb {
            background: #292929;
        }

        .chat-box::-webkit-scrollbar-thumb:hover {
            background: #c49d4c;
        }

        .chat-empty {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 30px;
        }

        .chat-empty-inner {
            max-width: 280px;
        }

        .chat-empty-avatar {
            width: 58px;
            height: 58px;
            border-radius: 50%;
            border: 1px solid #c49d4c;
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #c49d4c;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 1px;
        }

        .chat-empty h3 {
            font-size: 13px;
            margin-bottom: 8px;
            letter-spacing: 0.8px;
        }

        .chat-empty p {
            color: #555;
            font-size: 10px;
            line-height: 1.7;
            margin: 0;
        }

        .message-row {
            display: flex;
            width: 100%;
            align-items: flex-end;
            gap: 8px;
        }

        .message-row.customer {
            justify-content: flex-end;
        }

        .message-row.admin {
            justify-content: flex-start;
        }

        .message-avatar {
            width: 27px;
            height: 27px;
            min-width: 27px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 7px;
            font-weight: 800;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }

        .admin-avatar {
            background: #c49d4c;
            color: #111;
        }

        .customer-avatar {
            background: #181818;
            color: #c49d4c;
            border: 1px solid #333;
        }


        .message-content {
            max-width: 72%;
            display: flex;
            flex-direction: column;
        }

        .message-row.customer .message-content {
            align-items: flex-end;
        }

        .message-row.admin .message-content {
            align-items: flex-start;
        }

        .message-sender {
            color: #777;
            font-size: 8px;
            font-weight: 700;
            letter-spacing: 1px;
            margin: 0 8px 5px;
        }

        .message-row.customer .message-sender {
            color: #c49d4c;
        }

        .message-bubble {
            padding: 11px 14px;
            position: relative;
            word-break: break-word;
            font-size: 11px;
            line-height: 1.65;
        }

        .customer-bubble {
            background: #c49d4c;
            color: #111;
            border-radius: 18px 18px 4px 18px;
        }

        .admin-bubble {
            background: #1c1c1c;
            color: #ddd;
            border: 1px solid #292929;
            border-radius: 18px 18px 18px 4px;
        }

        .message-meta {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-top: 5px;
            padding: 0 5px;
        }

        .message-time {
            color: #555;
            font-size: 8px;
        }

        .customer-message-status {
            color: #c49d4c;
            font-size: 8px;
            letter-spacing: -1px;
        }

        .customer-message-status.read {
            color: #c49d4c;
        }


        .chat-composer {
            padding: 15px 22px 20px;
            background: #111;
            border-top: 1px solid #222;
        }

        .contact-form {
            display: flex;
            align-items: flex-end;
            gap: 10px;
        }

        .contact-field {
            flex: 1;
            position: relative;
        }

        .contact-field textarea {
            display: block;
            width: 100%;
            height: 48px;
            min-height: 48px;
            max-height: 120px;
            resize: none;
            background: #000;
            color: #fff;
            border: 1px solid #292929;
            border-radius: 24px;
            outline: none;
            padding: 14px 17px;
            padding-right: 40px;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            line-height: 1.5;
            transition: border-color 0.2s ease;
            overflow-y: auto;
        }

        .contact-field textarea:focus {
            border-color: #c49d4c;
        }

        .contact-field textarea::placeholder {
            color: #555;
        }

        .contact-submit {
            width: 46px;
            height: 46px;
            min-width: 46px;
            border: none;
            outline: none;
            border-radius: 50%;
            background: #c49d4c;
            color: #111;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition:
                background 0.2s ease,
                transform 0.2s ease;
            padding: 0;
        }

        .contact-submit:hover {
            background: #fff;
            transform: scale(1.05);
        }

        .contact-submit:active {
            transform: scale(0.95);
        }

        .contact-submit svg {
            width: 17px;
            height: 17px;
        }

        .chat-login-message {
            margin: 20px 22px;
            padding: 16px;
            border: 1px solid #292929;
            background: #080808;
            text-align: center;
        }

        .chat-login-message p {
            color: #777;
            font-size: 10px;
            line-height: 1.7;
            margin: 0 0 12px;
        }

        .chat-login-link {
            display: inline-block;
            background: #c49d4c;
            color: #111;
            text-decoration: none;
            padding: 10px 18px;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 1px;
            transition: 0.2s ease;
        }

        .chat-login-link:hover {
            background: #fff;
        }

        .contact-banner {
            background: #111;
            border-top: 1px solid #292929;
            border-bottom: 1px solid #292929;
            padding: 70px 30px;
            text-align: center;
        }

        .contact-banner-inner {
            max-width: 850px;
            margin: auto;
        }

        .contact-banner h2 {
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 15px;
        }

        .contact-banner h2 span {
            color: #c49d4c;
        }

        .contact-banner p {
            color: #888;
            font-size: 13px;
            line-height: 1.8;
        }

        @media (max-width: 900px) {

            .contact-main {
                grid-template-columns: 1fr;
                gap: 50px;
            }

            .contact-info {
                max-width: none;
            }

            .contact-hero h1 {
                font-size: 42px;
            }

            .chat-box {
                height: 420px;
            }
        }


        @media (max-width: 600px) {

            .contact-hero {
                min-height: 380px;
                padding: 70px 20px;
            }

            .contact-hero h1 {
                font-size: 34px;
            }

            .contact-main {
                padding: 65px 20px;
            }

            .contact-info h2 {
                font-size: 29px;
            }

            .contact-form-title {
                padding-left: 18px;
                padding-right: 18px;
            }

            .chat-header {
                padding: 0 18px;
            }

            .chat-box {
                margin-left: 18px;
                margin-right: 18px;
                height: 400px;
                padding: 18px 10px;
            }

            .chat-composer {
                padding-left: 18px;
                padding-right: 18px;
            }

            .message-content {
                max-width: 78%;
            }

            .message-bubble {
                font-size: 10px;
                padding: 10px 12px;
            }

            .contact-banner {
                padding: 60px 20px;
            }

            .contact-banner h2 {
                font-size: 28px;
            }
        }


        @media (max-width: 400px) {

            .chat-box {
                height: 380px;
            }

            .message-content {
                max-width: 82%;
            }

            .message-avatar {
                width: 24px;
                height: 24px;
                min-width: 24px;
                font-size: 6px;
            }

            .contact-submit {
                width: 44px;
                height: 44px;
                min-width: 44px;
            }
        }

    </style>

</head>


<body>

<div class="site contact-page">
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
    <section class="contact-hero">

        <div class="contact-hero-content">

            <div class="contact-small">
                ABELLA APPAREL
            </div>

            <h1>
                LET'S START<br>
                <span>A CONVERSATION.</span>
            </h1>

            <div class="contact-line"></div>

            <p>
                Have a question about our products, orders,
                or anything Abella Apparel? We'd love to
                hear from you.
            </p>

        </div>

    </section>
    <section class="contact-main">
        <div class="contact-info">
            <div class="contact-label">
                GET IN TOUCH
            </div>
            <h2>
                WE'RE HERE<br>
                <span>TO HELP.</span>
            </h2>
            <div class="contact-gold-line"></div>
            <p>
                Whether you have a question about our
                collection, your order, sizing, or simply
                want to connect with us, send us a message.
                Our team will be happy to assist you.
            </p>
            <div class="contact-details">
                <div class="contact-detail">
                    <div class="contact-detail-icon">
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.5"
                        >
                            <rect
                                x="3"
                                y="5"
                                width="18"
                                height="14"
                                rx="2"
                            ></rect>
                            <path
                                d="M3 7l9 6 9-6"
                            ></path>
                        </svg>
                    </div>
                    <div class="contact-detail-content">
                        <h3>
                            EMAIL
                        </h3>
                        <p>
                            abellaapparel@gmail.com
                        </p>
                    </div>
                </div>
                <div class="contact-detail">
                    <div class="contact-detail-icon">
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.5"
                        >
                            <path
                                d="M6.5 3.5h3l1.5 5-2 1.5a14 14 0 0 0 5 5l1.5-2 5 1.5v3c0 1.1-.9 2-2 2C10.5 19.5 4.5 13.5 4.5 6.5c0-1.1.9-2 2-2z"
                            ></path>
                        </svg>
                    </div>
                    <div class="contact-detail-content">
                        <h3>
                            PHONE
                        </h3>
                        <p>
                            +63 9XX XXX XXXX
                        </p>
                    </div>
                </div>
                <div class="contact-detail">
                    <div class="contact-detail-icon">
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.5"
                        >
                            <path
                                d="M12 21s7-6.2 7-12a7 7 0 1 0-14 0c0 5.8 7 12 7 12z"
                            ></path>
                            <circle
                                cx="12"
                                cy="9"
                                r="2.5"
                            ></circle>
                        </svg>
                    </div>
                    <div class="contact-detail-content">
                        <h3>
                            LOCATION
                        </h3>
                        <p>
                            Philippines
                        </p>
                    </div>
                </div>
                <div class="contact-detail">
                    <div class="contact-detail-icon">
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.5"
                        >
                            <circle
                                cx="12"
                                cy="12"
                                r="8.5"
                            ></circle>
                            <path
                                d="M12 7v5l3 2"
                            ></path>
                        </svg>
                    </div>
                    <div class="contact-detail-content">
                        <h3>
                            BUSINESS HOURS
                        </h3>
                        <p>
                            Monday – Saturday<br>
                            9:00 AM – 6:00 PM
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <div
            class="contact-form-wrapper"
            id="contact-form-wrapper"
        >
            <div class="chat-header">
                <div class="chat-header-left">
                    <div class="chat-avatar">
                        AA
                    </div>
                    <div class="chat-header-info">
                        <div class="chat-header-name">
                            ABELLA APPAREL
                        </div>
                        <div class="chat-header-status">
                            <span class="chat-online"></span>
                            Customer Support
                        </div>
                    </div>
                </div>
                <div class="chat-header-icon">
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.5"
                    >
                        <circle
                            cx="12"
                            cy="12"
                            r="9"
                        ></circle>
                        <path
                            d="M12 10v6"
                        ></path>
                        <circle
                            cx="12"
                            cy="7"
                            r=".7"
                            fill="currentColor"
                            stroke="none"
                        ></circle>
                    </svg>
                </div>
            </div>
            <div class="contact-form-title">
                <h2>
                    SEND US A <span>MESSAGE.</span>
                </h2>
                <p>
                    Start a conversation with the Abella Apparel team.
                </p>
            </div>
            <?php if (!empty($formMessage)): ?>
                <div
                    id="contact-form-message"
                    class="contact-message error"
                >
                    <?= htmlspecialchars($formMessage) ?>
                </div>
            <?php endif; ?>
            <?php if ($isUser): ?>

                <div
                    class="chat-box"
                    id="chat-box"
                >
                    <?php if (empty($messages)): ?>
                        <div class="chat-empty">
                            <div class="chat-empty-inner">
                                <div class="chat-empty-avatar">
                                    AA
                                </div>
                                <h3>
                                    START A CONVERSATION
                                </h3>
                                <p>
                                    Send us a message about your
                                    order, products, sizing, or
                                    anything else you'd like to ask.
                                </p>
                            </div>
                        </div>

                    <?php else: ?>
                        <?php foreach ($messages as $msg): ?>
                            <?php
                            $isCustomer =
                                strtolower($msg['sender']) === 'customer';
                            $messageClass =
                                $isCustomer
                                ? 'customer'
                                : 'admin';
                            ?>
                            <div
                                class="message-row <?= $messageClass ?>"
                            >
                                <?php if (!$isCustomer): ?>
                                    <div class="message-avatar admin-avatar">
                                        AA
                                    </div>
                                <?php endif; ?>
                                <div class="message-content">
                                    <div class="message-sender">

                                        <?= $isCustomer
                                            ? 'YOU'
                                            : 'ABELLA APPAREL' ?>
                                    </div>
                                    <div
                                        class="message-bubble <?= $isCustomer
                                            ? 'customer-bubble'
                                            : 'admin-bubble' ?>"
                                    >
                                        <?= nl2br(
                                            htmlspecialchars(
                                                $msg['message']
                                            )
                                        ) ?>
                                    </div>
                                    <div class="message-meta">
                                        <span class="message-time">
                                            <?= date(
                                                'M d • h:i A',
                                                strtotime(
                                                    $msg['created_at']
                                                )
                                            ) ?>
                                        </span>
                                        <?php if ($isCustomer): ?>
                                            <span
                                                class="customer-message-status <?= (int)$msg['is_read'] === 1
                                                    ? 'read'
                                                    : '' ?>"
                                            >
                                                <?= (int)$msg['is_read'] === 1
                                                    ? '✓✓'
                                                    : '✓' ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if ($isCustomer): ?>
                                    <div class="message-avatar customer-avatar">
                                        YOU
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div class="chat-composer">
                    <form
                        class="contact-form"
                        method="POST"
                        action="contact.php"
                        id="message-form"
                    >
                        <div class="contact-field">
                            <textarea
                                id="message"
                                name="message"
                                placeholder="Write a message..."
                                maxlength="2000"
                                required
                            ><?= htmlspecialchars($postMessage) ?></textarea>
                        </div>
                        <button
                            type="submit"
                            class="contact-submit"
                            aria-label="Send message"
                            title="Send message"
                        >
                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <path
                                    d="M22 2L11 13"
                                ></path>
                                <path
                                    d="M22 2L15 22L11 13L2 9L22 2Z"
                                ></path>
                            </svg>
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <div class="chat-login-message">
                    <p>
                        Please log in to your customer account
                        before starting a conversation with us.
                    </p>
                    <a
                        href="login.php"
                        class="chat-login-link"
                    >
                        LOGIN TO MESSAGE US
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </section>
    <section class="contact-banner">
        <div class="contact-banner-inner">
            <div class="contact-label">
                ABELLA APPAREL
            </div>
            <h2>
                WEAR YOUR <span>IDENTITY.</span>
            </h2>
            <p>
                Thank you for being part of the Abella Apparel
                journey. We create with passion and design
                for those who are not afraid to stand out.
            </p>
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

(function () {

    function scrollChatToBottom() {
        var chatBox =
            document.getElementById('chat-box');
        if (!chatBox) {
            return;
        }
        chatBox.scrollTop =
            chatBox.scrollHeight;
    }

    window.addEventListener(
        'load',
        function () {
            scrollChatToBottom();
            requestAnimationFrame(function () {
                scrollChatToBottom();
                requestAnimationFrame(function () {
                    scrollChatToBottom();
                });
            });
            setTimeout(function () {
                scrollChatToBottom();
            }, 100);
        }
    );
})();

(function () {
    var textarea =
        document.getElementById('message');
    if (!textarea) {
        return;
    }

    function resizeTextarea() {
        textarea.style.height = '48px';
        var newHeight =
            Math.min(
                textarea.scrollHeight,
                120
            );
        textarea.style.height =
            newHeight + 'px';
    }

    textarea.addEventListener(
        'input',
        resizeTextarea
    );

    resizeTextarea();

})();

(function () {
    var textarea =
        document.getElementById('message');
    var form =
        document.getElementById('message-form');
    var chatBox =
        document.getElementById('chat-box');
    if (!textarea || !form || !chatBox) {
        return;
    }
    function escapeHtml(text) {
        var div =
            document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function sendMessage() {
        var message =
            textarea.value.trim();
        if (message === '') {
            return;
        }
        var pageScroll =
            window.scrollY;
        var submitButton =
            form.querySelector(
                '.contact-submit'
            );

        if (submitButton) {
            submitButton.disabled = true;
        }

        var formData =
            new FormData(form);

        fetch(
            'contact.php',
            {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With':
                        'XMLHttpRequest'
                }
            }
        )
        .then(function (response) {
            return response.json();
        })
        .then(function (data) {
            if (!data.success) {
                var errorBox =
                    document.getElementById(
                        'contact-form-message'
                    );
                if (!errorBox) {
                    errorBox =
                        document.createElement(
                            'div'
                        );
                    errorBox.id =
                        'contact-form-message';
                    errorBox.className =
                        'contact-message error';
                    var title =
                        document.querySelector(
                            '.contact-form-title'
                        );
                    if (title) {

                        title.parentNode.insertBefore(
                            errorBox,
                            title.nextSibling
                        );
                    }
                }
                errorBox.textContent =
                    data.message ||
                    'Unable to send message.';
                window.scrollTo(
                    0,
                    pageScroll
                );
                return;
            }
            var emptyChat =
                chatBox.querySelector(
                    '.chat-empty'
                );
            if (emptyChat) {
                emptyChat.remove();
            }
            var row =
                document.createElement('div');
            row.className =
                'message-row customer';
            row.innerHTML =
                '<div class="message-content">' +
                    '<div class="message-sender">' +
                        'YOU' +
                    '</div>' +
                    '<div class="message-bubble customer-bubble">' +
                        escapeHtml(
                            data.message
                        ).replace(
                            /\n/g,
                            '<br>'
                        ) +
                    '</div>' +
                    '<div class="message-meta">' +
                        '<span class="message-time">' +
                            escapeHtml(
                                data.time
                            ) +
                        '</span>' +
                        '<span class="customer-message-status">' +
                            '✓' +
                        '</span>' +
                    '</div>' +
                '</div>' +
                '<div class="message-avatar customer-avatar">' +
                    'YOU' +
                '</div>';
            chatBox.appendChild(row);
            textarea.value = '';
            textarea.style.height =
                '48px';
            chatBox.scrollTop =
                chatBox.scrollHeight;
            window.scrollTo(
                0,
                pageScroll
            );
        })
        .catch(function (error) {
            console.error(
                'Message sending error:',
                error
            );
            window.scrollTo(
                0,
                pageScroll
            );
        })
        .finally(function () {
            if (submitButton) {
                submitButton.disabled =
                    false;
            }
        });
    }
    form.addEventListener(
        'submit',
        function (event) {
            event.preventDefault();
            sendMessage();
        }
    );
    textarea.addEventListener(
        'keydown',
        function (event) {
            if (
                event.key === 'Enter' &&
                !event.shiftKey
            ) {
                event.preventDefault();
                if (
                    textarea.value.trim() !== ''
                ) {
                    sendMessage();
                }
            }
        }
    );
})();
(function () {
    var textarea =
        document.getElementById('message');
    if (!textarea) {
        return;
    }
    textarea.addEventListener(
        'focus',
        function () {
            var chatBox =
                document.getElementById(
                    'chat-box'
                );
            if (chatBox) {
                setTimeout(
                    function () {
                        chatBox.scrollTop =
                            chatBox.scrollHeight;
                    },
                    100
                );
            }
        }
    );
})();
</script>
</body>
</html>