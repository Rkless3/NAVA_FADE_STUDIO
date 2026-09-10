<?php

session_start();

/*
|--------------------------------------------------------------------------
| Check Admin Login
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION["admin_logged_in"]) ||
    $_SESSION["admin_logged_in"] !== true
) {
    header("Location: login.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Required Files
|--------------------------------------------------------------------------
*/

require_once "../config/Database.php";
require_once "../classes/Service.php";


/*
|--------------------------------------------------------------------------
| Database Connection
|--------------------------------------------------------------------------
*/

$database = new Database();
$db = $database->connect();

$service = new Service($db);


/*
|--------------------------------------------------------------------------
| Delete Service
|--------------------------------------------------------------------------
*/

if (isset($_GET["delete"])) {

    $id = intval($_GET["delete"]);

    if ($id > 0 && $service->delete($id)) {
        header("Location: services.php?message=deleted");
        exit;
    }

    header("Location: services.php?message=error");
    exit;
}


/*
|--------------------------------------------------------------------------
| Add Service
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $price = trim($_POST["price"] ?? "");
    $duration = trim($_POST["duration"] ?? "");
    $image = trim($_POST["image"] ?? "");

    if (
        !empty($name) &&
        !empty($description) &&
        !empty($price) &&
        !empty($duration)
    ) {

        /*
        |--------------------------------------------------------------------------
        | Create Service
        |--------------------------------------------------------------------------
        */

        if (
            $service->create(
                $name,
                $description,
                (float) $price,
                $duration,
                $image
            )
        ) {
            header("Location: services.php?message=created");
            exit;
        }
    }

    header("Location: services.php?message=error");
    exit;
}


/*
|--------------------------------------------------------------------------
| Get All Services
|--------------------------------------------------------------------------
*/

$services = $service->getAll();


/*
|--------------------------------------------------------------------------
| Service Statistics
|--------------------------------------------------------------------------
*/

$totalServices = count($services);

$servicesWithImages = 0;
$totalPrice = 0;

foreach ($services as $item) {

    if (!empty($item["image"])) {
        $servicesWithImages++;
    }

    $totalPrice += (float) $item["price"];
}

$averagePrice = $totalServices > 0
    ? $totalPrice / $totalServices
    : 0;

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Manage Services | NAVA Fade Studio</title>


    <style>

        /* =========================================================
           RESET
           ========================================================= */

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }


        /* =========================================================
           BODY
           ========================================================= */

        body {

            min-height: 100vh;

            font-family:
                Bahnschrift,
                "Myriad Pro",
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

            color: #ffffff;
        }


        /* =========================================================
           HEADER
           ========================================================= */

        .admin-header {

            width: 100%;
            height: 90px;

            display: flex;
            align-items: center;
            justify-content: space-between;

            padding: 0 45px;

            background: #0e1423;

            border-bottom: 2px solid #b8862c;

            position: sticky;
            top: 0;

            z-index: 1000;
        }


        .admin-logo {

            display: flex;
            align-items: center;

            gap: 15px;
        }


        .admin-logo img {

            width: 120px;
            height: 120px;

            object-fit: contain;
        }


        .admin-logo-text {

            display: flex;
            flex-direction: column;
        }


        .admin-logo-text h1 {

            color: #b8862c;

            font-size: 21px;

            letter-spacing: 1px;
        }


        .admin-logo-text span {

            color: #888;

            font-size: 11px;

            letter-spacing: 2px;

            margin-top: 3px;
        }


        .admin-user {

            display: flex;
            align-items: center;

            gap: 18px;
        }


        .admin-user span {

            color: #ddd;

            font-size: 14px;
        }


        .admin-user strong {

            color: #b8862c;
        }


        .logout-btn {

            padding: 9px 18px;

            color: #b8862c;

            border: 1px solid #b8862c;

            border-radius: 7px;

            text-decoration: none;

            font-size: 13px;

            font-weight: bold;

            transition: 0.25s;
        }


        .logout-btn:hover {

            background: #b8862c;

            color: #0e1423;
        }


        /* =========================================================
           MAIN LAYOUT
           ========================================================= */

        .dashboard {

            display: flex;

            min-height:
                calc(100vh - 90px);
        }



        /* =========================================================
           MAIN CONTENT
           ========================================================= */

        .main-content {

            flex: 1;

            padding: 42px;

            min-width: 0;
        }


        /* =========================================================
           PAGE HEADER
           ========================================================= */

        .page-header {

            display: flex;

            align-items: flex-end;

            justify-content: space-between;

            gap: 25px;

            margin-bottom: 28px;
        }


        .page-title h2 {

            font-size: 34px;

            line-height: 1.1;

            letter-spacing: 0.5px;
        }


        .page-title p {

            margin-top: 8px;

            color: #999;

            font-size: 14px;
        }


        .add-btn {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 7px;

            padding: 12px 21px;

            background: #b8862c;

            color: #0e1423;

            border: none;

            border-radius: 8px;

            font-size: 14px;

            font-weight: bold;

            text-decoration: none;

            white-space: nowrap;

            transition: 0.25s;
        }


        .add-btn:hover {

            background: #d4a33a;

            transform: translateY(-2px);

            box-shadow:
                0 7px 20px rgba(184, 134, 44, 0.2);
        }


        /* =========================================================
           STAT CARDS
           ========================================================= */

        .stats-grid {

            display: grid;

            grid-template-columns:
                repeat(3, minmax(0, 1fr));

            gap: 18px;

            margin-bottom: 28px;
        }


        .stat-card {

            padding: 21px 22px;

            background:
                linear-gradient(
                    145deg,
                    rgba(20, 28, 47, 0.98),
                    rgba(14, 20, 35, 0.98)
                );

            border:
                1px solid rgba(184, 134, 44, 0.28);

            border-radius: 13px;

            position: relative;

            overflow: hidden;
        }


        .stat-card::after {

            content: "";

            position: absolute;

            width: 70px;
            height: 70px;

            right: -25px;
            bottom: -25px;

            border-radius: 50%;

            border: 1px solid
                rgba(184, 134, 44, 0.2);
        }


        .stat-label {

            color: #999;

            font-size: 12px;

            text-transform: uppercase;

            letter-spacing: 1.2px;
        }


        .stat-value {

            margin-top: 7px;

            color: #f0c34e;

            font-size: 27px;

            font-weight: bold;
        }


        /* =========================================================
           TOOLBAR
           ========================================================= */

        .toolbar {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;

            margin-bottom: 16px;
        }


        .section-title {

            font-size: 18px;

            color: #fff;
        }


        .search-box {

            position: relative;

            width: 280px;
        }


        .search-box input {

            width: 100%;

            padding: 11px 15px 11px 40px;

            background: #111827;

            border: 1px solid #333c4d;

            border-radius: 8px;

            color: #fff;

            outline: none;

            font-family: inherit;

            font-size: 13px;

            transition: 0.25s;
        }


        .search-box input:focus {

            border-color: #b8862c;

            box-shadow:
                0 0 0 3px
                rgba(184, 134, 44, 0.1);
        }


        .search-icon {

            position: absolute;

            left: 14px;
            top: 50%;

            transform:
                translateY(-50%);

            color: #777;

            font-size: 15px;
        }


        /* =========================================================
           MESSAGE
           ========================================================= */

        .message {

            display: flex;

            align-items: center;

            gap: 10px;

            padding: 13px 17px;

            margin-bottom: 20px;

            background:
                rgba(184, 134, 44, 0.14);

            border:
                1px solid rgba(184, 134, 44, 0.45);

            color: #f0c34e;

            border-radius: 9px;

            font-size: 14px;

            font-weight: bold;
        }


        /* =========================================================
           TABLE CONTAINER
           ========================================================= */

        .table-container {

            width: 100%;

            overflow-x: auto;

            background:
                rgba(14, 20, 35, 0.97);

            border:
                1px solid #30394b;

            border-radius: 14px;

            box-shadow:
                0 12px 35px rgba(0, 0, 0, 0.18);
        }


        table {

            width: 100%;

            min-width: 950px;

            border-collapse: collapse;
        }


        thead th {

            padding: 16px 18px;

            background:
                linear-gradient(
                    135deg,
                    #b8862c,
                    #a67625
                );

            color: #0e1423;

            text-align: left;

            font-size: 12px;

            text-transform: uppercase;

            letter-spacing: 0.8px;
        }


        tbody td {

            padding: 17px 18px;

            border-bottom:
                1px solid #252d3c;

            vertical-align: middle;

            font-size: 13px;

            color: #ddd;
        }


        tbody tr {

            transition: 0.2s;
        }


        tbody tr:hover {

            background:
                rgba(184, 134, 44, 0.055);
        }


        tbody tr:last-child td {

            border-bottom: none;
        }


        /* =========================================================
           IMAGE
           ========================================================= */

        .service-image {

            width: 78px;

            height: 55px;

            object-fit: cover;

            border-radius: 8px;

            border:
                1px solid #b8862c;

            display: block;
        }


        .no-image {

            width: 78px;

            height: 55px;

            display: flex;

            align-items: center;

            justify-content: center;

            background: #1b2231;

            border:
                1px dashed #555;

            border-radius: 8px;

            color: #777;

            font-size: 11px;
        }


        /* =========================================================
           SERVICE NAME
           ========================================================= */

        .service-name {

            color: #fff;

            font-size: 14px;

            font-weight: bold;
        }


        /* =========================================================
           DESCRIPTION
           ========================================================= */

        .description {

            max-width: 330px;

            color: #999;

            line-height: 1.5;

            display: -webkit-box;

            -webkit-line-clamp: 2;

            -webkit-box-orient: vertical;

            overflow: hidden;
        }


        /* =========================================================
           PRICE
           ========================================================= */

        .price {

            color: #f0c34e;

            font-weight: bold;

            white-space: nowrap;

            font-size: 14px;
        }


        /* =========================================================
           DURATION BADGE
           ========================================================= */

        .duration {

            display: inline-block;

            padding: 6px 10px;

            background:
                rgba(184, 134, 44, 0.12);

            border:
                1px solid rgba(184, 134, 44, 0.3);

            color: #e4bd55;

            border-radius: 20px;

            font-size: 11px;

            font-weight: bold;

            white-space: nowrap;
        }


        /* =========================================================
           ACTION BUTTONS
           ========================================================= */

        .actions {

            display: flex;

            align-items: center;

            gap: 7px;
        }


        .edit-btn,
        .delete-btn {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            padding: 7px 12px;

            border-radius: 6px;

            text-decoration: none;

            font-size: 12px;

            font-weight: bold;

            transition: 0.2s;
        }


        .edit-btn {

            background: #b8862c;

            color: #0e1423;
        }


        .edit-btn:hover {

            background: #d4a33a;

            transform: translateY(-1px);
        }


        .delete-btn {

            background:
                rgba(139, 48, 48, 0.9);

            color: #fff;
        }


        .delete-btn:hover {

            background: #a83a3a;

            transform: translateY(-1px);
        }


        /* =========================================================
           EMPTY STATE
           ========================================================= */

        .empty-state {

            text-align: center;

            padding: 65px 25px !important;

            color: #777;
        }


        .empty-icon {

            font-size: 38px;

            margin-bottom: 10px;
        }


        .empty-state h3 {

            color: #ddd;

            font-size: 18px;

            margin-bottom: 7px;
        }


        .empty-state p {

            color: #777;

            font-size: 13px;

            margin-bottom: 18px;
        }


        /* =========================================================
           NO SEARCH RESULTS
           ========================================================= */

        .no-search {

            display: none;

            text-align: center;

            padding: 35px;

            color: #777;

            font-size: 14px;
        }


        /* =========================================================
           RESPONSIVE
           ========================================================= */

        @media (max-width: 1000px) {

            .admin-header {

                padding: 0 25px;
            }


            .sidebar {

                width: 210px;
            }


            .main-content {

                padding: 30px 25px;
            }


            .stats-grid {

                grid-template-columns:
                    repeat(3, 1fr);
            }

        }


        @media (max-width: 800px) {

            .admin-header {

                height: 80px;

                padding: 0 18px;
            }


            .admin-logo img {

                width: 95px;
                height: 95px;
            }


            .admin-logo-text {

                display: none;
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

                padding: 12px;

                border-right: none;

                border-bottom:
                    1px solid
                    rgba(184, 134, 44, 0.3);
            }


            .sidebar-title {

                display: none;
            }


            .sidebar a {

                flex-shrink: 0;

                white-space: nowrap;

                margin: 0;

                padding: 10px 14px;
            }


            .main-content {

                padding: 25px 18px;
            }


            .page-header {

                align-items: flex-start;

                flex-direction: column;

                gap: 18px;
            }


            .add-btn {

                width: 100%;
            }


            .stats-grid {

                grid-template-columns: 1fr;
            }


            .toolbar {

                align-items: stretch;

                flex-direction: column;
            }


            .search-box {

                width: 100%;
            }

        }


        @media (max-width: 500px) {

            .main-content {

                padding: 20px 13px;
            }


            .page-title h2 {

                font-size: 28px;
            }


            .admin-header {

                padding: 0 12px;
            }


            .logout-btn {

                padding: 8px 13px;

                font-size: 12px;
            }


            .stat-card {

                padding: 18px;
            }

        }

    </style>

</head>


<body>

<?php include "../admin/navbar.php"; ?>

<!-- =========================================================
     MAIN DASHBOARD
     ========================================================= -->

<div class="admin-layout">

<?php include "../admin/sidebar.php"; ?>

<!-- =====================================================
         MAIN CONTENT
         ===================================================== -->

    <main class="main-content">


        <!-- =================================================
             PAGE HEADER
             ================================================= -->

        <div class="page-header">


            <div class="page-title">

                <h2>
                    Services
                </h2>

                <p>
                    Manage the services offered by
                    NAVA Fade Studio.
                </p>

            </div>


            <a
                href="add-service.php"
                class="add-btn"
            >
                + Add Service
            </a>

        </div>



        <!-- =================================================
             MESSAGES
             ================================================= -->

        <?php if (isset($_GET["message"])): ?>


            <?php if ($_GET["message"] === "created"): ?>

                <div class="message">
                    ✓ Service added successfully!
                </div>


            <?php elseif ($_GET["message"] === "updated"): ?>

                <div class="message">
                    ✓ Service updated successfully!
                </div>


            <?php elseif ($_GET["message"] === "deleted"): ?>

                <div class="message">
                    ✓ Service deleted successfully!
                </div>


            <?php elseif ($_GET["message"] === "error"): ?>

                <div class="message">
                    ⚠ Something went wrong. Please try again.
                </div>

            <?php endif; ?>


        <?php endif; ?>



        <!-- =================================================
             STATISTICS
             ================================================= -->

        <div class="stats-grid">


            <div class="stat-card">

                <div class="stat-label">
                    Total Services
                </div>

                <div class="stat-value">
                    <?= $totalServices ?>
                </div>

            </div>


            <div class="stat-card">

                <div class="stat-label">
                    Services With Images
                </div>

                <div class="stat-value">
                    <?= $servicesWithImages ?>
                </div>

            </div>


            <div class="stat-card">

                <div class="stat-label">
                    Average Price
                </div>

                <div class="stat-value">
                    ₱<?= number_format($averagePrice, 2) ?>
                </div>

            </div>


        </div>



        <!-- =================================================
             TOOLBARs
             ================================================= -->

        <div class="toolbar">


            <div class="section-title">
                Service List
            </div>


            <div class="search-box">

                <span class="search-icon">
                    🔍
                </span>

                <input
                    type="text"
                    id="serviceSearch"
                    placeholder="Search services..."
                    autocomplete="off"
                >

            </div>


        </div>



        <!-- =================================================
             SERVICES TABLE
             ================================================= -->

        <div class="table-container">


            <table id="servicesTable">


                <thead>

                    <tr>

                        <th>
                            Image
                        </th>

                        <th>
                            Service
                        </th>

                        <th>
                            Description
                        </th>

                        <th>
                            Price
                        </th>

                        <th>
                            Duration
                        </th>

                        <th>
                            Actions
                        </th>

                    </tr>

                </thead>


                <tbody id="serviceTableBody">


                    <?php if (!empty($services)): ?>


                        <?php foreach ($services as $item): ?>


                            <tr class="service-row">


                                <!-- IMAGE -->

                                <td>

                                    <?php if (!empty($item["image"])): ?>

                                        <img
                                            src="../assets/images/<?= htmlspecialchars(
                                                $item["image"]
                                            ) ?>"
                                            alt="<?= htmlspecialchars(
                                                $item["service_name"]
                                            ) ?>"
                                            class="service-image"
                                        >

                                    <?php else: ?>

                                        <div class="no-image">
                                            No Image
                                        </div>

                                    <?php endif; ?>

                                </td>



                                <!-- SERVICE NAME -->

                                <td>

                                    <div class="service-name">

                                        <?= htmlspecialchars(
                                            $item["service_name"]
                                        ) ?>

                                    </div>

                                </td>



                                <!-- DESCRIPTION -->

                                <td>

                                    <div class="description">

                                        <?= htmlspecialchars(
                                            $item["description"]
                                        ) ?>

                                    </div>

                                </td>



                                <!-- PRICE -->

                                <td class="price">

                                    ₱<?= number_format(
                                        (float) $item["price"],
                                        2
                                    ) ?>

                                </td>



                                <!-- DURATION -->

                                <td>

                                    <span class="duration">

                                        <?= htmlspecialchars(
                                            $item["duration"]
                                        ) ?>

                                    </span>

                                </td>



                                <!-- ACTIONS -->

                                <td>

                                    <div class="actions">

                                        <a
                                            href="edit-service.php?id=<?= $item["id"] ?>"
                                            class="edit-btn"
                                        >
                                            Edit
                                        </a>


                                        <a
                                            href="services.php?delete=<?= $item["id"] ?>"
                                            class="delete-btn"
                                            onclick="return confirm(
                                                'Are you sure you want to delete this service?'
                                            );"
                                        >
                                            Delete
                                        </a>

                                    </div>

                                </td>


                            </tr>


                        <?php endforeach; ?>


                    <?php else: ?>


                        <tr>

                            <td
                                colspan="6"
                                class="empty-state"
                            >

                                <div class="empty-icon">
                                    ✂️
                                </div>

                                <h3>
                                    No Services Available
                                </h3>

                                <p>
                                    Add your first service to
                                    start building your service list.
                                </p>

                                <a
                                    href="add-service.php"
                                    class="add-btn"
                                >
                                    + Add First Service
                                </a>

                            </td>

                        </tr>


                    <?php endif; ?>


                </tbody>


            </table>


            <!-- SEARCH EMPTY STATE -->

            <div
                class="no-search"
                id="noSearch"
            >
                No services match your search.
            </div>


        </div>


    </main>


</div>



<!-- =========================================================
     SEARCH SCRIPT
     ========================================================= -->

<script>

    const searchInput =
        document.getElementById("serviceSearch");

    const rows =
        document.querySelectorAll(".service-row");

    const noSearch =
        document.getElementById("noSearch");


    searchInput.addEventListener("input", function () {

        const searchTerm =
            this.value.toLowerCase().trim();

        let visibleRows = 0;


        rows.forEach(function (row) {

            const text =
                row.textContent.toLowerCase();


            if (text.includes(searchTerm)) {

                row.style.display = "";

                visibleRows++;

            } else {

                row.style.display = "none";

            }

        });


        if (
            searchTerm !== "" &&
            visibleRows === 0 &&
            rows.length > 0
        ) {

            noSearch.style.display = "block";

        } else {

            noSearch.style.display = "none";

        }

    });

</script>


</body>

</html>