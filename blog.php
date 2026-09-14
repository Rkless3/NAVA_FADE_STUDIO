<?php
session_start();

/* =========================================
   CUSTOMER SESSION / PROFILE DATA
========================================= */

$customer_logged_in = isset($_SESSION["customer_id"]);
$customer_name = $_SESSION["customer_name"] ?? "";
$customer_email = $_SESSION["customer_email"] ?? "";

$customer = null;
$initials = "";

if ($customer_logged_in) {

    require_once "config/Database.php";

    $database = new Database();
    $db = $database->connect();

    $customer_id = $_SESSION["customer_id"];

    $customer_query = $db->prepare("
        SELECT full_name, email
        FROM customers
        WHERE id = :id
        LIMIT 1
    ");

    $customer_query->execute([
        ":id" => $customer_id
    ]);

    $customer = $customer_query->fetch(PDO::FETCH_ASSOC);

    if ($customer) {
        $name_parts = preg_split('/\\s+/', trim($customer["full_name"]));

        foreach (array_slice($name_parts, 0, 2) as $part) {
            $initials .= strtoupper(substr($part, 0, 1));
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

    <title>
        Blog | NAVA Fade Studio
    </title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

    <style>

        /* =====================================================
           BLOG PAGE
        ===================================================== */

        .blog-page {
            background: #f8f8f8;
            padding: 90px 8%;
            font-family:
                Bahnschrift,
                "Myriad Pro",
                Arial,
                sans-serif;
            background:
                url("assets/images/pattern4.png");
        }


        /* =====================================================
           BLOG HERO
        ===================================================== */

        .blog-hero {
            min-height: 420px;

            display: flex;
            align-items: center;
            justify-content: center;

            text-align: center;

            position: relative;

            background:
                linear-gradient(
                    rgba(14, 20, 35, 0.82),
                    rgba(14, 20, 35, 0.82)
                ),
                url("assets/images/beard-trimming.png");

            background-size: cover;
            background-position: center;
        }


        .blog-hero-content {
            max-width: 850px;
            padding: 30px;
            color: white;
        }


        .blog-hero-content span {
            color: #b8862c;

            font-size: 14px;

            font-weight: bold;

            letter-spacing: 3px;
        }


        .blog-hero-content h1 {
            margin: 15px 0;

            font-size: clamp(
                42px,
                6vw,
                72px
            );

            text-transform: uppercase;

            font-weight: 800;
        }


        .blog-hero-content p {
            max-width: 650px;

            margin: auto;

            color: #ddd;

            line-height: 1.8;

            font-size: 17px;
        }


        /* =====================================================
           BLOG HEADER
        ===================================================== */

        .blog-section-header {
            max-width: 800px;

            margin: 0 auto 55px;

            text-align: center;
        }


        .blog-section-header span {
            color: #b8862c;

            font-size: 13px;

            font-weight: bold;

            letter-spacing: 3px;
        }


        .blog-section-header h2 {
            margin: 12px 0;

            color: #0e1423;

            font-size: clamp(
                32px,
                5vw,
                48px
            );
        }


        .blog-section-header p {
            color: #666;

            line-height: 1.8;
        }


        /* =====================================================
           BLOG GRID
        ===================================================== */

        .blog-grid {
            max-width: 1200px;

            margin: auto;

            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 30px;
        }


        /* =====================================================
           BLOG CARD
        ===================================================== */

        .blog-card {
            background: #0e1423;

            border-radius: 10px;

            overflow: hidden;

            border: 5px solid #0e1423;

            box-shadow:
                0 8px 30px
                rgba(0, 0, 0, 0.08);

            transition:
                transform 0.3s ease,
                box-shadow 0.3s ease;
        }


        .blog-card:hover {
            transform: translateY(-8px);

            box-shadow:
                0 15px 40px
                rgba(0, 0, 0, 0.13);
        }


        .blog-card-image {
            height: 230px;

            overflow: hidden;

            background: #eee;
        }


        .blog-card-image img {
            width: 100%;

            height: 100%;

            object-fit: cover;

            transition:
                transform 0.4s ease;
        }


        .blog-card:hover
        .blog-card-image img {
            transform: scale(1.06);
        }


        .blog-card-content {
            padding: 28px;
        }


        .blog-category {
            display: inline-block;

            color: #b8862c;

            font-size: 12px;

            font-weight: bold;

            letter-spacing: 1.5px;

            text-transform: uppercase;

            margin-bottom: 12px;
        }


        .blog-card h3 {
            color: #fff;

            font-size: 22px;

            line-height: 1.35;

            margin-bottom: 14px;
        }


        .blog-card p {
            color: #ccc;

            line-height: 1.7;

            margin-bottom: 22px;
        }


        .blog-date {
            display: block;

            color: #b8862c;

            font-size: 13px;

            margin-bottom: 20px;
        }


        .read-more {
            display: inline-block;

            color: #fff;

            font-weight: bold;

            text-decoration: none;

            border-bottom:
                2px solid #b8862c;

            padding-bottom: 4px;

            transition: 0.3s ease;
        }


        .read-more:hover {
            color: #b8862c;
        }


        /* =====================================================
           FEATURED BLOG
        ===================================================== */

        .featured-blog {
            max-width: 1200px;

            margin: 0 auto 80px;

            display: grid;

            grid-template-columns:
                1.1fr 1fr;

            background: #0e1423;

            border-radius: 12px;

            overflow: hidden;

            box-shadow:
                0 15px 40px
                rgba(0, 0, 0, 0.12);
        }


        .featured-blog-image {
            min-height: 430px;
        }


        .featured-blog-image img {
            width: 100%;

            height: 100%;

            object-fit: cover;
        }


        .featured-blog-content {
            padding: 55px;

            display: flex;

            flex-direction: column;

            justify-content: center;
        }


        .featured-blog-content span {
            color: #b8862c;

            font-size: 13px;

            font-weight: bold;

            letter-spacing: 2px;
        }


        .featured-blog-content h2 {
            color: white;

            font-size: clamp(
                30px,
                4vw,
                46px
            );

            line-height: 1.2;

            margin: 15px 0 20px;
        }


        .featured-blog-content p {
            color: #ccc;

            line-height: 1.8;

            margin-bottom: 25px;
        }


        .featured-blog-content .read-more {
            color: white;

            width: fit-content;
        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 950px) {

            .blog-grid {
                grid-template-columns:
                    repeat(2, 1fr);
            }


            .featured-blog {
                grid-template-columns: 1fr;
            }


            .featured-blog-image {
                min-height: 300px;
            }


            .blog-footer-content {
                grid-template-columns:
                    repeat(2, 1fr);
            }

        }


        @media (max-width: 650px) {

            .blog-page {
                padding: 65px 6%;
            }


            .blog-hero {
                min-height: 350px;
            }


            .blog-hero-content {
                padding: 20px;
            }


            .blog-hero-content h1 {
                font-size: 42px;
            }


            .blog-grid {
                grid-template-columns: 1fr;
            }


            .featured-blog-content {
                padding: 35px 25px;
            }


            .blog-footer-content {
                grid-template-columns: 1fr;
            }

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

            <a href="#services">
                Service
            </a>

            <a href="reviews.php">
                Reviews
            </a>

            <a href="shop.php">
                Shop
            </a>

            <a href="blog.php" class="active">
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
     BLOG HERO
===================================================== -->

<section class="blog-hero">

    <div class="blog-hero-content">

        <span>
            NAVA FADE STUDIO
        </span>

        <h1>
            Our Blog
        </h1>

        <p>
            Discover grooming tips, hairstyle inspiration,
            barbering advice, and updates from NAVA Fade Studio.
        </p>

    </div>

</section>


<!-- =====================================================
     BLOG CONTENT
===================================================== -->

<section class="blog-page">


    <div class="blog-section-header">

        <span>
            STYLE • GROOMING • CARE
        </span>

        <h2>
            The NAVA Journal
        </h2>

        <p>
            Stay updated with helpful grooming tips,
            style inspiration, and everything you need
            to maintain your best look.
        </p>

    </div>


    <!-- =================================================
         FEATURED BLOG
    ================================================= -->

    <div class="featured-blog">

        <div class="featured-blog-image">

            <img
                src="assets/images/beard-trimming.png"
                alt="Professional grooming"
            >

        </div>


        <div class="featured-blog-content">

            <span>
                FEATURED ARTICLE
            </span>

            <h2>
                How to Maintain a Fresh Look Between Haircuts
            </h2>

            <p>
                A great haircut does not have to look
                fresh for only a few days. Learn simple
                grooming habits that can help you maintain
                your style between visits to the barber.
            </p>

            <span>
                September 2026
            </span>

            <a
                href="#"
                class="read-more"
            >
                Read Article →
            </a>

        </div>

    </div>


    <!-- =================================================
         BLOG GRID
    ================================================= -->

    <div class="blog-grid">


        <!-- BLOG 1 -->

        <article class="blog-card">

            <div class="blog-card-image">

                <img
                    src="assets/images/beard-trim.png"
                    alt="Beard grooming"
                >

            </div>


            <div class="blog-card-content">

                <span class="blog-category">
                    Grooming
                </span>

                <h3>
                    Simple Beard Grooming Tips
                </h3>

                <p>
                    Learn the basic habits that can help
                    keep your beard neat, comfortable,
                    and well maintained.
                </p>

                <span class="blog-date">
                    September 2026
                </span>

                <a
                    href="#"
                    class="read-more"
                >
                    Read More →
                </a>

            </div>

        </article>


        <!-- BLOG 2 -->

        <article class="blog-card">

            <div class="blog-card-image">

                <img
                    src="assets/images/hair-clay.png"
                    alt="Hair styling product"
                >

            </div>


            <div class="blog-card-content">

                <span class="blog-category">
                    Hair Styling
                </span>

                <h3>
                    Choosing the Right Hair Product
                </h3>

                <p>
                    Hair clay, wax, and other styling
                    products can work differently.
                    Learn how to choose based on your
                    desired hairstyle.
                </p>

                <span class="blog-date">
                    September 2026
                </span>

                <a
                    href="#"
                    class="read-more"
                >
                    Read More →
                </a>

            </div>

        </article>


        <!-- BLOG 3 -->

        <article class="blog-card">

            <div class="blog-card-image">

                <img
                    src="assets/images/shampoo.png"
                    alt="Hair care product"
                >

            </div>


            <div class="blog-card-content">

                <span class="blog-category">
                    Hair Care
                </span>

                <h3>
                    Why Your Hair Needs Proper Care
                </h3>

                <p>
                    Good hair care starts with simple
                    routines. Discover practical ways
                    to keep your hair clean and healthy.
                </p>

                <span class="blog-date">
                    September 2026
                </span>

                <a
                    href="#"
                    class="read-more"
                >
                    Read More →
                </a>

            </div>

        </article>


        <!-- BLOG 4 -->

        <article class="blog-card">

            <div class="blog-card-image">

                <img
                    src="assets/images/hair-cut.png"
                    alt="Barber grooming"
                >

            </div>


            <div class="blog-card-content">

                <span class="blog-category">
                    Barber Tips
                </span>

                <h3>
                    How Often Should You Get a Haircut?
                </h3>

                <p>
                    Your ideal haircut schedule depends
                    on your hairstyle and how quickly
                    you want your look maintained.
                </p>

                <span class="blog-date">
                    August 2026
                </span>

                <a
                    href="#"
                    class="read-more"
                >
                    Read More →
                </a>

            </div>

        </article>


        <!-- BLOG 5 -->

        <article class="blog-card">

            <div class="blog-card-image">

                <img
                    src="assets/images/hair-styling.png"
                    alt="Hair styling"
                >

            </div>


            <div class="blog-card-content">

                <span class="blog-category">
                    Style
                </span>

                <h3>
                    Keeping Your Hairstyle Looking Sharp
                </h3>

                <p>
                    Small styling habits can make a big
                    difference in keeping your haircut
                    looking presentable throughout the day.
                </p>

                <span class="blog-date">
                    August 2026
                </span>

                <a
                    href="#"
                    class="read-more"
                >
                    Read More →
                </a>

            </div>

        </article>


        <!-- BLOG 6 -->

        <article class="blog-card">

            <div class="blog-card-image">

                <img
                    src="assets/images/hair-spray.png"
                    alt="Hair spray"
                >

            </div>


            <div class="blog-card-content">

                <span class="blog-category">
                    Grooming
                </span>

                <h3>
                    Finishing Your Style the Right Way
                </h3>

                <p>
                    A proper finishing product can help
                    complete your hairstyle while keeping
                    it neat throughout the day.
                </p>

                <span class="blog-date">
                    August 2026
                </span>

                <a
                    href="#"
                    class="read-more"
                >
                    Read More →
                </a>

            </div>

        </article>


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


<!-- =====================================================
     JAVASCRIPT
===================================================== -->

<script>

function toggleCustomerMenu() {

    document
        .getElementById("customerDropdown")
        .classList
        .toggle("show");

}


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