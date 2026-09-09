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
function uploadProductImage($file)
{
    $uploadDir =
        __DIR__ .
        "/assets/";
    if (!is_dir($uploadDir)) {
        mkdir(
            $uploadDir,
            0777,
            true
        );
    }
    if (
        !isset($file) ||
        $file['error'] === UPLOAD_ERR_NO_FILE
    ) {
        return "";
    }
    if (
        $file['error'] !==
        UPLOAD_ERR_OK
    ) {

        return "";
    }
    if (
        $file['size'] >
        5 * 1024 * 1024
    ) {
        return "";
    }
    $allowedTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif'
    ];
    $imageInfo =
        getimagesize(
            $file['tmp_name']
        );
    if (
        $imageInfo === false
    ) {
        return "";
    }
    $mime =
        $imageInfo['mime'];
    if (
        !isset(
            $allowedTypes[$mime]
        )
    ) {
        return "";
    }
    $extension =
        $allowedTypes[$mime];
    $fileName =
        'product_' .
        time() .
        '_' .
        uniqid() .
        '.' .
        $extension;
    $destination =
        $uploadDir .
        $fileName;
    if (
        move_uploaded_file(
            $file['tmp_name'],
            $destination
        )
    ) {
        return $fileName;
    }
    return "";
}
if (
    isset($_POST['add_product'])
) {
    $name =
        trim(
            $_POST['name'] ?? ''
        );
    $price =
        (float)(
            $_POST['price'] ?? 0
        );
    $category =
        trim(
            $_POST['category'] ?? ''
        );
    $stock =
        (int)(
            $_POST['stock'] ?? 0
        );
    $is_new =
        isset(
            $_POST['is_new']
        )
        ? 1
        : 0;
    $image =
        uploadProductImage(
            $_FILES['product_image'] ?? null
        );
    if (
        $name !== '' &&
        $price >= 0 &&
        $category !== '' &&
        $stock >= 0
    ) {
        $stmt =
            $conn->prepare("
                INSERT INTO products
                (
                    name,
                    price,
                    category,
                    image,
                    stock,
                    is_new
                )
                VALUES (?, ?, ?, ?, ?, ?)
            ");
        if ($stmt) {
            $stmt->bind_param(
                "sdssii",
                $name,
                $price,
                $category,
                $image,
                $stock,
                $is_new
            );
            $stmt->execute();
            $stmt->close();
        }
    }
    header(
        "Location: admin_products.php"
    );
    exit();
}
if (
    isset($_POST['update_product'])
) {
    $id =
        (int)(
            $_POST['id'] ?? 0
        );
    $name =
        trim(
            $_POST['name'] ?? ''
        );
    $price =
        (float)(
            $_POST['price'] ?? 0
        );
    $category =
        trim(
            $_POST['category'] ?? ''
        );
    $stock =
        (int)(
            $_POST['stock'] ?? 0
        );
    $is_new =
        isset(
            $_POST['is_new']
        )
        ? 1
        : 0;
    if (
        $id > 0 &&
        $name !== '' &&
        $price >= 0 &&
        $category !== '' &&
        $stock >= 0
    ) {
        $stmt =
            $conn->prepare("
                SELECT image
                FROM products
                WHERE id = ?
            ");
        $stmt->bind_param(
            "i",
            $id
        );
        $stmt->execute();
        $result =
            $stmt->get_result();
        $oldProduct =
            $result->fetch_assoc();
        $stmt->close();
        $image =
            $oldProduct['image'] ?? '';
        $newImage =
            uploadProductImage(
                $_FILES['product_image'] ?? null
            );
        if (
            $newImage !== ''
        ) {
            $image =
                $newImage;
            if (
                !empty(
                    $oldProduct['image']
                )
            ) {
                $oldImagePath =
                    __DIR__ .
                    "/assets/" .
                    basename(
                        $oldProduct['image']
                    );
                if (
                    file_exists(
                        $oldImagePath
                    )
                ) {
                    @unlink(
                        $oldImagePath
                    );
                }
            }
        }
        $stmt =
            $conn->prepare("
                UPDATE products
                SET
                    name = ?,
                    price = ?,
                    category = ?,
                    image = ?,
                    stock = ?,
                    is_new = ?
                WHERE id = ?
            ");
        if ($stmt) {
            $stmt->bind_param(
                "sdssiii",
                $name,
                $price,
                $category,
                $image,
                $stock,
                $is_new,
                $id
            );
            $stmt->execute();
            $stmt->close();
        }
    }
    header(
        "Location: admin_products.php"
    );
    exit();
}
if (
    isset($_GET['delete'])
) {
    $id =
        (int)$_GET['delete'];
    if (
        $id > 0
    ) {
        $stmt =
            $conn->prepare("
                SELECT image
                FROM products
                WHERE id = ?
            ");
        $stmt->bind_param(
            "i",
            $id
        );
        $stmt->execute();
        $result =
        $stmt->get_result();
        $product =
        $result->fetch_assoc();
        $stmt->close();
        $stmt =
        $conn->prepare("
                DELETE FROM products
                WHERE id = ?
            ");
        $stmt->bind_param(
            "i",
            $id
        );
        $stmt->execute();
        $stmt->close();
        if (
            !empty(
                $product['image']
            )
        ) {
            $imagePath =
                __DIR__ .
                "/assets/" .
                basename(
                    $product['image']
                );
            if (
                file_exists(
                    $imagePath
                )
            ) {

                @unlink(
                    $imagePath
                );
            }
        }
    }
    header(
        "Location: admin_products.php"
    );

    exit();
}
$result =
    $conn->query("
        SELECT *
        FROM products
        ORDER BY id DESC
    ");
$products = [];
if ($result) {
    while (
        $row =
        $result->fetch_assoc()
    ) {
        $products[] =
            $row;
    }
}
$totalProducts =
    count($products);
$totalStock =
    0;
$lowStock =
    0;
$outOfStock =
    0;
foreach (
    $products
    as $product
) {
    $stock =
        (int)$product['stock'];
    $totalStock +=
        $stock;
    if (
        $stock === 0
    ) {
        $outOfStock++;
    } elseif (
        $stock <= 5
    ) {
        $lowStock++;
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
    Products - Abella Apparel Admin
</title>
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
.stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
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
.product-section {
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
}
.section-header h2 {
    font-size: 18px;
    color: #fff;
}
.add-btn {
    background: #c49d4c;
    color: #111;
    border: none;
    padding: 11px 18px;
    border-radius: 0;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    transition: 0.2s ease;
}
.add-btn:hover {
    background: #fff;
    color: #111;
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
.product-image {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 0;
    border: 1px solid #333;
    background: #222;
}
.product-name {
    font-weight: 700;
    color: #fff;
}
.category {
    font-size: 11px;
    color: #999;
}
.price {
    font-weight: 700;
    color: #fff;
}
.stock-badge {
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
.new-badge {
    display: inline-block;
    background: #c49d4c;
    color: #111;
    padding: 5px 8px;
    border-radius: 0;
    font-size: 9px;
    font-weight: 700;
    letter-spacing: 1px;
}
.actions {
    display: flex;
    gap: 7px;
}
.action-btn {
    padding: 7px 10px;
    border-radius: 0;
    font-size: 11px;
    border: 1px solid #333;
    background: #151515;
    color: #ddd;
    cursor: pointer;
    transition: 0.2s ease;
}
.action-btn:hover {
    border-color: #c49d4c;
    color: #c49d4c;
    background: #1c1c1c;
}
.delete-btn:hover {
    border-color: #c62828;
    color: #f28b8b;
}
.modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.82);
    z-index: 100;
    align-items: center;
    justify-content: center;
    padding: 20px;
}
.modal.show {
    display: flex;
}
.modal-box {
    width: 100%;
    max-width: 550px;
    background: #111;
    color: #fff;
    border: 1px solid #333;
    border-radius: 0;
    padding: 25px;
    max-height: 90vh;
    overflow-y: auto;
}
.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}
.modal-header h2 {
    font-size: 20px;
    color: #fff;
}
.close-btn {
    border: none;
    background: none;
    font-size: 25px;
    cursor: pointer;
    color: #aaa;
}
.close-btn:hover {
    color: #c49d4c;
}
.form-group {
    margin-bottom: 15px;
}
.form-group label {
    display: block;
    font-size: 12px;
    font-weight: 700;
    margin-bottom: 6px;
    color: #ddd;
}
.form-group input,
.form-group select {
    width: 100%;
    padding: 11px;
    border: 1px solid #333;
    background: #0b0b0b;
    color: #fff;
    border-radius: 0;
    font-size: 13px;
    outline: none;
}
.form-group input::placeholder {
    color: #666;
}
.form-group input:focus,
.form-group select:focus {
    border-color: #c49d4c;
}
.file-input {
    width: 100%;
    padding: 10px;
    border: 1px dashed #555;
    background: #0b0b0b;
    color: #bbb;
    border-radius: 0;
    cursor: pointer;
}
.file-input:hover {
    border-color: #c49d4c;
}
.image-preview {
    width: 100%;
    height: 180px;
    object-fit: contain;
    border: 1px solid #333;
    border-radius: 0;
    background: #0b0b0b;
    margin-top: 10px;
    display: none;
}
.current-image {
    width: 100px;
    height: 100px;
    object-fit: cover;
    border: 1px solid #333;
    background: #0b0b0b;
    border-radius: 0;
    margin-bottom: 10px;
}
.checkbox-group {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 20px;
}
.checkbox-group label {
    font-size: 12px;
    color: #ddd;
}
.submit-btn {
    width: 100%;
    padding: 13px;
    background: #c49d4c;
    color: #111;
    border: none;
    border-radius: 0;
    cursor: pointer;
    font-weight: 700;
    transition: 0.2s ease;
}
.submit-btn:hover {
    background: #fff;
    color: #111;
}
@media (max-width: 1000px) {
    .stats {
        grid-template-columns: repeat(2, 1fr);
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
}
@media (max-width: 500px) {
    .sidebar {
        width: 180px;
        padding: 20px 15px;
    }
    .main {
        margin-left: 180px;
        padding: 15px;
    }
    .brand img {
        width: 130px;
    }
    .nav-menu a {
        font-size: 12px;
        padding: 11px 8px;
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
        <a
            href="admin_products.php"
            class="active"
        >
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
                Products
            </h1>
            <p>
                Manage your Abella Apparel products
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
                <?php
                echo $totalProducts;
                ?>
            </div>
        </div>
        <div class="stat-card">
            <h3>
                Total Stock
            </h3>
            <div class="number">
                <?php
                echo $totalStock;
                ?>
            </div>
        </div>
        <div class="stat-card">
            <h3>
                Low Stock
            </h3>
            <div class="number">
                <?php
                echo $lowStock;
                ?>
            </div>
        </div>
        <div class="stat-card">
            <h3>
                Out of Stock
            </h3>
            <div class="number">
                <?php
                echo $outOfStock;
                ?>
            </div>
        </div>
    </div>
    <section class="product-section">
        <div class="section-header">
            <h2>
                All Products
            </h2>
            <button
                class="add-btn"
                onclick="openAddModal()"
            >
                + ADD PRODUCT
            </button>
        </div>
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
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody>
                <?php if (
                    count($products) > 0
                ): ?>
                    <?php foreach (
                        $products
                        as $product
                    ): ?>
                        <tr>
                            <td>
                                <?php if (
                                    !empty(
                                        $product['image']
                                    )
                                ): ?>
                                    <img
                                        class="product-image"
                                        src="assets/<?php echo htmlspecialchars($product['image']); ?>"
                                        alt="<?php echo htmlspecialchars($product['name']); ?>"
                                        onerror="this.style.display='none';"
                                    >
                                <?php else: ?>
                                    <div
                                        style="
                                            width:60px;
                                            height:60px;
                                            background:#222;
                                            display:flex;
                                            align-items:center;
                                            justify-content:center;
                                            font-size:10px;
                                            color:#888;
                                            border-radius:0;
                                        "
                                    >
                                        NO IMAGE
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="product-name">
                                    <?php
                                    echo htmlspecialchars(
                                        $product['name']
                                    );
                                    ?>
                                </div>
                            </td>
                            <td>
                                <span class="category">
                                    <?php
                                    echo htmlspecialchars(
                                        $product['category']
                                    );
                                    ?>
                                </span>
                            </td>
                            <td>
                                <span class="price">
                                    ₱<?php
                                    echo number_format(
                                        $product['price'],
                                        2
                                    );
                                    ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                $stock =
                                    (int)$product['stock'];
                                if (
                                    $stock === 0
                                ) {
                                    $stockClass =
                                        'stock-out';
                                } elseif (
                                    $stock <= 5
                                ) {
                                    $stockClass =
                                        'stock-low';
                                } else {
                                    $stockClass =
                                        'stock-good';
                                }
                                ?>
                                <span
                                    class="stock-badge <?php echo $stockClass; ?>"
                                >
                                    <?php
                                    echo $stock;
                                    ?>
                                </span>
                            </td>
                            <td>
                                <?php if (
                                    (int)$product['is_new'] === 1
                                ): ?>
                                    <span class="new-badge">
                                        NEW
                                    </span>
                                <?php else: ?>
                                    <span class="category">
                                        Regular
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="actions">
                                    <button
                                        class="action-btn"
                                        onclick='openEditModal(
                                            <?php
                                            echo json_encode(
                                                $product,
                                                JSON_HEX_TAG |
                                                JSON_HEX_APOS |
                                                JSON_HEX_QUOT |
                                                JSON_HEX_AMP
                                            );
                                            ?>
                                        )'
                                    >
                                        EDIT
                                    </button>
                                    <a
                                        href="admin_products.php?delete=<?php echo (int)$product['id']; ?>"
                                        class="action-btn delete-btn"
                                        onclick="return confirm('Are you sure you want to delete this product?');"
                                    >
                                        DELETE
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td
                            colspan="7"
                            style="
                                text-align:center;
                                padding:40px;
                                color:#888;
                            "
                        >
                            No products found.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
<div
    class="modal"
    id="addModal"
>
    <div class="modal-box">
        <div class="modal-header">
            <h2>
                Add New Product
            </h2>
            <button
                class="close-btn"
                onclick="closeAddModal()"
            >
                ×
            </button>
        </div>
        <form
            method="POST"
            enctype="multipart/form-data"
        >
            <div class="form-group">
                <label>
                    Product Name
                </label>
                <input
                    type="text"
                    name="name"
                    placeholder="Enter product name"
                    required
                >
            </div>
            <div class="form-group">
                <label>
                    Price
                </label>
                <input
                    type="number"
                    name="price"
                    step="0.01"
                    min="0"
                    placeholder="0.00"
                    required
                >
            </div>
            <div class="form-group">
                <label>
                    Category
                </label>
                <select
                    name="category"
                    required
                >
                    <option value="">
                        Select Category
                    </option>
                    <option value="T-SHIRTS">
                        T-SHIRTS
                    </option>
                    <option value="HOODIES">
                        HOODIES
                    </option>
                    <option value="PANTS">
                        PANTS
                    </option>
                    <option value="ACCESSORIES">
                        ACCESSORIES
                    </option>
                </select>
            </div>
            <div class="form-group">
                <label>
                    Product Image
                </label>
                <input
                    type="file"
                    name="product_image"
                    id="addImage"
                    class="file-input"
                    accept="image/jpeg,image/png,image/webp,image/gif"
                    onchange="previewImage(this, 'addPreview')"
                >
                <img
                    id="addPreview"
                    class="image-preview"
                    alt="Image Preview"
                >
                <small
                    style="
                        display:block;
                        margin-top:6px;
                        color:#777;
                        font-size:11px;
                    "
                >
                    JPG, PNG, WEBP or GIF. Maximum 5MB.
                </small>
            </div>
            <div class="form-group">
                <label>
                    Stock
                </label>
                <input
                    type="number"
                    name="stock"
                    min="0"
                    value="0"
                    required
                >
            </div>
            <div class="checkbox-group">
                <input
                    type="checkbox"
                    name="is_new"
                    id="add_new"
                >
                <label for="add_new">

                    Mark as New Arrival
                </label>
            </div>
            <button
                type="submit"
                name="add_product"
                class="submit-btn"
            >
                ADD PRODUCT
            </button>
        </form>
    </div>
</div>
<div
    class="modal"
    id="editModal"
>

    <div class="modal-box">
        <div class="modal-header">
            <h2>
                Edit Product
            </h2>
            <button
                class="close-btn"
                onclick="closeEditModal()"
            >
                ×
            </button>
        </div>
        <form
            method="POST"
            enctype="multipart/form-data"
        >
            <input
                type="hidden"
                name="id"
                id="edit_id"
            >
            <div class="form-group">
                <label>
                    Product Name
                </label>
                <input
                    type="text"
                    name="name"
                    id="edit_name"
                    required
                >
            </div>
            <div class="form-group">
                <label>
                    Price
                </label>
                <input
                    type="number"
                    name="price"
                    id="edit_price"
                    step="0.01"
                    min="0"
                    required
                >
            </div>
            <div class="form-group">
                <label>
                    Category
                </label>
                <select
                    name="category"
                    id="edit_category"
                    required
                >
                    <option value="T-SHIRTS">
                        T-SHIRTS
                    </option>
                    <option value="HOODIES">
                        HOODIES
                    </option>
                    <option value="PANTS">
                        PANTS
                    </option>
                    <option value="ACCESSORIES">
                        ACCESSORIES
                    </option>
                </select>
            </div>
            <div
                class="form-group"
                id="currentImageContainer"
            >
                <label>
                    Current Image
                </label>
                <img
                    id="editCurrentImage"
                    class="current-image"
                    alt="Current Product Image"
                >
            </div>
            <div class="form-group">
                <label>
                    Replace Product Image
                </label>
                <input
                    type="file"
                    name="product_image"
                    id="editImage"
                    class="file-input"
                    accept="image/jpeg,image/png,image/webp,image/gif"
                    onchange="previewImage(this, 'editPreview')"
                >
                <img
                    id="editPreview"
                    class="image-preview"
                    alt="New Image Preview"
                >
                <small
                    style="
                        display:block;
                        margin-top:6px;
                        color:#777;
                        font-size:11px;
                    "
                >
                    Leave empty if you don't want to change the image.
                </small>
            </div>
            <div class="form-group">
                <label>
                    Stock
                </label>
                <input
                    type="number"
                    name="stock"
                    id="edit_stock"
                    min="0"
                    required
                >
            </div>
            <div class="checkbox-group">
                <input
                    type="checkbox"
                    name="is_new"
                    id="edit_new"
                >
                <label for="edit_new">
                    Mark as New Arrival
                </label>
            </div>
            <button
                type="submit"
                name="update_product"
                class="submit-btn"
            >
                UPDATE PRODUCT
            </button>
        </form>
    </div>
</div>
<script>

function openAddModal()
{
    document
        .getElementById("addModal")
        .classList.add("show");
}

function closeAddModal()
{
    document
        .getElementById("addModal")
        .classList.remove("show");
}
function openEditModal(product)
{
    document
        .getElementById("edit_id")
        .value =
        product.id;
    document
        .getElementById("edit_name")
        .value =
        product.name;
    document
        .getElementById("edit_price")
        .value =
        product.price;
    document
        .getElementById("edit_category")
        .value =
        product.category;
    document
        .getElementById("edit_stock")
        .value =
        product.stock;
    document
        .getElementById("edit_new")
        .checked =
        parseInt(
            product.is_new
        ) === 1;
    const currentImage =
        document.getElementById(
            "editCurrentImage"
        );
    if (
        product.image
    ) {
        currentImage.src =
            "assets/" +
            product.image;
        currentImage.style.display =
            "block";
    } else {
        currentImage.style.display =
            "none";

    }
    document
        .getElementById("editPreview")
        .style.display =
        "none";
    document
        .getElementById("editImage")
        .value =
        "";
    document
        .getElementById("editModal")
        .classList.add("show");

}
function closeEditModal()
{
    document
        .getElementById("editModal")
        .classList.remove("show");
}
function previewImage(
    input,
    previewId
)
{
    const preview =
        document.getElementById(
            previewId
        );
    if (
        input.files &&
        input.files[0]
    ) {
        const file =
            input.files[0];
        if (
            file.size >
            5 * 1024 * 1024
        ) {
            alert(
                "Image is too large. Maximum size is 5MB."
            );
            input.value =
                "";
            preview.style.display =
                "none";
            return;
        }
        const allowedTypes = [
            "image/jpeg",
            "image/png",
            "image/webp",
            "image/gif"
        ];
        if (
            !allowedTypes.includes(
                file.type
            )
        ) {
            alert(
                "Please select a JPG, PNG, WEBP or GIF image."
            );
            input.value =
                "";
            preview.style.display =
                "none";
            return;
        }
        const reader =
            new FileReader();
        reader.onload =
            function(event)
            {
                preview.src =
                    event.target.result;
                preview.style.display =
                    "block";
            };
        reader.readAsDataURL(
            file
        );
    } else {
        preview.style.display =
            "none";
    }
}
window.addEventListener(
    "click",
    function(event)
    {
        const addModal =
            document.getElementById(
                "addModal"
            );
        const editModal =
            document.getElementById(
                "editModal"
            );
        if (
            event.target === addModal
        ) {
            closeAddModal();
        }
        if (
            event.target === editModal
        ) {
            closeEditModal();
        }
    }
);
</script>
</body>
</html>