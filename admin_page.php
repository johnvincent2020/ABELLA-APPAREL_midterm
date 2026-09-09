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

$totalOrders = 0;
$totalCustomers = 0;
$totalProducts = 0;
$totalStock = 0;
$totalSales = 0;


$unreadMessages = 0;

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM messages
    WHERE sender = 'customer'
    AND is_read = 0
");

if ($result) {

    $row = $result->fetch_assoc();

    $unreadMessages = $row['total'] ?? 0;
}

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM orders
");

if ($result) {

    $row = $result->fetch_assoc();

    $totalOrders = $row['total'] ?? 0;
}

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM users
    WHERE role = 'user'
");

if ($result) {

    $row = $result->fetch_assoc();

    $totalCustomers = $row['total'] ?? 0;
}

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM products
");

if ($result) {

    $row = $result->fetch_assoc();

    $totalProducts = $row['total'] ?? 0;
}

$result = $conn->query("
    SELECT COALESCE(SUM(stock), 0) AS total
    FROM products
");

if ($result) {

    $row = $result->fetch_assoc();

    $totalStock = $row['total'] ?? 0;
}

$result = $conn->query("
    SELECT COALESCE(SUM(total_amount), 0) AS total
    FROM orders
    WHERE status != 'Cancelled'
");

if ($result) {

    $row = $result->fetch_assoc();

    $totalSales = $row['total'] ?? 0;
}

$totalSubscribers = 0;

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM subscribers
");

if ($result) {

    $row = $result->fetch_assoc();

    $totalSubscribers = $row['total'] ?? 0;
}

$recentSubscribers = [];

$result = $conn->query("
    SELECT
        s.id,
        s.email,
        s.subscribed_at,
        u.id AS user_id
    FROM subscribers s
    LEFT JOIN users u
        ON u.email = s.email
    ORDER BY s.subscribed_at DESC
    LIMIT 5
");

if ($result) {

    while ($row = $result->fetch_assoc()) {

        $recentSubscribers[] = $row;
    }
}

$recentOrders = [];

$result = $conn->query("
    SELECT
        id,
        customer_name,
        total_amount,
        status,
        created_at
    FROM orders
    ORDER BY created_at DESC
    LIMIT 5
");

if ($result) {

    while ($row = $result->fetch_assoc()) {

        $recentOrders[] = $row;
    }
}

$products = [];

$result = $conn->query("
    SELECT
        id,
        name,
        category,
        price,
        stock,
        image
    FROM products
    ORDER BY id DESC
    LIMIT 6
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
<title>
    Dashboard - Abella Apparel Admin
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
.admin-name {
    color: #888;
    font-size: 13px;
}
.store-btn {
    display: inline-block;
    padding: 10px 16px;
    border: 1px solid #333;
    background: #111;
    color: #fff;
    border-radius: 0;
    font-size: 13px;
    transition: 0.2s ease;
}
.store-btn:hover {
    border-color: #c49d4c;
    color: #c49d4c;
}
.stats {
    display: grid;
    grid-template-columns:
    repeat(4, 1fr);
    gap: 15px;
    margin-bottom: 25px;
}
.stat-card {
    background: #111;
    border: 1px solid #292929;
    padding: 22px;
    border-radius: 0;
}
.stat-card h3 {
    font-size: 12px;
    color: #888;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 10px;
}
.stat-card .number {
    font-size: 20px;
    font-weight: 600;
    color: #fff;
}
.stat-card .number.gold {
    color: #c49d4c;
}
.dashboard-grid {
    display: grid;
    grid-template-columns:
    1.5fr 1fr;
    gap: 20px;
    margin-bottom: 25px;
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
.panel-header a {
    color: #c49d4c;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.5px;
}
.panel-header a:hover {
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
.status {
    display: inline-block;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
}
.status.pending {
    color: #e6c86e;
}
.status.processing {
    color: #aaa;
}
.status.completed {
    color: #7edb8a;
}
.status.cancelled {
    color: #f28b8b;
}
.status.registered {
    color: #7edb8a;
}
.status.guest {
    color: #888;
}
.quick-actions {
    display: grid;
    grid-template-columns:
    repeat(2, 1fr);
    gap: 10px;
}
.quick-action {
    display: block;
    border: 1px solid #333;
    background: #151515;
    padding: 16px;
    color: #aaa;
    font-size: 12px;
    transition: 0.2s ease;
}
.quick-action:hover {
    border-color: #c49d4c;
    color: #c49d4c;
    background: #1c1c1c;
}
.product-info {
    display: flex;
    align-items: center;
    gap: 10px;
}
.product-image {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 0;
    border: 1px solid #333;
    background: #222;
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
}
.product-name {
    font-weight: 700;
    color: #fff;
    font-size: 12px;
}
.product-category {
    font-size: 11px;
    color: #999;
    margin-top: 3px;
}
.price {
    font-weight: 700;
    color: #fff;
}
.stock {
    display: inline-block;
    padding: 6px 9px;
    border-radius: 0;
    font-size: 11px;
    font-weight: 700;
}
.stock-good {
    background: #17351d;
    color: #7edb8a;
}
.stock-low {
    background: #3a3015;
    color: #e6c86e;
}
.stock-out {
    background: #3a1719;
    color: #f28b8b;
}
.empty {
    padding: 40px;
    text-align: center;
    color: #888;
    font-size: 12px;
}
@media (max-width: 1100px) {
    .stats {
        grid-template-columns:
            repeat(2, 1fr);
    }
    .dashboard-grid {
        grid-template-columns: 1fr;
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
    .admin-name {
        display: none;
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
    .quick-actions {
        grid-template-columns: 1fr;
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
        <a
            href="admin_page.php"
            class="active"
        >
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
                Admin Dashboard
            </h1>
            <p>
                Manage your Abella Apparel store from one place.
            </p>
        </div>
        <div class="topbar-right">
            <span class="admin-name">
                Welcome,
                <?= htmlspecialchars(
                    $_SESSION['user_name'] ?? 'Admin'
                ) ?>
            </span>
            <a
                href="index.php"
                target="_blank"
                class="store-btn"
            >
                VIEW STORE
            </a>
        </div>
    </div>
    <div class="stats">
        <div class="stat-card">
            <h3>
                Total Orders
            </h3>
            <div class="number">
                <?= number_format($totalOrders) ?>
            </div>
        </div>
        <div class="stat-card">
            <h3>
                Total Customers
            </h3>
            <div class="number">
                <?= number_format($totalCustomers) ?>
            </div>
        </div>
        <div class="stat-card">
            <h3>
                Total Products
            </h3>
            <div class="number">
                <?= number_format($totalProducts) ?>
            </div>
        </div>
        <div class="stat-card">
            <h3>
                Total Sales
            </h3>
            <div class="number gold">
                ₱<?= number_format(
                    $totalSales,
                    2
                ) ?>
            </div>
        </div>
    </div>
    <div class="dashboard-grid">
        <div class="panel">
            <div class="panel-header">
                <h2>
                    Recent Orders
                </h2>
                <a href="admin_orders.php">
                    VIEW ALL
                </a>
            </div>
            <?php if (!empty($recentOrders)): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>
                                    Order
                                </th>
                                <th>
                                    Customer
                                </th>
                                <th>
                                    Amount
                                </th>
                                <th>
                                    Status
                                </th>
                                <th>
                                    Date
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach (
                            $recentOrders
                            as $order
                        ): ?>
                            <?php
                            $statusClass =
                                strtolower(
                                    trim(
                                        $order['status']
                                    )
                                );
                            ?>
                            <tr>
                                <td>
                                    #<?= htmlspecialchars(
                                        $order['id']
                                    ) ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars(
                                        $order['customer_name']
                                    ) ?>

                                </td>
                                <td>
                                    ₱<?= number_format(
                                        $order['total_amount'],
                                        2
                                    ) ?>
                                </td>
                                <td>
                                    <span
                                        class="status <?= htmlspecialchars(
                                            $statusClass
                                        ) ?>"
                                    >
                                        <?= htmlspecialchars(
                                            $order['status']
                                        ) ?>
                                    </span>
                                </td>
                                <td>
                                    <?= date(
                                        'M d, Y',
                                        strtotime(
                                            $order['created_at']
                                        )
                                    ) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty">
                    No orders found.
                </div>
            <?php endif; ?>
        </div>
        <div class="panel">
            <div class="panel-header">
                <h2>
                    Quick Actions
                </h2>
            </div>
            <div class="panel-body">
                <div class="quick-actions">
                    <a
                        href="admin_products.php"
                        class="quick-action"
                    >
                        Manage Products
                    </a>
                    <a
                        href="admin_stock.php"
                        class="quick-action"
                    >
                        Manage Stock
                    </a>
                    <a
                        href="admin_orders.php"
                        class="quick-action"
                    >
                        View Orders
                    </a>
                    <a
                        href="admin_customers.php"
                        class="quick-action"
                    >
                        View Customers
                    </a>
                    <a
                        href="admin_messages.php"
                        class="quick-action"
                    >
                        Messages
                        <?php if ($unreadMessages > 0): ?>
                            (<?= $unreadMessages ?> New)
                        <?php endif; ?>
                    </a>
                    <a
                        href="admin_subscribers.php"
                        class="quick-action"
                    >
                        View Subscribers
                    </a>
                    <a
                        href="index.php"
                        target="_blank"
                        class="quick-action"
                    >
                        Open Store
                    </a>
                    <a
                        href="logout.php"
                        class="quick-action"
                    >
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="panel">
        <div class="panel-header">
            <h2>
                Product & Stock Overview
            </h2>
            <a href="admin_stock.php">
                MANAGE STOCK
            </a>
        </div>
        <?php if (!empty($products)): ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>
                                Product
                            </th>
                            <th>
                                Category
                            </th>
                            <th>
                                Price
                            </th>
                            <th>
                                Stock
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach (
                        $products
                        as $product
                    ): ?>
                        <?php
                        $stock =
                            (int) $product['stock'];
                        if ($stock === 0) {
                            $stockClass =
                                'stock-out';
                        } elseif ($stock <= 5) {
                            $stockClass =
                                'stock-low';
                        } else {
                            $stockClass =
                                'stock-good';
                        }
                        ?>
                        <tr>
                            <td>
                                <div class="product-info">
                                    <?php if (
                                        !empty(
                                            $product['image']
                                        )
                                    ): ?>
                                        <img
                                            class="product-image"
                                            src="assets/<?=
                                                htmlspecialchars(
                                                    basename(
                                                        $product['image']
                                                    )
                                                )
                                            ?>"
                                            alt="<?= htmlspecialchars(
                                                $product['name']
                                            ) ?>"
                                            onerror="this.style.display='none';"
                                        >
                                    <?php else: ?>
                                        <div
                                            class="product-image-placeholder"
                                        >
                                            NO IMAGE
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <div class="product-name">
                                            <?= htmlspecialchars(
                                                $product['name']
                                            ) ?>
                                        </div>
                                        <div class="product-category">
                                            <?= htmlspecialchars(
                                                $product['category']
                                            ) ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?= htmlspecialchars(
                                    $product['category']
                                ) ?>
                            </td>
                            <td>
                                <span class="price">
                                    ₱<?= number_format(
                                        $product['price'],
                                        2
                                    ) ?>
                                </span>
                            </td>
                            <td>
                                <span
                                    class="stock <?= $stockClass ?>"
                                >
                                    <?= number_format(
                                        $stock
                                    ) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty">
                No products found.
            </div>
        <?php endif; ?>
    </div>
    <div class="panel">
        <div class="panel-header">
            <h2>
                Newsletter Subscribers
            </h2>
            <a href="admin_subscribers.php">
                VIEW ALL (<?= number_format($totalSubscribers) ?>)
            </a>
        </div>
        <?php if (!empty($recentSubscribers)): ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>
                                Email
                            </th>
                            <th>
                                Subscribed On
                            </th>
                            <th>
                                Account Status
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach (
                        $recentSubscribers
                        as $subscriber
                    ): ?>
                        <?php
                        $canLogIn =
                            !empty($subscriber['user_id']);
                        ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars(
                                    $subscriber['email']
                                ) ?>
                            </td>
                            <td>
                                <?= date(
                                    'M d, Y',
                                    strtotime(
                                        $subscriber['subscribed_at']
                                    )
                                ) ?>
                            </td>
                            <td>
                                <?php if ($canLogIn): ?>
                                    <span class="status registered">
                                        Registered
                                    </span>
                                <?php else: ?>
                                    <span class="status guest">
                                        Guest
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty">
                No subscribers yet.
            </div>
        <?php endif; ?>
    </div>
</main>
</body>
</html>