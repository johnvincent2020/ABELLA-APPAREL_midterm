<?php
session_start();
require_once __DIR__ . '/config.php';
if (
    !isset($_SESSION['logged_in']) ||
    $_SESSION['logged_in'] !== true ||
    !isset($_SESSION['user_id'])
) {
    header("Location: login.php");
    exit();
}
if (empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: checkout.php");
    exit();
}
$customerName = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$address = trim($_POST['address'] ?? '');
$city = trim($_POST['city'] ?? '');
$postalCode = trim($_POST['postal_code'] ?? '');
$paymentMethod = trim($_POST['payment_method'] ?? 'Cash on Delivery');
if (
    $customerName === '' ||
    $email === '' ||
    $phone === '' ||
    $address === '' ||
    $city === '' ||
    $postalCode === ''
) {
    die("Please complete all required fields.");
}
$conn->begin_transaction();
try {
    $productStmt = $conn->prepare("
        SELECT
            id,
            name,
            price,
            image,
            stock
        FROM products
        WHERE id = ?
        FOR UPDATE
    ");
    if (!$productStmt) {
        throw new Exception("Could not prepare product query.");
    }
    $itemStmt = $conn->prepare("
        INSERT INTO order_items
        (
            order_id,
            product_name,
            product_price,
            quantity,
            subtotal
        )
        VALUES (?, ?, ?, ?, ?)
    ");

    if (!$itemStmt) {
        throw new Exception("Could not prepare order items query.");
    }
    $stockStmt = $conn->prepare("
        UPDATE products
        SET stock = stock - ?
        WHERE id = ?
          AND stock >= ?
    ");
    if (!$stockStmt) {
        throw new Exception("Could not prepare stock query.");
    }
    $totalAmount = 0;
    $verifiedItems = [];
    foreach ($_SESSION['cart'] as $item) {
        $productId = (int)($item['product_id'] ?? 0);
        $quantity = (int)($item['quantity'] ?? 0);
        if ($productId <= 0 && !empty($item['name'])) {
            $findStmt = $conn->prepare("
                SELECT id
                FROM products
                WHERE name = ?
                LIMIT 1
            ");
            if (!$findStmt) {
                throw new Exception("Could not find product.");
            }
            $oldName = $item['name'];
            $findStmt->bind_param(
                "s",
                $oldName
            );
            $findStmt->execute();
            $findResult = $findStmt->get_result();
            if ($findResult->num_rows === 0) {
                $findStmt->close();
                throw new Exception(
                    "Product '{$oldName}' no longer exists."
                );
            }
            $foundProduct = $findResult->fetch_assoc();
            $productId = (int)$foundProduct['id'];
            $findStmt->close();
        }
        if ($productId <= 0) {
            throw new Exception("Invalid product in cart.");
        }
        if ($quantity <= 0) {
            throw new Exception("Invalid product quantity.");
        }
        $productStmt->bind_param(
            "i",
            $productId
        );
        $productStmt->execute();
        $productResult = $productStmt->get_result();
        if ($productResult->num_rows === 0) {
            throw new Exception(
                "A product in your cart no longer exists."
            );
        }
        $product = $productResult->fetch_assoc();
        $availableStock = (int)$product['stock'];
        if ($availableStock <= 0) {
            throw new Exception(
                $product['name'] . " is OUT OF STOCK."
            );
        }
        if ($quantity > $availableStock) {
            throw new Exception(
                "Not enough stock for " .
                $product['name'] .
                ". Only " .
                $availableStock .
                " left."
            );
        }
        $productPrice = (float)$product['price'];
        $subtotal = $productPrice * $quantity;
        $totalAmount += $subtotal;
        $verifiedItems[] = [
            'id' => (int)$product['id'],
            'name' => $product['name'],
            'price' => $productPrice,
            'quantity' => $quantity,
            'subtotal' => $subtotal
        ];
    }
    $productStmt->close();
    $orderSql = "
        INSERT INTO orders
        (
            user_id,
            customer_name,
            email,
            phone,
            address,
            city,
            postal_code,
            payment_method,
            total_amount,
            status
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')
    ";
    $orderStmt = $conn->prepare($orderSql);
    if (!$orderStmt) {
        throw new Exception("Could not prepare order query.");
    }
    $userId = (int)$_SESSION['user_id'];
    $orderStmt->bind_param(
        "isssssssd",
        $userId,
        $customerName,
        $email,
        $phone,
        $address,
        $city,
        $postalCode,
        $paymentMethod,
        $totalAmount
    );
    $orderStmt->execute();
    $orderId = $conn->insert_id;
    $orderStmt->close();
    foreach ($verifiedItems as $verifiedItem) {
        $productName = $verifiedItem['name'];
        $productPrice = $verifiedItem['price'];
        $quantity = $verifiedItem['quantity'];
        $subtotal = $verifiedItem['subtotal'];
        $itemStmt->bind_param(
            "isdid",
            $orderId,
            $productName,
            $productPrice,
            $quantity,
            $subtotal
        );
        $itemStmt->execute();
    }
    $itemStmt->close();
    foreach ($verifiedItems as $verifiedItem) {
        $productId = $verifiedItem['id'];
        $quantity = $verifiedItem['quantity'];
        $stockStmt->bind_param(
            "iii",
            $quantity,
            $productId,
            $quantity
        );
        $stockStmt->execute();
        if ($stockStmt->affected_rows !== 1) {
            throw new Exception(
                "Stock could not be updated for " .
                $verifiedItem['name'] .
                "."
            );
        }
    }
    $stockStmt->close();
    $conn->commit();
    $_SESSION['cart'] = [];
    $_SESSION['last_order_id'] = $orderId;
    header("Location: order_success.php");
    exit();
} catch (Exception $e) {
    $conn->rollback();
    die(
        "Order failed: " .
        htmlspecialchars(
            $e->getMessage(),
            ENT_QUOTES,
            'UTF-8'
        )
    );
}
?>