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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>About Us | NAVA Fade Studio</title>

    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

<!-- =========================
     HEADER / NAVBAR
========================= -->

<header class="header">
    <div class="container navbar">

        <a href="index.php" class="logo">
            <img src="assets/images/logo.png" alt="NAVA Fade Studio">
        </a>

        <nav>

            <a href="index.php">Home</a>

            <a href="about.php" class="active">About Us</a>

            <a href="index.php#services">Service</a>

            <a href="reviews.php">Reviews</a>

            <a href="shop.php">Shop</a>

            <a href="blog.php">Blog</a>

            <a href="book.php" class="nav-button">
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


<!-- =========================
     ABOUT HERO
========================= -->

<section class="about-hero">

    <div class="about-hero-content">

        <span>ABOUT NAVA FADE STUDIO</span>

        <h1>
            MORE THAN JUST
            <strong>A HAIRCUT</strong>
        </h1>

        <div class="about-gold-line"></div>

    </div>

</section>


<!-- =========================
     WHO WE ARE
========================= -->

<section class="about-story">

    <div class="about-story-image">

        <img
            src="assets/images/beard-trimming.png"
            alt="NAVA Fade Studio grooming service"
        >

    </div>


    <div class="about-story-content">

        <span class="about-small-title">
            WHO WE ARE
        </span>

        <h2>
            WE ARE <span>NAVA <br>FADE STUDIO</span>
        </h2>

        <p>
            NAVA Fade Studio is a modern barbershop created
            for customers who want quality grooming and a
            comfortable experience in one place.
        </p>

        <p>
            From classic haircuts and beard grooming to
            hair treatments and styling, we provide services
            designed to help every customer look and feel
            confident.
        </p>

        <p>
            We believe that a great haircut is more than
            changing your appearance. It is about giving
            customers a fresh look and an experience worth
            coming back for.
        </p>

    </div>

</section>


<!-- =====================================================
     ABOUT HERO / OUR MISSION
===================================================== -->

<section class="about-mission">

    <div class="about-mission-hero">

        <div class="about-mission-overlay"></div>

        <div class="about-mission-content">

            <span class="mission-label">
                OUR MISSION
            </span>

            <h1>
                QUALITY GROOMING.
                <br>
                <strong>BETTER EXPERIENCE.</strong>
            </h1>

            <div class="mission-line"></div>

            <p>
                Our mission is to provide reliable and quality
                grooming services while creating a welcoming
                environment for every customer.
            </p>

            <p>
                NAVA Fade Studio aims to combine skilled barbers,
                quality services, and convenient appointment
                booking to make every visit simple and enjoyable.
            </p>

        </div>

    </div>

</section>


<!-- =========================
     WHY CHOOSE NAVA
========================= -->

<section class="why-nava">

    <div class="about-section-heading">

        <span class="about-small-title">
            WHY CHOOSE US
        </span>

        <h2>
            WHY <span>NAVA?</span>
        </h2>

        <p>
            We focus on the things that make a barbershop
            experience better.
        </p>

    </div>


    <div class="why-nava-grid">

        <div class="why-card">

            <div class="why-icon">
                ✂
            </div>

            <h3>
                QUALITY SERVICES
            </h3>

            <p>
                Professional grooming services designed
                around different customer needs and styles.
            </p>

        </div>


        <div class="why-card">

            <div class="why-icon">
                ★
            </div>

            <h3>
                CUSTOMER FOCUSED
            </h3>

            <p>
                We value customer satisfaction and aim to
                make every visit comfortable and enjoyable.
            </p>

        </div>


        <div class="why-card">

            <div class="why-icon">
                ✓
            </div>

            <h3>
                CONVENIENT BOOKING
            </h3>

            <p>
                Customers can easily choose their services
                and schedule an appointment online.
            </p>

        </div>

    </div>

</section>


<!-- =========================
     FOOTER
========================= -->

<footer class="footer">

    <div class="footer-content">

        <!-- BRAND -->

        <div>

            <img
                src="assets/images/logo.png"
                alt="NAVA Fade Studio"
                class="footer-logo"
            >

            <p>
                NAVA Fade Studio provides quality grooming
                services designed to give every customer a
                fresh and confident look.
            </p>

        </div>


        <!-- ABOUT -->

        <div>

            <h3>
                About Us
            </h3>

            <a href="about.php">
                Who We Are
            </a>

            <a href="index.php#services">
                Our Services
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
                About Us
            </a>

            <a href="index.php#services">
                Services
            </a>

            <a href="book.php">
                Book Appointment
            </a>

            <a href="#">
                Contact Us
            </a>

        </div>


        <!-- CONTACT -->

        <div>

            <h3>
                Contact Us
            </h3>

            <p>📍 Amlan, Negros Oriental</p>
            <p>📧 navafadestudio@gmail.com</p>
            <p>📞 0969 407 4629</p>

        </div>

    </div>

</footer>


<!-- =========================
     CUSTOMER DROPDOWN JS
========================= -->

<script>

function toggleCustomerMenu() {

    const dropdown =
        document.getElementById("customerDropdown");

    dropdown.classList.toggle("show");

}


document.addEventListener("click", function(event) {

    const menu =
        document.querySelector(".customer-menu");

    const dropdown =
        document.getElementById("customerDropdown");

    if (
        menu &&
        dropdown &&
        !menu.contains(event.target)
    ) {

        dropdown.classList.remove("show");

    }

});

</script>

</body>
</html>