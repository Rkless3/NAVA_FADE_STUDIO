<?php

session_start();

require_once "config/Database.php";
require_once "classes/Customer.php";

$database = new Database();
$db = $database->connect();

$customer = new Customer($db);

$error = "";


/* =====================================================
   IF ALREADY LOGGED IN
===================================================== */

if (
    isset($_SESSION["admin_logged_in"]) &&
    $_SESSION["admin_logged_in"] === true
) {
    header("Location: admin/dashboard.php");
    exit;
}

if (isset($_SESSION["customer_id"])) {
    header("Location: index.php");
    exit;
}


/* =====================================================
   LOGIN PROCESS
===================================================== */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $identifier = trim($_POST["identifier"] ?? "");
    $password = $_POST["password"] ?? "";


    /* =================================================
       VALIDATION
    ================================================= */

    if ($identifier === "" || $password === "") {

        $error = "Please enter your username/email and password.";

    } else {

        try {

            /* ============================================
               ADMIN LOGIN
               Admin credentials are stored in the users
               table. The password is verified using
               password_verify().
            ============================================ */

            $stmt = $db->prepare("
                SELECT id, username, password, role
                FROM users
                WHERE username = :username
                AND role = 'admin'
                LIMIT 1
            ");

            $stmt->execute([
                ":username" => $identifier
            ]);

            $admin = $stmt->fetch();


            /*
             * Verify the hashed administrator password.
             */
            if (
                $admin &&
                password_verify($password, $admin["password"])
            ) {

                /*
                 * Prevent session fixation and remove
                 * customer session information.
                 */
                unset(
                    $_SESSION["customer_id"],
                    $_SESSION["customer_name"],
                    $_SESSION["customer_email"]
                );

                session_regenerate_id(true);

                $_SESSION["admin_logged_in"] = true;
                $_SESSION["admin_id"] = $admin["id"];
                $_SESSION["admin_username"] = $admin["username"];
                $_SESSION["admin_role"] = $admin["role"];


                /*
                 * Automatically upgrade the password hash
                 * if PHP recommends a newer/default hash.
                 */
                if (
                    password_needs_rehash(
                        $admin["password"],
                        PASSWORD_DEFAULT
                    )
                ) {

                    $newHash = password_hash(
                        $password,
                        PASSWORD_DEFAULT
                    );

                    $updatePassword = $db->prepare("
                        UPDATE users
                        SET password = :password
                        WHERE id = :id
                    ");

                    $updatePassword->execute([
                        ":password" => $newHash,
                        ":id" => $admin["id"]
                    ]);
                }


                header("Location: admin/dashboard.php");
                exit;
            }


            /* ============================================
               CUSTOMER LOGIN
            ============================================ */

            $logged_customer = $customer->login(
                $identifier,
                $password
            );


            if ($logged_customer) {

                /*
                 * Prevent session mixing between
                 * customer and administrator.
                 */
                unset(
                    $_SESSION["admin_logged_in"],
                    $_SESSION["admin_id"],
                    $_SESSION["admin_username"],
                    $_SESSION["admin_role"]
                );

                session_regenerate_id(true);


                /*
                 * Store customer information.
                 */
                $_SESSION["customer_id"] =
                    $logged_customer["id"];

                $_SESSION["customer_name"] =
                    $logged_customer["full_name"];

                $_SESSION["customer_email"] =
                    $logged_customer["email"];


                header("Location: index.php");
                exit;
            }


            /*
             * If neither admin nor customer login
             * succeeded.
             */
            $error = "Invalid username/email or password.";

        } catch (PDOException $e) {

            /*
             * Do not expose database errors to users.
             */
            $error = "Unable to process login right now. Please try again.";
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
        Login | NAVA Fade Studio
    </title>


    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }


        body {

            min-height: 100vh;

            display: flex;

            align-items: center;

            justify-content: center;

            padding: 30px 15px;

            font-family:
                Bahnschrift,
                "Myriad Pro",
                Arial,
                sans-serif;

            background:
                linear-gradient(
                    rgba(14, 20, 35, 0.90),
                    rgba(14, 20, 35, 0.90)
                ),
                url("assets/images/pattern2.png");

            background-size: cover;

            background-position: center;

            color: white;
        }


        /* =====================================================
           LOGIN CONTAINER
        ===================================================== */

        .login-container {

            width: 100%;

            max-width: 450px;

            padding: 45px;

            background: #0e1423;

            border:
                2px solid #b8862c;

            border-radius: 25px;

            box-shadow:
                0 20px 50px
                rgba(0, 0, 0, 0.45);

            text-align: center;
        }


        /* =====================================================
           LOGO
        ===================================================== */

        .login-logo {

            width: 270px;

            max-width: 100%;

            height: auto;

            display: block;

            margin:
                0 auto 25px;
        }


        /* =====================================================
           HEADER
        ===================================================== */

        .login-header {

            margin-bottom: 30px;
        }


        .login-header h1 {

            margin-bottom: 8px;

            color: #ffffff;

            font-size: 32px;

            font-weight: 800;
        }


        .login-header h1 span {

            color: #b8862c;
        }


        .login-header p {

            color: #aaa;

            font-size: 15px;

            line-height: 1.5;
        }


        /* =====================================================
           ERROR MESSAGE
        ===================================================== */

        .error-message {

            margin-bottom: 20px;

            padding: 12px 15px;

            border-radius: 8px;

            background:
                rgba(139, 32, 32, 0.95);

            border:
                1px solid
                rgba(255, 100, 100, 0.4);

            color: #ffffff;

            font-size: 14px;

            line-height: 1.4;
        }


        /* =====================================================
           FORM
        ===================================================== */

        .form-group {

            text-align: left;

            margin-bottom: 20px;
        }


        .form-group label {

            display: block;

            margin-bottom: 8px;

            color: #ffffff;

            font-size: 15px;

            font-weight: 600;
        }


        .form-group input {

            width: 100%;

            padding: 14px 16px;

            border:
                2px solid #555;

            border-radius: 10px;

            background: #ffffff;

            color: #111111;

            font-family: inherit;

            font-size: 16px;

            outline: none;

            transition:
                border-color 0.2s ease,
                box-shadow 0.2s ease;
        }


        .form-group input:focus {

            border-color: #b8862c;

            box-shadow:
                0 0 0 3px
                rgba(184, 134, 44, 0.15);
        }


        /* =====================================================
           PASSWORD
        ===================================================== */

        .password-wrapper {

            position: relative;
        }


        .password-wrapper input {

            padding-right: 50px;
        }


        .toggle-password {

            position: absolute;

            right: 12px;

            top: 50%;

            transform:
                translateY(-50%);

            background: transparent;

            border: none;

            cursor: pointer;

            padding: 5px;

            color: #555;

            font-size: 18px;
        }


        .toggle-password:hover {

            color: #b8862c;
        }


        /* =====================================================
           LOGIN BUTTON
        ===================================================== */

        .login-btn {

            width: 100%;

            padding: 15px;

            margin-top: 5px;

            border: none;

            border-radius: 10px;

            background: #b8862c;

            color: #0e1423;

            font-family: inherit;

            font-size: 17px;

            font-weight: 800;

            cursor: pointer;

            transition:
                background-color 0.3s ease,
                transform 0.3s ease,
                box-shadow 0.3s ease;
        }


        .login-btn:hover {

            background: #d4a33a;

            transform:
                translateY(-2px);

            box-shadow:
                0 8px 20px
                rgba(184, 134, 44, 0.25);
        }


        /* =====================================================
           REGISTER / HOME LINKS
        ===================================================== */

        .login-links {

            margin-top: 25px;

            color: #ffffff;

            font-size: 15px;

            line-height: 1.6;
        }


        .login-links a {

            color: #b8862c;

            text-decoration: none;

            font-weight: 600;
        }


        .login-links a:hover {

            color: #d4a33a;

            text-decoration: underline;
        }


        .back-home {

            display: inline-block;

            margin-top: 18px;
        }


        /* =====================================================
           LOGIN NOTE
        ===================================================== */

        .login-note {

            margin-top: 25px;

            padding-top: 18px;

            border-top:
                1px solid
                rgba(255, 255, 255, 0.10);

            color: #777;

            font-size: 12px;

            line-height: 1.5;
        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 600px) {

            body {

                padding: 20px 15px;
            }


            .login-container {

                padding: 35px 25px;

                border-radius: 20px;
            }


            .login-logo {

                width: 240px;

                margin-bottom: 20px;
            }


            .login-header h1 {

                font-size: 28px;
            }


            .login-header p {

                font-size: 14px;
            }
        }


        @media (max-width: 400px) {

            .login-container {

                padding: 30px 20px;
            }


            .login-logo {

                width: 220px;
            }


            .login-header h1 {

                font-size: 25px;
            }


            .form-group input {

                padding: 13px 14px;

                font-size: 15px;
            }


            .login-btn {

                padding: 14px;

                font-size: 16px;
            }
        }

    </style>

</head>


<body>


<div class="login-container">


    <!-- =====================================================
         LOGO
    ===================================================== -->

    <img
        src="assets/images/logo.png"
        alt="NAVA Fade Studio Logo"
        class="login-logo"
    >


    <!-- =====================================================
         HEADER
    ===================================================== -->

    <div class="login-header">

        <p>
            Login to access your NAVA Fade Studio account.
        </p>

    </div>


    <!-- =====================================================
         ERROR
    ===================================================== -->

    <?php if (!empty($error)): ?>

        <div class="error-message">

            <?= htmlspecialchars($error) ?>

        </div>

    <?php endif; ?>


    <!-- =====================================================
         LOGIN FORM
    ===================================================== -->

    <form
        method="POST"
        action=""
    >


        <!-- USERNAME / EMAIL -->

        <div class="form-group">

            <label for="identifier">

                Email or Username

            </label>

            <input
                type="text"
                id="identifier"
                name="identifier"
                placeholder="Enter your email or username"
                value="<?= htmlspecialchars($_POST["identifier"] ?? "") ?>"
                autocomplete="username"
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
                    placeholder="Enter your password"
                    autocomplete="current-password"
                    required
                >


                <button
                    type="button"
                    class="toggle-password"
                    data-target="password"
                    aria-label="Show or hide password"
                >
                    👁
                </button>

            </div>

        </div>


        <!-- LOGIN -->

        <button
            type="submit"
            class="login-btn"
        >
            LOGIN
        </button>


    </form>


    <!-- =====================================================
         LINKS
    ===================================================== -->

    <div class="login-links">

        <p>

            Don't have an account?

            <a href="register.php">
                Create one here
            </a>

        </p>

    </div>


    <!-- =====================================================
         NOTE
    ===================================================== -->

    <div class="login-note">

        One login page for NAVA Fade Studio
        administrator and customer accounts.

    </div>


</div>


<!-- =====================================================
     PASSWORD TOGGLE
===================================================== -->

<script>

    const passwordButtons =
        document.querySelectorAll(".toggle-password");


    passwordButtons.forEach(function(button) {

        button.addEventListener(
            "click",
            function() {

                const targetId =
                    button.dataset.target;

                const passwordInput =
                    document.getElementById(targetId);


                if (
                    passwordInput.type ===
                    "password"
                ) {

                    passwordInput.type = "text";

                    button.textContent = "🙈";

                } else {

                    passwordInput.type = "password";

                    button.textContent = "👁";

                }

            }
        );

    });

</script>


</body>

</html>