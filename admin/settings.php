<?php

session_start();


/* =====================================================
   ADMIN ACCESS
===================================================== */

if (
    !isset($_SESSION["admin_logged_in"]) ||
    $_SESSION["admin_logged_in"] !== true
) {

    header("Location: ../login.php");

    exit;

}


/* =====================================================
   DATABASE
===================================================== */

require_once "../config/Database.php";


$database = new Database();

$db = $database->connect();


/* =====================================================
   DEFAULT SETTINGS
===================================================== */

$defaultSettings = [

    "studio_name" => "NAVA Fade Studio",

    "email" => "navafadestudio@gmail.com",

    "phone" => "0969 407 4629",

    "address" => "Amlan, Negros Oriental",

    "weekday_hours" => "9:00 AM - 8:00 PM",

    "saturday_hours" => "9:00 AM - 8:00 PM",

    "sunday_hours" => "10:00 AM - 6:00 PM",

    "discount" => "20",

    "description" =>
        "NAVA Fade Studio is dedicated to delivering clean, modern, and personalized grooming experiences."

];


/* =====================================================
   LOAD SETTINGS
===================================================== */

$settings = $defaultSettings;


$stmt = $db->query("
    SELECT
        setting_key,
        setting_value
    FROM settings
");


$rows = $stmt->fetchAll();


foreach ($rows as $row) {

    if (
        array_key_exists(
            $row["setting_key"],
            $settings
        )
    ) {

        $settings[
            $row["setting_key"]
        ] = $row["setting_value"];

    }

}


/* =====================================================
   SAVE SETTINGS
===================================================== */

$message = "";


if (
    $_SERVER["REQUEST_METHOD"] === "POST"
) {

    $updatedSettings = [

        "studio_name" =>
            trim(
                $_POST["studio_name"] ?? ""
            ),

        "email" =>
            trim(
                $_POST["email"] ?? ""
            ),

        "phone" =>
            trim(
                $_POST["phone"] ?? ""
            ),

        "address" =>
            trim(
                $_POST["address"] ?? ""
            ),

        "weekday_hours" =>
            trim(
                $_POST["weekday_hours"] ?? ""
            ),

        "saturday_hours" =>
            trim(
                $_POST["saturday_hours"] ?? ""
            ),

        "sunday_hours" =>
            trim(
                $_POST["sunday_hours"] ?? ""
            ),

        "discount" =>
            trim(
                $_POST["discount"] ?? "0"
            ),

        "description" =>
            trim(
                $_POST["description"] ?? ""
            )

    ];


    try {

        $db->beginTransaction();


        $stmt = $db->prepare("
            INSERT INTO settings (
                setting_key,
                setting_value
            )
            VALUES (
                :setting_key,
                :setting_value
            )
            ON DUPLICATE KEY UPDATE
                setting_value = VALUES(setting_value)
        ");


        foreach (
            $updatedSettings
            as $key => $value
        ) {

            $stmt->execute([

                ":setting_key" => $key,

                ":setting_value" => $value

            ]);

        }


        $db->commit();


        $settings =
            $updatedSettings;


        $message =
            "Settings saved successfully!";


    } catch (PDOException $e) {

        if ($db->inTransaction()) {

            $db->rollBack();

        }


        $message =
            "Unable to save settings. Please try again.";

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
        Settings | NAVA Fade Studio Admin
    </title>


    <style>

        * {
            box-sizing: border-box;

            margin: 0;

            padding: 0;
        }


        body {

            font-family:
                "Bahnschrift",
                "Segoe UI",
                Arial,
                sans-serif;

            background:
                linear-gradient(
                    rgba(8, 12, 22, 0.92),
                    rgba(8, 12, 22, 0.96)
                ),
                url("../assets/images/pattern3.png");

            background-size: cover;

            background-position: center;

            background-attachment: fixed;

            color: #fff;

            min-height: 100vh;

        }

        /* =========================================================
        BRAND COLORS
        ========================================================= */

        :root {
            --navy: #0e1423;
            --gold: #d9a82e;
            --light-gold: #e8bd55;

            --white: #ffffff;
            --black: #111111;

            --light-gray: #f5f5f5;
            --gray: #cccccc;
            --dark-gray: #555555;
        }


        /* =====================================================
           HEADER
        ===================================================== */

        .admin-header {

            height: 95px;

            background: #0e1423;

            border-bottom:
                2px solid #b8862c;

            display: flex;

            align-items: center;

            justify-content: space-between;

            padding: 0 40px;

            position: sticky;

            top: 0;

            z-index: 1000;

        }


        .admin-logo img {

            width: 150px;

            height: 150px;

            object-fit: contain;

        }


        .admin-user {

            display: flex;

            align-items: center;

            gap: 25px;

            color: #ddd;

        }


        .admin-user span {

            font-size: 15px;

        }


        .logout-btn {

            text-decoration: none;

            color: white;

            background: #b8862c;

            padding: 10px 20px;

            border-radius: 5px;

            font-weight: bold;

            transition: 0.3s ease;

        }


        .logout-btn:hover {

            background: #d09c36;

            transform: translateY(-2px);

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
           MAIN
        ===================================================== */

        .main-content {

            flex: 1;

            padding: 45px;

            max-width: 1250px;

        }


        .page-header {

            margin-bottom: 35px;

        }


        .page-header h2 {

            font-size: 38px;

            margin-bottom: 8px;

        }


        .page-header p {

            color: #aaa;

            line-height: 1.6;

        }


        /* =====================================================
           MESSAGE
        ===================================================== */

        .message {

            background:
                    rgba(23, 35, 21, 0.96);

            border:
                1px solid #2cb831;

            color: #2cb831;

            padding: 15px 20px;

            border-radius: 7px;

            margin-bottom: 25px;

        }


        /* =====================================================
           SETTINGS CARD
        ===================================================== */

        .settings-card {

            border: 1px solid rgba(184, 134, 44, 0.35);
            border-radius: 14px;

            background:
                linear-gradient(
                    145deg,
                    rgba(18, 27, 46, 0.96),
                    rgba(13, 20, 35, 0.96)
                );

            color: #222;

            border-radius: 10px;

            padding: 35px;

            box-shadow:
                0 15px 40px
                rgba(0, 0, 0, 0.25);

        }


        .settings-section {

            margin-bottom: 35px;

        }


        .settings-section:last-child {

            margin-bottom: 0;

        }


        .settings-section-title {

            color: var(--light-gold);

            font-size: 22px;

            margin-bottom: 8px;

            padding-bottom: 12px;

            border-bottom:
                2px solid #eee;

        }


        .settings-section-description {

            color: #777;

            font-size: 14px;

            margin-bottom: 25px;

        }


        .form-grid {

            display: grid;

            grid-template-columns:
                repeat(2, 1fr);

            gap: 22px;

        }


        .form-group {

            display: flex;

            flex-direction: column;

        }


        .form-group.full {

            grid-column: 1 / -1;

        }


        .form-group label {

            color: #fff;

            font-weight: bold;

            font-size: 14px;

            margin-bottom: 8px;

        }


        .form-group input,
        .form-group textarea {

            width: 100%;

            background:
                rgba(
                    184,
                    134,
                    44,
                    0.15
                );

            border:
                1px solid #b8862c;

            color: #fff;

            border-radius: 6px;

            padding: 13px 14px;

            font-family: inherit;

            font-size: 15px;

            outline: none;

            transition: 0.3s ease;

        }


        .form-group input:focus,
        .form-group textarea:focus {

            border-color: #b8862c;

            box-shadow:
                0 0 0 3px
                rgba(
                    184,
                    134,
                    44,
                    0.12
                );

        }


        .form-group textarea {

            min-height: 120px;

            resize: vertical;

        }


        .form-hint {

            color: #999;

            font-size: 12px;

            margin-top: 6px;

        }


        /* =====================================================
           BUTTONS
        ===================================================== */

        .form-actions {

            display: flex;

            justify-content: flex-end;

            gap: 12px;

            margin-top: 35px;

            padding-top: 25px;

            border-top:
                1px solid #eee;

        }


        .save-btn {

            border: none;

            background: #b8862c;

            color: white;

            padding: 13px 28px;

            border-radius: 6px;

            font-family: inherit;

            font-weight: bold;

            font-size: 15px;

            cursor: pointer;

            transition: 0.3s ease;

        }


        .save-btn:hover {

            background: #9f7324;

            transform: translateY(-2px);

        }


        .reset-btn {

            display: inline-flex;

            align-items: center;

            text-decoration: none;

            background: #eee;

            color: #333;

            padding: 13px 25px;

            border-radius: 6px;

            font-weight: bold;

        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 900px) {

            .sidebar {

                width: 200px;

            }


            .main-content {

                padding: 30px;

            }

        }


        @media (max-width: 700px) {

            .admin-header {

                padding: 0 20px;

            }


            .admin-logo img {

                width: 110px;

                height: 110px;

            }


            .admin-user span {

                display: none;

            }


            .dashboard {

                flex-direction: column;

            }


            .sidebar {

                width: 100%;

                display: flex;

                gap: 5px;

                overflow-x: auto;

                padding: 15px;

            }


            .sidebar-title {

                display: none;

            }


            .sidebar a {

                white-space: nowrap;

                margin: 0;

            }


            .main-content {

                padding: 25px 20px;

            }


            .page-header h2 {

                font-size: 30px;

            }


            .settings-card {

                padding: 25px 20px;

            }


            .form-grid {

                grid-template-columns: 1fr;

            }


            .form-group.full {

                grid-column: auto;

            }


            .form-actions {

                flex-direction: column;

            }


            .save-btn,
            .reset-btn {

                width: 100%;

                justify-content: center;

            }

        }

    </style>

</head>


<body>

<?php include "../admin/navbar.php"; ?>

<!-- =====================================================
     ADMIN LAYOUT
===================================================== -->

<div class="admin-layout">

<?php include "../admin/sidebar.php"; ?>

<!-- =================================================
         MAIN CONTENT
    ================================================= -->

    <main class="main-content">


        <div class="page-header">

            <h2>
                Settings
            </h2>

            <p>
                Manage your NAVA Fade Studio
                business information and website details.
            </p>

        </div>


        <?php if ($message !== ""): ?>

            <div class="message">

                <?= htmlspecialchars(
                    $message
                ) ?>

            </div>

        <?php endif; ?>


        <!-- =================================================
             SETTINGS FORM
        ================================================= -->

        <div class="settings-card">


            <form
                method="POST"
                action="settings.php"
            >


                <!-- =========================================
                     BUSINESS INFORMATION
                ========================================= -->

                <div class="settings-section">

                    <h3 class="settings-section-title">
                        Business Information
                    </h3>


                    <p class="settings-section-description">

                        Update the basic information
                        of your barber studio.

                    </p>


                    <div class="form-grid">


                        <div class="form-group">

                            <label for="studio_name">
                                Studio Name
                            </label>

                            <input
                                type="text"
                                id="studio_name"
                                name="studio_name"
                                value="<?= htmlspecialchars(
                                    $settings["studio_name"]
                                ) ?>"
                                required
                            >

                        </div>


                        <div class="form-group">

                            <label for="email">
                                Email Address
                            </label>

                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="<?= htmlspecialchars(
                                    $settings["email"]
                                ) ?>"
                                required
                            >

                        </div>


                        <div class="form-group">

                            <label for="phone">
                                Phone Number
                            </label>

                            <input
                                type="text"
                                id="phone"
                                name="phone"
                                value="<?= htmlspecialchars(
                                    $settings["phone"]
                                ) ?>"
                            >

                        </div>


                        <div class="form-group">

                            <label for="address">
                                Address
                            </label>

                            <input
                                type="text"
                                id="address"
                                name="address"
                                value="<?= htmlspecialchars(
                                    $settings["address"]
                                ) ?>"
                            >

                        </div>


                        <div class="form-group full">

                            <label for="description">
                                Studio Description
                            </label>

                            <textarea
                                id="description"
                                name="description"
                            ><?= htmlspecialchars(
                                $settings["description"]
                            ) ?></textarea>

                        </div>


                    </div>

                </div>


                <!-- =========================================
                     BUSINESS HOURS
                ========================================= -->

                <div class="settings-section">

                    <h3 class="settings-section-title">
                        Business Hours
                    </h3>


                    <p class="settings-section-description">

                        Set the opening hours displayed
                        for your customers.

                    </p>


                    <div class="form-grid">


                        <div class="form-group">

                            <label for="weekday_hours">
                                Monday - Friday
                            </label>

                            <input
                                type="text"
                                id="weekday_hours"
                                name="weekday_hours"
                                value="<?= htmlspecialchars(
                                    $settings["weekday_hours"]
                                ) ?>"
                            >

                        </div>


                        <div class="form-group">

                            <label for="saturday_hours">
                                Saturday
                            </label>

                            <input
                                type="text"
                                id="saturday_hours"
                                name="saturday_hours"
                                value="<?= htmlspecialchars(
                                    $settings["saturday_hours"]
                                ) ?>"
                            >

                        </div>


                        <div class="form-group">

                            <label for="sunday_hours">
                                Sunday
                            </label>

                            <input
                                type="text"
                                id="sunday_hours"
                                name="sunday_hours"
                                value="<?= htmlspecialchars(
                                    $settings["sunday_hours"]
                                ) ?>"
                            >

                        </div>


                    </div>

                </div>


                <!-- =========================================
                     PROMOTION
                ========================================= -->

                <div class="settings-section">

                    <h3 class="settings-section-title">
                        Promotion
                    </h3>


                    <p class="settings-section-description">

                        Manage the promotional discount
                        displayed on the website.

                    </p>


                    <div class="form-grid">


                        <div class="form-group">

                            <label for="discount">
                                First Booking Discount (%)
                            </label>

                            <input
                                type="number"
                                id="discount"
                                name="discount"
                                min="0"
                                max="100"
                                value="<?= htmlspecialchars(
                                    $settings["discount"]
                                ) ?>"
                            >

                            <span class="form-hint">
                                Example: 20 means 20% off.
                            </span>

                        </div>


                    </div>

                </div>


                <!-- =========================================
                     ACTIONS
                ========================================= -->

                <div class="form-actions">


                    <a
                        href="settings.php"
                        class="reset-btn"
                    >
                        Reset
                    </a>


                    <button
                        type="submit"
                        class="save-btn"
                    >
                        Save Settings
                    </button>


                </div>


            </form>


        </div>


    </main>


</div>


</body>

</html>