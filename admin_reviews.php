<?php
session_start();
require_once 'config.php';



if (
    !isset($_SESSION['logged_in']) ||
    $_SESSION['logged_in'] !== true ||
    !isset($_SESSION['user_role']) ||
    $_SESSION['user_role'] !== 'admin'
) {
    header("Location: login_register.php");
    exit();
}



if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['delete_review_id'])
) {
    $reviewId = (int)$_POST['delete_review_id'];

    if ($reviewId > 0) {
        $stmt = $conn->prepare("
            DELETE FROM reviews
            WHERE id = ?
        ");

        if ($stmt) {
            $stmt->bind_param("i", $reviewId);
            $stmt->execute();
            $stmt->close();

            $_SESSION['review_deleted'] = 'Review deleted successfully.';
        }
    }

    header("Location: admin_reviews.php");
    exit();
}



$reviewDeletedMessage = $_SESSION['review_deleted'] ?? '';
unset($_SESSION['review_deleted']);



$reviews = [];

$sql = "
    SELECT
        r.id,
        r.rating,
        r.review,
        r.created_at,

        u.name AS customer_name,
        u.email AS customer_email,
        u.profile_photo,

        p.name AS product_name,
        p.image AS product_image

    FROM reviews r

    LEFT JOIN users u
        ON r.user_id = u.id

    LEFT JOIN products p
        ON r.product_id = p.id

    ORDER BY
        r.created_at DESC,
        r.id DESC
";

$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $reviews[] = $row;
    }

    $result->free();
}



$adminName = $_SESSION['user_name'] ?? 'Admin';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Reviews - Abella Apparel</title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #000;
            color: #fff;
        }

        a {
            text-decoration: none;
        }

        

        .sidebar {
            width: 250px;
            background: #111;
            color: #fff;
            padding: 30px 20px;
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            z-index: 20;
            border-right: 1px solid #292929;
        }

        .brand {
            padding: 0 12px 35px;
            display: flex;
            align-items: center;
            height: 70px;
        }

        .brand img {
            display: block;
            width: 150px;
            height: auto;
            max-height: 60px;
            object-fit: contain;
            object-position: left center;
        }

        .menu-title {
            color: #888;
            font-size: 10px;
            letter-spacing: 2px;
            margin: 0 12px 12px;
        }

        .nav-menu {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .nav-menu a {
            display: flex;
            align-items: center;
            gap: 0;
            padding: 13px 12px;
            border-radius: 0;
            color: #bbb;
            font-size: 13px;
            transition: 0.2s ease;
        }

        .nav-menu a:hover {
            background: #1d1d1d;
            color: #fff;
        }

        .nav-menu a.active {
            background: #c49d4c;
            color: #111;
            font-weight: 700;
        }

        .logout-link {
            position: absolute;
            left: 20px;
            right: 20px;
            bottom: 25px;
        }

        .logout-link a {
            display: block;
            padding: 13px;
            text-align: center;
            border: 1px solid #444;
            border-radius: 0;
            color: #aaa;
            font-size: 12px;
        }

        .logout-link a:hover {
            border-color: #c49d4c;
            color: #c49d4c;
        }

        

        .main {
            margin-left: 250px;
            min-height: 100vh;
            background: #000;
            color: #fff;
            padding: 30px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-title h1 {
            font-size: 28px;
            font-weight: 800;
            color: #fff;
        }

        .page-title p {
            color: #888;
            font-size: 13px;
            margin-top: 5px;
        }

        .topbar-right {
            display: flex;
            align-items: center;
        }

        .back-btn {
            display: inline-block;
            padding: 10px 16px;
            border: 1px solid #333;
            background: #111;
            color: #fff;
            border-radius: 0;
            font-size: 13px;
            transition: 0.2s ease;
        }

        .back-btn:hover {
            border-color: #c49d4c;
            color: #c49d4c;
        }

        

        .panel {
            background: #111;
            border: 1px solid #292929;
            border-radius: 0;
            overflow: hidden;
        }

        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #292929;
            background: #111;
        }

        .panel-header h2 {
            font-size: 18px;
            color: #fff;
        }

        .panel-body {
            padding: 20px;
        }

        

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #111;
        }

        th {
            background: #1a1a1a;
            color: #c49d4c;
            padding: 14px;
            text-align: left;
            font-size: 11px;
            letter-spacing: 1px;
            text-transform: uppercase;
            border-bottom: 1px solid #333;
        }

        td {
            padding: 14px;
            border-bottom: 1px solid #292929;
            font-size: 13px;
            vertical-align: middle;
            color: #ddd;
        }

        tr:hover td {
            background: #181818;
        }

        tr:last-child td {
            border-bottom: none;
        }

        

        .customer-info {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 180px;
        }

        .customer-photo {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid #333;
            background: #222;
            flex-shrink: 0;
        }

        .customer-photo-placeholder {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: #222;
            border: 1px solid #333;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #888;
            font-size: 10px;
            flex-shrink: 0;
        }

        .customer-name {
            color: #fff;
            font-weight: 700;
            font-size: 12px;
        }

        .customer-email {
            color: #888;
            font-size: 11px;
            margin-top: 3px;
        }

        

        .product-info {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 160px;
        }

        .product-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 0;
            border: 1px solid #333;
            background: #222;
            flex-shrink: 0;
        }

        .product-image-placeholder {
            width: 50px;
            height: 50px;
            background: #222;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 9px;
            color: #888;
            border: 1px solid #333;
            flex-shrink: 0;
        }

        .product-name {
            font-weight: 700;
            color: #fff;
            font-size: 12px;
        }

        

        .rating {
            white-space: nowrap;
        }

        .star {
            color: #c49d4c;
            font-size: 15px;
        }

        .star-empty {
            color: #444;
            font-size: 15px;
        }

        .rating-number {
            color: #888;
            font-size: 11px;
            margin-left: 5px;
        }

        

        .review-text {
            max-width: 400px;
            color: #ccc;
            line-height: 1.5;
            font-size: 12px;
            word-break: break-word;
        }

        .no-review {
            color: #666;
            font-style: italic;
        }

        .date {
            color: #888;
            font-size: 11px;
            white-space: nowrap;
        }

        

        .delete-form {
            margin: 0;
        }

        .delete-btn {
            border: 1px solid #5a2929;
            background: #211111;
            color: #f28b8b;
            padding: 8px 12px;
            font-size: 11px;
            cursor: pointer;
            border-radius: 0;
            transition: 0.2s ease;
        }

        .delete-btn:hover {
            border-color: #f28b8b;
            background: #3a1719;
            color: #fff;
        }

        

        .empty {
            padding: 40px;
            text-align: center;
            color: #888;
            font-size: 12px;
        }

        

        #review-success-message {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 9999;

            background: #17351d;
            color: #7edb8a;

            border: 1px solid #2d5c36;

            padding: 12px 20px;

            font-size: 13px;
            font-weight: 600;

            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.4);

            opacity: 1;

            transition: opacity 0.3s ease;

            pointer-events: none;
        }

        

        @media (max-width: 1100px) {

            .table-container {
                overflow-x: auto;
            }

        }

        @media (max-width: 700px) {

            .sidebar {
                width: 200px;
            }

            .main {
                margin-left: 200px;
                padding: 20px;
            }

            .topbar {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .topbar-right {
                width: 100%;
            }

        }

        @media (max-width: 500px) {

            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
                padding: 20px;
            }

            .main {
                margin-left: 0;
                padding: 15px;
            }

            .brand {
                justify-content: center;
            }

            .brand img {
                object-position: center;
            }

            .logout-link {
                position: static;
                margin-top: 20px;
            }

            .nav-menu {
                flex-direction: row;
                flex-wrap: wrap;
            }

            .nav-menu a {
                padding: 10px;
            }

            .topbar {
                margin-bottom: 20px;
            }

            .page-title h1 {
                font-size: 23px;
            }

            .page-title p {
                font-size: 11px;
            }

        }

    </style>
</head>

<body>

<?php if ($reviewDeletedMessage): ?>

    <div id="review-success-message">
        <?= htmlspecialchars($reviewDeletedMessage) ?>
    </div>

<?php endif; ?>




<aside class="sidebar">

    <div class="brand">
        <img src="assets/header-logo.png" alt="Abella Apparel">
    </div>

    <div class="menu-title">MAIN MENU</div>

    <nav class="nav-menu">

        <a href="admin_page.php">
            Dashboard
        </a>

        <a href="admin_orders.php">
            Orders
        </a>

        <a href="admin_customers.php">
            Customers
        </a>

        <a href="admin_products.php">
            Products
        </a>

        <a href="admin_stock.php">
            Stock
        </a>

        <a href="admin_messages.php">
            Messages
        </a>

        <a href="admin_reviews.php" class="active">
            Reviews
        </a>

        <a href="admin_subscribers.php">
            Subscribers
        </a>

        <a href="index.php" target="_blank">
            View Store
        </a>

    </nav>

    <div class="logout-link">
        <a href="logout.php">
            LOGOUT
        </a>
    </div>

</aside>




<main class="main">

    <div class="topbar">

        <div class="page-title">

            <h1>Reviews</h1>

            <p>
                Manage customer reviews and feedback.
            </p>

        </div>

        <div class="topbar-right">

           <a href="javascript:history.back()" class="back-btn">
    BACK
</a>

        </div>

    </div>


    

    <div class="panel">

        <div class="panel-header">

            <h2>Customer Reviews</h2>

        </div>

        <div class="panel-body">

            <?php if (empty($reviews)): ?>

                <div class="empty">
                    No customer reviews yet.
                </div>

            <?php else: ?>

                <div class="table-container">

                    <table>

                        <thead>

                            <tr>
                                <th>Customer</th>
                                <th>Product</th>
                                <th>Rating</th>
                                <th>Feedback</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>

                        </thead>

                        <tbody>

                        <?php foreach ($reviews as $review): ?>

                            <tr>

                                

                                <td>

                                    <div class="customer-info">

                                        <?php
                                        $profilePhoto = trim((string)($review['profile_photo'] ?? ''));
                                        $profilePhotoSrc = '';

                                        if ($profilePhoto !== '') {

                                            if (
                                                strpos($profilePhoto, 'http://') === 0 ||
                                                strpos($profilePhoto, 'https://') === 0
                                            ) {
                                                $profilePhotoSrc = $profilePhoto;
                                            } else {
                                                $profilePhotoSrc = 'assets/profiles/' . basename($profilePhoto);
                                            }
                                        }
                                        ?>

                                        <?php if ($profilePhotoSrc !== ''): ?>

                                            <img
                                                src="<?= htmlspecialchars($profilePhotoSrc) ?>"
                                                alt="Customer"
                                                class="customer-photo"
                                                onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                            >

                                            <div
                                                class="customer-photo-placeholder"
                                                style="display:none;"
                                            >
                                                USER
                                            </div>

                                        <?php else: ?>

                                            <div class="customer-photo-placeholder">
                                                USER
                                            </div>

                                        <?php endif; ?>


                                        <div>

                                            <div class="customer-name">
                                                <?= htmlspecialchars($review['customer_name'] ?? 'Unknown Customer') ?>
                                            </div>

                                            <div class="customer-email">
                                                <?= htmlspecialchars($review['customer_email'] ?? '') ?>
                                            </div>

                                        </div>

                                    </div>

                                </td>


                                

                                <td>

                                    <div class="product-info">

                                        <?php
                                        $productImage = trim((string)($review['product_image'] ?? ''));
                                        ?>

                                        <?php if ($productImage !== ''): ?>

                                            <img
                                                src="assets/<?= htmlspecialchars(basename($productImage)) ?>"
                                                alt="Product"
                                                class="product-image"
                                            >

                                        <?php else: ?>

                                            <div class="product-image-placeholder">
                                                NO IMAGE
                                            </div>

                                        <?php endif; ?>


                                        <div class="product-name">

                                            <?= htmlspecialchars($review['product_name'] ?? 'Unknown Product') ?>

                                        </div>

                                    </div>

                                </td>


                                

                                <td>

                                    <div class="rating">

                                        <?php
                                        $rating = (int)$review['rating'];

                                        for ($i = 1; $i <= 5; $i++):
                                        ?>

                                            <?php if ($i <= $rating): ?>

                                                <span class="star">★</span>

                                            <?php else: ?>

                                                <span class="star-empty">★</span>

                                            <?php endif; ?>

                                        <?php endfor; ?>

                                        <span class="rating-number">
                                            <?= $rating ?>/5
                                        </span>

                                    </div>

                                </td>


                                

                                <td>

                                    <?php if (
                                        isset($review['review']) &&
                                        trim($review['review']) !== ''
                                    ): ?>

                                        <div class="review-text">
                                            <?= nl2br(htmlspecialchars($review['review'])) ?>
                                        </div>

                                    <?php else: ?>

                                        <span class="no-review">
                                            No written feedback
                                        </span>

                                    <?php endif; ?>

                                </td>


                                

                                <td>

                                    <div class="date">

                                        <?= htmlspecialchars(
                                            date(
                                                'M d, Y h:i A',
                                                strtotime($review['created_at'])
                                            )
                                        ) ?>

                                    </div>

                                </td>


                                

                                <td>

                                    <form
                                        method="POST"
                                        class="delete-form"
                                        onsubmit="return confirm('Are you sure you want to delete this review?');"
                                    >

                                        <input
                                            type="hidden"
                                            name="delete_review_id"
                                            value="<?= (int)$review['id'] ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="delete-btn"
                                        >
                                            DELETE
                                        </button>

                                    </form>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </div>

    </div>

</main>




<script>

setTimeout(function () {

    const message = document.getElementById('review-success-message');

    if (message) {

        message.style.opacity = '0';

        setTimeout(function () {

            message.remove();

        }, 300);

    }

}, 1000);

</script>

</body>
</html>