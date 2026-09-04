<?php

session_start();

require_once __DIR__ . '/../login_register/config.php';


/* =========================
   LOGIN CHECK
========================= */

if (
    !isset($_SESSION['logged_in']) ||
    $_SESSION['logged_in'] !== true ||
    !isset($_SESSION['user_id'])
) {
    header("Location: http://localhost/login_register/");
    exit();
}


/* =========================
   CART CHECK
========================= */

if (empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit();
}


/* =========================
   ONLY ACCEPT POST
========================= */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: checkout.php");
    exit();
}


/* =========================
   GET CUSTOMER INFORMATION
========================= */

$customerName = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$address = trim($_POST['address'] ?? '');
$city = trim($_POST['city'] ?? '');
$postalCode = trim($_POST['postal_code'] ?? '');
$paymentMethod = trim($_POST['payment_method'] ?? 'Cash on Delivery');


/* =========================
   CHECK REQUIRED FIELDS
========================= */

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


/* =========================
   START TRANSACTION
========================= */

$conn->begin_transaction();

try {

    /* =========================
       PREPARE PRODUCT QUERY
       LOCK PRODUCT WHILE ORDERING
    ========================= */

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


    /* =========================
       PREPARE ORDER ITEM QUERY
    ========================= */

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


    /* =========================
       PREPARE STOCK UPDATE
    ========================= */

    $stockStmt = $conn->prepare("
        UPDATE products
        SET stock = stock - ?
        WHERE id = ?
          AND stock >= ?
    ");

    if (!$stockStmt) {
        throw new Exception("Could not prepare stock query.");
    }


    /* =========================
       VERIFY ALL PRODUCTS
       AND CALCULATE TOTAL
    ========================= */

    $totalAmount = 0;

    $verifiedItems = [];


    foreach ($_SESSION['cart'] as $item) {

        $productId = (int)($item['product_id'] ?? 0);
        $quantity = (int)($item['quantity'] ?? 0);


        /* =========================
           OLD CART COMPATIBILITY
        ========================= */

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


        /* =========================
           CHECK QUANTITY
        ========================= */

        if ($productId <= 0) {
            throw new Exception("Invalid product in cart.");
        }

        if ($quantity <= 0) {
            throw new Exception("Invalid product quantity.");
        }


        /* =========================
           LOCK PRODUCT
        ========================= */

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


        /* =========================
           CHECK STOCK
        ========================= */

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


        /* =========================
           USE DATABASE PRICE
        ========================= */

        $productPrice = (float)$product['price'];

        $subtotal = $productPrice * $quantity;

        $totalAmount += $subtotal;


        /* =========================
           SAVE VERIFIED PRODUCT
        ========================= */

        $verifiedItems[] = [
            'id' => (int)$product['id'],
            'name' => $product['name'],
            'price' => $productPrice,
            'quantity' => $quantity,
            'subtotal' => $subtotal
        ];
    }


    /* =========================
       CLOSE PRODUCT QUERY
    ========================= */

    $productStmt->close();


    /* =========================
       CREATE ORDER
    ========================= */

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


    /* =========================
       GET ORDER ID
    ========================= */

    $orderId = $conn->insert_id;

    $orderStmt->close();


    /* =========================
       INSERT ORDER ITEMS
    ========================= */

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


    /* =========================
       REDUCE PRODUCT STOCK
    ========================= */

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


        /* =========================
           MAKE SURE STOCK UPDATED
        ========================= */

        if ($stockStmt->affected_rows !== 1) {

            throw new Exception(
                "Stock could not be updated for " .
                $verifiedItem['name'] .
                "."
            );
        }
    }


    $stockStmt->close();


    /* =========================
       SAVE DATABASE CHANGES
    ========================= */

    $conn->commit();


    /* =========================
       CLEAR CART
    ========================= */

    $_SESSION['cart'] = [];


    /* =========================
       SAVE ORDER ID
    ========================= */

    $_SESSION['last_order_id'] = $orderId;


    /* =========================
       GO TO SUCCESS PAGE
    ========================= */

    header("Location: order_success.php");
    exit();


} catch (Exception $e) {

    /* =========================
       CANCEL EVERYTHING
    ========================= */

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