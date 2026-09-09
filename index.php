<?php
session_start();

/* =====================================================
   HOMEPAGE ACCESS
   index.php is PUBLIC.
   Logged-out visitors must stay on index.php.
===================================================== */

$customer_id = $_SESSION["customer_id"] ?? null;
$customer = null;
$initials = "";

require_once "config/Database.php";
require_once "classes/Service.php";

$database = new Database();
$db = $database->connect();


$success = "";

$error = "";

$serviceObject = new Service($db);
$services = $serviceObject->getAll();

/* =========================================
   UPDATE PROFILE
========================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST" && $customer_id !== null) {

    $full_name =
        trim($_POST["full_name"] ?? "");

    $email =
        trim($_POST["email"] ?? "");

    $contact_number =
        trim($_POST["contact_number"] ?? "");


    if (
        empty($full_name) ||
        empty($email) ||
        empty($contact_number)
    ) {

        $error =
            "Please fill in all fields.";

    } elseif (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $error =
            "Please enter a valid email address.";

    } else {

        try {


            /* -------------------------
               CHECK EMAIL
            ------------------------- */

            $email_check =
                $db->prepare("
                    SELECT id
                    FROM customers
                    WHERE email = :email
                    AND id != :id
                    LIMIT 1
                ");


            $email_check->execute([

                ":email" =>
                    $email,

                ":id" =>
                    $customer_id

            ]);


            if ($email_check->fetch()) {

                $error =
                    "This email address is already being used.";

            } else {


                /* -------------------------
                   UPDATE
                ------------------------- */

                $update_query =
                    $db->prepare("
                        UPDATE customers
                        SET
                            full_name = :full_name,
                            email = :email,
                            contact_number = :contact_number
                        WHERE id = :id
                    ");


                $update_query->execute([

                    ":full_name" =>
                        $full_name,

                    ":email" =>
                        $email,

                    ":contact_number" =>
                        $contact_number,

                    ":id" =>
                        $customer_id

                ]);


                /* -------------------------
                   UPDATE SESSION
                ------------------------- */

                $_SESSION["customer_name"] =
                    $full_name;

                $_SESSION["customer_email"] =
                    $email;


                $success =
                    "Your profile has been updated successfully!";

            }

        } catch (PDOException $e) {

            $error =
                "Unable to update your profile. Please try again.";

        }

    }

}


/* =========================================
   GET CUSTOMER (ONLY IF LOGGED IN)
========================================= */

if ($customer_id !== null) {

    $customer_query =
        $db->prepare("
            SELECT
                full_name,
                email,
                contact_number,
                created_at
            FROM customers
            WHERE id = :id
            LIMIT 1
        ");

    $customer_query->execute([
        ":id" => $customer_id
    ]);

    $customer =
        $customer_query->fetch(
            PDO::FETCH_ASSOC
        );

    /* =========================================
       SAFETY CHECK
       If the customer record no longer exists,
       clear the invalid customer session but
       KEEP THE USER ON index.php.
    ========================================= */

    if (!$customer) {
        unset(
            $_SESSION["customer_id"],
            $_SESSION["customer_name"],
            $_SESSION["customer_email"]
        );

        $customer_id = null;
    }
}

/* =========================================
   INITIALS FOR LOGGED-IN CUSTOMER
========================================= */

if ($customer) {

    $name_parts =
        preg_split(
            '/\s+/',
            trim($customer["full_name"])
        );

    foreach (
        array_slice($name_parts, 0, 2)
        as $part
    ) {
        if ($part !== "") {
            $initials .=
                strtoupper(
                    substr($part, 0, 1)
                );
        }
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

    <title>NAVA Fade Studio</title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

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

            <a href="index.php" class="active">
                Home
            </a>

            <a href="about.php">
                About Us
            </a>

            <a href="#services">
                Service
            </a>

            <a href="reviews.php">
                Reviews
            </a>

            <a href="shop.php">
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

                            <?= htmlspecialchars(
                                $initials
                            ) ?>

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

                            <?= htmlspecialchars(
                                $initials
                            ) ?>

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
                        <span class="dropdown-icon">
                            🛍️
                        </span>

                        My Orders
                    </a>


                    <a
                        href="appointments.php"
                        class="customer-dropdown-link"
                    >
                        <span class="dropdown-icon">
                            📅
                        </span>

                        My Appointments
                    </a>


                    <a
                        href="review.php"
                        class="customer-dropdown-link"
                    >
                        <span class="dropdown-icon">
                            ⭐
                        </span>

                        Write a Review
                    </a>


                    <div class="dropdown-divider"></div>


                    <a
                        href="logout.php"
                        class="customer-dropdown-link logout-link"
                    >
                        <span class="dropdown-icon">
                            🚪
                        </span>

                        Logout
                    </a>


                </div>

            </div>

            <?php else: ?>

                <a
                    href="register.php"
                    class="nav-button"
                >
                    Register
                </a>

            <?php endif; ?>

        </nav>

    </div>

</header>



<!-- =====================================================
     HERO SECTION
===================================================== -->

<section id="home" class="hero">

    <div class="hero-content">


        <!-- HERO TEXT -->

        <div class="hero-text">

            <h1>

                Sharp Style. Fresh <br>Look.

                <span>Confidence.</span>

            </h1>


            <div class="gold-line"></div>


            <p>

                Discover a style that fits you perfectly.
                Our professional barbers <br>
                are dedicated to
                giving you a clean, confident look that
                suits your <br>personality.

            </p>


            <a
                href="#services"
                class="hero-button"
            >

                Explore More

                <span>➜</span>

            </a>


            <!-- OPENING HOURS -->

            <div class="opening-hours">

                <h3>
                    Opening & Closing Time
                </h3>


                <div class="hours">


                    <div>

                        <strong>
                            Monday - Friday
                        </strong>

                        <p>
                            9:00 am - 8:00 pm
                        </p>

                    </div>


                    <div class="divider"></div>


                    <div>

                        <strong>
                            Saturday - Sunday
                        </strong>

                        <p>
                            10:00 am - 7:00 pm
                        </p>

                    </div>


                </div>

            </div>

        </div>



        <!-- HERO IMAGE -->

        <div class="hero-image">

            <img
                src="assets/images/hero.png"
                alt="NAVA Fade Studio Barber"
            >

        </div>


    </div>

</section>



<!-- =====================================================
     SERVICE HIGHLIGHTS / TICKER
===================================================== -->

<div class="service-ticker">

    <div class="ticker-content">

        HAIR CUT
        &nbsp;
        &nbsp;
        +
        &nbsp;
        &nbsp;
        BEARD TRIMMING
        &nbsp;
        +
        &nbsp;
        HAIR STYLING
        &nbsp;
        +
        &nbsp;
        CLEAN SHAVE
        &nbsp;
        +
        &nbsp;
        FACIAL & WASH
        &nbsp;
        +
        &nbsp;
        HAIR TREATMENT
        &nbsp;
        +
        &nbsp;
        HOT TOWEL
        &nbsp;
        +
        &nbsp;
        HAIR CUT
        &nbsp;
        +
        &nbsp;
        BEARD TRIMMING
        &nbsp;
        +
        &nbsp;
        HAIR STYLING
        &nbsp;
        +
        &nbsp;
        CLEAN SHAVE
        &nbsp;
        +
        &nbsp;
        FACIAL & WASH
        &nbsp;
        +
        &nbsp;
        HAIR TREATMENT
        &nbsp;
        +
        &nbsp;
        HOT TOWEL
        &nbsp;
        +

    </div>

</div>



<!-- =====================================================
     OUR BARBER SERVICES
===================================================== -->

<section
    id="services"
    class="services"
>


    <div class="section-title">

        <h2>

            Our Barber <span>Services</span>

        </h2>


        <p>

            Professional grooming services designed
            to keep you looking sharp and confident.

        </p>

    </div>



    <div class="service-grid">


        <?php foreach ($services as $service): ?>


            <div class="service-card">


                <!-- SERVICE IMAGE -->

                <div class="service-image">

                    <img
                        src="assets/images/<?=
                        htmlspecialchars(
                            $service['image']
                        )
                        ?>"
                        alt="<?=
                        htmlspecialchars(
                            $service['service_name']
                        )
                        ?>"
                    >

                </div>



                <!-- SERVICE INFORMATION -->

                <div class="service-info">


                    <h3>

                        <?= htmlspecialchars(
                            $service['service_name']
                        ) ?>

                    </h3>


                    <p>

                        <?= htmlspecialchars(
                            $service['description']
                        ) ?>

                    </p>



                    <div class="service-bottom">


                        <div>

                            <strong>

                                ₱<?= number_format(
                                    $service['price'],
                                    0
                                ) ?>

                            </strong>


                            <span>

                                <?= htmlspecialchars(
                                    $service['duration']
                                ) ?>

                            </span>

                        </div>



                        <a
                            href="book.php?service=<?= $service['id'] ?>"
                            class="book-button"
                        >

                            Book Now

                        </a>


                    </div>


                </div>


            </div>


        <?php endforeach; ?>


    </div>

</section>



<!-- =========================================
     WE ARE HAPPY TO MAKE YOU HANDSOME
     ========================================= -->

<section class="about-preview" id="about-preview">

    <!-- LEFT SIDE: PHOTO + REVIEW -->
    <div class="about-preview-visual">

        <!-- Beard Trimming Photo -->
        <div class="about-preview-image">
            <img
                src="assets/images/beard-trimming.png"
                alt="Professional beard trimming at NAVA Fade Studio"
            >
        </div>

        <!-- Customer Review -->
        <div class="about-review-card">

            <div class="review-header">

                <div class="review-avatar">
                    R
                </div>

                <div class="review-user">
                    <strong>Rouilo B.</strong>
                    <span>1 review</span>
                </div>

            </div>

            <div class="review-rating">
                <span class="stars">★★★★★</span>
                <span class="review-date">2 months ago</span>
            </div>

            <p>
                Just got a haircut and beard trim at NAVA Fade Studio
                and I couldn’t be happier with the results! The barber
                was professional, precise, and paid attention to every
                detail. He made sure everything was perfect and took
                the time to shape my beard exactly how I wanted.
            </p>

            <p>
                The shop is clean, has a great atmosphere, and everyone
                is welcoming. You can tell they take pride in their work.
                Highly recommended to anyone looking for a fresh cut
                and top-quality service!
            </p>

        </div>

    </div>


    <!-- RIGHT SIDE: CONTENT -->
    <div class="about-preview-content">

        <h2>
            We Are Happy to Make You Handsome
        </h2>

        <p>
            Discover a style that fits you perfectly. Our professional
            barbers are dedicated to helping you look and feel your best
            with personalized haircuts, beard grooming, and quality
            barbering services.
        </p>

        <p>
            Whether you're going for a clean classic look or a modern
            style, we'll make sure every detail is carefully crafted
            to suit your personality and preferences.
        </p>

        <a href="#services" class="about-book-btn">
            Book Now
        </a>

    </div>

</section>



<!-- =====================================================
     LATEST SHOP
===================================================== -->

<section
    id="shop"
    class="shop"
>


    <div class="section-title">

        <h2>

            Our Latest
            <span>Shop</span>

        </h2>


        <p>

            Quality grooming products to help
            you maintain your style at home.

        </p>

    </div>



    <div class="shop-grid">


        <!-- PRODUCT 1 -->

        <div class="shop-product-card">

            <img
                src="assets/images/shampoo.png"
                alt="NAVA Shampoo"
            >

            <div class="product-info">

                <h3>
                    NAVA Shampoo
                </h3>

                <div class="product-rating">
                    ★★★★★
                </div>

                <div class="product-price">
                    ₱350
                </div>

                <div class="shop-buttons">

                    <a 
                    href="shop.php" class="add-cart-btn">
                        Add to Cart
                    </a>

                    <a href="shop.php" class="view-btn">
                        View Details
                    </a>

                </div>

            </div>

        </div>



        <!-- PRODUCT 2 -->

        <div class="shop-product-card">


            <img
                src="assets/images/hair-clay.png"
                alt="NAVA Hair Wax"
            >

            <div class="product-info">

                <h3>
                    NAVA Hair Wax
                </h3>

                <div class="product-rating">
                    ★★★★★
                </div>

                <div class="product-price">
                    ₱280
                </div>

                <div class="shop-buttons">
  
                    <a 
                    href="shop.php" class="add-cart-btn">
                        Add to Cart
                    </a>

                    <a href="shop.php" class="view-btn">
                        View Details
                    </a>

                </div>

            </div>

        </div>



        <!-- PRODUCT 3 -->

        <div class="shop-product-card">

            <img
                src="assets/images/hair-spray.png"
                alt="NAVA Hair Spray"
            >

            <div class="product-info">

                <h3>
                    NAVA Hair Spray
                </h3>

                <div class="product-rating">
                    ★★★★★
                </div>

                <div class="product-price">
                    ₱300
                </div>

                <div class="shop-buttons">

                    <a 
                    href="shop.php" class="add-cart-btn">
                        Add to Cart
                    </a>

                    <a href="shop.php" class="view-btn">
                        View Details
                    </a>

                </div>

            </div>

        </div>


    </div>

</section>


<!-- =====================================================
     LATEST BLOGS
===================================================== -->

<section class="home-blog">

    <div class="home-blog-header">

        <span>
            FROM THE NAVA JOURNAL
        </span>

        <h2>
            Latest <strong>Blogs</strong>
        </h2>

        <p>
            Grooming tips, hairstyle inspiration,
            barbering advice, and helpful guides
            from NAVA Fade Studio.
        </p>

    </div>


    <div class="home-blog-grid">


        <!-- BLOG 1 -->

        <article class="home-blog-card">

            <div class="home-blog-image">

                <img
                    src="assets/images/beard-trimming.png"
                    alt="Beard grooming tips"
                >

            </div>


            <div class="home-blog-content">

                <span>
                    GROOMING
                </span>

                <h3>
                    Simple Beard Grooming Tips
                </h3>

                <p>
                    Discover simple habits that can help
                    keep your beard neat and well maintained.
                </p>

                <a href="blog.php">
                    Read More →
                </a>

            </div>

        </article>


        <!-- BLOG 2 -->

        <article class="home-blog-card">

            <div class="home-blog-image">

                <img
                    src="assets/images/hair-clay.png"
                    alt="Hair styling products"
                >

            </div>


            <div class="home-blog-content">

                <span>
                    HAIR STYLING
                </span>

                <h3>
                    Choosing the Right Hair Product
                </h3>

                <p>
                    Learn how different styling products
                    can help you achieve the look you want.
                </p>

                <a href="blog.php">
                    Read More →
                </a>

            </div>

        </article>


        <!-- BLOG 3 -->

        <article class="home-blog-card">

            <div class="home-blog-image">

                <img
                    src="assets/images/shampoo.png"
                    alt="Hair care"
                >

            </div>


            <div class="home-blog-content">

                <span>
                    HAIR CARE
                </span>

                <h3>
                    Why Proper Hair Care Matters
                </h3>

                <p>
                    Build simple hair-care habits that help
                    you maintain your style between visits.
                </p>

                <a href="blog.php">
                    Read More →
                </a>

            </div>

        </article>


    </div>


    <div class="home-blog-button">

        <a href="blog.php">
            View All Blogs
        </a>

    </div>

</section>


<style>

/* =====================================================
   HOMEPAGE BLOG
===================================================== */

.home-blog {

    padding:
        100px 8%;

    background:
        #f8f8f8;

}


.home-blog-header {

    max-width:
        750px;

    margin:
        0 auto 55px;

    text-align:
        center;

}


.home-blog-header span {

    color:
        #b8862c;

    font-size:
        13px;

    font-weight:
        bold;

    letter-spacing:
        3px;

}


.home-blog-header h2 {

    color:
        #0e1423;

    font-size:
        clamp(
            34px,
            5vw,
            48px
        );

    margin:
        12px 0;

}


.home-blog-header h2 strong {

    color:
        #b8862c;

}


.home-blog-header p {

    color:
        #666;

    line-height:
        1.8;

}


.home-blog-grid {

    max-width:
        1200px;

    margin:
        auto;

    display:
        grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap:
        30px;

}


.home-blog-card {

    background:
        white;

    border-radius:
        10px;

    overflow:
        hidden;

    box-shadow:
        0 8px 30px
        rgba(
            0,
            0,
            0,
            0.08
        );

    transition:
        0.3s ease;

}


.home-blog-card:hover {

    transform:
        translateY(-7px);

    box-shadow:
        0 15px 40px
        rgba(
            0,
            0,
            0,
            0.13
        );

}


.home-blog-image {

    height:
        220px;

    overflow:
        hidden;

}


.home-blog-image img {

    width:
        100%;

    height:
        100%;

    object-fit:
        cover;

    transition:
        transform 0.4s ease;

}


.home-blog-card:hover
.home-blog-image img {

    transform:
        scale(1.06);

}


.home-blog-content {

    padding:
        28px;

}


.home-blog-content span {

    color:
        #b8862c;

    font-size:
        12px;

    font-weight:
        bold;

    letter-spacing:
        1.5px;

}


.home-blog-content h3 {

    color:
        #0e1423;

    font-size:
        21px;

    line-height:
        1.4;

    margin:
        12px 0;

}


.home-blog-content p {

    color:
        #666;

    line-height:
        1.7;

    margin-bottom:
        20px;

}


.home-blog-content a {

    color:
        #0e1423;

    text-decoration:
        none;

    font-weight:
        bold;

    border-bottom:
        2px solid #b8862c;

    padding-bottom:
        4px;

}


.home-blog-content a:hover {

    color:
        #b8862c;

}


.home-blog-button {

    text-align:
        center;

    margin-top:
        45px;

}


.home-blog-button a {

    display:
        inline-block;

    background:
        #0e1423;

    color:
        white;

    text-decoration:
        none;

    padding:
        14px 30px;

    border-radius:
        5px;

    font-weight:
        bold;

    transition:
        0.3s ease;

}


.home-blog-button a:hover {

    background:
        #b8862c;

}


@media (max-width: 900px) {

    .home-blog-grid {

        grid-template-columns:
            repeat(2, 1fr);

    }

}


@media (max-width: 600px) {

    .home-blog {

        padding:
            70px 6%;

    }


    .home-blog-grid {

        grid-template-columns:
            1fr;

    }

}

</style>


<!-- =====================================================
     BOOKING CTA
===================================================== -->

<section class="about-booking-cta">

    <div class="about-booking-card">

        <div class="about-booking-overlay"></div>

        <div class="about-booking-content">

            <span class="booking-label">
                READY FOR A FRESH LOOK?
            </span>

            <h2>
                GET <span>20% OFF</span>
                <br>
                YOUR FIRST BOOKING
            </h2>

            <div class="booking-line"></div>

            <p>
                Book your appointment with NAVA Fade Studio
                and enjoy 20% off your first visit.
            </p>

            <p>
                Let our skilled barbers give you a clean,
                sharp, and confident style tailored just for you.
            </p>

            <a href="book.php" class="booking-cta-button">
                BOOK AN APPOINTMENT
            </a>

        </div>

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
                delivering clean, modern, and
                personalized grooming experiences.

            </p>

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



<!-- =====================================================
     JAVASCRIPT
===================================================== -->

<script src="assets/js/script.js"></script>

<script>

function toggleCustomerMenu() {

    document
        .getElementById("customerDropdown")
        .classList
        .toggle("show");

}


/* Close dropdown when clicking outside */

document.addEventListener(
    "click",
    function(event) {

        const menu =
            document.querySelector(".customer-menu");


        if (
            menu &&
            !menu.contains(event.target)
        ) {

            document
                .getElementById("customerDropdown")
                ?.classList
                .remove("show");

        }

    }
);

</script>


</body>

</html>