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

$database = new Database();

$db = $database->connect();

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
        My Profile | NAVA Fade Studio
    </title>


    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >


    <style>

        /* =========================================
           PROFILE PAGE
        ========================================= */

        .profile-page {

            min-height: calc(100vh - 160px);

            padding: 90px 8%;

            display: flex;

            justify-content: center;

            align-items: center;

            background: url("assets/images/pattern3.png");

            

            
        }


        .profile-wrapper {

            width: 100%;

            max-width: 800px;
        }


        /* =========================================
           CONTAINER
        ========================================= */

        .profile-container {

            position: relative;

            overflow: hidden;

            padding: 42px;

            border-radius: 22px;

            border: 1px solid rgba(184, 134, 44, 0.35);
            border-radius: 14px;

            background:
                linear-gradient(
                    145deg,
                    rgba(18, 27, 46, 0.96),
                    rgba(13, 20, 35, 0.96)
                );

            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.08);
        }


        .profile-container::before {

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
                    #b8862c,
                    transparent
                );
        }


        /* =========================================
           HEADER
        ========================================= */

        .profile-header {

            text-align: center;

            margin-bottom: 35px;
        }


        .profile-eyebrow {

            margin-bottom: 10px;

            color: #b8862c;

            font-size: 11px;

            font-weight: 800;

            letter-spacing: 4px;
        }


        .profile-header h1 {

            margin: 0;

            color: #ffffff;

            font-size: 42px;

            font-weight: 800;
        }


        .profile-header h1 span {

            color: #b8862c;
        }


        .profile-header p {

            margin: 12px 0 0;

            color: #666;

            font-size: 14px;
        }


        /* =========================================
           PROFILE IDENTITY
        ========================================= */

        .profile-identity {

            display: flex;

            align-items: center;

            gap: 18px;

            margin-bottom: 32px;

            padding: 20px;

            border-radius: 14px;

            background: #fafafa;

            border: 1px solid rgba(0, 0, 0, 0.07);
        }


        .profile-main-avatar {

            width: 70px;

            height: 70px;

            min-width: 70px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 50%;

            background: #b8862c;

            color: #ffffff;

            font-size: 25px;

            font-weight: 900;

            box-shadow:
                0 8px 20px
                rgba(200, 148, 47, 0.20);
        }


        .profile-identity-text {

            min-width: 0;

            display: flex;

            flex-direction: column;

            gap: 5px;
        }


        .profile-identity-text h2 {

            margin: 0;

            color: #0e1423;

            font-size: 21px;

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;
        }


        .profile-identity-text p {

            margin: 0;

            color: #666;

            font-size: 13px;

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;
        }


        /* =========================================
           SECTION TITLE
        ========================================= */

        .profile-section-title {

            margin-bottom: 18px;

            color: #b8862c;

            font-size: 12px;

            font-weight: 800;

            letter-spacing: 2px;

            text-transform: uppercase;
        }


        /* =========================================
           MESSAGES
        ========================================= */

        .profile-message {

            margin-bottom: 22px;

            padding: 13px 16px;

            border-radius: 9px;

            font-size: 13px;

            font-weight: 600;
        }


        .profile-success {

            color: #70d889;

            background:
                rgba(70, 200, 100, 0.08);

            border: 1px solid
                rgba(70, 200, 100, 0.25);
        }


        .profile-error {

            color: #ff7777;

            background:
                rgba(255, 80, 80, 0.08);

            border: 1px solid
                rgba(255, 80, 80, 0.25);
        }


        /* =========================================
           FORM
        ========================================= */

        .profile-form {

            display: flex;

            flex-direction: column;

            gap: 20px;
        }


        .profile-form-group {

            display: flex;

            flex-direction: column;

            gap: 8px;
        }


        .profile-form-group label {

            color: #0e1423;

            font-size: 13px;

            font-weight: 700;
        }


        .profile-form-group input {

            width: 100%;

            height: 49px;

            padding: 0 15px;

            border: 1px solid #ddd;

            border-radius: 9px;

            outline: none;

            background: #ffffff;

            color: #0e1423;

            font-family: inherit;

            font-size: 14px;

            transition:
                border-color 0.2s ease,
                box-shadow 0.2s ease;
        }


        .profile-form-group input:hover {

            border-color:
                rgba(200, 148, 47, 0.35);
        }


        .profile-form-group input:focus {

            border-color: #b8862c;

            box-shadow:
                0 0 0 3px
                rgba(200, 148, 47, 0.08);
        }


        /* =========================================
           MEMBER INFO
        ========================================= */

        .member-info {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

            margin-top: 5px;

            padding: 15px 16px;

            border-radius: 10px;

            background: rgba(184, 134, 44, 0.06);

            border: 1px solid rgba(184, 134, 44, 0.18);
        }


        .member-info span {

            color: #777;

            font-size: 12px;
        }


        .member-info strong {

            color: #b8862c;

            font-size: 12px;
        }


        /* =========================================
           UPDATE BUTTON
        ========================================= */

        .update-profile-btn {

            width: 100%;

            min-height: 52px;

            margin-top: 5px;

            border: none;

            border-radius: 9px;

            background:
                linear-gradient(
                    135deg,
                    #b8862c,
                    #d9a238
                );

            color: #111827;

            font-family: inherit;

            font-size: 14px;

            font-weight: 800;

            cursor: pointer;

            transition:
                transform 0.2s ease,
                box-shadow 0.2s ease;
        }


        .update-profile-btn:hover {

            transform: translateY(-2px);

            box-shadow:
                0 10px 25px
                rgba(200, 148, 47, 0.22);
        }


        /* =========================================
           BACK
        ========================================= */

        .profile-back {

            text-align: center;

            margin-top: 25px;
        }


        .profile-back a {

            color: #b8862c;

            text-decoration: none;

            font-size: 13px;

            font-weight: 700;

            transition: color 0.2s ease;
        }


        .profile-back a:hover {

            color: #b8862c;
        }


        /* =========================================
           RESPONSIVE
        ========================================= */

        @media (max-width: 600px) {

            .profile-page {

                padding: 50px 15px 70px;
            }


            .profile-container {

                padding: 30px 22px;

                border-radius: 17px;
            }


            .profile-header h1 {

                font-size: 35px;
            }


            .profile-identity {

                padding: 16px;
            }


            .profile-main-avatar {

                width: 60px;

                height: 60px;

                min-width: 60px;

                font-size: 21px;
            }


            .profile-identity-text h2 {

                font-size: 18px;
            }

        }


        @media (max-width: 400px) {

            .profile-container {

                padding: 26px 18px;
            }


            .profile-header h1 {

                font-size: 31px;
            }


            .profile-identity {

                gap: 12px;
            }


            .member-info {

                align-items: flex-start;

                flex-direction: column;

                gap: 5px;
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


        <a
            href="index.php"
            class="logo"
        >

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


            <?php if (isset($_SESSION["customer_id"])): ?>

                <div class="customer-menu">

                    <button
                        class="customer-menu-btn"
                        type="button"
                        onclick="toggleCustomerMenu()"
                    >

                        👤
                        <?= htmlspecialchars(
                            $_SESSION["customer_name"]
                        ) ?>

                        <span class="dropdown-arrow">
                            ▼
                        </span>

                    </button>


                    <div
                        class="customer-dropdown"
                        id="customerDropdown"
                    >

                        <a href="my-orders.php">
                            🛍️ My Orders
                        </a>

                        <a href="appointments.php">
                            📅 My Appointments
                        </a>

                        <a href="review.php">
                            ⭐ Write a Review
                        </a>

                        <div class="dropdown-divider"></div>

                        <a
                            href="logout.php"
                            class="logout-link"
                        >
                            🚪 Logout
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
     PROFILE
========================================= -->

<section class="profile-page">


    <div class="profile-wrapper">


        <div class="profile-container">


            <!-- HEADER -->

            <div class="profile-header">

                <div class="profile-eyebrow">
                    NAVA FADE STUDIO
                </div>


                <h1>
                    My <span>Profile</span>
                </h1>


                <p>
                    Manage your account information.
                </p>

            </div>


            <!-- IDENTITY -->

            <div class="profile-identity">

                <div class="profile-main-avatar">

                    <?= htmlspecialchars(
                        $initials
                    ) ?>

                </div>


                <div class="profile-identity-text">

                    <h2>
                        <?= htmlspecialchars(
                            $customer["full_name"]
                        ) ?>
                    </h2>


                    <p>
                        <?= htmlspecialchars(
                            $customer["email"]
                        ) ?>
                    </p>

                </div>

            </div>


            <!-- SUCCESS -->

            <?php if (!empty($success)): ?>

                <div class="profile-message profile-success">

                    ✓ &nbsp;

                    <?= htmlspecialchars(
                        $success
                    ) ?>

                </div>

            <?php endif; ?>


            <!-- ERROR -->

            <?php if (!empty($error)): ?>

                <div class="profile-message profile-error">

                    ! &nbsp;

                    <?= htmlspecialchars(
                        $error
                    ) ?>

                </div>

            <?php endif; ?>


            <!-- SECTION -->

            <div class="profile-section-title">

                Account Information

            </div>


            <!-- FORM -->

            <form
                method="POST"
                class="profile-form"
            >


                <!-- NAME -->

                <div class="profile-form-group">

                    <label for="full_name">
                        Full Name
                    </label>


                    <input
                        type="text"
                        id="full_name"
                        name="full_name"
                        value="<?= htmlspecialchars(
                            $customer["full_name"]
                        ) ?>"
                        required
                    >

                </div>


                <!-- EMAIL -->

                <div class="profile-form-group">

                    <label for="email">
                        Email Address
                    </label>


                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?= htmlspecialchars(
                            $customer["email"]
                        ) ?>"
                        required
                    >

                </div>


                <!-- CONTACT -->

                <div class="profile-form-group">

                    <label for="contact_number">
                        Contact Number
                    </label>


                    <input
                        type="tel"
                        id="contact_number"
                        name="contact_number"
                        value="<?= htmlspecialchars(
                            $customer["contact_number"]
                        ) ?>"
                        required
                    >

                </div>


                <!-- MEMBER -->

                <div class="member-info">

                    <span>
                        Member since
                    </span>


                    <strong>

                        <?= date(
                            "F j, Y",
                            strtotime(
                                $customer["created_at"]
                            )
                        ) ?>

                    </strong>

                </div>


                <!-- UPDATE -->

                <button
                    type="submit"
                    class="update-profile-btn"
                >

                    UPDATE PROFILE

                </button>


            </form>


            <!-- BACK -->

            <div class="profile-back">

                <a href="index.php">
                    ← Back to Home
                </a>

            </div>


        </div>

    </div>

</section>


<!-- =========================================
     DROPDOWN SCRIPT
========================================= -->

<script>

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