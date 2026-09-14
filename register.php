<?php

require_once "config/Database.php";
require_once "classes/Customer.php";


$database = new Database();
$db = $database->connect();

$customer = new Customer($db);

$success = "";
$error = "";


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $full_name = trim($_POST["full_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $contact_number = trim($_POST["contact_number"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";


    // =========================================
    // VALIDATION
    // =========================================

    if (
        empty($full_name) ||
        empty($email) ||
        empty($contact_number) ||
        empty($password) ||
        empty($confirm_password)
    ) {

        $error = "Please fill in all required fields.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    } elseif (strlen($password) < 6) {

        $error = "Password must be at least 6 characters.";

    } elseif ($password !== $confirm_password) {

        $error = "Passwords do not match.";

    } elseif ($customer->emailExists($email)) {

        $error = "This email is already registered.";

    } else {

        try {

            $customer->register(
                $full_name,
                $email,
                $contact_number,
                $password
            );

            $success =
                "Account created successfully! You can now log in.";

        } catch (PDOException $e) {

            $error =
                "Registration failed. Please try again.";

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

    <title>Register | NAVA Fade Studio</title>


    <!-- =========================================
         FONT AWESOME
    ========================================== -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >


    <style>

        /* =========================================
           RESET
        ========================================= */

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }


        /* =========================================
           BODY
        ========================================= */

        body {

            min-height: 100vh;

            display: flex;

            align-items: center;

            justify-content: center;

            padding: 35px 20px;

            font-family:
                Bahnschrift,
                "Myriad Pro",
                Arial,
                sans-serif;

            background-image:
                linear-gradient(
                    rgba(14, 20, 35, 0.90),
                    rgba(14, 20, 35, 0.90)
                ),
                url("assets/images/pattern3.png");

            background-size: cover;

            background-position: center;

            color: white;

        }


        /* =========================================
           REGISTER CONTAINER
        ========================================= */

        .register-container {

            width: 100%;

            max-width: 585px;

            padding: 40px;

            background: #0e1423;

            border: 3px solid #b8862c;

            border-radius: 25px;

            box-shadow:
                0 15px 40px
                rgba(0, 0, 0, 0.45);

            text-align: center;

        }


        /* =========================================
           HEADER
        ========================================= */

        .register-header {

            text-align: center;

            margin-bottom: 28px;

        }


        /* =========================================
           LOGO
        ========================================= */

        .register-logo {

            width: 270px;

            max-width: 100%;

            height: auto;

            display: block;

            margin: 0 auto 20px;

        }


        /* =========================================
           TITLE
        ========================================= */

        .register-header h1 {

            margin-bottom: 8px;

            font-size: 30px;

            color: #b8862c;

            font-weight: 700;

        }


        /* =========================================
           SUBTITLE
        ========================================= */

        .register-header p {

            color: #ddd;

            font-size: 15px;

            line-height: 1.5;

        }


        /* =========================================
           SUCCESS MESSAGE
        ========================================= */

        .success-message {

            margin-bottom: 20px;

            padding: 12px 15px;

            border-radius: 8px;

            background: #1f6b45;

            color: white;

            font-size: 14px;

            line-height: 1.4;

        }


        /* =========================================
           ERROR MESSAGE
        ========================================= */

        .error-message {

            margin-bottom: 20px;

            padding: 12px 15px;

            border-radius: 8px;

            background: #8b2020;

            color: white;

            font-size: 14px;

            line-height: 1.4;

        }


        /* =========================================
           FORM GROUP
        ========================================= */

        .form-group {

            text-align: left;

            margin-bottom: 18px;

        }


        /* =========================================
           LABEL
        ========================================= */

        .form-group label {

            display: block;

            margin-bottom: 8px;

            color: #fff;

            font-weight: 600;

            font-size: 15px;

        }


        /* =========================================
           INPUT
        ========================================= */

        .form-group input {

            width: 100%;

            padding: 14px 16px;

            border: 2px solid #555;

            border-radius: 10px;

            background: #fff;

            color: #111;

            font-size: 16px;

            outline: none;

            transition:
                border-color 0.25s ease,
                box-shadow 0.25s ease;

        }


        .form-group input:focus {

            border-color: #b8862c;

            box-shadow:
                0 0 0 3px
                rgba(184, 134, 44, 0.15);

        }


        /* =========================================
           PASSWORD WRAPPER
        ========================================= */

        .password-wrapper {

            position: relative;

        }


        .password-wrapper input {

            width: 100%;

            padding-right: 50px;

        }


        /* =========================================
           PASSWORD TOGGLE
        ========================================= */

        .toggle-password {

            position: absolute;

            right: 12px;

            top: 50%;

            transform: translateY(-50%);

            width: 34px;

            height: 34px;

            display: flex;

            align-items: center;

            justify-content: center;

            background: transparent;

            border: none;

            border-radius: 50%;

            cursor: pointer;

            color: #0e1423;

            font-size: 17px;

            padding: 0;

            transition:
                color 0.2s ease,
                background-color 0.2s ease;

        }


        .toggle-password:hover {

            color: #d4a33a;

            background-color:
                rgba(184, 134, 44, 0.10);

        }


        .toggle-password:focus {

            outline: none;

            box-shadow:
                0 0 0 2px
                rgba(184, 134, 44, 0.25);

        }


        /* =========================================
           CREATE ACCOUNT BUTTON
        ========================================= */

        .register-btn {

            width: 100%;

            padding: 15px;

            margin-top: 4px;

            border: none;

            border-radius: 10px;

            background: #b8862c;

            color: #0e1423;

            font-size: 17px;

            font-weight: bold;

            cursor: pointer;

            transition:
                background-color 0.3s ease,
                transform 0.3s ease;

        }


        .register-btn:hover {

            background: #d4a33a;

            transform: translateY(-2px);

        }


        /* =========================================
           BOTTOM LINKS
        ========================================= */

        .booking-back {

            margin-top: 22px;

            color: #fff;

            font-size: 15px;

            line-height: 1.5;

        }


        .booking-back a {

            color: #b8862c;

            text-decoration: none;

            font-weight: 600;

        }


        .booking-back a:hover {

            color: #d4a33a;

            text-decoration: underline;

        }


        /* =========================================
           RESPONSIVE
        ========================================= */

        @media (max-width: 650px) {

            body {

                padding: 25px 15px;

            }


            .register-container {

                padding: 35px 25px;

                border-radius: 20px;

            }


            .register-logo {

                width: 240px;

                margin-bottom: 18px;

            }


            .register-header h1 {

                font-size: 27px;

            }


            .register-header p {

                font-size: 14px;

            }

        }


        @media (max-width: 400px) {

            .register-container {

                padding: 30px 20px;

            }


            .register-logo {

                width: 210px;

            }


            .register-header h1 {

                font-size: 24px;

            }


            .form-group input {

                padding: 13px 14px;

                font-size: 15px;

            }


            .register-btn {

                padding: 14px;

                font-size: 16px;

            }

        }

    </style>

</head>


<body>


    <div class="register-container">


        <!-- =====================================
             HEADER
        ====================================== -->

        <div class="register-header">


            <img
                src="assets/images/logo.png"
                alt="NAVA Fade Studio Logo"
                class="register-logo"
            >


            <h1>
                Create Your Account
            </h1>


            <p>
                Sign up to book an appointment and manage your reservations.
            </p>


        </div>


        <!-- =====================================
             SUCCESS MESSAGE
        ====================================== -->

        <?php if (!empty($success)): ?>

            <div class="success-message">

                <?= htmlspecialchars($success) ?>

            </div>

        <?php endif; ?>


        <!-- =====================================
             ERROR MESSAGE
        ====================================== -->

        <?php if (!empty($error)): ?>

            <div class="error-message">

                <?= htmlspecialchars($error) ?>

            </div>

        <?php endif; ?>


        <!-- =====================================
             REGISTRATION FORM
        ====================================== -->

        <form method="POST" action="">


            <!-- FULL NAME -->

            <div class="form-group">

                <label for="full_name">
                    Full Name
                </label>

                <input
                    type="text"
                    id="full_name"
                    name="full_name"
                    placeholder="Enter your full name"
                    value="<?= htmlspecialchars($_POST["full_name"] ?? "") ?>"
                    required
                >

            </div>


            <!-- EMAIL -->

            <div class="form-group">

                <label for="email">
                    Email Address
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="Enter your email address"
                    value="<?= htmlspecialchars($_POST["email"] ?? "") ?>"
                    required
                >

            </div>


            <!-- CONTACT NUMBER -->

            <div class="form-group">

                <label for="contact_number">
                    Contact Number
                </label>

                <input
                    type="tel"
                    id="contact_number"
                    name="contact_number"
                    placeholder="09XXXXXXXXX"
                    value="<?= htmlspecialchars($_POST["contact_number"] ?? "") ?>"
                    required
                >

            </div>


            <!-- PASSWORD -->

            <div class="form-group">

                <label for="password">
                    Password
                </label>


                <div class="password-wrapper">

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Create a password"
                        required
                    >


                    <button
                        type="button"
                        class="toggle-password"
                        data-target="password"
                        aria-label="Show password"
                    >

                        <i class="fa-solid fa-eye"></i>

                    </button>

                </div>

            </div>


            <!-- CONFIRM PASSWORD -->

            <div class="form-group">

                <label for="confirm_password">
                    Confirm Password
                </label>


                <div class="password-wrapper">

                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        placeholder="Confirm your password"
                        required
                    >


                    <button
                        type="button"
                        class="toggle-password"
                        data-target="confirm_password"
                        aria-label="Show password"
                    >

                        <i class="fa-solid fa-eye"></i>

                    </button>

                </div>

            </div>


            <!-- CREATE ACCOUNT -->

            <button
                type="submit"
                class="register-btn"
            >
                CREATE ACCOUNT
            </button>


        </form>


        <!-- =====================================
             LOGIN / HOME LINKS
        ====================================== -->

        <div class="booking-back">


            <p>

                Already have an account?

                <a href="login.php">
                    Login here
                </a>

            </p>


            <br>


            <a href="index.php">
                ← Back to Home
            </a>


        </div>


    </div>


    <!-- =========================================
         PASSWORD TOGGLE
    ========================================= -->

    <script>

        const passwordButtons =
            document.querySelectorAll(".toggle-password");


        passwordButtons.forEach(function(button) {

            button.addEventListener("click", function() {

                const targetId =
                    button.dataset.target;

                const passwordInput =
                    document.getElementById(targetId);

                const icon =
                    button.querySelector("i");


                if (passwordInput.type === "password") {

                    passwordInput.type = "text";

                    icon.classList.remove("fa-eye");

                    icon.classList.add("fa-eye-slash");

                    button.setAttribute(
                        "aria-label",
                        "Hide password"
                    );

                } else {

                    passwordInput.type = "password";

                    icon.classList.remove("fa-eye-slash");

                    icon.classList.add("fa-eye");

                    button.setAttribute(
                        "aria-label",
                        "Show password"
                    );

                }

            });

        });

    </script>


</body>

</html>