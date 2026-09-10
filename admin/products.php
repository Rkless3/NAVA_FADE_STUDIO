<?php

session_start();

/*
|--------------------------------------------------------------------------
| Check Admin Login
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION["admin_logged_in"]) ||
    $_SESSION["admin_logged_in"] !== true
) {
    header("Location: ../login.php");
    exit;
}

require_once "../config/Database.php";
require_once "../classes/Product.php";


/*
|--------------------------------------------------------------------------
| Database Connection
|--------------------------------------------------------------------------
*/

$database = new Database();
$db = $database->connect();

$product = new Product($db);


/*
|--------------------------------------------------------------------------
| Delete Product
|--------------------------------------------------------------------------
*/

if (isset($_GET["delete"])) {

    $id = intval($_GET["delete"]);

    if ($product->delete($id)) {

        header("Location: products.php?message=deleted");
        exit;
    }

    header("Location: products.php?message=error");
    exit;
}


/*
|--------------------------------------------------------------------------
| Add Product
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $price = trim($_POST["price"] ?? "");
    $stock = trim($_POST["stock"] ?? "");
    $status = trim($_POST["status"] ?? "Active");

    $image = "";


    /*
    |--------------------------------------------------------------------------
    | Upload Product Image
    |--------------------------------------------------------------------------
    */

    if (
        isset($_FILES["image"]) &&
        $_FILES["image"]["error"] === UPLOAD_ERR_OK
    ) {

        $file = $_FILES["image"];

        $originalName = $file["name"];
        $tmpName = $file["tmp_name"];

        $extension = strtolower(
            pathinfo($originalName, PATHINFO_EXTENSION)
        );


        $allowedExtensions = [
            "jpg",
            "jpeg",
            "png",
            "webp"
        ];


        if (!in_array($extension, $allowedExtensions, true)) {

            header(
                "Location: products.php?message=invalid_image"
            );

            exit;
        }


        /*
        |--------------------------------------------------------------------------
        | Create Unique Filename
        |--------------------------------------------------------------------------
        */

        $image = uniqid("product_", true) . "." . $extension;


        /*
        |--------------------------------------------------------------------------
        | Image Destination
        |--------------------------------------------------------------------------
        */

        $uploadPath =
            "../assets/images/" . $image;


        if (!move_uploaded_file($tmpName, $uploadPath)) {

            header(
                "Location: products.php?message=upload_error"
            );

            exit;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Validate Product Information
    |--------------------------------------------------------------------------
    */

    if (
        !empty($name) &&
        !empty($description) &&
        $price !== "" &&
        $stock !== "" &&
        !empty($image)
    ) {

        $product->product_name = $name;
        $product->description = $description;
        $product->price = (float) $price;
        $product->stock = (int) $stock;
        $product->image = $image;
        $product->status = $status;


        if ($product->create()) {

            header(
                "Location: products.php?message=created"
            );

            exit;
        }
    }


    header(
        "Location: products.php?message=error"
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| Get All Products
|--------------------------------------------------------------------------
*/

$products = $product->getAll();


/*
|--------------------------------------------------------------------------
| Product Statistics
|--------------------------------------------------------------------------
*/

$totalProducts = count($products);

$activeProducts = 0;
$inactiveProducts = 0;
$lowStockProducts = 0;
$outOfStockProducts = 0;


foreach ($products as $item) {

    if (($item["status"] ?? "") === "Active") {

        $activeProducts++;

    } else {

        $inactiveProducts++;
    }


    $stock = (int) ($item["stock"] ?? 0);


    if ($stock <= 0) {

        $outOfStockProducts++;

    } elseif ($stock <= 5) {

        $lowStockProducts++;
    }
}


$stockAttention =
    $lowStockProducts + $outOfStockProducts;

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
        Manage Products | NAVA Fade Studio
    </title>


    <style>

        /* =====================================================
           RESET
        ===================================================== */

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }


        html {
            scroll-behavior: smooth;
        }


        body {

            min-height: 100vh;

            font-family:
                Bahnschrift,
                Myriad Pro; 

            background:
                linear-gradient(
                    rgba(8, 12, 22, 0.92),
                    rgba(8, 12, 22, 0.96)
                ),
                url("../assets/images/pattern3.png");

            background-size: cover;

            background-position: center;

            background-attachment: fixed;

            color: #ffffff;

        }


        /* =====================================================
           HEADER
        ===================================================== */

        .admin-header {

            width: 100%;

            height: 95px;

            display: flex;

            align-items: center;

            justify-content: space-between;

            padding: 0 50px;

            background: #0e1423;

            border-bottom: 2px solid #b8862c;

            position: sticky;

            top: 0;

            z-index: 1000;

        }


        .admin-logo {

            display: flex;

            align-items: center;

            gap: 15px;

        }


        .admin-logo img {

            width: 165px;

            height: 165px;

            object-fit: contain;

        }


        .admin-user {

            display: flex;

            align-items: center;

            gap: 20px;

        }


        .admin-user span {

            font-size: 15px;

        }


        .admin-user span strong {

            color: #d4a33a;

        }


        .logout-btn {

            padding: 10px 20px;

            color: #b8862c;

            border: 2px solid #b8862c;

            border-radius: 8px;

            text-decoration: none;

            font-weight: bold;

            transition: 0.3s ease;

        }


        .logout-btn:hover {

            background: #b8862c;

            color: #0e1423;

        }


        /* =====================================================
           LAYOUT
        ===================================================== */

        .dashboard {

            display: flex;

            min-height:
                calc(100vh - 95px);

        }


        /* =====================================================
           MAIN CONTENT
        ===================================================== */

        .main-content {

            flex: 1;

            min-width: 0;

            padding: 42px 44px 60px;

        }


        /* =====================================================
           PAGE HEADER
        ===================================================== */

        .page-header {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 25px;

            margin-bottom: 28px;

        }


        .page-header h2 {

            font-size: 36px;

            line-height: 1.1;

            color: #ffffff;

        }


        .page-header p {

            margin-top: 8px;

            color: #aaa;

            font-size: 14px;

        }


        .add-btn {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            padding: 13px 25px;

            background: #b8862c;

            color: #0e1423;

            border: none;

            border-radius: 8px;

            font-weight: bold;

            text-decoration: none;

            cursor: pointer;

            white-space: nowrap;

            transition: 0.3s ease;

        }


        .add-btn:hover {

            background: #d4a33a;

            transform: translateY(-2px);

        }


        /* =====================================================
           STATISTICS
        ===================================================== */

        .stats-grid {

            display: grid;

            grid-template-columns:
                repeat(3, minmax(0, 1fr));

            gap: 18px;

            margin-bottom: 30px;

        }


        .stat-card {

            min-height: 105px;

            padding: 22px 24px;

            background:
                linear-gradient(
                    145deg,
                    rgba(18, 27, 46, 0.97),
                    rgba(14, 20, 35, 0.97)
                );

            border:
                1px solid
                rgba(184, 134, 44, 0.35);

            border-radius: 14px;

            position: relative;

            overflow: hidden;

        }


        .stat-card::after {

            content: "";

            position: absolute;

            width: 80px;

            height: 80px;

            right: -35px;

            bottom: -35px;

            border:
                1px solid
                rgba(184, 134, 44, 0.25);

            border-radius: 50%;

        }


        .stat-label {

            color: #9ba6b8;

            font-size: 11px;

            letter-spacing: 1.5px;

            text-transform: uppercase;

            margin-bottom: 8px;

        }


        .stat-number {

            color: #f0c34e;

            font-size: 28px;

            font-weight: 700;

        }


        .stat-description {

            margin-top: 5px;

            color: #78859a;

            font-size: 12px;

        }


        /* =====================================================
           SEARCH AREA
        ===================================================== */

        .list-toolbar {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;

            margin-bottom: 18px;

        }


        .list-title {

            font-size: 19px;

            font-weight: 700;

            color: #ffffff;

        }


        .search-box {

            width: 300px;

            position: relative;

        }


        .search-box input {

            width: 100%;

            height: 42px;

            padding:
                0 15px 0 43px;

            background: #111a2b;

            color: #ffffff;

            border:
                1px solid
                #344055;

            border-radius: 9px;

            outline: none;

            font-family: inherit;

            font-size: 13px;

            transition: 0.25s ease;

        }


        .search-box input::placeholder {

            color: #748096;

        }


        .search-box input:focus {

            border-color: #b8862c;

        }


        .search-icon {

            position: absolute;

            left: 15px;

            top: 50%;

            transform: translateY(-50%);

            font-size: 15px;

            pointer-events: none;

        }


        /* =====================================================
           MESSAGE
        ===================================================== */

        .message {

            padding: 14px 18px;

            margin-bottom: 22px;

            background:
                rgba(184, 134, 44, 0.12);

            color: #d5a63a;

            border:
                1px solid
                rgba(184, 134, 44, 0.6);

            border-radius: 9px;

            font-weight: bold;

        }


        .message.error {

            background:
                rgba(229, 115, 115, 0.12);

            color: #e57373;

            border-color: #e57373;

        }


        /* =====================================================
           TABLE
        ===================================================== */

        .table-container {

            width: 100%;

            overflow-x: auto;

            background: #0e1423;

            border:
                1px solid
                rgba(184, 134, 44, 0.25);

            border-radius: 15px;

            box-shadow:
                0 8px 25px
                rgba(0, 0, 0, 0.15);

        }


        table {

            width: 100%;

            border-collapse: collapse;

            min-width: 1050px;

        }


        th {

            padding: 16px 18px;

            text-align: left;

            background: #b8862c;

            color: #0e1423;

            font-size: 12px;

            text-transform: uppercase;

            letter-spacing: 0.7px;

            white-space: nowrap;

        }


        td {

            padding: 16px 18px;

            border-bottom:
                1px solid
                #252d3d;

            vertical-align: middle;

            font-size: 13px;

        }


        tr:last-child td {

            border-bottom: none;

        }


        tbody tr {

            transition: 0.2s ease;

        }


        tbody tr:hover {

            background:
                rgba(184, 134, 44, 0.045);

        }


        /* =====================================================
           PRODUCT IMAGE
        ===================================================== */

        .product-image {

            width: 82px;

            height: 65px;

            object-fit: contain;

            background: #ffffff;

            border:
                2px solid
                #b8862c;

            border-radius: 8px;

            padding: 4px;

            display: block;

        }


        .no-image {

            width: 82px;

            height: 65px;

            display: flex;

            align-items: center;

            justify-content: center;

            background: #151c2d;

            border:
                1px solid
                #3c4658;

            border-radius: 8px;

            color: #777;

            font-size: 11px;

            text-align: center;

        }


        /* =====================================================
           PRODUCT NAME
        ===================================================== */

        .product-name {

            color: #ffffff;

            font-weight: 700;

            font-size: 14px;

            min-width: 150px;

        }


        /* =====================================================
           DESCRIPTION
        ===================================================== */

        .description {

            max-width: 330px;

            color: #a8b1c1;

            line-height: 1.5;

        }


        /* =====================================================
           PRICE
        ===================================================== */

        .price {

            color: #f0c34e;

            font-weight: bold;

            white-space: nowrap;

        }


        /* =====================================================
           STOCK
        ===================================================== */

        .stock-badge {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            min-width: 82px;

            padding: 7px 11px;

            border-radius: 20px;

            font-size: 11px;

            font-weight: bold;

        }


        .stock-in {

            background:
                rgba(111, 207, 151, 0.12);

            color: #6fcf97;

            border:
                1px solid
                rgba(111, 207, 151, 0.25);

        }


        .stock-low {

            background:
                rgba(240, 195, 78, 0.12);

            color: #f0c34e;

            border:
                1px solid
                rgba(240, 195, 78, 0.25);

        }


        .stock-out {

            background:
                rgba(229, 115, 115, 0.12);

            color: #e57373;

            border:
                1px solid
                rgba(229, 115, 115, 0.25);

        }


        /* =====================================================
           STATUS
        ===================================================== */

        .status {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            padding: 6px 12px;

            border-radius: 20px;

            font-size: 11px;

            font-weight: bold;

        }


        .status-active {

            background:
                rgba(111, 207, 151, 0.12);

            color: #6fcf97;

            border:
                1px solid
                rgba(111, 207, 151, 0.25);

        }


        .status-inactive {

            background:
                rgba(229, 115, 115, 0.12);

            color: #e57373;

            border:
                1px solid
                rgba(229, 115, 115, 0.25);

        }


        /* =====================================================
           ACTION BUTTONS
        ===================================================== */

        .actions {

            display: flex;

            align-items: center;

            gap: 7px;

            white-space: nowrap;

        }


        .edit-btn,
        .delete-btn {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            padding: 8px 13px;

            border-radius: 6px;

            text-decoration: none;

            font-size: 12px;

            font-weight: bold;

            transition: 0.25s ease;

        }


        .edit-btn {

            background: #b8862c;

            color: #0e1423;

        }


        .edit-btn:hover {

            background: #d4a33a;

            transform: translateY(-1px);

        }


        .delete-btn {

            background: #8b3030;

            color: #ffffff;

        }


        .delete-btn:hover {

            background: #a43b3b;

            transform: translateY(-1px);

        }

        .stock-btn {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            padding: 8px 12px;

            background: #b8862c;

            color: #0e1423;

            border: none;

            border-radius: 6px;

            text-decoration: none;

            font-size: 12px;

            font-weight: bold;

            cursor: pointer;

            transition: 0.3s ease;
        }


        .stock-btn:hover {

            background: #d4a33a;

            transform: translateY(-1px);
        }


        /* =====================================================
           EMPTY STATE
        ===================================================== */

        .empty-message {

            padding: 55px 30px !important;

            text-align: center;

            color: #858fa0;

        }


        .empty-message strong {

            display: block;

            color: #ffffff;

            font-size: 17px;

            margin-bottom: 6px;

        }


        /* =====================================================
           ADD PRODUCT SECTION
        ===================================================== */

        .add-product-section {

            margin-top: 35px;

            padding: 30px;

            background: #0e1423;

            border:
                1px solid
                rgba(184, 134, 44, 0.28);

            border-radius: 15px;

        }


        .add-product-header {

            margin-bottom: 25px;

        }


        .add-product-header h2 {

            font-size: 25px;

            margin-bottom: 7px;

        }


        .add-product-header p {

            color: #aaa;

            font-size: 13px;

        }


        .product-form {

            max-width: 900px;

        }


        .form-group {

            display: flex;

            flex-direction: column;

            margin-bottom: 19px;

        }


        .form-group label {

            margin-bottom: 8px;

            color: #ffffff;

            font-size: 13px;

            font-weight: bold;

        }


        .form-group input,
        .form-group textarea,
        .form-group select {

            width: 100%;

            padding: 12px 14px;

            background: #151c2d;

            color: #ffffff;

            border:
                1px solid
                #3f4859;

            border-radius: 8px;

            font-family: inherit;

            font-size: 13px;

            outline: none;

            transition: 0.25s ease;

        }


        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {

            border-color: #b8862c;

            box-shadow:
                0 0 0 2px
                rgba(184, 134, 44, 0.08);

        }


        .form-group textarea {

            resize: vertical;

            min-height: 110px;

        }


        .form-group input[type="file"] {

            padding: 10px;

            cursor: pointer;

        }


        .form-group small {

            margin-top: 7px;

            color: #777f90;

            font-size: 11px;

        }


        .form-row {

            display: grid;

            grid-template-columns:
                1fr 1fr;

            gap: 18px;

        }


        .save-product-btn {

            padding: 13px 24px;

            background: #b8862c;

            color: #0e1423;

            border: none;

            border-radius: 8px;

            font-weight: bold;

            cursor: pointer;

            font-family: inherit;

            transition: 0.3s ease;

        }


        .save-product-btn:hover {

            background: #d4a33a;

            transform: translateY(-2px);

        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 1100px) {

            .main-content {

                padding:
                    35px 28px 50px;

            }


            .stats-grid {

                grid-template-columns:
                    repeat(3, 1fr);

            }

        }


        @media (max-width: 800px) {

            .admin-header {

                padding: 0 20px;

            }


            .admin-logo img {

                width: 145px;

                height: 145px;

            }


            .admin-user span {

                display: none;

            }


            .dashboard {

                flex-direction: column;

            }


            .sidebar {

                width: 100%;

                min-height: auto;

                display: flex;

                align-items: center;

                gap: 5px;

                overflow-x: auto;

                padding: 15px;

                border-right: none;

                border-bottom:
                    1px solid
                    rgba(184, 134, 44, 0.5);

            }


            .sidebar-title {

                display: none;

            }


            .sidebar a {

                white-space: nowrap;

                margin: 0;

                padding:
                    12px 16px;

            }


            .main-content {

                padding:
                    28px 20px 45px;

            }


            .page-header {

                flex-direction: column;

                align-items: flex-start;

                gap: 18px;

            }


            .page-header h2 {

                font-size: 31px;

            }


            .stats-grid {

                grid-template-columns:
                    1fr;

            }


            .list-toolbar {

                flex-direction: column;

                align-items: flex-start;

            }


            .search-box {

                width: 100%;

            }


            .form-row {

                grid-template-columns:
                    1fr;

            }

        }


        @media (max-width: 500px) {

            .admin-header {

                height: 85px;

                padding: 0 12px;

            }


            .admin-logo img {

                width: 130px;

                height: 130px;

            }


            .logout-btn {

                padding:
                    8px 14px;

                font-size: 12px;

            }


            .main-content {

                padding:
                    25px 14px 40px;

            }


            .page-header h2 {

                font-size: 28px;

            }


            .add-product-section {

                padding: 22px 18px;

            }

        }

    </style>

</head>


<body>

<?php include "../admin/navbar.php"; ?>

<!-- =====================================================
     ADMIN LAYOUT
====================================================== -->

<div class="admin-layout">

<?php include "../admin/sidebar.php"; ?>

<!-- =================================================
         MAIN CONTENT
    ================================================== -->

    <main class="main-content">


        <!-- PAGE HEADER -->

        <div class="page-header">


            <div>

                <h2>
                    Products
                </h2>

                <p>
                    Manage the products sold by
                    NAVA Fade Studio.
                </p>

            </div>


            <a
                href="#add-product"
                class="add-btn"
            >
                + Add Product
            </a>


        </div>



        <!-- =================================================
             MESSAGES
        ================================================== -->

        <?php if (isset($_GET["message"])): ?>


            <?php if ($_GET["message"] === "created"): ?>

                <div class="message">
                    Product added successfully!
                </div>


            <?php elseif ($_GET["message"] === "deleted"): ?>

                <div class="message">
                    Product deleted successfully!
                </div>


            <?php elseif ($_GET["message"] === "invalid_image"): ?>

                <div class="message error">

                    Invalid image format.
                    Please use JPG, JPEG, PNG, or WEBP.

                </div>


            <?php elseif ($_GET["message"] === "upload_error"): ?>

                <div class="message error">

                    Image upload failed.
                    Please try again.

                </div>


            <?php elseif ($_GET["message"] === "error"): ?>

                <div class="message error">

                    Something went wrong.

                </div>


            <?php endif; ?>


        <?php endif; ?>



        <!-- =================================================
             STATISTICS
        ================================================== -->

        <section class="stats-grid">


            <!-- TOTAL PRODUCTS -->

            <div class="stat-card">

                <div class="stat-label">
                    Total Products
                </div>

                <div class="stat-number">
                    <?= $totalProducts ?>
                </div>

                <div class="stat-description">
                    Products in the shop
                </div>

            </div>


            <!-- ACTIVE PRODUCTS -->

            <div class="stat-card">

                <div class="stat-label">
                    Active Products
                </div>

                <div class="stat-number">
                    <?= $activeProducts ?>
                </div>

                <div class="stat-description">
                    <?= $inactiveProducts ?>
                    inactive
                </div>

            </div>


            <!-- STOCK ATTENTION -->

            <div class="stat-card">

                <div class="stat-label">
                    Stock Attention
                </div>

                <div class="stat-number">
                    <?= $stockAttention ?>
                </div>

                <div class="stat-description">

                    <?= $lowStockProducts ?>
                    low stock ·
                    <?= $outOfStockProducts ?>
                    out of stock

                </div>

            </div>


        </section>



        <!-- =================================================
             PRODUCT LIST TOOLBAR
        ================================================== -->

        <div class="list-toolbar">


            <div class="list-title">
                Product List
            </div>


            <div class="search-box">

                <span class="search-icon">
                    🔍
                </span>

                <input
                    type="text"
                    id="productSearch"
                    placeholder="Search products..."
                    autocomplete="off"
                >

            </div>


        </div>



        <!-- =================================================
             PRODUCTS TABLE
        ================================================== -->

        <div class="table-container">


            <table id="productsTable">


                <thead>

                    <tr>

                        <th>
                            Image
                        </th>

                        <th>
                            Product
                        </th>

                        <th>
                            Description
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


                    <?php if (!empty($products)): ?>


                        <?php foreach ($products as $item): ?>


                            <tr class="product-row">


                                <!-- IMAGE -->

                                <td>

                                    <?php if (!empty($item["image"])): ?>

                                        <img
                                            src="../assets/images/<?= htmlspecialchars($item["image"]) ?>"
                                            alt="<?= htmlspecialchars($item["product_name"]) ?>"
                                            class="product-image"
                                        >

                                    <?php else: ?>

                                        <div class="no-image">
                                            No image
                                        </div>

                                    <?php endif; ?>

                                </td>



                                <!-- PRODUCT NAME -->

                                <td>

                                    <div class="product-name">

                                        <?= htmlspecialchars(
                                            $item["product_name"]
                                        ) ?>

                                    </div>

                                </td>



                                <!-- DESCRIPTION -->

                                <td>

                                    <div class="description">

                                        <?= htmlspecialchars(
                                            $item["description"]
                                        ) ?>

                                    </div>

                                </td>



                                <!-- PRICE -->

                                <td class="price">

                                    ₱<?= number_format(
                                        (float) $item["price"],
                                        2
                                    ) ?>

                                </td>



                                <!-- STOCK -->

                                <td>

                                    <?php

                                    $stock =
                                        (int) $item["stock"];

                                    if ($stock <= 0):

                                    ?>

                                        <span
                                            class="
                                                stock-badge
                                                stock-out
                                            "
                                        >
                                            Out of stock
                                        </span>


                                    <?php elseif ($stock <= 5): ?>

                                        <span
                                            class="
                                                stock-badge
                                                stock-low
                                            "
                                        >
                                            <?= $stock ?>
                                            left
                                        </span>


                                    <?php else: ?>

                                        <span
                                            class="
                                                stock-badge
                                                stock-in
                                            "
                                        >
                                            <?= $stock ?>
                                            in stock
                                        </span>

                                    <?php endif; ?>

                                </td>



                                <!-- STATUS -->

                                <td>

                                    <?php if (
                                        $item["status"] === "Active"
                                    ): ?>

                                        <span
                                            class="
                                                status
                                                status-active
                                            "
                                        >
                                            Active
                                        </span>

                                    <?php else: ?>

                                        <span
                                            class="
                                                status
                                                status-inactive
                                            "
                                        >
                                            Inactive
                                        </span>

                                    <?php endif; ?>

                                </td>



                                <!-- ACTIONS -->

                                <td>

                                   <div class="actions">

                                        <?php if ((int) $item["stock"] === 0): ?>

                                            <a
                                                href="add-stock.php?id=<?= (int) $item["id"] ?>"
                                                class="stock-btn"
                                            >
                                                + Add Stock
                                            </a>

                                        <?php endif; ?>


                                        <a
                                            href="edit-product.php?id=<?= (int) $item["id"] ?>"
                                            class="edit-btn"
                                        >
                                            Edit
                                        </a>


                                        <a
                                            href="products.php?delete=<?= (int) $item["id"] ?>"
                                            class="delete-btn"
                                            onclick="
                                                return confirm(
                                                    'Are you sure you want to delete this product?'
                                                );
                                            "
                                        >
                                            Delete
                                        </a>

                                    </div>
                                </td>


                            </tr>


                        <?php endforeach; ?>


                    <?php else: ?>


                        <tr>

                            <td
                                colspan="7"
                                class="empty-message"
                            >

                                <strong>
                                    No Products Found
                                </strong>

                                There are currently no products
                                in your shop.

                            </td>

                        </tr>


                    <?php endif; ?>


                </tbody>


            </table>


        </div>



        <!-- =================================================
             ADD PRODUCT
        ================================================== -->

        <section
            class="add-product-section"
            id="add-product"
        >


            <div class="add-product-header">

                <h2>
                    Add New Product
                </h2>

                <p>
                    Add a new product to the
                    NAVA Fade Studio shop.
                </p>

            </div>



            <form
                method="POST"
                class="product-form"
                enctype="multipart/form-data"
            >


                <!-- PRODUCT NAME -->

                <div class="form-group">

                    <label for="name">
                        Product Name
                    </label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        placeholder="e.g. NAVA Hair Wax"
                        required
                    >

                </div>



                <!-- DESCRIPTION -->

                <div class="form-group">

                    <label for="description">
                        Description
                    </label>

                    <textarea
                        id="description"
                        name="description"
                        placeholder="Enter product description..."
                        required
                    ></textarea>

                </div>



                <!-- PRICE + STOCK -->

                <div class="form-row">


                    <div class="form-group">

                        <label for="price">
                            Price
                        </label>

                        <input
                            type="number"
                            id="price"
                            name="price"
                            min="0"
                            step="0.01"
                            placeholder="250.00"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="stock">
                            Stock
                        </label>

                        <input
                            type="number"
                            id="stock"
                            name="stock"
                            min="0"
                            placeholder="20"
                            required
                        >

                    </div>


                </div>



                <!-- IMAGE -->

                <div class="form-group">

                    <label for="image">
                        Product Image
                    </label>

                    <input
                        type="file"
                        id="image"
                        name="image"
                        accept="
                            image/png,
                            image/jpeg,
                            image/webp
                        "
                        required
                    >

                    <small>
                        Supported formats:
                        JPG, JPEG, PNG, and WEBP.
                    </small>

                </div>



                <!-- STATUS -->

                <div class="form-group">

                    <label for="status">
                        Status
                    </label>

                    <select
                        id="status"
                        name="status"
                    >

                        <option value="Active">
                            Active
                        </option>

                        <option value="Inactive">
                            Inactive
                        </option>

                    </select>

                </div>



                <!-- SUBMIT -->

                <button
                    type="submit"
                    class="save-product-btn"
                >
                    + Add Product
                </button>


            </form>


        </section>


    </main>


</div>



<!-- =====================================================
     SEARCH SCRIPT
====================================================== -->

<script>

    const searchInput =
        document.getElementById("productSearch");

    const productRows =
        document.querySelectorAll(".product-row");


    searchInput.addEventListener(
        "input",
        function () {

            const searchValue =
                this.value
                    .toLowerCase()
                    .trim();


            productRows.forEach(
                function (row) {

                    const rowText =
                        row.textContent
                            .toLowerCase();


                    if (
                        rowText.includes(
                            searchValue
                        )
                    ) {

                        row.style.display = "";

                    } else {

                        row.style.display = "none";

                    }

                }
            );

        }
    );

</script>


</body>

</html>