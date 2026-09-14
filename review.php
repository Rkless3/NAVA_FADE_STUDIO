<?php

session_start();


/* =========================================
   CHECK CUSTOMER LOGIN
========================================= */

if (!isset($_SESSION["customer_id"])) {

    header("Location: login.php");
    exit();

}


/* =========================================
   DATABASE
========================================= */

require_once "config/Database.php";
require_once "classes/Review.php";

$database = new Database();
$db = $database->connect();

$review = new Review($db);

$customer_logged_in = isset($_SESSION["customer_id"]);

$customer_name = $_SESSION["customer_name"] ?? "";

$customer_email = $_SESSION["customer_email"] ?? "";

$customer_id = $_SESSION["customer_id"];

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


$customer_query->execute([

    ":id" =>
        $customer_id

]);


$customer =
    $customer_query->fetch(
        PDO::FETCH_ASSOC
    );


/* =========================================
   SAFETY CHECK
========================================= */

if (!$customer) {

    session_destroy();

    header("Location: login.php");

    exit();

}


/* =========================================
   INITIAL
========================================= */

$name_parts =
    preg_split(
        '/\s+/',
        trim($customer["full_name"])
    );


$initials = "";


foreach (
    array_slice($name_parts, 0, 2)
    as $part
) {

    $initials .=
        strtoupper(
            substr($part, 0, 1)
        );

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
            $review->create(
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
        Write a Review | NAVA Fade Studio
    </title>


    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >


    <style>

        /* =========================================
           REVIEW PAGE
        ========================================= */

        * {
            box-sizing: border-box;
        }


        body {
            margin: 0;
            background: #0e1423;
        }


        .review-page {

            min-height: calc(100vh - 160px);

            padding: 75px 20px 90px;

            display: flex;

            justify-content: center;

            align-items: center;

            background:
                linear-gradient(
                    rgba(14, 20, 35, 0.91),
                    rgba(14, 20, 35, 0.96)
                ),
                url("assets/images/pattern2.png");

            background-size: cover;

            background-position: center;
        }


        .review-wrapper {

            width: 100%;

            max-width: 760px;
        }


        /* =========================================
           REVIEW CONTAINER
        ========================================= */

        .review-container {

            position: relative;

            width: 100%;

            padding: 45px 50px;

            overflow: hidden;

            border-radius: 22px;

            border: 1px solid
                rgba(200, 148, 47, 0.65);

            background:
                linear-gradient(
                    145deg,
                    rgba(21, 29, 47, 0.98),
                    rgba(11, 17, 30, 0.98)
                );

            box-shadow:
                0 25px 65px rgba(0, 0, 0, 0.38);
        }


        .review-container::before {

            content: "";

            position: absolute;

            top: 0;
            left: 0;

            width: 100%;

            height: 4px;

            background:
                linear-gradient(
                    90deg,
                    transparent,
                    #c8942f,
                    transparent
                );
        }


        .review-container::after {

            content: "";

            position: absolute;

            width: 180px;

            height: 180px;

            right: -90px;

            top: -90px;

            border-radius: 50%;

            border: 1px solid
                rgba(200, 148, 47, 0.12);

            box-shadow:
                0 0 0 30px rgba(200, 148, 47, 0.025),
                0 0 0 60px rgba(200, 148, 47, 0.018);

            pointer-events: none;
        }


        /* =========================================
           HEADER
        ========================================= */

        .review-header {

            text-align: center;

            margin-bottom: 40px;
        }


        .review-eyebrow {

            display: inline-block;

            margin-bottom: 12px;

            color: #c8942f;

            font-size: 11px;

            font-weight: 800;

            letter-spacing: 4px;

            text-transform: uppercase;
        }


        .review-container h1 {

            margin: 0;

            color: #ffffff;

            font-size: clamp(34px, 5vw, 48px);

            line-height: 1.1;

            font-weight: 800;
        }


        .review-container h1 span {

            color: #c8942f;
        }


        .review-subtitle {

            max-width: 520px;

            margin: 14px auto 0;

            color: #9ca7b9;

            font-size: 15px;

            line-height: 1.6;
        }


        /* =========================================
           MESSAGES
        ========================================= */

        .message {

            display: flex;

            align-items: center;

            gap: 12px;

            margin-bottom: 28px;

            padding: 15px 18px;

            border-radius: 10px;

            font-size: 13px;

            font-weight: 600;

            line-height: 1.5;
        }


        .message-icon {

            width: 27px;

            height: 27px;

            flex-shrink: 0;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 50%;

            font-size: 13px;

            font-weight: 900;
        }


        .success-message {

            color: #70d889;

            background: rgba(70, 200, 100, 0.08);

            border: 1px solid
                rgba(70, 200, 100, 0.25);
        }


        .success-message .message-icon {

            background: rgba(70, 200, 100, 0.15);
        }


        .error-message {

            color: #ff7777;

            background: rgba(255, 80, 80, 0.08);

            border: 1px solid
                rgba(255, 80, 80, 0.25);
        }


        .error-message .message-icon {

            background: rgba(255, 80, 80, 0.15);
        }


        /* =========================================
           FORM SECTION
        ========================================= */

        .review-section {

            margin-bottom: 30px;
        }


        .review-section-label {

            display: block;

            margin-bottom: 12px;

            color: #ffffff;

            font-size: 14px;

            font-weight: 700;
        }


        .required {

            color: #c8942f;
        }


        /* =========================================
           STAR RATING
        ========================================= */

        .rating-box {

            padding: 22px;

            border-radius: 13px;

            background: rgba(255, 255, 255, 0.025);

            border: 1px solid
                rgba(255, 255, 255, 0.07);
        }


        .rating-description {

            margin-bottom: 14px;

            color: #7f8b9f;

            font-size: 12px;
        }


        .stars {

            display: flex;

            flex-direction: row-reverse;

            justify-content: flex-end;

            width: max-content;

            gap: 5px;
        }


        .stars input {

            position: absolute;

            opacity: 0;

            pointer-events: none;
        }


        .stars label {

            color: #3d4656;

            font-size: 43px;

            line-height: 1;

            cursor: pointer;

            transition:
                color 0.2s ease,
                transform 0.2s ease;
        }


        .stars label:hover {

            transform: scale(1.08);
        }


        .stars label:hover,
        .stars label:hover ~ label,
        .stars input:checked ~ label {

            color: #dca63b;

            text-shadow:
                0 0 15px rgba(220, 166, 59, 0.25);
        }


        .stars input:focus-visible + label {

            outline: 2px solid #c8942f;

            outline-offset: 4px;

            border-radius: 3px;
        }


        .rating-selected {

            min-height: 18px;

            margin-top: 10px;

            color: #c8942f;

            font-size: 12px;

            font-weight: 700;
        }


        /* =========================================
           COMMENT
        ========================================= */

        .comment-section {

            margin-bottom: 28px;
        }


        .comment-header {

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 15px;

            margin-bottom: 12px;
        }


        .comment-hint {

            color: #687488;

            font-size: 11px;
        }


        .comment-section textarea {

            width: 100%;

            min-height: 170px;

            padding: 17px 18px;

            resize: vertical;

            border: 1px solid
                rgba(255, 255, 255, 0.12);

            border-radius: 12px;

            outline: none;

            background: #0d1525;

            color: #ffffff;

            font-family: inherit;

            font-size: 14px;

            line-height: 1.6;

            transition:
                border-color 0.25s ease,
                box-shadow 0.25s ease,
                background 0.25s ease;
        }


        .comment-section textarea::placeholder {

            color: #647086;
        }


        .comment-section textarea:hover {

            border-color:
                rgba(200, 148, 47, 0.35);
        }


        .comment-section textarea:focus {

            border-color: #c8942f;

            background: #0f1829;

            box-shadow:
                0 0 0 3px
                rgba(200, 148, 47, 0.09);
        }


        /* =========================================
           SUBMIT BUTTON
        ========================================= */

        .submit-review-btn {

            position: relative;

            width: 100%;

            min-height: 53px;

            border: none;

            border-radius: 9px;

            background:
                linear-gradient(
                    135deg,
                    #b8862c,
                    #d9a238
                );

            color: #101624;

            font-size: 14px;

            font-weight: 800;

            letter-spacing: 0.3px;

            cursor: pointer;

            box-shadow:
                0 8px 20px
                rgba(200, 148, 47, 0.18);

            transition:
                transform 0.25s ease,
                box-shadow 0.25s ease,
                filter 0.25s ease;
        }


        .submit-review-btn:hover {

            transform: translateY(-2px);

            filter: brightness(1.05);

            box-shadow:
                0 12px 28px
                rgba(200, 148, 47, 0.28);
        }


        .submit-review-btn:active {

            transform: translateY(0);
        }


        /* =========================================
           SUCCESS STATE
        ========================================= */

        .success-state {

            text-align: center;

            padding: 20px 5px 5px;
        }


        .success-icon {

            width: 72px;

            height: 72px;

            margin: 0 auto 20px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 50%;

            background:
                rgba(70, 200, 100, 0.08);

            border: 1px solid
                rgba(70, 200, 100, 0.30);

            color: #70d889;

            font-size: 30px;
        }


        .success-state h2 {

            margin: 0 0 10px;

            color: #ffffff;

            font-size: 25px;
        }


        .success-state p {

            max-width: 500px;

            margin: 0 auto 25px;

            color: #909bad;

            font-size: 14px;

            line-height: 1.6;
        }


        /* =========================================
           BACK BUTTON
        ========================================= */

        .back-home {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 7px;

            margin-top: 22px;

            color: #c8942f;

            text-decoration: none;

            font-size: 13px;

            font-weight: 700;

            transition:
                color 0.2s ease,
                transform 0.2s ease;
        }


        .back-home:hover {

            color: #e0aa3b;

            transform: translateX(-2px);
        }


        .success-state .back-home {

            margin-top: 0;

            padding: 11px 20px;

            border: 1px solid
                rgba(200, 148, 47, 0.40);

            border-radius: 8px;

            background:
                rgba(200, 148, 47, 0.05);
        }


        .success-state .back-home:hover {

            background:
                rgba(200, 148, 47, 0.10);
        }


        /* =========================================
           REVIEW NOTE
        ========================================= */

        .review-note {

            display: flex;

            align-items: flex-start;

            gap: 10px;

            margin-top: 22px;

            padding: 14px 16px;

            border-radius: 10px;

            background:
                rgba(200, 148, 47, 0.045);

            border: 1px solid
                rgba(200, 148, 47, 0.10);

            color: #7f8b9f;

            font-size: 11px;

            line-height: 1.5;
        }


        .review-note-icon {

            flex-shrink: 0;

            color: #c8942f;

            font-size: 14px;
        }


        /* =========================================
           RESPONSIVE
        ========================================= */

        @media (max-width: 700px) {

            .review-page {

                padding: 55px 15px 70px;

                background-size: 230px;
            }


            .review-container {

                padding: 35px 25px;

                border-radius: 18px;
            }


            .review-container h1 {

                font-size: 36px;
            }


            .review-subtitle {

                font-size: 14px;
            }


            .stars label {

                font-size: 38px;
            }

        }


        @media (max-width: 500px) {

            .review-page {

                padding: 40px 12px 55px;

                align-items: flex-start;
            }


            .review-container {

                padding: 30px 20px;

                border-radius: 15px;
            }


            .review-header {

                margin-bottom: 30px;
            }


            .review-eyebrow {

                font-size: 9px;

                letter-spacing: 3px;
            }


            .review-container h1 {

                font-size: 31px;
            }


            .review-subtitle {

                font-size: 13px;

                line-height: 1.5;
            }


            .rating-box {

                padding: 18px;
            }


            .stars {

                gap: 2px;
            }


            .stars label {

                font-size: 34px;
            }


            .comment-section textarea {

                min-height: 150px;

                font-size: 13px;
            }


            .comment-header {

                align-items: flex-start;

                flex-direction: column;

                gap: 4px;
            }

        }


        @media (max-width: 360px) {

            .review-container {

                padding: 25px 16px;
            }


            .review-container h1 {

                font-size: 28px;
            }


            .stars label {

                font-size: 30px;
            }

        }

    </style>

</head>


<body>


<!-- =========================================
     HEADER
========================================= -->

<header class="header">

    <div class="container navbar">


        <!-- LOGO -->

        <a href="index.php" class="logo">

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


<!-- =========================================
     REVIEW PAGE
========================================= -->

<div class="review-page">


    <div class="review-wrapper">


        <div class="review-container">


            <!-- =================================
                 HEADER
            ================================== -->

            <div class="review-header">


                <h1>
                    Write a <span>Review</span>
                </h1>


                <p class="review-subtitle">
                    Tell us about your experience and help<br>
                    us continue delivering the NAVA standard.
                </p>

            </div>


            <!-- =================================
                 SUCCESS
            ================================== -->

            <?php if ($success): ?>


                <div class="success-state">


                    <div class="success-icon">
                        ✓
                    </div>


                    <div class="message success-message">

                        <div class="message-icon">
                            ✓
                        </div>

                        <div>
                            <?= htmlspecialchars($success) ?>
                        </div>

                    </div>


                    <h2>
                        Review Submitted!
                    </h2>


                    <p>
                        Your feedback has been received and
                        is now waiting for approval. Thank you
                        for choosing NAVA Fade Studio.
                    </p>


                    <a
                        href="index.php"
                        class="back-home"
                    >
                        ← Back to Home
                    </a>


                </div>


            <?php else: ?>


                <!-- =================================
                     ERROR
                ================================== -->

                <?php if ($error): ?>

                    <div class="message error-message">

                        <div class="message-icon">
                            !
                        </div>

                        <div>
                            <?= htmlspecialchars($error) ?>
                        </div>

                    </div>

                <?php endif; ?>


                <!-- =================================
                     FORM
                ================================== -->

                <form
                    method="POST"
                    action="review.php"
                >


                    <!-- RATING -->

                    <div class="review-section">


                        <label class="review-section-label">
                            Your Rating
                            <span class="required">*</span>
                        </label>


                        <div class="rating-box">


                            <div class="rating-description">
                                Select the number of stars that
                                best represents your experience.
                            </div>


                            <div class="stars">


                                <input
                                    type="radio"
                                    id="star5"
                                    name="rating"
                                    value="5"
                                    <?= $selected_rating === 5
                                        ? "checked"
                                        : "" ?>
                                >

                                <label
                                    for="star5"
                                    title="5 Stars"
                                >
                                    ★
                                </label>


                                <input
                                    type="radio"
                                    id="star4"
                                    name="rating"
                                    value="4"
                                    <?= $selected_rating === 4
                                        ? "checked"
                                        : "" ?>
                                >

                                <label
                                    for="star4"
                                    title="4 Stars"
                                >
                                    ★
                                </label>


                                <input
                                    type="radio"
                                    id="star3"
                                    name="rating"
                                    value="3"
                                    <?= $selected_rating === 3
                                        ? "checked"
                                        : "" ?>
                                >

                                <label
                                    for="star3"
                                    title="3 Stars"
                                >
                                    ★
                                </label>


                                <input
                                    type="radio"
                                    id="star2"
                                    name="rating"
                                    value="2"
                                    <?= $selected_rating === 2
                                        ? "checked"
                                        : "" ?>
                                >

                                <label
                                    for="star2"
                                    title="2 Stars"
                                >
                                    ★
                                </label>


                                <input
                                    type="radio"
                                    id="star1"
                                    name="rating"
                                    value="1"
                                    <?= $selected_rating === 1
                                        ? "checked"
                                        : "" ?>
                                >

                                <label
                                    for="star1"
                                    title="1 Star"
                                >
                                    ★
                                </label>


                            </div>


                            <div
                                class="rating-selected"
                                id="ratingSelected"
                            ></div>


                        </div>

                    </div>


                    <!-- COMMENT -->

                    <div class="review-section comment-section">


                        <div class="comment-header">

                            <label
                                class="review-section-label"
                                for="comment"
                                style="margin-bottom: 0;"
                            >
                                Your Review
                                <span class="required">*</span>
                            </label>


                            <span class="comment-hint">
                                Share your experience honestly.
                            </span>

                        </div>


                        <textarea
                            id="comment"
                            name="comment"
                            placeholder="Tell us what you liked about your experience at NAVA Fade Studio..."
                            required
                        ><?= htmlspecialchars(
                            $comment_value
                        ) ?></textarea>


                    </div>


                    <!-- NOTE -->

                    <div class="review-note">

                        <span class="review-note-icon">
                            ℹ
                        </span>

                        <span>
                            Your review will be reviewed by our
                            team before it appears publicly on
                            the NAVA Fade Studio website.
                        </span>

                    </div>


                    <!-- SUBMIT -->

                    <button
                        type="submit"
                        class="submit-review-btn"
                    >
                        ⭐ &nbsp; Submit Review
                    </button>


                </form>


                <!-- BACK -->

                <div style="text-align: center;">

                    <a
                        href="index.php"
                        class="back-home"
                    >
                        ← Back to Home
                    </a>

                </div>


            <?php endif; ?>


        </div>

    </div>

</div>


<!-- =========================================
     FOOTER
========================================= -->

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


            <p>
                📍 Amlan, Negros Oriental
            </p>


            <p>
                📧 navafadestudio@gmail.com
            </p>


            <p>
                📞 0969 407 4629
            </p>

        </div>


    </div>

</footer>


<!-- =========================================
     STAR RATING SCRIPT
========================================= -->

<script>

    const ratingInputs =
        document.querySelectorAll(
            '.stars input'
        );

    const ratingSelected =
        document.getElementById(
            'ratingSelected'
        );


    function updateRatingText() {

        const selected =
            document.querySelector(
                '.stars input:checked'
            );


        if (!selected) {

            ratingSelected.textContent = "";

            return;
        }


        const rating =
            selected.value;


        const messages = {

            1: "1 Star — We'll work to improve.",

            2: "2 Stars — Thank you for your feedback.",

            3: "3 Stars — We appreciate your feedback.",

            4: "4 Stars — We're glad you enjoyed your experience!",

            5: "5 Stars — We're glad you loved your NAVA experience!"

        };


        ratingSelected.textContent =
            messages[rating] || "";

    }


    ratingInputs.forEach(
        function(input) {

            input.addEventListener(
                "change",
                updateRatingText
            );

        }
    );


    updateRatingText();


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


        dropdown.classList.toggle(
            "show"
        );

    }


    document.addEventListener(
        "click",
        function(event) {

            const menu =
                document.querySelector(
                    ".customer-menu"
                );


            const dropdown =
                document.getElementById(
                    "customerDropdown"
                );


            if (
                menu &&
                dropdown &&
                !menu.contains(event.target)
            ) {

                dropdown.classList.remove(
                    "show"
                );

            }

        }
    );

</script>


</body>

</html>