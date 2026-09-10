<?php

session_start();

require_once "config/Database.php";
require_once "classes/Review.php";


/* =========================================
   DATABASE
========================================= */

$database = new Database();
$db = $database->connect();

$reviewModel = new Review($db);


$customer_logged_in = isset($_SESSION["customer_id"]);

$customer_name = $_SESSION["customer_name"] ?? "";
$customer_email = $_SESSION["customer_email"] ?? "";
$customer_id = $_SESSION["customer_id"] ?? null;

$success = "";

$error = "";


/* =========================================
   UPDATE PROFILE
========================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

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
   GET CUSTOMER
========================================= */

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

$customer = null;

if ($customer_id) {

    $customer_query->execute([
        ":id" => $customer_id
    ]);

    $customer = $customer_query->fetch(
        PDO::FETCH_ASSOC
    );
}


/* =========================================
   INITIAL
========================================= */

$initials = "";

if ($customer) {

    $name_parts = preg_split(
        '/\s+/',
        trim($customer["full_name"])
    );

    foreach (
        array_slice($name_parts, 0, 2)
        as $part
    ) {
        $initials .= strtoupper(
            substr($part, 0, 1)
        );
    }
}
/* =========================================
   VARIABLES
========================================= */

$error = "";
$success = "";

$selected_rating = (int) ($_POST["rating"] ?? 0);
$comment_value = trim($_POST["comment"] ?? "");


/* =========================================
   SUBMIT REVIEW
========================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $rating = (int) ($_POST["rating"] ?? 0);

    $comment =
        trim($_POST["comment"] ?? "");


    /* -------------------------
       VALIDATION
    ------------------------- */

    if ($rating < 1 || $rating > 5) {

        $error =
            "Please select a rating from 1 to 5 stars.";

    } elseif ($comment === "") {

        $error =
            "Please write a comment.";

    } else {


        /* -------------------------
           CREATE REVIEW
        ------------------------- */

        if (
            $reviewModel->create(
                $_SESSION["customer_id"],
                $rating,
                $comment
            )
        ) {

            $success =
                "Thank you! Your review has been submitted and is waiting for approval.";

            $selected_rating = 0;
            $comment_value = "";

        } else {

            $error =
                "Something went wrong. Please try again.";

        }

    }

}


/* =========================================
   GET REVIEWS
========================================= */

$approvedReviews = $reviewModel->getApproved();

$featuredReview = $reviewModel->getFeatured();


/* =========================================
   COUNT
========================================= */

$reviewCount = count($approvedReviews);

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
        Reviews | NAVA Fade Studio
    </title>


    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >


    <style>

        /* =========================================
        REVIEWS HERO
        ========================================= */

        .reviews-hero {
            position: relative;

            width: 100%;
            min-height: 520px;

            display: flex;
            align-items: center;
            justify-content: center;

            box-sizing: border-box;

            overflow: hidden;

            background-image:
                url("../images/reviews-hero.png");

            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }


        /* DARK NAVY OVERLAY */

        .reviews-hero-overlay {
            position: absolute;

            inset: 0;

            background:
                linear-gradient(
                    120deg,
                    rgba(7, 14, 29, 0.92),
                    rgba(16, 31, 64, 0.72),
                    rgba(7, 14, 29, 0.88)
                );

            z-index: 1;
        }


        /* HERO CONTENT */

        .reviews-hero-content {
            position: relative;

            z-index: 2;

            width: 90%;
            max-width: 900px;

            text-align: center;

            color: #ffffff;
        }


        /* SMALL LABEL */

        .reviews-hero-label {
            display: block;

            margin-bottom: 20px;

            color: #e1aa32;

            font-size: 15px;

            font-weight: 700;

            letter-spacing: 6px;
        }


        /* MAIN TITLE */

        .reviews-hero-content h1 {
            margin: 0;

            color: #ffffff;

            font-size:
                clamp(48px, 7vw, 86px);

            line-height: 1.05;

            font-weight: 800;

            letter-spacing: -1px;

            text-shadow:
                0 4px 18px rgba(0, 0, 0, 0.5);
        }


        /* GOLD WORD */

        .reviews-hero-content h1 span {
            color: #e1aa32;
        }


        /* DESCRIPTION */

        .reviews-hero-content p {
            max-width: 720px;

            margin: 28px auto 0;

            color: #f2f2f2;

            font-size: 19px;

            line-height: 1.7;

            text-shadow:
                0 2px 10px rgba(0, 0, 0, 0.5);
        }


        /* =========================================
        TABLET
        ========================================= */

        @media (max-width: 900px) {

            .reviews-hero {
                min-height: 500px;
            }

            .reviews-hero-content h1 {
                font-size: 60px;
            }

            .reviews-hero-content p {
                font-size: 17px;
            }
        }


        /* =========================================
        MOBILE
        ========================================= */

        @media (max-width: 600px) {

            .reviews-hero {
                min-height: 460px;
            }

            .reviews-hero-content {
                width: 88%;
            }

            .reviews-hero-label {
                font-size: 11px;
                letter-spacing: 4px;
            }

            .reviews-hero-content h1 {
                font-size: 43px;
                line-height: 1.08;
            }

            .reviews-hero-content p {
                margin-top: 22px;

                font-size: 15px;

                line-height: 1.6;
            }
        }


        /* =========================================
           REVIEWS PAGE
        ========================================= */

        .reviews-page {

            padding: 90px 7%;

            background-image:
                url("assets/images/pattern.png");

            background-size: cover;

            background-position: center;

            min-height: 600px;
        }


        .reviews-page-header {

            text-align: center;

            margin-bottom: 55px;
        }


        .reviews-page-header span {

            color: #b8862c;

            font-size: 14px;

            font-weight: bold;

            letter-spacing: 3px;
        }


        .reviews-page-header h2 {

            color: #000000;

            font-size:
                clamp(35px, 5vw, 55px);

            margin: 10px 0 15px;
        }


        .reviews-page-header p {

            color: #333333;

            font-size: 17px;

            line-height: 1.6;
        }


        /* =========================================
           FEATURED REVIEW
        ========================================= */

        .featured-review-wrapper {

            max-width: 1000px;

            margin: 0 auto 70px;
        }


        .featured-label {

            text-align: center;

            color: #b8862c;

            font-size: 14px;

            font-weight: bold;

            letter-spacing: 2px;

            margin-bottom: 15px;
        }


        .featured-review {

            background: #ffffff;

            border:
                5px solid #b8862c;

            border-radius: 25px;

            padding: 35px;

            box-shadow:
                0 12px 35px
                rgba(0, 0, 0, 0.15);
        }


        .featured-review .review-top {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;

            margin-bottom: 20px;
        }


        .review-customer {

            display: flex;

            align-items: center;

            gap: 14px;
        }


        .review-avatar {

            width: 50px;

            height: 50px;

            min-width: 50px;

            border-radius: 50%;

            background: #1683d8;

            color: #ffffff;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 20px;

            font-weight: bold;
        }


        .review-customer-info strong {

            display: block;

            color: #222222;

            font-size: 17px;

            margin-bottom: 3px;
        }


        .review-customer-info span {

            color: #777777;

            font-size: 13px;
        }


        .featured-badge {

            background: #b8862c;

            color: #ffffff;

            padding: 8px 13px;

            border-radius: 20px;

            font-size: 12px;

            font-weight: bold;

            white-space: nowrap;
        }


        .review-rating {

            display: flex;

            align-items: center;

            gap: 12px;

            margin-bottom: 18px;
        }


        .review-stars {

            color: #f5b400;

            font-size: 20px;

            letter-spacing: 2px;
        }


        .review-date {

            color: #777777;

            font-size: 13px;
        }


        .featured-review p {

            color: #333333;

            font-size: 16px;

            line-height: 1.7;

            margin-bottom: 15px;
        }


        .featured-review p:last-child {

            margin-bottom: 0;
        }


        /* =========================================
           ALL REVIEWS
        ========================================= */

        .all-reviews-title {

            text-align: center;

            margin-bottom: 35px;
        }


        .all-reviews-title h3 {

            color: #000000;

            font-size: 32px;

            margin-bottom: 8px;
        }


        .all-reviews-title p {

            color: #555555;

            font-size: 15px;
        }


        .reviews-grid {

            max-width: 1200px;

            margin: 0 auto;

            display: grid;

            grid-template-columns:
                repeat(2, minmax(0, 1fr));

            gap: 25px;
        }


        .review-card {

            background: #ffffff;

            border:
                3px solid #b8862c;

            border-radius: 20px;

            padding: 25px;

            box-shadow:
                0 8px 25px
                rgba(0, 0, 0, 0.10);
        }


        .review-card .review-top {

            display: flex;

            align-items: center;

            gap: 14px;

            margin-bottom: 17px;
        }


        .review-card .review-avatar {

            width: 45px;

            height: 45px;

            min-width: 45px;

            font-size: 17px;
        }


        .review-card .review-rating {

            margin-bottom: 13px;
        }


        .review-card .review-stars {

            font-size: 17px;
        }


        .review-card p {

            color: #333333;

            font-size: 15px;

            line-height: 1.6;
        }


        /* =========================================
           WRITE REVIEW
        ========================================= */

        .write-review-section {

            text-align: center;

            margin-top: 60px;
        }


        .write-review-section p {

            color: #333333;

            margin-bottom: 20px;

            font-size: 16px;
        }


        .write-review-btn {

            display: inline-block;

            padding: 14px 30px;

            background: #b8862c;

            color: #ffffff;

            text-decoration: none;

            border-radius: 10px;

            font-weight: bold;

            transition: 0.3s;
        }


        .write-review-btn:hover {

            background: #966d20;

            transform: translateY(-2px);
        }


        /* =========================================
           NO REVIEWS
        ========================================= */

        .no-reviews {

            max-width: 700px;

            margin: 0 auto;

            text-align: center;

            background: #ffffff;

            border:
                3px solid #b8862c;

            border-radius: 20px;

            padding: 50px 30px;
        }


        .no-reviews h3 {

            color: #111111;

            font-size: 28px;

            margin-bottom: 12px;
        }


        .no-reviews p {

            color: #555555;

            line-height: 1.6;

            margin-bottom: 20px;
        }


        /* =========================================
           RESPONSIVE
        ========================================= */

        @media (max-width: 800px) {

            .reviews-page {

                padding: 70px 25px;
            }


            .reviews-grid {

                grid-template-columns: 1fr;
            }


            .featured-review {

                padding: 25px;
            }


            .featured-review .review-top {

                align-items: flex-start;

                flex-direction: column;
            }

        }


        @media (max-width: 600px) {

            .reviews-hero {

                min-height: 300px;

                padding: 80px 20px;
            }


            .reviews-hero-content p {

                font-size: 15px;
            }


            .reviews-page {

                padding: 60px 20px;
            }


            .featured-review {

                border-width: 4px;

                border-radius: 18px;

                padding: 20px;
            }


            .review-card {

                padding: 20px;
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


        <!-- LOGO -->

        <a
            href="index.php"
            class="logo"
        >

            <img
                src="assets/images/logo.png"
                alt="NAVA Fade Studio Logo"
            >

        </a>



        <!-- NAVIGATION -->

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


            <a
                href="reviews.php"
                class="active"
            >
                Reviews
            </a>


            <a href="shop.php">
                Shop
            </a>


            <a href="blog.php">
                Blog
            </a>


            <a
                href="book.php"
                class="nav-button"
            >
                Book Now
            </a>



            <!-- CUSTOMER MENU -->

            <?php if (isset($_SESSION["customer_id"])): ?>

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

                <a href="register.php" class="nav-button">
                    Register
                </a>

            <?php endif; ?>


        </nav>


    </div>

</header>



<!-- =========================================
     REVIEWS HERO
========================================= -->

<section class="reviews-hero">

    <div class="reviews-hero-overlay"></div>

    <div class="reviews-hero-content">

        <span class="reviews-hero-label">
            NAVA FADE STUDIO
        </span>

        <h1>
            CUSTOMER <span>REVIEWS</span>
        </h1>

        <p>
            See what our customers have to say
            about their experience at NAVA Fade Studio.
        </p>

    </div>

</section>



<!-- =====================================================
     REVIEWS CONTENT
===================================================== -->

<section class="reviews-page">


    <div class="reviews-page-header">


        <span>
            WHAT OUR CLIENTS SAY
        </span>


        <h2>
            Reviews & Feedback
        </h2>


        <p>
            Real feedback from customers who
            experienced our services.
        </p>


    </div>



    <?php if ($featuredReview): ?>


        <!-- =============================================
             FEATURED REVIEW
        ============================================== -->

        <div class="featured-review-wrapper">


            <div class="featured-label">

                ⭐ FEATURED REVIEW

            </div>


            <div class="featured-review">


                <div class="review-top">


                    <div class="review-customer">


                        <div class="review-avatar">

                            <?= strtoupper(
                                substr(
                                    $featuredReview["customer_name"],
                                    0,
                                    1
                                )
                            ) ?>

                        </div>


                        <div class="review-customer-info">

                            <strong>

                                <?= htmlspecialchars(
                                    $featuredReview["customer_name"]
                                ) ?>

                            </strong>


                            <span>
                                Customer review
                            </span>

                        </div>


                    </div>


                    <span class="featured-badge">

                        ⭐ Featured

                    </span>


                </div>



                <div class="review-rating">


                    <span class="review-stars">

                        <?php

                        for (
                            $i = 1;
                            $i <= 5;
                            $i++
                        ) {

                            echo $i <= $featuredReview["rating"]
                                ? "★"
                                : "☆";
                        }

                        ?>

                    </span>


                    <span class="review-date">

                        <?= date(
                            "M d, Y",
                            strtotime(
                                $featuredReview["created_at"]
                            )
                        ) ?>

                    </span>


                </div>



                <p>

                    <?= nl2br(
                        htmlspecialchars(
                            $featuredReview["comment"]
                        )
                    ) ?>

                </p>


            </div>


        </div>


    <?php endif; ?>



    <!-- =============================================
         ALL APPROVED REVIEWS
    ============================================== -->

    <?php if (!empty($approvedReviews)): ?>


        <div class="all-reviews-title">


            <h3>
                All Customer Reviews
            </h3>


            <p>

                <?= $reviewCount ?>

                approved review<?= $reviewCount === 1 ? "" : "s" ?>

            </p>


        </div>



        <div class="reviews-grid">


            <?php foreach (
                $approvedReviews
                as $review
            ): ?>


                <?php

                /*
                 * Do not duplicate the featured review
                 * in the normal review grid.
                 */

                if (
                    $featuredReview &&
                    (int) $review["id"] ===
                    (int) $featuredReview["id"]
                ) {

                    continue;
                }

                ?>


                <div class="review-card">


                    <div class="review-top">


                        <div class="review-avatar">


                            <?= strtoupper(
                                substr(
                                    $review["customer_name"],
                                    0,
                                    1
                                )
                            ) ?>


                        </div>


                        <div class="review-customer-info">


                            <strong>

                                <?= htmlspecialchars(
                                    $review["customer_name"]
                                ) ?>

                            </strong>


                            <span>

                                Customer review

                            </span>


                        </div>


                    </div>



                    <div class="review-rating">


                        <span class="review-stars">


                            <?php

                            for (
                                $i = 1;
                                $i <= 5;
                                $i++
                            ) {

                                echo $i <= $review["rating"]
                                    ? "★"
                                    : "☆";
                            }

                            ?>


                        </span>


                        <span class="review-date">

                            <?= date(
                                "M d, Y",
                                strtotime(
                                    $review["created_at"]
                                )
                            ) ?>


                        </span>


                    </div>



                    <p>

                        <?= nl2br(
                            htmlspecialchars(
                                $review["comment"]
                            )
                        ) ?>


                    </p>


                </div>


            <?php endforeach; ?>


        </div>


    <?php else: ?>


        <div class="no-reviews">


            <h3>
                No Reviews Yet
            </h3>


            <p>
                Be the first to share your
                experience at NAVA Fade Studio.
            </p>


        </div>


    <?php endif; ?>



    <!-- =============================================
         WRITE REVIEW
    ============================================== -->

    <div class="write-review-section">


        <?php if (
            isset($_SESSION["customer_id"])
        ): ?>


            <p>
                Have you visited NAVA Fade Studio?
                We'd love to hear your experience.
            </p>


            <a
                href="review.php"
                class="write-review-btn"
            >
                ⭐ Write a Review
            </a>


        <?php else: ?>


            <p>
                Log in or create an account
                to leave a review.
            </p>


            <a
                href="login.php"
                class="write-review-btn"
            >
                Login to Write a Review
            </a>


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
                delivering clean, modern, and
                personalized grooming experiences.
            </p>


        </div>



        <!-- ABOUT -->

        <div>


            <h3>
                About
            </h3>


            <a href="about.php">
                About Us
            </a>


            <a href="index.php#services">
                Services
            </a>


            <a href="shop.php">
                Shop
            </a>


            <a href="reviews.php">
                Reviews
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


            <a href="index.php#services">
                Our Services
            </a>


            <a href="reviews.php">
                Customer Reviews
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


            <p>📍 Amlan, Negros Oriental</p>
            <p>📧 navafadestudio@gmail.com</p>
            <p>📞 0969 407 4629</p>


        </div>


    </div>


</footer>



<script>

function toggleCustomerMenu() {

    const dropdown =
        document.getElementById(
            "customerDropdown"
        );

    dropdown.classList.toggle("show");
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