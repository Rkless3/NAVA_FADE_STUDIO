<?php

session_start();


/* =========================================
   ADMIN SECURITY
========================================= */

if (
    !isset($_SESSION["admin_logged_in"]) ||
    $_SESSION["admin_logged_in"] !== true
) {
    header("Location: login.php");
    exit;
}


/* =========================================
   DATABASE
========================================= */

require_once "../config/Database.php";
require_once "../classes/Review.php";


$database = new Database();
$db = $database->connect();

$reviewModel = new Review($db);


/* =========================================
   ALLOWED STATUS
========================================= */

$allowedStatuses = [
    "Pending",
    "Approved",
    "Hidden"
];


$message = "";


/* =========================================
   UPDATE STATUS
========================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["update_status"])
) {

    $review_id = (int) ($_POST["review_id"] ?? 0);

    $status = $_POST["status"] ?? "";


    if (
        $review_id > 0 &&
        in_array($status, $allowedStatuses, true)
    ) {

        if (
            $reviewModel->updateStatus(
                $review_id,
                $status
            )
        ) {

            header(
                "Location: reviews.php?message=updated"
            );

            exit;

        } else {

            $message =
                "Failed to update review status.";
        }

    } else {

        $message =
            "Invalid review information.";
    }
}


/* =========================================
   SET FEATURED REVIEW
========================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["set_featured"])
) {

    $review_id = (int) ($_POST["review_id"] ?? 0);


    if ($review_id > 0) {

        if (
            $reviewModel->setFeatured(
                $review_id
            )
        ) {

            header(
                "Location: reviews.php?message=featured"
            );

            exit;

        } else {

            $message =
                "Failed to set homepage review.";
        }
    }
}


/* =========================================
   DELETE REVIEW
========================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["delete_review"])
) {

    $review_id = (int) ($_POST["review_id"] ?? 0);


    if ($review_id > 0) {

        if (
            $reviewModel->delete(
                $review_id
            )
        ) {

            header(
                "Location: reviews.php?message=deleted"
            );

            exit;

        } else {

            $message =
                "Failed to delete review.";
        }
    }
}


/* =========================================
   MESSAGES
========================================= */

if (
    isset($_GET["message"]) &&
    $_GET["message"] === "updated"
) {

    $message =
        "Review status updated successfully.";
}


if (
    isset($_GET["message"]) &&
    $_GET["message"] === "deleted"
) {

    $message =
        "Review deleted successfully.";
}


if (
    isset($_GET["message"]) &&
    $_GET["message"] === "featured"
) {

    $message =
        "Homepage review updated successfully.";
}


/* =========================================
   GET REVIEWS
========================================= */

$reviews = $reviewModel->getAll();

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


    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }


        body {

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

            color: white;

            min-height: 100vh;
        }


        /* =====================================
           HEADER
        ===================================== */

        .admin-header {

            width: 100%;

            height: 95px;

            display: flex;
            align-items: center;
            justify-content: space-between;

            padding: 0 50px;

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

            width: 165px;

            height: 165px;

            object-fit: contain;
        }


        .admin-user {

            display: flex;

            align-items: center;

            gap: 20px;

        }


        .admin-user span {

            font-size: 15px;

        }


        .logout-btn {

            padding: 10px 20px;

            color: #b8862c;

            border: 2px solid #b8862c;

            border-radius: 8px;

            text-decoration: none;

            font-weight: bold;

        }


        .logout-btn:hover {

            background: #b8862c;

            color: #0e1423;
        }


        /* =====================================
           LAYOUT
        ===================================== */

        .admin-layout {

            display: flex;

            min-height:
                calc(100vh - 80px);
        }


        /* =====================================
           CONTENT
        ===================================== */

        .main-content {

            flex: 1;

            padding: 55px;

            min-width: 0;
        }


        .page-header {

            margin-bottom: 35px;
        }


        .page-header h1 {

            font-size: 38px;

            margin-bottom: 8px;
        }


        .page-header p {

            color: #aeb5c3;

            font-size: 17px;
        }


        /* =====================================
           MESSAGE
        ===================================== */

        .message {

            background:
                    rgba(23, 35, 21, 0.96);

            border:
                1px solid #2cb831;

            color: #2cb831;

            padding: 15px 18px;

            border-radius: 9px;

            margin-bottom: 25px;

            font-weight: bold;
        }


        /* =====================================
           HOMEPAGE NOTICE
        ===================================== */

        .featured-notice {

            background:
                rgba(184, 134, 44, 0.10);

            border:
                1px solid
                rgba(184, 134, 44, 0.6);

            color: #dfe3ea;

            padding: 15px 18px;

            border-radius: 9px;

            margin-bottom: 25px;

            font-size: 14px;

            line-height: 1.5;
        }


        .featured-notice strong {

            color: #d19a2a;
        }


        /* =====================================
           TABLE
        ===================================== */

        .table-container {

            background:
                rgba(14, 20, 35, 0.96);

            border:
                1px solid #b8862c;

            border-radius: 15px;

            overflow-x: auto;

            box-shadow:
                0 10px 30px
                rgba(0, 0, 0, 0.25);
        }


        table {

            width: 100%;

            min-width: 1150px;

            border-collapse: collapse;
        }


        thead {

            background: #b8862c;

            color: #0e1423;
        }


        th {

            padding: 18px 16px;

            text-align: left;

            font-size: 14px;
        }


        td {

            padding: 18px 16px;

            border-bottom:
                1px solid
                rgba(255, 255, 255, 0.08);

            vertical-align: top;
        }


        tbody tr:hover {

            background:
                rgba(255, 255, 255, 0.03);
        }


        /* =====================================
           CUSTOMER
        ===================================== */

        .customer-name {

            font-weight: bold;

            color: white;

            margin-bottom: 5px;
        }


        .customer-email {

            color: #9fa7b8;

            font-size: 13px;
        }


        /* =====================================
           RATING
        ===================================== */

        .stars {

            color: #d19a2a;

            font-size: 18px;

            letter-spacing: 2px;

            white-space: nowrap;
        }


        .rating-number {

            color: #9fa7b8;

            font-size: 13px;

            margin-left: 6px;
        }


        /* =====================================
           COMMENT
        ===================================== */

        .review-comment {

            max-width: 400px;

            color: #dfe3ea;

            line-height: 1.5;

            font-size: 14px;
        }


        /* =====================================
           STATUS
        ===================================== */

        .status {

            display: inline-block;

            padding: 7px 12px;

            border-radius: 20px;

            font-size: 12px;

            font-weight: bold;
        }


        .status-pending {

            background:
                rgba(255, 193, 7, 0.15);

            color: #ffc107;
        }


        .status-approved {

            background:
                rgba(76, 175, 80, 0.15);

            color: #4caf50;
        }


        .status-hidden {

            background:
                rgba(158, 158, 158, 0.15);

            color: #bdbdbd;
        }


        /* =====================================
           HOMEPAGE FEATURED
        ===================================== */

        .featured-badge {

            display: inline-block;

            padding: 8px 12px;

            border-radius: 20px;

            background: #b8862c;

            color: #0e1423;

            font-size: 12px;

            font-weight: bold;

            white-space: nowrap;
        }


        .feature-btn {

            background: transparent;

            color: #b8862c;

            border:
                1px solid #b8862c;

            border-radius: 7px;

            padding: 9px 12px;

            font-family: inherit;

            font-weight: bold;

            cursor: pointer;

            transition: 0.3s;
        }


        .feature-btn:hover {

            background: #b8862c;

            color: #0e1423;
        }


        .feature-disabled {

            color: #777;

            font-size: 13px;
        }


        /* =====================================
           ACTION
        ===================================== */

        .status-form {

            display: flex;

            align-items: center;

            gap: 7px;

            margin-bottom: 8px;
        }


        .status-select {

            background: #151d30;

            color: white;

            border:
                1px solid #4d5567;

            border-radius: 7px;

            padding: 9px;

            font-family: inherit;

            font-size: 13px;
        }


        .update-btn {

            background: #b8862c;

            color: #0e1423;

            border: none;

            border-radius: 7px;

            padding: 9px 12px;

            font-family: inherit;

            font-weight: bold;

            cursor: pointer;

            transition: 0.3s;
        }


        .update-btn:hover {

            background: #d19a2a;
        }


        .delete-form {

            margin-top: 5px;
        }


        .delete-btn {

            background: transparent;

            color: #f44336;

            border:
                1px solid #f44336;

            border-radius: 7px;

            padding: 8px 12px;

            font-family: inherit;

            font-weight: bold;

            cursor: pointer;

            transition: 0.3s;
        }


        .delete-btn:hover {

            background: #f44336;

            color: white;
        }


        /* =====================================
           DATE
        ===================================== */

        .review-date {

            color: #9fa7b8;

            font-size: 13px;

            white-space: nowrap;
        }


        /* =====================================
           EMPTY
        ===================================== */

        .empty-reviews {

            text-align: center;

            padding: 70px 20px;

            color: #9fa7b8;

            font-size: 17px;
        }


        /* =====================================
           RESPONSIVE
        ===================================== */

        @media (max-width: 900px) {

            .admin-header {

                padding: 0 20px;
            }


            .sidebar {

                width: 220px;
            }


            .main-content {

                padding: 35px 25px;
            }

        }


        @media (max-width: 650px) {

            .admin-header {

                min-height: 80px;

                height: auto;

                padding: 12px 15px;
            }


            .admin-logo img {

                width: 130px;

                height: 130px;
            }


            .admin-user span {

                display: none;
            }


            .admin-layout {

                flex-direction: column;
            }


            .sidebar {

                width: 100%;

                padding: 15px;

                border-right: none;

                border-bottom:
                    1px solid
                    rgba(184, 134, 44, 0.15);
            }


            .sidebar-title {

                margin:
                    5px 10px 15px;
            }


            .sidebar a {

                display: inline-block;

                margin: 3px;

                padding: 10px 14px;

                font-size: 14px;
            }


            .main-content {

                padding: 30px 15px;
            }


            .page-header h1 {

                font-size: 30px;
            }

        }

    </style>

</head>


<body>

<?php include "../admin/navbar.php"; ?>

<!-- =====================================
     LAYOUT
===================================== -->

<div class="admin-layout">

<?php include "../admin/sidebar.php"; ?>

<!-- =================================
         MAIN CONTENT
    ================================== -->

    <main class="main-content">


        <div class="page-header">

            <h1>
                Reviews
            </h1>


            <p>
                View and manage customer reviews
                and feedback.
            </p>

        </div>



        <!-- =================================
             MESSAGE
        ================================= -->

        <?php if (!empty($message)): ?>

            <div class="message">

                <?= htmlspecialchars($message) ?>

            </div>

        <?php endif; ?>



        <!-- =================================
             HOMEPAGE NOTICE
        ================================= -->

        <div class="featured-notice">

            <strong>
                ⭐ Homepage Review:
            </strong>

            Choose one approved review to display
            in the homepage's Reviews.

        </div>



        <!-- =================================
             REVIEWS TABLE
        ================================= -->

        <div class="table-container">


            <?php if (empty($reviews)): ?>


                <div class="empty-reviews">

                    No customer reviews yet.

                </div>


            <?php else: ?>


                <table>


                    <thead>

                        <tr>

                            <th>
                                Customer
                            </th>


                            <th>
                                Rating
                            </th>


                            <th>
                                Review
                            </th>


                            <th>
                                Status
                            </th>


                            <th>
                                Homepage
                            </th>


                            <th>
                                Date
                            </th>


                            <th>
                                Action
                            </th>

                        </tr>

                    </thead>



                    <tbody>


                        <?php foreach ($reviews as $review): ?>


                            <tr>


                                <!-- CUSTOMER -->

                                <td>

                                    <div
                                        class="customer-name"
                                    >

                                        <?= htmlspecialchars(
                                            $review["customer_name"]
                                        ) ?>

                                    </div>


                                    <div
                                        class="customer-email"
                                    >

                                        <?= htmlspecialchars(
                                            $review["customer_email"]
                                        ) ?>

                                    </div>

                                </td>



                                <!-- RATING -->

                                <td>

                                    <span class="stars">

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


                                    <span
                                        class="rating-number"
                                    >

                                        <?= (int) $review["rating"] ?>/5

                                    </span>

                                </td>



                                <!-- COMMENT -->

                                <td>

                                    <div
                                        class="review-comment"
                                    >

                                        <?= nl2br(
                                            htmlspecialchars(
                                                $review["comment"]
                                            )
                                        ) ?>

                                    </div>

                                </td>



                                <!-- STATUS -->

                                <td>

                                    <?php

                                    $statusClass =
                                        strtolower(
                                            $review["status"]
                                        );

                                    ?>


                                    <span
                                        class="status status-<?= htmlspecialchars(
                                            $statusClass
                                        ) ?>"
                                    >

                                        <?= htmlspecialchars(
                                            $review["status"]
                                        ) ?>

                                    </span>

                                </td>



                                <!-- HOMEPAGE -->

                                <td>


                                    <?php if (
                                        $review["status"] === "Approved"
                                    ): ?>


                                        <?php if (
                                            (int) $review["is_featured"] === 1
                                        ): ?>


                                            <span
                                                class="featured-badge"
                                            >

                                                ⭐ Shown

                                            </span>


                                        <?php else: ?>


                                            <form
                                                method="POST"
                                                action="reviews.php"
                                            >


                                                <input
                                                    type="hidden"
                                                    name="review_id"
                                                    value="<?= (int) $review["id"] ?>"
                                                >


                                                <button
                                                    type="submit"
                                                    name="set_featured"
                                                    class="feature-btn"
                                                >

                                                    Show on Homepage

                                                </button>


                                            </form>


                                        <?php endif; ?>


                                    <?php else: ?>


                                        <span
                                            class="feature-disabled"
                                        >

                                            Approve first

                                        </span>


                                    <?php endif; ?>


                                </td>



                                <!-- DATE -->

                                <td>

                                    <div
                                        class="review-date"
                                    >

                                        <?= date(
                                            "M d, Y",
                                            strtotime(
                                                $review["created_at"]
                                            )
                                        ) ?>


                                        <br>


                                        <?= date(
                                            "h:i A",
                                            strtotime(
                                                $review["created_at"]
                                            )
                                        ) ?>

                                    </div>

                                </td>



                                <!-- ACTION -->

                                <td>


                                    <!-- UPDATE STATUS -->

                                    <form
                                        method="POST"
                                        action="reviews.php"
                                        class="status-form"
                                    >


                                        <input
                                            type="hidden"
                                            name="review_id"
                                            value="<?= (int) $review["id"] ?>"
                                        >


                                        <select
                                            name="status"
                                            class="status-select"
                                        >


                                            <?php foreach (
                                                $allowedStatuses
                                                as $status
                                            ): ?>


                                                <option
                                                    value="<?= htmlspecialchars($status) ?>"
                                                    <?= $review["status"] === $status
                                                        ? "selected"
                                                        : "" ?>
                                                >

                                                    <?= htmlspecialchars(
                                                        $status
                                                    ) ?>

                                                </option>


                                            <?php endforeach; ?>


                                        </select>


                                        <button
                                            type="submit"
                                            name="update_status"
                                            class="update-btn"
                                        >

                                            Update

                                        </button>


                                    </form>



                                    <!-- DELETE -->

                                    <form
                                        method="POST"
                                        action="reviews.php"
                                        class="delete-form"
                                        onsubmit="return confirm('Delete this review?');"
                                    >


                                        <input
                                            type="hidden"
                                            name="review_id"
                                            value="<?= (int) $review["id"] ?>"
                                        >


                                        <button
                                            type="submit"
                                            name="delete_review"
                                            class="delete-btn"
                                        >

                                            Delete

                                        </button>


                                    </form>


                                </td>


                            </tr>


                        <?php endforeach; ?>


                    </tbody>


                </table>


            <?php endif; ?>


        </div>


    </main>


</div>


</body>

</html>