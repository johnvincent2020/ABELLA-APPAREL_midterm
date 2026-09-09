<?php
session_start();
require_once 'config.php';
if (
    !isset($_SESSION['logged_in']) ||
    $_SESSION['logged_in'] !== true ||
    ($_SESSION['user_role'] ?? '') !== 'admin'
) {
    header("Location: index.php");
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
$search = trim($_GET['search'] ?? '');
if ($search !== '') {
    $safeSearch = $conn->real_escape_string($search);
    $customerQuery = "
        SELECT
            u.id,
            u.name,
            u.email,
            u.profile_photo,
            u.created_at,
            COUNT(o.id) AS order_count,
            COALESCE(SUM(o.total_amount), 0) AS total_spent,
            MAX(o.created_at) AS last_order
        FROM users u
        LEFT JOIN orders o
            ON u.id = o.user_id
        WHERE u.role = 'user'
        AND (
            u.name LIKE '%$safeSearch%'
            OR u.email LIKE '%$safeSearch%'
        )
        GROUP BY
            u.id,
            u.name,
            u.email,
            u.profile_photo,
            u.created_at
        ORDER BY u.id DESC
    ";
} else {
    $customerQuery = "
        SELECT
            u.id,
            u.name,
            u.email,
            u.created_at,
            u.profile_photo,
            COUNT(o.id) AS order_count,
            COALESCE(SUM(o.total_amount), 0) AS total_spent,
            MAX(o.created_at) AS last_order
        FROM users u
        LEFT JOIN orders o
            ON u.id = o.user_id
        WHERE u.role = 'user'
        GROUP BY
            u.id,
            u.name,
            u.email,
            u.profile_photo,
            u.created_at
        ORDER BY u.id DESC
    ";
}
$customers = [];
$result = $conn->query($customerQuery);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $customers[] = $row;
    }
}
$totalCustomers = 0;
$customersWithOrders = 0;
$totalCustomerOrders = 0;
$totalCustomerSpending = 0;
$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM users
    WHERE role = 'user'
");
if ($result) {
    $row = $result->fetch_assoc();
    $totalCustomers = (int)($row['total'] ?? 0);
}
$result = $conn->query("
    SELECT COUNT(DISTINCT u.id) AS total
    FROM users u
    INNER JOIN orders o
        ON u.id = o.user_id
    WHERE u.role = 'user'
");
if ($result) {
    $row = $result->fetch_assoc();
    $customersWithOrders = (int)($row['total'] ?? 0);
}
$result = $conn->query("
    SELECT COUNT(o.id) AS total
    FROM orders o
    INNER JOIN users u
        ON u.id = o.user_id
    WHERE u.role = 'user'
");
if ($result) {
    $row = $result->fetch_assoc();
    $totalCustomerOrders = (int)($row['total'] ?? 0);
}
$result = $conn->query("
    SELECT COALESCE(SUM(o.total_amount), 0) AS total
    FROM orders o
    INNER JOIN users u
        ON u.id = o.user_id
    WHERE u.role = 'user'
");
if ($result) {
    $row = $result->fetch_assoc();
    $totalCustomerSpending = (float)($row['total'] ?? 0);
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
        Customers - Abella Apparel Admin
    </title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        html {
            background: #000;
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
            color: #ffffff;
        }
        .customer-section {
            background: #111;
            border: 1px solid #292929;
            border-radius: 0;
            overflow: hidden;
        }
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #292929;
            background: #111;
            gap: 20px;
        }
        .section-header h2 {
            font-size: 18px;
            color: #fff;
        }
        .search-form {
            display: flex;
            gap: 8px;
        }
        .search-input {
            width: 280px;
            padding: 11px 13px;
            border: 1px solid #333;
            outline: none;
            font-size: 12px;
            background: #0b0b0b;
            color: #fff;
            border-radius: 0;
        }
        .search-input::placeholder {
            color: #666;
        }
        .search-input:focus {
            border-color: #c49d4c;
        }
        .search-btn {
            background: #c49d4c;
            color: #111;
            border: none;
            padding: 0 18px;
            cursor: pointer;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1px;
            border-radius: 0;
        }
        .search-btn:hover {
            background: #d6b25f;
        }
        .clear-btn {
            display: flex;
            align-items: center;
            padding: 0 12px;
            font-size: 11px;
            color: #888;
        }
        .clear-btn:hover {
            color: #c49d4c;
        }
        .table-container {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: #111;
            min-width: 850px;
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
        tbody tr {
            background: #111;
        }
        tbody tr:hover td {
            background: #181818;
        }
        .customer-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .customer-avatar {
            width: 40px;
            height: 40px;
            min-width: 40px;
            background: #1a1a1a;
            border: 1px solid #333;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #c49d4c;
            font-size: 13px;
            font-weight: 700;
            overflow: hidden;
        }
        .customer-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .customer-avatar span {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
        }
        .customer-details {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .customer-name {
            color: #fff;
            font-size: 13px;
            font-weight: 700;
        }
        .customer-email {
            color: #888;
            font-size: 11px;
        }
        .order-count {
            color: #fff;
            font-weight: 700;
        }
        .money {
            color: #c49d4c;
            font-weight: 700;
        }
        .date-text {
            color: #888;
            font-size: 12px;
        }
        .empty-state {
            padding: 50px 20px;
            text-align: center;
            color: #666;
            font-size: 13px;
        }
        .empty-state strong {
            display: block;
            color: #aaa;
            margin-bottom: 6px;
            font-size: 14px;
        }
        @media (max-width: 1100px) {
            .stats {
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
            .topbar {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            .topbar-right {
                width: 100%;
            }
            .section-header {
                flex-direction: column;
                align-items: flex-start;
            }
            .search-form {
                width: 100%;
            }
            .search-input {
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
            .stats {
                grid-template-columns: 1fr;
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
        <a href="admin_orders.php">
            Orders
        </a>
        <a
            href="admin_customers.php"
            class="active"
        >
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
        <a href="admin_reviews.php">
    Reviews
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
                Customers
            </h1>
            <p>
                Manage your Abella Apparel customers
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
            <h3>
                Total Customers
            </h3>
            <div class="number">
                <?= number_format($totalCustomers) ?>
            </div>
        </div>
        <div class="stat-card">
            <h3>
                Customers With Orders
            </h3>
            <div class="number">
                <?= number_format($customersWithOrders) ?>
            </div>
        </div>
        <div class="stat-card">
            <h3>
                Total Orders
            </h3>
            <div class="number">
                <?= number_format($totalCustomerOrders) ?>
            </div>
        </div>
        <div class="stat-card">
            <h3>
                Total Spending
            </h3>
            <div class="number">
                ₱<?= number_format(
                    $totalCustomerSpending,
                    2
                ) ?>
            </div>
        </div>
    </div>
    <section class="customer-section">
        <div class="section-header">
            <h2>
                Customer List
            </h2>
            <form
                method="GET"
                action="admin_customers.php"
                class="search-form"
            >
                <input
                    type="text"
                    name="search"
                    class="search-input"
                    placeholder="Search customer..."
                    value="<?= htmlspecialchars($search) ?>"
                >
                <button
                    type="submit"
                    class="search-btn"
                >
                    SEARCH
                </button>
                <?php if ($search !== ''): ?>
                    <a
                        href="admin_customers.php"
                        class="clear-btn"
                    >
                        CLEAR
                    </a>
                <?php endif; ?>
            </form>
        </div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>
                            Customer
                        </th>
                        <th>
                            Orders
                        </th>
                        <th>
                            Total Spent
                        </th>
                        <th>
                            Last Order
                        </th>
                        <th>
                            Registered
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($customers) > 0): ?>
                        <?php foreach ($customers as $customer): ?>
                            <?php
                            $customerName =
                                $customer['name'] ??
                                'Unknown Customer';
                            $customerEmail =
                                $customer['email'] ??
                                '';
                            $firstLetter =
                                strtoupper(
                                    substr(
                                        trim($customerName),
                                        0,
                                        1
                                    )
                                );
                            ?>
                            <tr>
                                <td>
                                    <div class="customer-info">
                                        <div class="customer-avatar">
                                            <?php if (
                                                !empty(
                                                    $customer['profile_photo']
                                                )
                                            ): ?>
                                                <img
                                                    src="assets/profiles/<?= htmlspecialchars(
                                                        basename(
                                                            $customer['profile_photo']
                                                        )
                                                    ) ?>"
                                                    alt="<?= htmlspecialchars(
                                                        $customerName
                                                    ) ?>"
                                                    onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                                >
                                                <span style="display:none;">
                                                    <?= htmlspecialchars(
                                                        $firstLetter
                                                    ) ?>
                                                </span>
                                            <?php else: ?>
                                                <span>
                                                    <?= htmlspecialchars(
                                                        $firstLetter
                                                    ) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="customer-details">
                                            <div class="customer-name">
                                                <?= htmlspecialchars(
                                                    $customerName
                                                ) ?>
                                            </div>
                                            <div class="customer-email">

                                                <?= htmlspecialchars(
                                                    $customerEmail
                                                ) ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="order-count">
                                        <?= number_format(
                                            (int)(
                                                $customer['order_count']
                                                ?? 0
                                            )
                                        ) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="money">
                                        ₱<?= number_format(
                                            (float)(
                                                $customer['total_spent']
                                                ?? 0
                                            ),
                                            2
                                        ) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="date-text">
                                        <?php if (
                                            !empty(
                                                $customer['last_order']
                                            )
                                        ): ?>
                                            <?= date(
                                                'M d, Y',
                                                strtotime(
                                                    $customer['last_order']
                                                )
                                            ) ?>
                                        <?php else: ?>
                                            No orders
                                        <?php endif; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="date-text">
                                        <?php if (
                                            !empty(
                                                $customer['created_at']
                                            )
                                        ): ?>
                                            <?= date(
                                                'M d, Y',
                                                strtotime(
                                                    $customer['created_at']
                                                )
                                            ) ?>
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td
                                colspan="5"
                                class="empty-state"
                            >
                                <strong>
                                    No customers found
                                </strong>
                                <?php if ($search !== ''): ?>
                                    No customer matched
                                    "<?= htmlspecialchars(
                                        $search
                                    ) ?>".
                                <?php else: ?>
                                    There are currently no
                                    registered customers.
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
</body>
</html>