<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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


if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['action']) &&
    $_POST['action'] === 'update_stock'
) {

    header('Content-Type: application/json; charset=UTF-8');

    $productId = isset($_POST['product_id'])
        ? (int)$_POST['product_id']
        : 0;

    $stock = isset($_POST['stock'])
        ? (int)$_POST['stock']
        : -1;


    if ($productId <= 0) {

        echo json_encode([
            'success' => false,
            'message' => 'Invalid product ID.'
        ]);

        exit();
    }


    if ($stock < 0) {

        echo json_encode([
            'success' => false,
            'message' => 'Stock cannot be negative.'
        ]);

        exit();
    }

    $checkStmt = $conn->prepare("
        SELECT id
        FROM products
        WHERE id = ?
        LIMIT 1
    ");

    if (!$checkStmt) {

        echo json_encode([
            'success' => false,
            'message' => 'Database error.'
        ]);

        exit();
    }

    $checkStmt->bind_param("i", $productId);
    $checkStmt->execute();

    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows === 0) {

        $checkStmt->close();

        echo json_encode([
            'success' => false,
            'message' => 'Product not found.'
        ]);

        exit();
    }

    $checkStmt->close();


    $stmt = $conn->prepare("
        UPDATE products
        SET stock = ?
        WHERE id = ?
    ");

    if (!$stmt) {

        echo json_encode([
            'success' => false,
            'message' => 'Failed to prepare database query.'
        ]);

        exit();
    }

    $stmt->bind_param(
        "ii",
        $stock,
        $productId
    );


    if ($stmt->execute()) {

        echo json_encode([
            'success' => true,
            'message' => 'Stock updated successfully.',
            'stock' => $stock
        ]);

    } else {

        echo json_encode([
            'success' => false,
            'message' => 'Failed to update stock.'
        ]);
    }


    $stmt->close();

    exit();
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
");

if ($result) {

    while ($row = $result->fetch_assoc()) {

        $products[] = $row;
    }
}

$totalProducts = count($products);
$totalStock = 0;
$lowStock = 0;
$outOfStock = 0;
$inStock = 0;

foreach ($products as $product) {
    $stock = (int)$product['stock'];
    $totalStock += $stock;
    if ($stock <= 0) {
        $outOfStock++;
    } elseif ($stock <= 5) {
        $lowStock++;
    } else {
        $inStock++;
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
    Stock - Abella Apparel Admin
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
.stat-card .number.warning {
    color: #e6c86e;
}
.stat-card .number.danger {
    color: #f28b8b;
}
.stat-card .number.green {
    color: #7edb8a;
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
.search-box {
    width: 260px;
    padding: 10px 12px;
    background: #000;
    border: 1px solid #333;
    color: #fff;
    outline: none;
    border-radius: 0;
    font-size: 12px;
}
.search-box:focus {
    border-color: #c49d4c;
}
.search-box::placeholder {

    color: #777;
}
.table-container {
    width: 100%;
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
.product-info {
    display: flex;
    align-items: center;
    gap: 10px;
}
.product-image {
    width: 55px;
    height: 55px;
    object-fit: cover;
    border-radius: 0;
    border: 1px solid #333;
    background: #222;
}
.product-image-placeholder {
    width: 55px;
    height: 55px;
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
.product-id {
    font-size: 10px;
    color: #777;
    margin-top: 3px;
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
.stock-control {
    display: flex;
    align-items: center;
    gap: 5px;
}
.stock-btn {
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #151515;
    border: 1px solid #333;
    color: #fff;
    cursor: pointer;
    font-size: 16px;
    border-radius: 0;
    transition: 0.2s ease;
}
.stock-btn:hover {
    border-color: #c49d4c;
    color: #c49d4c;
    background: #1c1c1c;
}
.stock-input {
    width: 55px;
    height: 30px;
    background: #000;
    border: 1px solid #333;
    color: #fff;
    text-align: center;
    outline: none;
    border-radius: 0;
    font-size: 12px;
}
.stock-input:focus {
    border-color: #c49d4c;
}
.stock-input::-webkit-inner-spin-button,
.stock-input::-webkit-outer-spin-button {
    -webkit-appearance: none;
    margin: 0;
}
.stock-input {
    -moz-appearance: textfield;
}
.status {
    display: inline-block;
    padding: 6px 9px;
    border-radius: 0;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
}
.status-good {
    background: #17351d;
    color: #7edb8a;
}
.status-low {
    background: #3a3015;
    color: #e6c86e;
}
.status-out {
    background: #3a1719;
    color: #f28b8b;
}
.update-btn {
    padding: 9px 14px;
    background: #c49d4c;
    color: #111;
    border: 1px solid #c49d4c;
    border-radius: 0;
    font-size: 10px;
    font-weight: 700;
    cursor: pointer;
    transition: 0.2s ease;
}
.update-btn:hover {
    background: #fff;
    border-color: #fff;
}
.update-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
.empty {
    padding: 50px;
    text-align: center;
    color: #888;
    font-size: 12px;
}
.success-message {
    position: fixed;
    left: 0;
    top: 0;
    padding: 10px 16px;
    background: rgba(17, 17, 17, 0.88);
    border: 1px solid rgba(196, 157, 76, 0.65);
    color: #c49d4c;
    font-size: 12px;
    font-weight: 400;
    letter-spacing: 0.2px;
    z-index: 9999;
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
    white-space: nowrap;
    transform: translate(-50%, -50%);
    transition:
        opacity 0.25s ease,
        visibility 0.25s ease;
}
.success-message.show {
    opacity: 1;
    visibility: visible;
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
    .panel-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    .search-box {
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
        <a href="admin_customers.php">
            Customers
        </a>
        <a href="admin_products.php">
            Products
        </a>
        <a
            href="admin_stock.php"
            class="active"
        >
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
                Stock Management
            </h1>
            <p>
                Manage and update your product inventory.
            </p>
        </div>
      <div class="topbar-right">
    <a
        href="admin_page.php"
        class="back-btn"
    >
        BACK
    </a>
</div>
</div>
    <div class="stats">
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
                Total Stock
            </h3>
            <div class="number gold">
                <?= number_format($totalStock) ?>
            </div>
        </div>
        <div class="stat-card">
            <h3>
                Low Stock
            </h3>
            <div class="number warning">
                <?= number_format($lowStock) ?>
            </div>
        </div>
        <div class="stat-card">
            <h3>
                Out of Stock
            </h3>
            <div class="number danger">
                <?= number_format($outOfStock) ?>
            </div>
        </div>
    </div>
    <div class="panel">
        <div class="panel-header">
            <h2>
                Product Inventory
            </h2>
            <input
                type="text"
                id="searchInput"
                class="search-box"
                placeholder="Search products..."
            >
        </div>
        <?php if (!empty($products)): ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>
                                Image
                            </th>
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
                            <th>
                                Status
                            </th>
                            <th>
                                Action
                            </th>
                        </tr>
                    </thead>
                    <tbody id="productTableBody">
                    <?php foreach ($products as $product): ?>
                        <?php
                        $stock =
                            (int)$product['stock'];
                        if ($stock <= 0) {
                            $statusClass =
                                'status-out';
                            $statusText =
                                'Out of Stock';
                        } elseif ($stock <= 5) {
                            $statusClass =
                                'status-low';
                            $statusText =
                                'Low Stock';
                        } else {
                            $statusClass =
                                'status-good';
                            $statusText =
                                'In Stock';
                        }
                        ?>
                        <tr
                            data-product-id="<?= (int)$product['id'] ?>"
                            data-original-stock="<?= $stock ?>"
                        >
                            <td>
                                <?php if (
                                    !empty(
                                        $product['image']
                                    )
                                ): ?>
                                    <img
                                        class="product-image"
                                        src="assets/<?= htmlspecialchars(
                                            basename(
                                                $product['image']
                                            )
                                        ) ?>"
                                        alt="<?= htmlspecialchars(
                                            $product['name']
                                        ) ?>"
                                        onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                    >
                                    <div
                                        class="product-image-placeholder"
                                        style="display:none;"
                                    >
                                        NO IMAGE
                                    </div>
                                <?php else: ?>
                                    <div
                                        class="product-image-placeholder"
                                    >
                                        NO IMAGE
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="product-info">
                                    <div>
                                        <div class="product-name">

                                            <?= htmlspecialchars(
                                                $product['name']
                                            ) ?>
                                        </div>
                                        <div class="product-id">
                                            ID #<?= (int)$product['id'] ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="product-category">
                                    <?= htmlspecialchars(
                                        $product['category']
                                    ) ?>
                                </span>
                            </td>
                            <td>
                                <span class="price">
                                    ₱<?= number_format(
                                        (float)$product['price'],
                                        2
                                    ) ?>
                                </span>
                            </td>
                            <td>
                                <div class="stock-control">
                                    <button
                                        type="button"
                                        class="stock-btn"
                                        onclick="changeStock(this, -1)"
                                    >
                                        −
                                    </button>
                                    <input
                                        type="number"
                                        class="stock-input"
                                        value="<?= $stock ?>"
                                        min="0"
                                        step="1"
                                        onchange="validateStockInput(this)"
                                    >
                                    <button
                                        type="button"
                                        class="stock-btn"
                                        onclick="changeStock(this, 1)"
                                    >
                                        +
                                    </button>
                                </div>
                            </td>
                            <td>
                                <span
                                    class="status <?= $statusClass ?>"
                                >
                                    <?= $statusText ?>
                                </span>
                            </td>
                            <td>
                                <button
                                    type="button"
                                    class="update-btn"
                                    onclick="updateStock(this)"
                                >
                                    UPDATE
                                </button>
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
</main>
<div
    id="successMessage"
    class="success-message"
>
    Stock updated successfully.
</div>
<script>
function changeStock(button, amount) {
    const row =
        button.closest('tr');
    if (!row) {
        return;
    }
    const input =
        row.querySelector('.stock-input');
    let stock =
        parseInt(input.value, 10);
    if (isNaN(stock)) {
        stock = 0;
    }
    stock += amount;
    if (stock < 0) {
        stock = 0;
    }
    input.value = stock;
    updateRowStatus(
        row,
        stock
    );
}
function validateStockInput(input) {
    let stock =
        parseInt(input.value, 10);
    if (
        isNaN(stock) ||
        stock < 0
    ) {
        stock = 0;
    }
    input.value = stock;
    const row =
        input.closest('tr');
    if (row) {
        updateRowStatus(
            row,
            stock
        );
    }
}
function updateRowStatus(
    row,
    stock
) {
    const status =
        row.querySelector('.status');
    if (!status) {
        return;
    }
    status.classList.remove(
        'status-good',
        'status-low',
        'status-out'
    );
    if (stock <= 0) {
        status.classList.add(
            'status-out'
        );
        status.textContent =
            'Out of Stock';
    } else if (stock <= 5) {
        status.classList.add(
            'status-low'
        );
        status.textContent =
            'Low Stock';
    } else {
        status.classList.add(
            'status-good'
        );
        status.textContent =
            'In Stock';
    }
}
function updateStock(button) {
    const row =
        button.closest('tr');
    if (!row) {
        return;
    }
    const productId =
        row.dataset.productId;
    const input =
        row.querySelector('.stock-input');
    let stock =
        parseInt(input.value, 10);
    if (
        isNaN(stock) ||
        stock < 0
    ) {
        stock = 0;
        input.value = 0;
    }
    const formData =
        new FormData();
    formData.append(
        'action',
        'update_stock'
    );
    formData.append(
        'product_id',
        productId
    );
    formData.append(
        'stock',
        stock
    );
    const originalText =
        button.textContent;
    button.disabled = true;
    fetch(
        'admin_stock.php',
        {
            method: 'POST',
            body: formData
        }
    )
    .then(response => {
        if (!response.ok) {

            throw new Error(
                'Server error.'
            );
        }
        return response.json();
    })
    .then(data => {
        if (!data.success) {
            throw new Error(
                data.message ||
                'Failed to update stock.'
            );
        }
        const updatedStock =
            parseInt(
                data.stock,
                10
            );
        input.value =
            updatedStock;
        row.dataset.originalStock =
            updatedStock;
        updateRowStatus(
            row,
            updatedStock
        );
        recalculateStatistics();
        showSuccess(row);
    })
    .catch(error => {
        const originalStock =
            parseInt(
                row.dataset.originalStock,
                10
            );
        input.value =
            originalStock;
        updateRowStatus(
            row,
            originalStock
        );
        alert(
            error.message ||
            'Something went wrong.'
        );
    })
    .finally(() => {
        button.disabled = false;
    });
}
function recalculateStatistics() {
    const rows =
        document.querySelectorAll(
            '#productTableBody tr'
        );
    let totalProducts = 0;
    let totalStock = 0;
    let lowStock = 0;
    let outOfStock = 0;
    rows.forEach(row => {
        if (
            row.style.display === 'none'
        ) {
            return;
        }
        const input =
            row.querySelector(
                '.stock-input'
            );
        if (!input) {
            return;
        }
        let stock =
            parseInt(
                input.value,
                10
            );
        if (isNaN(stock)) {
            stock = 0;
        }
        totalProducts++;
        totalStock += stock;
        if (stock <= 0) {
            outOfStock++;
        } else if (stock <= 5) {
            lowStock++;
        }
    });
    const statCards =
        document.querySelectorAll(
            '.stat-card .number'
        );
    if (statCards.length >= 4) {
        statCards[0].textContent =
            totalProducts.toLocaleString();
        statCards[1].textContent =
            totalStock.toLocaleString();
        statCards[2].textContent =
            lowStock.toLocaleString();
        statCards[3].textContent =
            outOfStock.toLocaleString();
    }
}
function showSuccess(row) {
    const message =
        document.getElementById('successMessage');
    if (!message || !row) {
        return;
    }
    const rect = row.getBoundingClientRect();
    const centerX =
        rect.left + (rect.width / 2);
    const centerY =
        rect.top + (rect.height / 2);
    message.style.left =
        centerX + 'px';
    message.style.top =
        centerY + 'px';
    message.classList.remove('show');
    void message.offsetWidth;
    message.classList.add('show');
    clearTimeout(
        window.successMessageTimer
    );
    window.successMessageTimer =
        setTimeout(function () {
            message.classList.remove('show');
        }, 1500);
}
const searchInput =
    document.getElementById(
        'searchInput'
    );
if (searchInput) {
    searchInput.addEventListener(
        'input',
        function () {
            const search =
                this.value
                    .toLowerCase()
                    .trim();
            const rows =
                document.querySelectorAll(
                    '#productTableBody tr'
                );
            rows.forEach(
                function (row) {
                    const text =
                        row.textContent
                            .toLowerCase();
                    if (
                        text.includes(search)
                    ) {
                        row.style.display =
                            '';
                    } else {
                        row.style.display =
                            'none';
                    }
                }
            );
        }
    );
}
</script>
</body>
</html>