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

$unreadMessages = 0;

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM messages
    WHERE sender = 'customer'
    AND is_read = 0
");

if ($result) {
    $row = $result->fetch_assoc();
    $unreadMessages = (int)($row['total'] ?? 0);
}
$allowed_statuses = [
    'Pending',
    'Processing',
    'Shipped',
    'Delivered'
];

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['update_status'])
) {

    $order_id = (int)($_POST['order_id'] ?? 0);
    $status = trim($_POST['status'] ?? '');

    if (
        $order_id > 0 &&
        in_array($status, $allowed_statuses, true)
    ) {

        $stmt = $conn->prepare(
            "UPDATE orders SET status = ? WHERE id = ?"
        );

        if ($stmt) {

            $stmt->bind_param(
                "si",
                $status,
                $order_id
            );

            $stmt->execute();
            $stmt->close();
        }
    }

    header(
        "Location: ./admin_orders.php#order-{$order_id}"
    );

    exit();
}
$orders = [];

$sql = "
    SELECT
        id,
        user_id,
        customer_name,
        email,
        phone,
        address,
        city,
        postal_code,
        payment_method,
        total_amount,
        status,
        created_at
    FROM orders
    ORDER BY created_at DESC
";

$result = $conn->query($sql);

if ($result) {

    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
}

function getOrderItems($conn, $order_id)
{
    $items = [];

    $stmt = $conn->prepare("
        SELECT
            oi.product_name,
            oi.product_price,
            oi.quantity,
            oi.subtotal,
            p.image
        FROM order_items oi
        LEFT JOIN products p
            ON oi.product_name = p.name
        WHERE oi.order_id = ?
    ");

    if (!$stmt) {
        return $items;
    }

    $stmt->bind_param(
        "i",
        $order_id
    );

    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }

    $stmt->close();

    return $items;
}


$total_orders = count($orders);

$pending = 0;
$processing = 0;
$shipped = 0;
$delivered = 0;

foreach ($orders as $order) {

    switch ($order['status']) {

        case 'Pending':
            $pending++;
            break;

        case 'Processing':
            $processing++;
            break;

        case 'Shipped':
            $shipped++;
            break;

        case 'Delivered':
            $delivered++;
            break;
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
    Orders - Abella Apparel Admin
</title>
<style>
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}
body {

    font-family:
        Arial,
        Helvetica,
        sans-serif;

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
.message-notification {
    margin-left: auto;
    min-width: 20px;
    height: 20px;
    padding: 0 6px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #c49d4c;
    color: #111;
    font-size: 10px;
    font-weight: 700;
    border-radius: 50%;
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
.page-title {
    flex: 1;
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
    gap: 15px;
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
.stats {
    display: grid;
    grid-template-columns:
    repeat(5, 1fr);
    gap: 15px;
    margin-bottom: 25px;
}
.stat-card {
    background: #111;
    border: 1px solid #292929;
    padding: 22px;
    border-radius: 0;
    min-height: 105px;
}
.stat-title {
    font-size: 11px;
    color: #888;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 10px;
}
.stat-number {
    font-size: 22px;
    font-weight: 700;
    color: #fff;
}
.orders-container {
    display: flex;
    flex-direction: column;
    gap: 20px;
}
.order-card {
    background: #111;
    border: 1px solid #292929;
    border-radius: 0;
    overflow: hidden;
    color: #fff;
    scroll-margin-top: 25px;
}
.order-header {
    background: #111;
    padding: 20px 22px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    border-bottom: 1px solid #292929;
}
.order-id {
    font-weight: 700;
    color: #c49d4c;
    font-size: 17px;
}
.order-date {
    font-size: 12px;
    color: #888;
    margin-top: 5px;
}
.status-form {
    display: flex;
    align-items: center;
    gap: 8px;
}
.status-form select {
    padding: 9px 12px;
    border: 1px solid #333;
    background: #181818;
    color: #fff;
    font-size: 13px;
    border-radius: 0;
    cursor: pointer;
    outline: none;
}
.status-form select:focus {
    border-color: #c49d4c;
}
.status-form button {
    padding: 9px 14px;
    border: none;
    background: #c49d4c;
    color: #111;
    font-weight: 700;
    font-size: 12px;
    border-radius: 0;
    cursor: pointer;
    transition: 0.2s ease;
}
.status-form button:hover {
    background: #fff;
}
.customer-section {
    padding: 20px 22px;
    background: #111;
    border-bottom: 1px solid #292929;
}
.section-title {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #c49d4c;
    margin-bottom: 15px;
    font-weight: 700;
}
.customer-grid {
    display: grid;
    grid-template-columns:
    repeat(4, 1fr);
    gap: 20px;
}
.customer-item {
    min-width: 0;
}
.customer-item strong {
    display: block;
    font-size: 12px;
    margin-bottom: 5px;
    color: #888;
    font-weight: 600;
}
.customer-item span {
    font-size: 13px;
    color: #ddd;
    line-height: 1.5;
    word-break: break-word;
}
.products-section {
    padding: 20px 22px;
    background: #111;
    border-bottom: 1px solid #292929;
}
.product-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.product-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 12px;
    background: #181818;
    border: 1px solid #292929;
    border-radius: 0;
}
.product-image {
    width: 65px;
    height: 65px;
    object-fit: cover;
    border: 1px solid #333;
    border-radius: 0;
    background: #222;
}
.product-info {
    flex: 1;
}
.product-name {
    font-weight: 700;
    font-size: 14px;
    margin-bottom: 5px;
    color: #fff;
}
.product-details {
    font-size: 12px;
    color: #888;
}
.product-subtotal {
    font-weight: 700;
    font-size: 14px;
    color: #c49d4c;
}
.order-footer {
    background: #111;
    padding: 18px 22px;
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 30px;
}
.payment {
    font-size: 13px;
    color: #888;
}
.payment strong {
    color: #ddd;
}
.total {
    font-size: 18px;
    font-weight: 700;
    color: #fff;
}
.total span {
    color: #c49d4c;
}
.empty {
    background: #111;
    border: 1px solid #292929;
    padding: 60px 20px;
    text-align: center;
    border-radius: 0;
    color: #fff;
}
.empty h2 {
    margin-bottom: 8px;
    font-size: 20px;
}
.empty p {
    color: #888;
    font-size: 13px;
}
@media (max-width: 1100px) {
    .stats {
        grid-template-columns:
            repeat(2, 1fr);
    }
    .customer-grid {
        grid-template-columns:
            repeat(2, 1fr);
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
    .stats {
        grid-template-columns: 1fr;
    }
    .topbar {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    .topbar-right {
        width: 100%;
    }
    .order-header {
        flex-direction: column;
        align-items: flex-start;
    }
    .status-form {
        width: 100%;
    }
    .status-form select {
        flex: 1;
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
    .customer-grid {
        grid-template-columns: 1fr;
    }
    .product-item {
        align-items: flex-start;
    }
    .order-footer {
        flex-direction: column;
        align-items: flex-end;
        gap: 10px;
    }
}
</style>
</head>
<body>
<aside class="sidebar">
    <div class="brand">
        <img
            src="assets/header-logo.png"
            alt="Abella Apparel"
        >
    </div>
    <div class="menu-title">
        MAIN MENU
    </div>
    <nav class="nav-menu">
        <a href="admin_page.php">
            Dashboard
        </a>
        <a
            href="admin_orders.php"
            class="active"
        >
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
            <?php if ($unreadMessages > 0): ?>
                <span class="message-notification">
                    <?= $unreadMessages ?>
                </span>
            <?php endif; ?>
        </a>
        <a href="admin_subscribers.php">
            Subscribers
        </a>
        <a
            href="index.php"
            target="_blank"
        >
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
            <h1>
                Manage Orders
            </h1>
            <p>
                View and manage all customer orders.
            </p>
        </div>
        <div class="topbar-right">
            <a
                href="javascript:history.back()"
                class="back-btn"
            >
                BACK
            </a>
        </div>
    </div>
    <div class="stats">
        <div class="stat-card">
            <div class="stat-title">
                Total Orders
            </div>
            <div class="stat-number">
                <?= number_format($total_orders) ?>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-title">
                Pending
            </div>
            <div class="stat-number">
                <?= number_format($pending) ?>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-title">
                Processing
            </div>
            <div class="stat-number">
                <?= number_format($processing) ?>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-title">
                Shipped
            </div>
            <div class="stat-number">
                <?= number_format($shipped) ?>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-title">
                Delivered
            </div>
            <div class="stat-number">
                <?= number_format($delivered) ?>
            </div>
        </div>
    </div>
    <div class="orders-container">
        <?php if (empty($orders)): ?>
            <div class="empty">
                <h2>
                    No Orders Yet
                </h2>
                <p>
                    Customer orders will appear here.
                </p>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <?php
                $items = getOrderItems(
                    $conn,
                    $order['id']
                );
                ?>
                <div
                    class="order-card"
                    id="order-<?= (int)$order['id'] ?>"
                >
                    <div class="order-header">
                        <div>
                            <div class="order-id">
                                ABELLA-<?=
                                    date(
                                        'Y',
                                        strtotime(
                                            $order['created_at']
                                        )
                                    )
                                ?>-<?=
                                    str_pad(
                                        $order['id'],
                                        4,
                                        '0',
                                        STR_PAD_LEFT
                                    )
                                ?>
                            </div>
                            <div class="order-date">

                                <?= date(
                                    'F d, Y h:i A',
                                    strtotime(
                                        $order['created_at']
                                    )
                                ) ?>

                            </div>
                        </div>
                        <form
                            method="POST"
                            class="status-form"
                        >
                            <input
                                type="hidden"
                                name="order_id"
                                value="<?= (int)$order['id'] ?>"
                            >
                            <select name="status">

                                <?php foreach (
                                    $allowed_statuses
                                    as $status
                                ): ?>

                                    <option
                                        value="<?= htmlspecialchars($status) ?>"
                                        <?= $order['status'] === $status
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        <?= htmlspecialchars($status) ?>

                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button
                                type="submit"
                                name="update_status"
                            >
                                UPDATE
                            </button>
                        </form>
                    </div>
                    <div class="customer-section">
                        <div class="section-title">
                            Customer Information
                        </div>
                        <div class="customer-grid">
                            <div class="customer-item">
                                <strong>
                                    Name
                                </strong>
                                <span>
                                    <?= htmlspecialchars(
                                        $order['customer_name'] ?? ''
                                    ) ?>
                                </span>
                            </div>
                            <div class="customer-item">
                                <strong>
                                    Email
                                </strong>
                                <span>
                                    <?= htmlspecialchars(
                                        $order['email'] ?? ''
                                    ) ?>
                                </span>
                            </div>
                            <div class="customer-item">
                                <strong>
                                    Phone
                                </strong>
                                <span>
                                    <?= htmlspecialchars(
                                        $order['phone'] ?? ''
                                    ) ?>
                                </span>
                            </div>
                            <div class="customer-item">
                                <strong>
                                    Payment
                                </strong>
                                <span>
                                    <?= htmlspecialchars(
                                        $order['payment_method'] ?? ''
                                    ) ?>
                                </span>
                            </div>
                            <div class="customer-item">
                                <strong>
                                    Address
                                </strong>
                                <span>
                                    <?= htmlspecialchars(
                                        $order['address'] ?? ''
                                    ) ?>
                                </span>
                            </div>
                            <div class="customer-item">
                                <strong>
                                    City
                                </strong>
                                <span>
                                    <?= htmlspecialchars(
                                        $order['city'] ?? ''
                                    ) ?>
                                </span>
                            </div>
                            <div class="customer-item">
                                <strong>
                                    Postal Code
                                </strong>
                                <span>
                                    <?= htmlspecialchars(
                                        $order['postal_code'] ?? ''
                                    ) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="products-section">
                        <div class="section-title">
                            Ordered Products
                        </div>
                        <div class="product-list">
                            <?php if (empty($items)): ?>
                                <p style="color:#888;">
                                    No product information found.
                                </p>
                            <?php else: ?>
                                <?php foreach ($items as $item): ?>
                                    <div class="product-item">
                                        <?php
                                        if (!empty($item['image'])) {
                                            $image =
                                                'assets/' .
                                                basename(
                                                    $item['image']
                                                );
                                        } else {
                                            $image =
                                                'assets/placeholder.png';
                                        }
                                        ?>
                                        <img
                                            src="<?= htmlspecialchars($image) ?>"
                                            alt="<?= htmlspecialchars($item['product_name']) ?>"
                                            class="product-image"
                                            onerror="this.style.display='none';"
                                        >
                                        <div class="product-info">
                                            <div class="product-name">
                                                <?= htmlspecialchars(
                                                    $item['product_name']
                                                ) ?>
                                            </div>
                                            <div class="product-details">
                                                ₱<?= number_format(
                                                    (float)$item['product_price'],
                                                    2
                                                ) ?>
                                                ×
                                                <?= (int)$item['quantity'] ?>
                                            </div>
                                        </div>
                                        <div class="product-subtotal">
                                            ₱<?= number_format(
                                                (float)$item['subtotal'],
                                                2
                                            ) ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="order-footer">
                        <div class="payment">
                            Payment:
                            <strong>
                                <?= htmlspecialchars(
                                    $order['payment_method'] ?? ''
                                ) ?>
                            </strong>
                        </div>
                        <div class="total">
                            Total:
                            <span>
                                ₱<?= number_format(
                                    (float)$order['total_amount'],
                                    2
                                ) ?>
                            </span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>
</body>
</html>