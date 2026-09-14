<?php

session_start();

/* =========================================
   CUSTOMER SESSION / PROFILE
========================================= */

$customer_logged_in = isset($_SESSION["customer_id"]);
$customer_name = $_SESSION["customer_name"] ?? "";
$customer_email = $_SESSION["customer_email"] ?? "";

require_once "config/Database.php";
require_once "classes/Product.php";


$database = new Database();
$db = $database->connect();

/* =========================================
   GET CUSTOMER PROFILE
========================================= */

$customer = null;
$initials = "";

if ($customer_logged_in) {

    $customer_query = $db->prepare("
        SELECT full_name, email
        FROM customers
        WHERE id = :id
        LIMIT 1
    ");

    $customer_query->execute([
        ":id" => (int) $_SESSION["customer_id"]
    ]);

    $customer = $customer_query->fetch(PDO::FETCH_ASSOC);

    if ($customer) {

        $name_parts = preg_split(
            '/\s+/',
            trim($customer["full_name"])
        );

        foreach (array_slice($name_parts, 0, 2) as $part) {
            $initials .= strtoupper(substr($part, 0, 1));
        }

    } else {

        $initials = strtoupper(
            substr($customer_name, 0, 1)
        );

    }
}

$productModel = new Product($db);


/* =========================================
   MAXIMUM ORDER QUANTITY
========================================= */

const MAX_ORDER_QUANTITY = 5;


/* =========================================
   ADD TO CART
========================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    &&
    isset($_POST["add_to_cart"])
) {

    $product_id =
        (int) ($_POST["product_id"] ?? 0);


    /*
    |--------------------------------------------------------------------------
    | CUSTOMER LOGIN
    |--------------------------------------------------------------------------
    */

    if (
        !isset($_SESSION["customer_id"])
    ) {

        header("Location: login.php");
        exit;

    }


    /*
    |--------------------------------------------------------------------------
    | GET PRODUCT
    |--------------------------------------------------------------------------
    */

    $product =
        $productModel->getById(
            $product_id
        );


    /*
    |--------------------------------------------------------------------------
    | PRODUCT VALIDATION
    |--------------------------------------------------------------------------
    */

    if (
        !$product
        ||
        $product["status"] !== "Active"
        ||
        (int) $product["stock"] <= 0
    ) {

        header(
            "Location: shop.php"
        );

        exit;

    }


    /*
    |--------------------------------------------------------------------------
    | CREATE CART
    |--------------------------------------------------------------------------
    */

    if (
        !isset($_SESSION["cart"])
    ) {

        $_SESSION["cart"] = [];

    }


    /*
    |--------------------------------------------------------------------------
    | CURRENT CART QUANTITY
    |--------------------------------------------------------------------------
    */

    $currentQuantity =
        (int) (
            $_SESSION["cart"][
                $product_id
            ]
            ?? 0
        );


    /*
    |--------------------------------------------------------------------------
    | AVAILABLE STOCK
    |--------------------------------------------------------------------------
    */

    $availableStock =
        (int) $product["stock"];


    /*
    |--------------------------------------------------------------------------
    | MAXIMUM CUSTOMER QUANTITY
    |--------------------------------------------------------------------------
    |
    | Example:
    |
    | Stock = 10
    | Maximum = 5
    |
    | Stock = 3
    | Maximum = 3
    |
    */

    $maxQuantity =
        min(
            $availableStock,
            MAX_ORDER_QUANTITY
        );


    /*
    |--------------------------------------------------------------------------
    | MAXIMUM REACHED
    |--------------------------------------------------------------------------
    */

    if (
        $currentQuantity >=
        $maxQuantity
    ) {

        header(
            "Location: cart.php?message=max_quantity"
        );

        exit;

    }


    /*
    |--------------------------------------------------------------------------
    | ADD ONE PRODUCT
    |--------------------------------------------------------------------------
    */

    $_SESSION["cart"][
        $product_id
    ] =
        $currentQuantity + 1;


    /*
    |--------------------------------------------------------------------------
    | GO TO CART
    |--------------------------------------------------------------------------
    */

    header(
        "Location: cart.php"
    );

    exit;
}


/* =========================================
   GET ACTIVE PRODUCTS
========================================= */

$products =
    $productModel->getActive();

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
        Shop | NAVA Fade Studio
    </title>


    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >


    <style>

        /*
        |--------------------------------------------------------------------------
        | MAXIMUM REACHED BUTTON
        |--------------------------------------------------------------------------
        */

        .shop-add-btn:disabled {

            opacity: 0.5;

            cursor: not-allowed;

            transform: none !important;

        }


        /*
        |--------------------------------------------------------------------------
        | STOCK INFORMATION
        |--------------------------------------------------------------------------
        */

        .shop-stock-info {

            margin-top: 8px;

            color: #888;

            font-size: 12px;

        }


        .shop-stock-info strong {

            color: #b8862c;

        }

    </style>

</head>


<body>


<!-- =====================================================
     HEADER
===================================================== -->

<header class="header">

    <div class="container navbar">

        <a href="index.php" class="logo">
            <img
                src="assets/images/logo.png"
                alt="NAVA Fade Studio Logo"
            >
        </a>

        <nav>

            <a href="index.php">
                Home
            </a>

            <a href="about.php">
                About Us
            </a>

            <a href="index.php#services">
                Service
            </a>

            <a href="reviews.php">
                Reviews
            </a>

            <a href="shop.php" class="active">
                Shop
            </a>

            <a href="blog.php">
                Blog
            </a>

            <a href="book.php" class="nav-button">
                Book Now
            </a>

            <?php if (isset($_SESSION["customer_id"])): ?>

                <!-- CUSTOMER MENU -->

                <div class="customer-menu">

                    <button
                        class="customer-menu-btn"
                        type="button"
                        onclick="toggleCustomerMenu()"
                    >

                        <span class="profile-avatar">
                            <?= htmlspecialchars($initials) ?>
                        </span>

                        <span class="dropdown-arrow">
                            ▼
                        </span>

                    </button>

                    <div
                        class="customer-dropdown"
                        id="customerDropdown"
                    >

                        <a
                            href="profile.php"
                            class="customer-profile-card"
                        >

                            <span class="profile-avatar">
                                <?= htmlspecialchars($initials) ?>
                            </span>

                            <span class="profile-details">

                                <strong>
                                    <?= htmlspecialchars(
                                        $customer["full_name"]
                                    ) ?>
                                </strong>

                                <small>
                                    <?= htmlspecialchars(
                                        $customer["email"]
                                    ) ?>
                                </small>

                            </span>

                        </a>

                        <div class="dropdown-divider"></div>

                        <a
                            href="my-orders.php"
                            class="customer-dropdown-link"
                        >
                            <span class="dropdown-icon">🛍️</span>
                            My Orders
                        </a>

                        <a
                            href="appointments.php"
                            class="customer-dropdown-link"
                        >
                            <span class="dropdown-icon">📅</span>
                            My Appointments
                        </a>

                        <a
                            href="review.php"
                            class="customer-dropdown-link"
                        >
                            <span class="dropdown-icon">⭐</span>
                            Write a Review
                        </a>

                        <div class="dropdown-divider"></div>

                        <a
                            href="logout.php"
                            class="customer-dropdown-link logout-link"
                        >
                            <span class="dropdown-icon">🚪</span>
                            Logout
                        </a>

                    </div>

                </div>

            <?php else: ?>

                <a href="register.php" class="nav-button">
                    Register
                </a>

            <?php endif; ?>

        </nav>

    </div>

</header>


<!-- =========================================
     SHOP HERO
========================================= -->

<section class="shop-hero">

    <div class="shop-hero-content">


        <span>
            NAVA FADE STUDIO
        </span>


        <h1>
            OUR SHOP
        </h1>


        <p>
            Discover premium grooming products
            designed to keep you looking sharp
            every day.
        </p>


    </div>


</section>


<!-- =========================================
     PRODUCTS
========================================= -->

<section class="shop-page">


    <div class="shop-page-header">


        <span>
            PREMIUM GROOMING
        </span>


        <h2>
            OUR PRODUCTS
        </h2>


    </div>


    <div class="shop-grid">


        <?php if (
            !empty($products)
        ): ?>


            <?php foreach (
                $products as $item
            ): ?>


                <?php

                /*
                |--------------------------------------------------------------------------
                | CURRENT CART QUANTITY
                |--------------------------------------------------------------------------
                */

                $cartQuantity =
                    (int) (
                        $_SESSION["cart"][
                            $item["id"]
                        ]
                        ?? 0
                    );


                /*
                |--------------------------------------------------------------------------
                | AVAILABLE STOCK
                |--------------------------------------------------------------------------
                */

                $availableStock =
                    (int) $item["stock"];


                /*
                |--------------------------------------------------------------------------
                | MAXIMUM CUSTOMER QUANTITY
                |--------------------------------------------------------------------------
                */

                $maxQuantity =
                    min(
                        $availableStock,
                        MAX_ORDER_QUANTITY
                    );


                /*
                |--------------------------------------------------------------------------
                | CAN ADD?
                |--------------------------------------------------------------------------
                */

                $canAdd =
                    $cartQuantity <
                    $maxQuantity;

                ?>


                <div
                    class="shop-product-card1"
                >


                    <img
                        src="assets/images/<?= htmlspecialchars(
                            $item["image"]
                        ) ?>"
                        alt="<?= htmlspecialchars(
                            $item["product_name"]
                        ) ?>"
                    >


                    <div class="product-info">


                        <h3>
                            <?= htmlspecialchars(
                                $item["product_name"]
                            ) ?>
                        </h3>


                        <div class="product-rating">
                            ★★★★★
                        </div>


                        <p>
                            <?= htmlspecialchars(
                                $item["description"]
                            ) ?>
                        </p>


                        <div
                            class="shop-product-bottom"
                        >


                            <div>


                                <span
                                    class="shop-price"
                                >
                                    ₱<?= number_format(
                                        $item["price"],
                                        2
                                    ) ?>
                                </span>


                                <?php if (
                                    $availableStock > 0
                                ): ?>

                                    <div
                                        class="shop-stock-info"
                                    >

                                        Available:

                                        <strong>
                                            <?= $availableStock ?>
                                        </strong>

                                        &nbsp;|&nbsp;

                                        Max:

                                        <strong>
                                            <?= $maxQuantity ?>
                                        </strong>

                                    </div>

                                <?php endif; ?>


                            </div>


                            <?php if (
                                $availableStock > 0
                            ): ?>


                                <form
                                    method="POST"
                                    action="shop.php"
                                >


                                    <input
                                        type="hidden"
                                        name="product_id"
                                        value="<?= (int) $item["id"] ?>"
                                    >


                                    <button
                                        class="shop-add-btn"
                                        type="submit"
                                        name="add_to_cart"
                                        <?= !$canAdd
                                            ? "disabled"
                                            : ""
                                        ?>
                                    >

                                        <?php if (
                                            $canAdd
                                        ): ?>

                                            ADD TO CART

                                        <?php else: ?>

                                            MAX REACHED

                                        <?php endif; ?>

                                    </button>


                                </form>


                            <?php else: ?>


                                <button
                                    class="shop-add-btn"
                                    type="button"
                                    disabled
                                >

                                    OUT OF STOCK

                                </button>


                            <?php endif; ?>


                        </div>


                    </div>


                </div>


            <?php endforeach; ?>


        <?php else: ?>


            <p>
                No products available at the moment.
            </p>


        <?php endif; ?>


    </div>


</section>


<!-- =====================================================
     FOOTER
===================================================== -->

<footer class="footer">


    <div class="footer-content">


        <!-- BRAND -->

        <div>

            <img
                src="assets/images/logo.png"
                class="footer-logo"
                alt="NAVA Fade Studio Logo"
            >


            <p>

                NAVA Fade Studio is dedicated to
                <br>delivering clean, modern, and
                <br>personalized grooming experiences.

            </p>

                <div class="footer-socials">


                    <a
                        href="#"
                        aria-label="Facebook">

                        <img
                            src="assets/images/facebook.png"
                            alt="Facebook">

                    </a>



                    <a
                        href="#"
                        aria-label="Instagram">

                        <img
                            src="assets/images/instagram.png"
                            alt="Instagram">

                    </a>



                    <a
                        href="#"
                        aria-label="X">

                        <img
                            src="assets/images/x-icon.png"
                            alt="X">

                    </a>


                </div>

        </div>



        <!-- ABOUT LINKS -->

        <div>

            <h3>
                About
            </h3>


            <a href="about.php">
                About Us
            </a>

            <a href="#services">
                Services
            </a>

            <a href="shop.php">
                Shop
            </a>

            <a href="blog.php">
                Blog
            </a>

        </div>



        <!-- SUPPORT -->

        <div>

            <h3>
                Support
            </h3>


            <a href="about.php">
                Who We Are
            </a>

            <a href="#services">
                Our Services
            </a>

            <a href="book.php">
                Book Appointment
            </a>

            <a href="#">
                Contact Us
            </a>

        </div>



        <!-- ADDRESS -->

        <div>

            <h3>
                Address
            </h3>


            <!-- KEEP YOUR EXISTING ADDRESS -->

            <p>📍 Amlan, Negros Oriental</p>

            <p>📧 navafadestudio@gmail.com</p>

            <p>📞 0969 407 4629</p>

        </div>


    </div>


</footer>


<script>

/* =========================================
   CUSTOMER DROPDOWN
========================================= */

function toggleCustomerMenu() {

    const dropdown =
        document.getElementById(
            "customerDropdown"
        );

    if (!dropdown) {
        return;
    }

    dropdown.classList.toggle("show");

}


/* Close dropdown when clicking outside */

document.addEventListener(
    "click",
    function(event) {

        const menu =
            document.querySelector(
                ".customer-menu"
            );

        if (
            menu &&
            !menu.contains(event.target)
        ) {

            document
                .getElementById(
                    "customerDropdown"
                )
                ?.classList
                .remove("show");

        }

    }
);

</script>


</body>

</html>