<?php

session_start();

require_once '../login_register/config.php';


/* =========================================
   LOGIN / USER CHECK
========================================= */

$isLoggedIn = isset($_SESSION['logged_in'])
    && $_SESSION['logged_in'] === true;

$isUser = $isLoggedIn
    && isset($_SESSION['user_role'])
    && $_SESSION['user_role'] === 'user';


/* =========================================
   CART COUNT
========================================= */

$cartCount = 0;

if (isset($_SESSION['cart'])) {

    foreach ($_SESSION['cart'] as $item) {

        $cartCount += (int)$item['quantity'];

    }

}


/* =========================================
   CONTACT FORM
========================================= */

$formMessage = '';
$formSuccess = false;

// Values used to re-fill the fields ONLY when there's a validation error.
// On success we redirect (Post/Redirect/Get), so the fields start empty.
$postName    = '';
$postEmail   = '';
$postSubject = '';
$postMessage = '';

/*
 * If we just redirected here after a successful submit,
 * pick up the flash message from the session and show it once.
 */
if (isset($_SESSION['contact_success'])) {

    $formSuccess = true;

    $formMessage =
        'Thank you, ' .
        htmlspecialchars($_SESSION['contact_success']) .
        '. Your message has been received.';

    unset($_SESSION['contact_success']);

}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (
        empty($name) ||
        empty($email) ||
        empty($subject) ||
        empty($message)
    ) {

        $formMessage = 'Please fill in all fields.';

        // Keep what the user typed so they don't lose it on an error.
        $postName = $name;
        $postEmail = $email;
        $postSubject = $subject;
        $postMessage = $message;

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $formMessage = 'Please enter a valid email address.';

        $postName = $name;
        $postEmail = $email;
        $postSubject = $subject;
        $postMessage = $message;
} else {

    /* =========================================
       SAVE CONTACT MESSAGE
    ========================================= */

    $stmt = $conn->prepare("
        INSERT INTO contact_messages
        (name, email, subject, message)
        VALUES (?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "ssss",
        $name,
        $email,
        $subject,
        $message
    );

    if ($stmt->execute()) {

        $_SESSION['contact_success'] = $name;

        $stmt->close();

        header('Location: contact.php#contact-form-wrapper');
        exit;

    } else {

        $formMessage = 'Sorry, your message could not be sent. Please try again.';

        $postName = $name;
        $postEmail = $email;
        $postSubject = $subject;
        $postMessage = $message;

        $stmt->close();
    }

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

        /* =========================================
           CONTACT PAGE
        ========================================= */

        .contact-page {

            background: #000;

            color: #fff;

            min-height: 100vh;

        }


        /* =========================================
           CONTACT HERO
        ========================================= */

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


        /* =========================================
           CONTACT MAIN
        ========================================= */

        .contact-main {

            max-width: 1180px;

            margin: 0 auto;

            padding: 90px 30px;

            display: grid;

            grid-template-columns: 0.8fr 1.2fr;

            gap: 70px;

            align-items: start;

        }


        /* =========================================
           CONTACT INFORMATION
        ========================================= */

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


        /* =========================================
           CONTACT DETAILS
        ========================================= */

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


        /* =========================================
           CONTACT FORM
        ========================================= */

        .contact-form-wrapper {

            background: #111;

            border: 1px solid #292929;

            padding: 40px;

        }


        .contact-form-title {

            margin-bottom: 30px;

        }


        .contact-form-title h2 {

            font-size: 25px;

            font-weight: 800;

            margin-bottom: 8px;

        }


        .contact-form-title h2 span {

            color: #c49d4c;

        }


        .contact-form-title p {

            color: #777;

            font-size: 12px;

            line-height: 1.7;

        }


        .contact-form {

            display: flex;

            flex-direction: column;

            gap: 18px;

        }


        .contact-form-row {

            display: grid;

            grid-template-columns: 1fr 1fr;

            gap: 18px;

        }


        .contact-field {

            display: flex;

            flex-direction: column;

        }


        .contact-field label {

            color: #aaa;

            font-size: 10px;

            font-weight: 700;

            letter-spacing: 1.5px;

            margin-bottom: 8px;

        }


        .contact-field input,

        .contact-field textarea {

            width: 100%;

            background: #000;

            color: #fff;

            border: 1px solid #292929;

            outline: none;

            padding: 14px;

            font-family: Arial, Helvetica, sans-serif;

            font-size: 12px;

            transition: 0.2s ease;

        }


        .contact-field input {

            height: 46px;

        }


        .contact-field textarea {

            height: 140px;

            resize: vertical;

            min-height: 120px;

        }


        .contact-field input:focus,

        .contact-field textarea:focus {

            border-color: #c49d4c;

        }


        .contact-field input::placeholder,

        .contact-field textarea::placeholder {

            color: #555;

        }


        /* =========================================
           SEND BUTTON
        ========================================= */

        .contact-submit {

            display: inline-block;

            border: none;

            outline: none;

            padding: 14px 28px;

            background: #c49d4c;

            color: #111;

            text-decoration: none;

            font-family: Arial, Helvetica, sans-serif;

            font-size: 11px;

            font-weight: 700;

            letter-spacing: 1.5px;

            cursor: pointer;

            transition: 0.2s ease;

            align-self: flex-start;

        }


        .contact-submit:hover {

            background: #fff;

            color: #111;

        }


        /* =========================================
           FORM MESSAGE
        ========================================= */

        .contact-message {

            padding: 13px 15px;

            margin-bottom: 20px;

            font-size: 11px;

            line-height: 1.6;

            transition: opacity 0.6s ease;

        }


        .contact-message.success {

            background: rgba(196,157,76,0.10);

            border: 1px solid #c49d4c;

            color: #c49d4c;

        }


        .contact-message.error {

            background: rgba(180,60,60,0.10);

            border: 1px solid #7b3d3d;

            color: #d88b8b;

        }


        /* =========================================
           CONTACT BANNER
        ========================================= */

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


        /* =========================================
           RESPONSIVE
        ========================================= */

        @media (max-width: 900px) {

            .contact-main {

                grid-template-columns: 1fr;

                gap: 50px;

            }


            .contact-info {

                max-width: none;

            }


            .contact-form-wrapper {

                padding: 35px;

            }


            .contact-hero h1 {

                font-size: 42px;

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


            .contact-form-wrapper {

                padding: 25px 20px;

            }


            .contact-form-row {

                grid-template-columns: 1fr;

                gap: 18px;

            }


            .contact-banner {

                padding: 60px 20px;

            }


            .contact-banner h2 {

                font-size: 28px;

            }

        }

    </style>

</head>


<body>

<div class="site contact-page">


    <!-- =========================================
         HEADER
    ========================================== -->

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


            <!-- NAVIGATION -->

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


            <!-- ICONS -->

            <div class="icons">


                <!-- SEARCH -->

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


                <!-- ACCOUNT -->

                <a
                    href="<?= $isUser
                        ? 'http://localhost/login_register/user_page.php'
                        : 'http://localhost/login_register/' ?>"
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


                <!-- CART -->

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


    <!-- =========================================
         CONTACT HERO
    ========================================== -->

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


    <!-- =========================================
         CONTACT MAIN
    ========================================== -->

    <section class="contact-main">


        <!-- =====================================
             CONTACT INFORMATION
        ====================================== -->

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


                <!-- EMAIL -->

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


                <!-- PHONE -->

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


                <!-- LOCATION -->

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


                <!-- HOURS -->

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


        <!-- =====================================
             CONTACT FORM
        ====================================== -->

        <div class="contact-form-wrapper" id="contact-form-wrapper">


            <div class="contact-form-title">

                <h2>
                    SEND US A <span>MESSAGE.</span>
                </h2>

                <p>
                    Fill out the form below and we'll get
                    back to you as soon as possible.
                </p>

            </div>


            <?php if (!empty($formMessage)): ?>

                <div
                    id="contact-form-message"
                    class="contact-message
                    <?= $formSuccess ? 'success' : 'error' ?>"
                >

                    <?= $formMessage ?>

                </div>

            <?php endif; ?>


            <form
                class="contact-form"
                method="POST"
                action="contact.php"
            >


                <!-- NAME + EMAIL -->

                <div class="contact-form-row">


                    <div class="contact-field">

                        <label for="name">
                            YOUR NAME
                        </label>

                        <input
                            type="text"
                            id="name"
                            name="name"
                            placeholder="Enter your name"
                            value="<?= htmlspecialchars($postName) ?>"
                            required
                        >

                    </div>


                    <div class="contact-field">

                        <label for="email">
                            EMAIL ADDRESS
                        </label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            placeholder="Enter your email"
                            value="<?= htmlspecialchars($postEmail) ?>"
                            required
                        >

                    </div>


                </div>


                <!-- SUBJECT -->

                <div class="contact-field">

                    <label for="subject">
                        SUBJECT
                    </label>

                    <input
                        type="text"
                        id="subject"
                        name="subject"
                        placeholder="What can we help you with?"
                        value="<?= htmlspecialchars($postSubject) ?>"
                        required
                    >

                </div>


                <!-- MESSAGE -->

                <div class="contact-field">

                    <label for="message">
                        MESSAGE
                    </label>

                    <textarea
                        id="message"
                        name="message"
                        placeholder="Write your message here..."
                        required
                    ><?= htmlspecialchars($postMessage) ?></textarea>

                </div>


                <!-- SUBMIT -->

                <button
                    type="submit"
                    class="contact-submit"
                >
                    SEND MESSAGE
                </button>


            </form>

        </div>

    </section>


    <!-- =========================================
         CONTACT BANNER
    ========================================== -->

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


    <!-- =========================================
         FOOTER
    ========================================== -->

    <footer class="footer">

        <div class="footer-main">


            <!-- BRAND -->

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


                    <!-- FACEBOOK -->

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


                    <!-- INSTAGRAM -->

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


                    <!-- TIKTOK -->

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


            <!-- SHOP -->

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


            <!-- COMPANY -->

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


            <!-- HELP -->

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


            <!-- LEGAL -->

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


        <!-- COPYRIGHT -->

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


<?php if ($formSuccess): ?>

    <script>

        // Fade out and smoothly collapse the "thank you" message after
        // a few seconds, so the form below eases up instead of jumping.
        (function () {

            var msg = document.getElementById('contact-form-message');

            if (!msg) return;

            setTimeout(function () {

                // Lock in the current height so we can animate FROM it.
                var startHeight = msg.offsetHeight;

                msg.style.height = startHeight + 'px';
                msg.style.overflow = 'hidden';

                // Force the browser to register the height above
                // before we change it, so the transition actually runs.
                msg.offsetHeight;

                msg.style.transition =
                    'opacity 0.4s ease, height 0.4s ease 0.2s, ' +
                    'margin 0.4s ease 0.2s, padding 0.4s ease 0.2s';

                msg.style.opacity = '0';

                setTimeout(function () {

                    msg.style.height = '0';
                    msg.style.marginBottom = '0';
                    msg.style.paddingTop = '0';
                    msg.style.paddingBottom = '0';

                }, 400);

                setTimeout(function () {

                    msg.remove();

                }, 850);

            }, 3500);

        })();

    </script>

<?php endif; ?>

</body>

</html>