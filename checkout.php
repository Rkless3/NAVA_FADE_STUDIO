<?php

session_start();

/* =========================================
   LOGIN REQUIREMENT
========================================= */

if (!isset($_SESSION["customer_id"])) {
    header("Location: login.php");
    exit;
}


/* =========================================
   REQUIRE FILES
========================================= */

require_once "config/Database.php";
require_once "classes/Product.php";


/* =========================================
   DATABASE CONNECTION
========================================= */

$database = new Database();
$db = $database->connect();

$productModel = new Product($db);


/* =========================================
   GET CUSTOMER INFORMATION
========================================= */

$customer_id = (int) $_SESSION["customer_id"];

$customerQuery = "
    SELECT
        id,
        full_name,
        email,
        contact_number
    FROM customers
    WHERE id = :id
    LIMIT 1
";

$customerStmt = $db->prepare($customerQuery);

$customerStmt->bindValue(
    ":id",
    $customer_id,
    PDO::PARAM_INT
);

$customerStmt->execute();

$customer = $customerStmt->fetch();


/* =========================================
   CUSTOMER NOT FOUND
========================================= */

if (!$customer) {

    session_destroy();

    header("Location: login.php");
    exit;
}


/* =========================================
   GET CART
========================================= */

$cart = $_SESSION["cart"] ?? [];


/* =========================================
   EMPTY CART
========================================= */

if (empty($cart)) {

    header("Location: cart.php");
    exit;
}


/* =========================================
   PREPARE ORDER ITEMS
========================================= */

$orderItems = [];

$total = 0;


foreach ($cart as $product_id => $quantity) {

    $product_id = (int) $product_id;
    $quantity = (int) $quantity;


    /* INVALID QUANTITY */

    if ($quantity <= 0) {
        continue;
    }


    /* GET PRODUCT */

    $product = $productModel->getById($product_id);


    /* PRODUCT DOES NOT EXIST */

    if (!$product) {
        continue;
    }


    /* PRODUCT IS INACTIVE */

    if ($product["status"] !== "Active") {
        continue;
    }


    /* PRODUCT IS OUT OF STOCK */

    if ((int) $product["stock"] <= 0) {
        continue;
    }


    /* CUSTOMER MAXIMUM: 5 UNITS PER PRODUCT */

    $maxAllowed = min((int) $product["stock"], 5);

    if ($quantity > $maxAllowed) {
        $quantity = $maxAllowed;
    }


    if ($quantity <= 0) {
        continue;
    }


    /* PRICE */

    $price = (float) $product["price"];

    $subtotal = $price * $quantity;

    $total += $subtotal;


    /* ADD TO ORDER ITEMS */

    $orderItems[] = [

        "product_id" => $product_id,

        "quantity" => $quantity,

        "price" => $price,

        "subtotal" => $subtotal,

        "product_name" => $product["product_name"]

    ];
}


/* =========================================
   VALIDATE ORDER ITEMS
========================================= */

if (empty($orderItems)) {

    $_SESSION["cart"] = [];

    header("Location: cart.php");
    exit;
}


/* =========================================
   ORDER VARIABLES
========================================= */

$orderSuccess = false;

$order_id = null;

$errorMessage = "";

$payment_method = $_POST["payment_method"] ?? "";

$reference_number = trim($_POST["reference_number"] ?? "");

/* =========================================
   PICKUP / DELIVERY VARIABLES
========================================= */

$delivery_method = $_POST["delivery_method"] ?? "pickup";
$delivery_address = trim($_POST["delivery_address"] ?? "");
$delivery_contact = trim($_POST["delivery_contact"] ?? "");
$delivery_landmark = trim($_POST["delivery_landmark"] ?? "");
$delivery_notes = trim($_POST["delivery_notes"] ?? "");


/* =========================================
   PLACE ORDER
========================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    try {

        /* =====================================
           VALIDATE PAYMENT METHOD
        ===================================== */

        if (!in_array($payment_method, ["Cash", "GCash"], true)) {
            throw new Exception("Please select a valid payment method.");
        }

        if ($payment_method === "GCash") {

            if ($reference_number === "") {
                throw new Exception("Please enter your GCash reference number.");
            }

            if (!preg_match('/^[A-Za-z0-9\\-]{5,100}$/', $reference_number)) {
                throw new Exception("Please enter a valid GCash reference number.");
            }

        } else {
            $reference_number = null;
        }


        /* =====================================
           VALIDATE PICKUP / DELIVERY
        ===================================== */

        if (!in_array($delivery_method, ["pickup", "delivery"], true)) {
            throw new Exception("Please select a valid order fulfillment method.");
        }

        if ($delivery_method === "delivery") {
            if ($delivery_address === "") {
                throw new Exception("Please enter your delivery address.");
            }

            if ($delivery_contact === "") {
                throw new Exception("Please enter your delivery contact number.");
            }

            if (!preg_match('/^[0-9+()\-\s]{7,30}$/', $delivery_contact)) {
                throw new Exception("Please enter a valid delivery contact number.");
            }
        } else {
            $delivery_address = null;
            $delivery_contact = null;
            $delivery_landmark = null;
            $delivery_notes = null;
        }


        /* =====================================
           START TRANSACTION
        ===================================== */

        $db->beginTransaction();


        /* =====================================
           RECHECK STOCK
        ===================================== */

        foreach ($orderItems as $item) {

            $lockStmt = $db->prepare("
                SELECT id, product_name, stock, status
                FROM products
                WHERE id = :product_id
                FOR UPDATE
            ");

            $lockStmt->bindValue(
                ":product_id",
                $item["product_id"],
                PDO::PARAM_INT
            );

            $lockStmt->execute();

            $lockedProduct = $lockStmt->fetch();

            if (!$lockedProduct || $lockedProduct["status"] !== "Active") {
                throw new Exception(
                    $item["product_name"] . " is no longer available."
                );
            }

            $availableStock = (int) $lockedProduct["stock"];
            $maxAllowed = min($availableStock, 5);

            if ($item["quantity"] > $maxAllowed) {
                throw new Exception(
                    "Only " . $maxAllowed . " unit(s) of " .
                    $item["product_name"] . " can be ordered right now."
                );
            }
        }


        /* =====================================
           CREATE ORDER
        ===================================== */

        $orderQuery = "
            INSERT INTO orders
            (
                customer_id,
                total_amount,
                delivery_method,
                delivery_address,
                delivery_contact,
                delivery_landmark,
                delivery_notes,
                status
            )
            VALUES
            (
                :customer_id,
                :total_amount,
                :delivery_method,
                :delivery_address,
                :delivery_contact,
                :delivery_landmark,
                :delivery_notes,
                'Pending'
            )
        ";

        $orderStmt = $db->prepare($orderQuery);


        $orderStmt->bindValue(
            ":customer_id",
            $customer_id,
            PDO::PARAM_INT
        );


        $orderStmt->bindValue(
            ":total_amount",
            $total
        );

        $orderStmt->bindValue(":delivery_method", $delivery_method);
        $orderStmt->bindValue(":delivery_address", $delivery_address, $delivery_address === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $orderStmt->bindValue(":delivery_contact", $delivery_contact, $delivery_contact === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $orderStmt->bindValue(":delivery_landmark", $delivery_landmark, $delivery_landmark === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $orderStmt->bindValue(":delivery_notes", $delivery_notes, $delivery_notes === null ? PDO::PARAM_NULL : PDO::PARAM_STR);


        $orderStmt->execute();


        /* =====================================
           GET ORDER ID
        ===================================== */

        $order_id = (int) $db->lastInsertId();


        /* =====================================
           PREPARE ORDER ITEM QUERY
        ===================================== */

        $itemQuery = "
            INSERT INTO order_items
            (
                order_id,
                product_id,
                quantity,
                price,
                subtotal
            )
            VALUES
            (
                :order_id,
                :product_id,
                :quantity,
                :price,
                :subtotal
            )
        ";

        $itemStmt = $db->prepare($itemQuery);


        /* =====================================
           STOCK UPDATE QUERY
        ===================================== */

        $stockQuery = "
            UPDATE products
            SET stock = stock - :quantity
            WHERE id = :product_id
            AND stock >= :quantity
        ";

        $stockStmt = $db->prepare($stockQuery);


        /* =====================================
           PROCESS EACH ORDER ITEM
        ===================================== */

        foreach ($orderItems as $item) {


            /* =================================
               INSERT ORDER ITEM
            ================================= */

            $itemStmt->bindValue(
                ":order_id",
                $order_id,
                PDO::PARAM_INT
            );


            $itemStmt->bindValue(
                ":product_id",
                $item["product_id"],
                PDO::PARAM_INT
            );


            $itemStmt->bindValue(
                ":quantity",
                $item["quantity"],
                PDO::PARAM_INT
            );


            $itemStmt->bindValue(
                ":price",
                $item["price"]
            );


            $itemStmt->bindValue(
                ":subtotal",
                $item["subtotal"]
            );


            $itemStmt->execute();


            /* =================================
               REDUCE PRODUCT STOCK
            ================================= */

            $stockStmt->bindValue(
                ":quantity",
                $item["quantity"],
                PDO::PARAM_INT
            );


            $stockStmt->bindValue(
                ":product_id",
                $item["product_id"],
                PDO::PARAM_INT
            );


            $stockStmt->execute();


            /* =================================
               CHECK STOCK UPDATE
            ================================= */

            if ($stockStmt->rowCount() === 0) {

                throw new Exception(
                    "Not enough stock available for " .
                    $item["product_name"]
                );
            }
        }


        /* =====================================
           CREATE PAYMENT RECORD
        ===================================== */

        $paymentStmt = $db->prepare("
            INSERT INTO payments
            (
                customer_id,
                order_id,
                payment_method,
                reference_number,
                amount,
                status
            )
            VALUES
            (
                :customer_id,
                :order_id,
                :payment_method,
                :reference_number,
                :amount,
                'Pending'
            )
        ");

        $paymentStmt->bindValue(":customer_id", $customer_id, PDO::PARAM_INT);
        $paymentStmt->bindValue(":order_id", $order_id, PDO::PARAM_INT);
        $paymentStmt->bindValue(":payment_method", $payment_method);

        if ($reference_number === null) {
            $paymentStmt->bindValue(":reference_number", null, PDO::PARAM_NULL);
        } else {
            $paymentStmt->bindValue(":reference_number", $reference_number);
        }

        $paymentStmt->bindValue(":amount", $total);
        $paymentStmt->execute();


        /* =====================================
           COMPLETE TRANSACTION
        ===================================== */

        $db->commit();


        /* =====================================
           CLEAR CART
        ===================================== */

        $_SESSION["cart"] = [];


        /* =====================================
           SUCCESS
        ===================================== */

        $orderSuccess = true;


    } catch (Exception $e) {


        /* =====================================
           ROLLBACK
        ===================================== */

        if ($db->inTransaction()) {
            $db->rollBack();
        }


        $errorMessage = $e->getMessage();
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
        Checkout | NAVA Fade Studio
    </title>


    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >


    <style>

        /* =====================================================
           CHECKOUT PAGE
        ===================================================== */

        * {
            box-sizing: border-box;
        }


        body {
            margin: 0;
            font-family: Bahnschrift, "Myriad Pro", Arial, sans-serif;
            background: #0b1020;
        }


        .checkout-page {

            min-height: 100vh;

            padding: 70px 7% 90px;

            background:
                linear-gradient(
                    rgba(7, 12, 25, 0.88),
                    rgba(7, 12, 25, 0.92)
                ),
                url("assets/images/pattern3.png")
                center center / cover fixed;

            color: #ffffff;
        }


        /* =====================================================
           CHECKOUT HERO
        ===================================================== */

        .checkout-hero {

            max-width: 1100px;

            margin: 0 auto 55px;

            text-align: center;
        }


        .checkout-logo {

            width: 150px;

            height: auto;

            display: block;

            margin: 0 auto 25px;
        }


        .checkout-eyebrow {

            display: inline-block;

            margin-bottom: 12px;

            color: #d4a33a;

            font-size: 13px;

            font-weight: 700;

            letter-spacing: 5px;

            text-transform: uppercase;
        }


        .checkout-hero h1 {

            margin: 0;

            font-size: clamp(42px, 5vw, 68px);

            line-height: 1.05;

            font-weight: 800;

            letter-spacing: -1px;

            text-shadow: 0 8px 25px rgba(0, 0, 0, 0.35);
        }


        .checkout-hero h1 span {

            color: #d4a33a;
        }


        .checkout-hero p {

            max-width: 650px;

            margin: 18px auto 0;

            color: #c7ccda;

            font-size: 16px;

            line-height: 1.6;
        }


        /* =====================================================
           MAIN CHECKOUT CONTAINER
        ===================================================== */

        .checkout-container {

            max-width: 1100px;

            margin: 0 auto;

            display: grid;

            grid-template-columns: 1fr 1fr;

            gap: 25px;

            align-items: stretch;
        }


        /* =====================================================
           CHECKOUT BOX
        ===================================================== */

        .checkout-box {

            position: relative;

            background: rgba(14, 20, 35, 0.96);

            border: 1px solid rgba(255, 255, 255, 0.12);

            border-radius: 18px;

            padding: 32px;

            box-shadow:
                0 18px 45px rgba(0, 0, 0, 0.28);

            overflow: hidden;
        }


        .checkout-box::before {

            content: "";

            position: absolute;

            top: 0;

            left: 0;

            width: 100%;

            height: 3px;

            background: linear-gradient(
                90deg,
                transparent,
                #b8862c,
                #e0b13d,
                #b8862c,
                transparent
            );
        }


        .checkout-box h2 {

            margin: 0 0 28px;

            color: #ffffff;

            font-size: 25px;

            font-weight: 800;
        }


        .checkout-box h2::after {

            content: "";

            display: block;

            width: 45px;

            height: 3px;

            margin-top: 10px;

            background: #b8862c;

            border-radius: 5px;
        }


        /* =====================================================
           CUSTOMER INFORMATION
        ===================================================== */

        .customer-info {

            display: flex;

            flex-direction: column;
        }


        .customer-info-row {

            display: flex;

            align-items: flex-start;

            gap: 16px;

            padding: 18px 0;

            border-bottom: 1px solid rgba(255, 255, 255, 0.10);
        }


        .customer-info-row:last-child {

            border-bottom: none;
        }


        .customer-info-icon {

            width: 40px;

            height: 40px;

            min-width: 40px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 10px;

            background: rgba(184, 134, 44, 0.12);

            border: 1px solid rgba(184, 134, 44, 0.35);

            color: #d4a33a;

            font-size: 17px;
        }


        .customer-info-content {

            flex: 1;

            min-width: 0;
        }


        .customer-info-label {

            display: block;

            margin-bottom: 5px;

            color: #8f97a9;

            font-size: 12px;

            font-weight: 600;

            text-transform: uppercase;

            letter-spacing: 1px;
        }


        .customer-info-value {

            display: block;

            color: #ffffff;

            font-size: 16px;

            font-weight: 600;

            word-break: break-word;
        }


        /* =====================================================
           ORDER ITEMS
        ===================================================== */

        .checkout-products {

            display: flex;

            flex-direction: column;
        }


        .checkout-product {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;

            padding: 18px 0;

            border-bottom: 1px solid rgba(255, 255, 255, 0.10);
        }


        .checkout-product:last-child {

            border-bottom: none;
        }


        .checkout-product-info {

            flex: 1;

            min-width: 0;
        }


        .checkout-product-info h3 {

            margin: 0 0 7px;

            color: #ffffff;

            font-size: 17px;

            font-weight: 700;
        }


        .checkout-product-details {

            display: flex;

            align-items: center;

            flex-wrap: wrap;

            gap: 10px;
        }


        .checkout-product-details span {

            color: #9ca4b5;

            font-size: 13px;
        }


        .checkout-product-details .quantity {

            padding: 4px 9px;

            background: #151d30;

            border: 1px solid #29334a;

            border-radius: 6px;

            color: #d5dae5;

            font-weight: 600;
        }


        .checkout-product-subtotal {

            color: #f0b429;

            font-size: 18px;

            font-weight: 800;

            white-space: nowrap;
        }


        /* =====================================================
           TOTAL
        ===================================================== */

        .checkout-total {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;

            margin-top: 20px;

            padding-top: 22px;

            border-top: 1px solid rgba(255, 255, 255, 0.18);
        }


        .checkout-total-label {

            color: #cbd0da;

            font-size: 16px;

            font-weight: 600;
        }


        .checkout-total-amount {

            color: #f0b429;

            font-size: 32px;

            font-weight: 800;

            white-space: nowrap;
        }


        /* =====================================================
           ORDER NOTICE
        ===================================================== */

        .checkout-notice {

            display: flex;

            align-items: flex-start;

            gap: 12px;

            margin-top: 22px;

            padding: 14px 16px;

            background: rgba(184, 134, 44, 0.08);

            border: 1px solid rgba(184, 134, 44, 0.22);

            border-radius: 10px;
        }


        .checkout-notice-icon {

            color: #d4a33a;

            font-size: 17px;

            line-height: 1.4;
        }


        .checkout-notice p {

            margin: 0;

            color: #aeb5c3;

            font-size: 12px;

            line-height: 1.5;
        }


        .checkout-notice strong {

            color: #d4a33a;
        }


        /* =====================================================
           PICKUP / DELIVERY
        ===================================================== */

        .fulfillment-section { margin-top: 28px; padding-top: 25px; border-top: 1px solid rgba(255,255,255,0.10); }
        .fulfillment-section h3 { margin: 0 0 16px; color: #ffffff; font-size: 18px; font-weight: 800; }
        .fulfillment-options { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .fulfillment-option { position: relative; }
        .fulfillment-option input[type="radio"] { position: absolute; opacity: 0; pointer-events: none; }
        .fulfillment-option label { min-height: 78px; display: flex; align-items: center; gap: 13px; padding: 15px; border: 1px solid #354057; border-radius: 11px; background: #11182a; color: #ffffff; cursor: pointer; transition: 0.2s ease; }
        .fulfillment-option label:hover { border-color: #b8862c; }
        .fulfillment-option input[type="radio"]:checked + label { border-color: #d4a33a; background: rgba(184,134,44,0.10); box-shadow: 0 0 0 1px rgba(212,163,58,0.15); }
        .fulfillment-icon { width: 38px; height: 38px; min-width: 38px; display: flex; align-items: center; justify-content: center; border-radius: 9px; background: rgba(184,134,44,0.14); border: 1px solid rgba(184,134,44,0.35); color: #d4a33a; font-weight: 900; font-size: 17px; }
        .fulfillment-copy { display: flex; flex-direction: column; gap: 4px; }
        .fulfillment-copy strong { color: #ffffff; font-size: 14px; }
        .fulfillment-copy span { color: #8f97a9; font-size: 11px; line-height: 1.4; }
        .delivery-fields { margin-top: 15px; padding: 18px; background: rgba(17,24,42,0.75); border: 1px solid rgba(255,255,255,0.08); border-radius: 11px; }
        .delivery-fields[hidden] { display: none; }
        .delivery-field { margin-bottom: 14px; }
        .delivery-field:last-child { margin-bottom: 0; }
        .delivery-field label { display: block; margin-bottom: 7px; color: #d5dae5; font-size: 12px; font-weight: 700; }
        .delivery-field label span { color: #d4a33a; }
        .delivery-field input, .delivery-field textarea { width: 100%; padding: 11px 13px; border: 1px solid #354057; border-radius: 9px; outline: none; background: #0f1627; color: #ffffff; font-family: inherit; font-size: 14px; transition: 0.2s ease; }
        .delivery-field input { height: 45px; }
        .delivery-field textarea { min-height: 85px; resize: vertical; }
        .delivery-field input:focus, .delivery-field textarea:focus { border-color: #d4a33a; box-shadow: 0 0 0 3px rgba(212,163,58,0.08); }
        .delivery-note { margin-top: 10px; color: #8f97a9; font-size: 11px; line-height: 1.5; }


        /* =====================================================
           PAYMENT METHOD
        ===================================================== */

        .payment-section {
            margin-top: 28px;
            padding-top: 25px;
            border-top: 1px solid rgba(255, 255, 255, 0.10);
        }

        .payment-section h3 {
            margin: 0 0 16px;
            color: #ffffff;
            font-size: 18px;
            font-weight: 800;
        }

        .payment-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .payment-option {
            position: relative;
        }

        .payment-option input[type="radio"] {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .payment-option label {
            min-height: 78px;
            display: flex;
            align-items: center;
            gap: 13px;
            padding: 15px;
            border: 1px solid #354057;
            border-radius: 11px;
            background: #11182a;
            color: #ffffff;
            cursor: pointer;
            transition: 0.2s ease;
        }

        .payment-option label:hover {
            border-color: #b8862c;
        }

        .payment-option input[type="radio"]:checked + label {
            border-color: #d4a33a;
            background: rgba(184, 134, 44, 0.10);
            box-shadow: 0 0 0 1px rgba(212, 163, 58, 0.15);
        }

        .payment-icon {
            width: 38px;
            height: 38px;
            min-width: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 9px;
            background: rgba(184, 134, 44, 0.14);
            border: 1px solid rgba(184, 134, 44, 0.35);
            color: #d4a33a;
            font-weight: 900;
            font-size: 17px;
        }

        .payment-copy {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .payment-copy strong {
            color: #ffffff;
            font-size: 14px;
        }

        .payment-copy span {
            color: #8f97a9;
            font-size: 11px;
            line-height: 1.4;
        }

        .gcash-reference {
            display: none;
            margin-top: 14px;
        }

        .gcash-reference.show {
            display: block;
        }

        .gcash-reference label {
            display: block;
            margin-bottom: 7px;
            color: #d5dae5;
            font-size: 12px;
            font-weight: 700;
        }

        .gcash-reference input {
            width: 100%;
            height: 45px;
            padding: 0 13px;
            border: 1px solid #354057;
            border-radius: 9px;
            outline: none;
            background: #0f1627;
            color: #ffffff;
            font-family: inherit;
            font-size: 14px;
        }

        .gcash-reference input:focus {
            border-color: #d4a33a;
            box-shadow: 0 0 0 3px rgba(212, 163, 58, 0.08);
        }

        .gcash-reference small {
            display: block;
            margin-top: 6px;
            color: #8f97a9;
            font-size: 11px;
        }

        .payment-note {
            margin-top: 12px;
            color: #8f97a9;
            font-size: 11px;
            line-height: 1.5;
        }


        /* =====================================================
           ACTION BUTTONS
        ===================================================== */

        .checkout-actions {

            max-width: 1100px;

            margin: 30px auto 0;

            display: flex;

            justify-content: flex-end;

            align-items: center;

            gap: 14px;
        }


        .checkout-back,
        .checkout-place-btn {

            min-height: 48px;

            padding: 13px 25px;

            border-radius: 9px;

            font-family: inherit;

            font-size: 14px;

            font-weight: 700;

            text-decoration: none;

            transition:
                transform 0.2s ease,
                background 0.2s ease,
                border-color 0.2s ease,
                box-shadow 0.2s ease;
        }


        .checkout-back {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            background: rgba(14, 20, 35, 0.95);

            border: 1px solid #354057;

            color: #ffffff;
        }


        .checkout-back:hover {

            transform: translateY(-2px);

            border-color: #b8862c;

            color: #d4a33a;
        }


        .checkout-place-btn {

            min-width: 150px;

            border: none;

            background: linear-gradient(
                135deg,
                #b8862c,
                #d4a33a
            );

            color: #0e1423;

            cursor: pointer;

            box-shadow:
                0 8px 20px rgba(184, 134, 44, 0.18);
        }


        .checkout-place-btn:hover {

            transform: translateY(-2px);

            background: linear-gradient(
                135deg,
                #d4a33a,
                #e5b94e
            );

            box-shadow:
                0 10px 25px rgba(184, 134, 44, 0.28);
        }


        /* =====================================================
           ERROR MESSAGE
        ===================================================== */

        .checkout-error {

            max-width: 1100px;

            margin: 0 auto 25px;

            padding: 18px 22px;

            background: rgba(100, 25, 25, 0.75);

            border: 1px solid rgba(220, 80, 80, 0.45);

            border-radius: 12px;

            box-shadow:
                0 10px 25px rgba(0, 0, 0, 0.18);
        }


        .checkout-error strong {

            display: block;

            margin-bottom: 6px;

            color: #ff8d8d;

            font-size: 15px;
        }


        .checkout-error p {

            margin: 0;

            color: #e8dada;

            font-size: 14px;

            line-height: 1.5;
        }


        /* =====================================================
           SUCCESS PAGE
        ===================================================== */

        .checkout-success {

            max-width: 650px;

            margin: 30px auto 0;

            padding: 50px 45px;

            text-align: center;

            background: rgba(14, 20, 35, 0.97);

            border: 1px solid #b8862c;

            border-radius: 20px;

            box-shadow:
                0 20px 55px rgba(0, 0, 0, 0.35);
        }


        .success-logo {

            width: 145px;

            height: auto;

            margin: 0 auto 25px;
        }


        .checkout-success-icon {

            width: 72px;

            height: 72px;

            margin: 0 auto 22px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 50%;

            background: rgba(184, 134, 44, 0.12);

            border: 2px solid #b8862c;

            color: #d4a33a;

            font-size: 36px;

            font-weight: 800;
        }


        .checkout-success .success-label {

            display: block;

            margin-bottom: 10px;

            color: #d4a33a;

            font-size: 12px;

            font-weight: 700;

            letter-spacing: 4px;

            text-transform: uppercase;
        }


        .checkout-success h2 {

            margin: 0 0 20px;

            color: #ffffff;

            font-size: 31px;

            font-weight: 800;
        }


        .checkout-success p {

            margin: 9px 0;

            color: #bfc5d1;

            font-size: 15px;

            line-height: 1.6;
        }


        .checkout-success strong {

            color: #f0b429;
        }


        .success-order-details {

            margin: 25px 0;

            padding: 20px;

            background: #11182a;

            border: 1px solid #283249;

            border-radius: 12px;
        }


        .success-detail {

            display: flex;

            justify-content: space-between;

            gap: 20px;

            padding: 10px 0;

            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }


        .success-detail:last-child {

            border-bottom: none;
        }


        .success-detail span:first-child {

            color: #8f97a9;

            font-size: 13px;
        }


        .success-detail span:last-child {

            color: #ffffff;

            font-size: 14px;

            font-weight: 700;

            text-align: right;
        }


        .checkout-success-btn {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            margin-top: 15px;

            padding: 14px 28px;

            background: linear-gradient(
                135deg,
                #b8862c,
                #d4a33a
            );

            color: #0e1423;

            border-radius: 9px;

            text-decoration: none;

            font-size: 14px;

            font-weight: 800;

            transition:
                transform 0.2s ease,
                box-shadow 0.2s ease;
        }


        .checkout-success-btn:hover {

            transform: translateY(-2px);

            box-shadow:
                0 10px 25px rgba(184, 134, 44, 0.25);
        }


        /* =====================================================
           TABLET
        ===================================================== */

        @media (max-width: 900px) {

            .checkout-page {

                padding: 60px 5% 80px;

            }


            .checkout-container {

                grid-template-columns: 1fr;

            }


            .checkout-hero {

                margin-bottom: 40px;

            }


            .checkout-logo {

                width: 135px;

            }


            .checkout-hero h1 {

                font-size: 50px;

            }

        }


        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 600px) {

            .checkout-page {

                padding: 45px 18px 60px;

                background-attachment: scroll;

            }


            .checkout-hero {

                margin-bottom: 32px;

            }


            .checkout-logo {

                width: 120px;

                margin-bottom: 20px;

            }


            .checkout-eyebrow {

                font-size: 11px;

                letter-spacing: 3px;

            }


            .checkout-hero h1 {

                font-size: 40px;

            }


            .checkout-hero p {

                font-size: 14px;

            }


            .checkout-box {

                padding: 23px 20px;

                border-radius: 15px;

            }


            .checkout-box h2 {

                font-size: 21px;

            }


            .fulfillment-options,
            .payment-options {
                grid-template-columns: 1fr;
            }

            .delivery-fields {
                padding: 15px;
            }


            .customer-info-row {

                gap: 12px;

                padding: 15px 0;

            }


            .customer-info-icon {

                width: 36px;

                height: 36px;

                min-width: 36px;

                font-size: 15px;

            }


            .customer-info-value {

                font-size: 14px;

            }


            .checkout-product {

                gap: 12px;

            }


            .checkout-product-info h3 {

                font-size: 15px;

            }


            .checkout-product-details {

                gap: 6px;

            }


            .checkout-product-details span {

                font-size: 12px;

            }


            .checkout-product-subtotal {

                font-size: 15px;

            }


            .checkout-total-amount {

                font-size: 25px;

            }


            .checkout-actions {

                flex-direction: column-reverse;

                width: 100%;

            }


            .checkout-back,
            .checkout-place-btn {

                width: 100%;

            }


            .checkout-success {

                margin-top: 10px;

                padding: 38px 22px;

                border-radius: 16px;

            }


            .success-logo {

                width: 120px;

            }


            .checkout-success h2 {

                font-size: 25px;

            }


            .success-detail {

                align-items: flex-start;

            }

        }


        /* =====================================================
           VERY SMALL MOBILE
        ===================================================== */

        @media (max-width: 400px) {

            .checkout-hero h1 {

                font-size: 34px;

            }


            .checkout-total {

                align-items: flex-start;

                flex-direction: column;

                gap: 5px;

            }


            .checkout-total-amount {

                font-size: 28px;

            }


            .checkout-product {

                align-items: flex-start;

                flex-direction: column;

            }


            .checkout-product-subtotal {

                align-self: flex-end;

            }

        }

    </style>

</head>


<body>


<main class="checkout-page">


<?php if ($orderSuccess): ?>


    <!-- =====================================
         SUCCESS
    ====================================== -->

    <section class="checkout-success">


        <img
            src="assets/images/logo.png"
            alt="NAVA Fade Studio Logo"
            class="success-logo"
        >


        <div class="checkout-success-icon">
            ✓
        </div>


        <span class="success-label">
            NAVA FADE STUDIO
        </span>


        <h2>
            Order Placed Successfully!
        </h2>


        <p>
            Thank you,
            <strong>
                <?= htmlspecialchars($customer["full_name"]) ?>
            </strong>.
        </p>


        <p>
            Your order has been received and is currently
            <strong>Pending</strong>.
        </p>


        <div class="success-order-details">


            <div class="success-detail">

                <span>
                    Order Number
                </span>

                <span>
                    #<?= (int) $order_id ?>
                </span>

            </div>


            <div class="success-detail">

                <span>
                    Total Amount
                </span>

                <span>
                    ₱<?= number_format($total, 2) ?>
                </span>

            </div>


            <div class="success-detail">
                <span>Fulfillment</span>
                <span><?= $delivery_method === "delivery" ? "Delivery" : "Pickup" ?></span>
            </div>

            <?php if ($delivery_method === "delivery"): ?>
                <div class="success-detail"><span>Delivery Address</span><span><?= htmlspecialchars($delivery_address ?? "") ?></span></div>
                <div class="success-detail"><span>Contact Number</span><span><?= htmlspecialchars($delivery_contact ?? "") ?></span></div>
                <?php if (!empty($delivery_landmark)): ?>
                    <div class="success-detail"><span>Landmark</span><span><?= htmlspecialchars($delivery_landmark) ?></span></div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="success-detail">

                <span>
                    Payment Method
                </span>

                <span>
                    <?= htmlspecialchars($payment_method ?: "Pending") ?>
                </span>

            </div>


            <?php if ($payment_method === "GCash" && !empty($reference_number)): ?>

                <div class="success-detail">
                    <span>GCash Reference</span>
                    <span><?= htmlspecialchars($reference_number) ?></span>
                </div>

            <?php endif; ?>


            <div class="success-detail">
                <span>Payment Status</span>
                <span>Pending Verification</span>
            </div>


            <div class="success-detail">

                <span>
                    Order Status
                </span>

                <span>
                    Pending
                </span>

            </div>


        </div>


        <p>
            You can check your order status anytime from
            <strong>My Orders</strong>.
        </p>


        <a
            href="shop.php"
            class="checkout-success-btn"
        >
            Continue Shopping
        </a>


    </section>


<?php else: ?>


    <!-- =====================================
         CHECKOUT HERO
    ====================================== -->

    <header class="checkout-hero">


        <img
            src="assets/images/logo.png"
            alt="NAVA Fade Studio Logo"
            class="checkout-logo"
        >


        <span class="checkout-eyebrow">
            NAVA FADE STUDIO
        </span>


        <h1>
            Secure <span>Checkout</span>
        </h1>


        <p>
            Review your information and selected grooming
            products before placing your order.
        </p>


    </header>


    <!-- =====================================
         ERROR MESSAGE
    ====================================== -->

    <?php if (!empty($errorMessage)): ?>


        <div class="checkout-error">

            <strong>
                Order Failed
            </strong>

            <p>
                <?= htmlspecialchars($errorMessage) ?>
            </p>

        </div>


    <?php endif; ?>


    <!-- =====================================
         CHECKOUT CONTENT
    ====================================== -->

    <section class="checkout-container">


        <!-- =================================
             CUSTOMER INFORMATION
        ================================== -->

        <div class="checkout-box">


            <h2>
                Customer Information
            </h2>


            <div class="customer-info">


                <!-- FULL NAME -->

                <div class="customer-info-row">


                    <div class="customer-info-icon">
                        👤
                    </div>


                    <div class="customer-info-content">

                        <span class="customer-info-label">
                            Full Name
                        </span>

                        <span class="customer-info-value">
                            <?= htmlspecialchars($customer["full_name"]) ?>
                        </span>

                    </div>


                </div>


                <!-- EMAIL -->

                <div class="customer-info-row">


                    <div class="customer-info-icon">
                        ✉
                    </div>


                    <div class="customer-info-content">

                        <span class="customer-info-label">
                            Email Address
                        </span>

                        <span class="customer-info-value">
                            <?= htmlspecialchars($customer["email"]) ?>
                        </span>

                    </div>


                </div>


                <!-- CONTACT -->

                <div class="customer-info-row">


                    <div class="customer-info-icon">
                        ☎
                    </div>


                    <div class="customer-info-content">

                        <span class="customer-info-label">
                            Contact Number
                        </span>

                        <span class="customer-info-value">
                            <?= htmlspecialchars($customer["contact_number"]) ?>
                        </span>

                    </div>


                </div>


            </div>


            <!-- NOTICE -->

            <div class="checkout-notice">


                <div class="checkout-notice-icon">
                    🔒
                </div>


                <p>
                    Your order will be submitted securely and
                    will initially have a
                    <strong>Pending</strong>
                    status until it is confirmed by NAVA Fade Studio.
                </p>


            </div>


            <!-- ORDER FULFILLMENT -->

            <div class="fulfillment-section">
                <h3>Order Fulfillment</h3>
                <div class="fulfillment-options">
                    <div class="fulfillment-option">
                        <input type="radio" id="delivery_pickup" name="delivery_method" value="pickup" <?= $delivery_method === "pickup" ? "checked" : "" ?> form="checkoutForm" required>
                        <label for="delivery_pickup">
                            <span class="fulfillment-icon">⌂</span>
                            <span class="fulfillment-copy"><strong>Pickup</strong><span>Pick up your order from NAVA Fade Studio.</span></span>
                        </label>
                    </div>
                    <div class="fulfillment-option">
                        <input type="radio" id="delivery_delivery" name="delivery_method" value="delivery" <?= $delivery_method === "delivery" ? "checked" : "" ?> form="checkoutForm" required>
                        <label for="delivery_delivery">
                            <span class="fulfillment-icon">▣</span>
                            <span class="fulfillment-copy"><strong>Delivery</strong><span>Have your order delivered to your address.</span></span>
                        </label>
                    </div>
                </div>

                <div class="delivery-fields" id="deliveryFields" <?= $delivery_method === "delivery" ? "" : "hidden" ?>>
                    <div class="delivery-field">
                        <label for="delivery_address">Delivery Address <span>*</span></label>
                        <textarea id="delivery_address" name="delivery_address" form="checkoutForm" placeholder="Enter your complete delivery address"><?= htmlspecialchars($delivery_address) ?></textarea>
                    </div>
                    <div class="delivery-field">
                        <label for="delivery_contact">Contact Number <span>*</span></label>
                        <input type="text" id="delivery_contact" name="delivery_contact" form="checkoutForm" maxlength="30" placeholder="e.g. 09XXXXXXXXX" value="<?= htmlspecialchars($delivery_contact) ?>">
                    </div>
                    <div class="delivery-field">
                        <label for="delivery_landmark">Landmark</label>
                        <input type="text" id="delivery_landmark" name="delivery_landmark" form="checkoutForm" maxlength="255" placeholder="Optional landmark" value="<?= htmlspecialchars($delivery_landmark) ?>">
                    </div>
                    <div class="delivery-field">
                        <label for="delivery_notes">Delivery Notes</label>
                        <textarea id="delivery_notes" name="delivery_notes" form="checkoutForm" placeholder="Optional instructions for the delivery"><?= htmlspecialchars($delivery_notes) ?></textarea>
                    </div>
                    <p class="delivery-note">Please make sure your delivery address and contact number are correct before placing your order.</p>
                </div>
            </div>


            <!-- PAYMENT METHOD -->

            <div class="payment-section">

                <h3>Payment Method</h3>

                <div class="payment-options">

                    <div class="payment-option">

                        <input
                            type="radio"
                            id="payment_cash"
                            name="payment_method"
                            value="Cash"
                            <?= $payment_method === "Cash" ? "checked" : "" ?>
                            form="checkoutForm"
                            required
                        >

                        <label for="payment_cash">
                            <span class="payment-icon">₱</span>
                            <span class="payment-copy">
                                <strong>Cash</strong>
                                <span>Pay in cash when your order is received.</span>
                            </span>
                        </label>

                    </div>


                    <div class="payment-option">

                        <input
                            type="radio"
                            id="payment_gcash"
                            name="payment_method"
                            value="GCash"
                            <?= $payment_method === "GCash" ? "checked" : "" ?>
                            form="checkoutForm"
                            required
                        >

                        <label for="payment_gcash">
                            <span class="payment-icon">G</span>
                            <span class="payment-copy">
                                <strong>GCash</strong>
                                <span>Pay using GCash.</span>
                            </span>
                        </label>

                    </div>

                </div>


                <div
                    class="gcash-reference <?= $payment_method === "GCash" ? "show" : "" ?>"
                    id="gcashReference"
                >

                    <label for="reference_number">
                        GCash Reference Number
                    </label>

                    <input
                        type="text"
                        id="reference_number"
                        name="reference_number"
                        maxlength="100"
                        placeholder="Enter your GCash reference number"
                        value="<?= htmlspecialchars(
                            $reference_number ?? "",
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>"
                        form="checkoutForm"
                        <?= $payment_method === "GCash" ? "required" : "" ?>
                    >

                    <small>
                        Required when GCash is selected. Reference number only — no receipt upload required.
                    </small>

                </div>


                <p class="payment-note">
                    Your payment will initially be marked
                    <strong>Pending</strong>
                    until NAVA Fade Studio verifies it.
                </p>

            </div>


        </div>


        <!-- =================================
             ORDER SUMMARY
        ================================== -->

        <div class="checkout-box">


            <h2>
                Order Summary
            </h2>


            <div class="checkout-products">


                <?php foreach ($orderItems as $item): ?>


                    <div class="checkout-product">


                        <div class="checkout-product-info">


                            <h3>
                                <?= htmlspecialchars($item["product_name"]) ?>
                            </h3>


                            <div class="checkout-product-details">


                                <span class="quantity">
                                    Qty:
                                    <?= (int) $item["quantity"] ?>
                                </span>


                                <span>
                                    ₱<?= number_format($item["price"], 2) ?>
                                    each
                                </span>


                            </div>


                        </div>


                        <div class="checkout-product-subtotal">

                            ₱<?= number_format($item["subtotal"], 2) ?>

                        </div>


                    </div>


                <?php endforeach; ?>


            </div>


            <!-- TOTAL -->

            <div class="checkout-total">


                <span class="checkout-total-label">
                    Total Amount
                </span>


                <span class="checkout-total-amount">
                    ₱<?= number_format($total, 2) ?>
                </span>


            </div>


        </div>


    </section>


    <!-- =====================================
         ACTIONS
    ====================================== -->

    <div class="checkout-actions">


        <a
            href="cart.php"
            class="checkout-back"
        >
            ← Back to Cart
        </a>


        <form
            method="POST"
            action="checkout.php"
            id="checkoutForm"
        >

            <button
                type="submit"
                class="checkout-place-btn"
            >
                Place Order
            </button>

        </form>


    </div>


<?php endif; ?>


</main>


<script>
(function () {

    const cash = document.getElementById("payment_cash");
    const gcash = document.getElementById("payment_gcash");
    const referenceField = document.getElementById("gcashReference");
    const referenceInput = document.getElementById("reference_number");

    function updatePaymentFields() {
        if (!cash || !gcash || !referenceField || !referenceInput) return;
        const isGCash = gcash.checked;
        referenceField.classList.toggle("show", isGCash);
        referenceInput.required = isGCash;
        if (!isGCash) referenceInput.value = "";
    }

    if (cash && gcash) {
        cash.addEventListener("change", updatePaymentFields);
        gcash.addEventListener("change", updatePaymentFields);
    }

    const fulfillmentRadios = document.querySelectorAll('input[name="delivery_method"]');
    const deliveryFields = document.getElementById("deliveryFields");
    const deliveryAddress = document.getElementById("delivery_address");
    const deliveryContact = document.getElementById("delivery_contact");

    function updateDeliveryFields() {
        if (!deliveryFields) return;
        const selected = document.querySelector('input[name="delivery_method"]:checked')?.value;
        const isDelivery = selected === "delivery";
        deliveryFields.hidden = !isDelivery;
        if (deliveryAddress) deliveryAddress.required = isDelivery;
        if (deliveryContact) deliveryContact.required = isDelivery;
    }

    fulfillmentRadios.forEach(function (radio) {
        radio.addEventListener("change", updateDeliveryFields);
    });

    updatePaymentFields();
    updateDeliveryFields();

})();
</script>


</body>

</html>