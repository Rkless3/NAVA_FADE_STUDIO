<?php

session_start();


/* =========================================
   CHECK IF CUSTOMER IS LOGGED IN
========================================= */

if (!isset($_SESSION["customer_id"])) {

    header("Location: login.php");
    exit();

}


require_once "config/Database.php";

$database = new Database();
$db = $database->connect();


$customer_id = $_SESSION["customer_id"];


/* =========================================
   GET CUSTOMER APPOINTMENTS
========================================= */

try {

    $appointment_query = $db->prepare("
        SELECT
            a.id,
            a.service,
            a.appointment_date,
            a.appointment_time,
            a.notes,
            a.status,
            a.created_at,
            p.id AS payment_id,
            p.payment_method,
            p.reference_number,
            p.amount AS payment_amount,
            p.status AS payment_status,
            p.paid_at
        FROM appointments a
        LEFT JOIN payments p
            ON p.appointment_id = a.id
        WHERE a.customer_id = :customer_id
        ORDER BY
            a.appointment_date DESC,
            a.appointment_time DESC,
            a.id DESC
    ");


    $appointment_query->execute([

        ":customer_id" => $customer_id

    ]);


    $appointments =
        $appointment_query->fetchAll(PDO::FETCH_ASSOC);


} catch (PDOException $e) {

    $appointments = [];

    $error =
        "Unable to load your appointments.";

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
        My Appointments | NAVA Fade Studio
    </title>


    <link
        rel="stylesheet"
        href="assets/css/style.css"
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

            font-family:
                Bahnschrift,
                "Myriad Pro",
                Arial;

            color: #ffffff;

            background-color: #0e1423;

            background-image:

                linear-gradient(
                    rgba(7, 14, 29, 0.72),
                    rgba(7, 14, 29, 0.72)
                ),

                url("assets/images/pattern3.png");

            background-size: cover;

            background-position: center;

            background-repeat: repeat;

            background-attachment: fixed;

        }


        /* =========================================
           PAGE
        ========================================= */

        .booking-page {

            width: 100%;

            min-height: 100vh;

            padding: 50px 20px 70px;

            display: flex;

            justify-content: center;

        }

    .appointment-payment {
        
        margin-top:18px; 
        padding:18px; 
        border-top:1px solid 
            rgba(255,255,255,.08); 
         
        background:rgba(14,20,35,.35);
        border-radius:10px;
    
    }
    .appointment-payment h4 {
        
        color:#d19a2a;
        margin-bottom:10px;
    }

    .appointment-payment p {
        
        margin:7px 0; 
    }

    .payment-status {
        
        display:inline-block;
        padding:5px 10px;
        border-radius:14px;
        font-size:12px;
        font-weight:bold;
    }
    
    .payment-pending {
        
        background:
            rgba(255,193,7,.15);
        color:#ffc107; 
    }

    .payment-paid { 
        
        background:
            rgba(76,175,80,.15); 
        color:#4caf50; 
    }
    
    .payment-cancelled, .payment-failed {
        
        background:
            rgba(244,67,54,.15);
        color:#f44336; 
    }

    .pending-note {
        
        color:#ffc107; 
        font-size:13px; 
    }

    .paid-note {
         
        color:#4caf50; 
        font-size:13px; 
    }

    .appointment-card {
        
        position: relative; 
    }

    .appointment-top h3 {
        
        max-width: 75%; 
        line-height: 1.45; 
    }
    
    .payment-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px 18px;
    }

    .payment-item {
        margin: 0; 
    }

    .payment-label {
        display: block;
        color: #aaa;
        font-size: 12px;
        margin-bottom: 3px;
        text-transform: uppercase;
        letter-spacing: .4px;
    }

    .payment-value {
        
        color: #fff; 
        font-weight: 600; 
        word-break: break-word; 
    }

    .reference-value {
        
        font-family: monospace; 
        letter-spacing: .5px; 
    }
    
    @media (max-width: 600px) {
        .payment-grid { grid-template-columns: 1fr; }
        .appointment-top h3 { max-width: 100%; }
    }

</style>
</head>


<body>


<section class="booking-page">


    <div class="booking-container appointments-container">


        <!-- HEADER -->

        <div class="booking-header">

            <h1>
                NAVA FADE STUDIO
            </h1>


            <h2>
                MY APPOINTMENTS
            </h2>


            <p>
                View your appointments, services, and payment status.
            </p>

        </div>



        <!-- ERROR MESSAGE -->

        <?php if (!empty($error)): ?>

            <div class="error-message">

                <?= htmlspecialchars($error) ?>

            </div>

        <?php endif; ?>



        <!-- APPOINTMENTS -->

        <?php if (!empty($appointments)): ?>


            <div class="appointments-list">


                <?php foreach ($appointments as $appointment): ?>


                    <div class="appointment-card">


                        <div class="appointment-top">


                            <h3>

                                <?= htmlspecialchars(
                                    $appointment["service"]
                                ) ?>

                            </h3>


                            <span
                                class="
                                    appointment-status
                                    status-<?= strtolower(
                                        $appointment["status"]
                                    ) ?>
                                "
                            >

                                <?= htmlspecialchars(
                                    $appointment["status"]
                                ) ?>

                            </span>


                        </div>



                        <div class="appointment-details">


                            <p>

                                📅

                                <strong>Date:</strong>

                                <?= date(
                                    "F j, Y",
                                    strtotime(
                                        $appointment[
                                            "appointment_date"
                                        ]
                                    )
                                ) ?>

                            </p>


                            <p>

                                🕒

                                <strong>Time:</strong>

                                <?= date(
                                    "g:i A",
                                    strtotime(
                                        $appointment[
                                            "appointment_time"
                                        ]
                                    )
                                ) ?>

                            </p>


                            <?php if (
                                !empty(
                                    $appointment["notes"]
                                )
                            ): ?>


                                <p class="appointment-notes">

                                    <strong>
                                        Notes:
                                    </strong>

                                    <?= htmlspecialchars(
                                        $appointment["notes"]
                                    ) ?>

                                </p>


                            <?php endif; ?>


                        </div>

                        <!-- PAYMENT DETAILS -->
                        <div class="appointment-payment">
                            <h4>Payment Details</h4>

                            <?php if (!empty($appointment["payment_id"])): ?>
                                <?php
                                $paymentStatus = $appointment["payment_status"] ?? "Pending";
                                $paymentClass = "payment-pending";

                                if ($paymentStatus === "Paid") {
                                    $paymentClass = "payment-paid";
                                } elseif (
                                    $paymentStatus === "Cancelled" ||
                                    $paymentStatus === "Failed"
                                ) {
                                    $paymentClass = "payment-cancelled";
                                }
                                ?>

                                <div class="payment-grid">
                                    <p class="payment-item">
                                        <span class="payment-label">Method</span>
                                        <span class="payment-value">
                                            <?= htmlspecialchars($appointment["payment_method"]) ?>
                                        </span>
                                    </p>

                                    <p class="payment-item">
                                        <span class="payment-label">Amount</span>
                                        <span class="payment-value">
                                            ₱<?= number_format((float)$appointment["payment_amount"], 2) ?>
                                        </span>
                                    </p>

                                    <?php if ($appointment["payment_method"] === "GCash"): ?>
                                        <p class="payment-item">
                                            <span class="payment-label">Reference Number</span>
                                            <span class="payment-value reference-value">
                                                <?= !empty($appointment["reference_number"])
                                                    ? htmlspecialchars($appointment["reference_number"])
                                                    : "Not provided" ?>
                                            </span>
                                        </p>
                                    <?php endif; ?>

                                    <p class="payment-item">
                                        <span class="payment-label">Payment Status</span>
                                        <span class="payment-value">
                                            <span class="payment-status <?= $paymentClass ?>">
                                                <?= htmlspecialchars($paymentStatus) ?>
                                            </span>
                                        </span>
                                    </p>
                                </div>

                                <?php if ($paymentStatus === "Pending"): ?>
                                    <p class="pending-note">
                                        Your payment is waiting for admin verification.
                                    </p>
                                <?php elseif ($paymentStatus === "Paid"): ?>
                                    <p class="paid-note">
                                        Payment verified successfully.
                                    </p>
                                <?php elseif ($paymentStatus === "Cancelled"): ?>
                                    <p class="pending-note">
                                        This payment was cancelled.
                                    </p>
                                <?php elseif ($paymentStatus === "Failed"): ?>
                                    <p class="pending-note">
                                        This payment could not be verified.
                                    </p>
                                <?php endif; ?>

                            <?php else: ?>
                                <p class="pending-note">
                                    No payment record was found for this appointment.
                                </p>
                            <?php endif; ?>
                        </div>

                    </div>


                <?php endforeach; ?>


            </div>


        <?php else: ?>


            <!-- NO APPOINTMENTS -->

            <div class="no-appointments">


                <div class="no-appointments-icon">

                    📅

                </div>


                <h3>
                    No Appointments Yet
                </h3>


                <p>

                    You haven't booked an appointment yet.

                </p>


                <a
                    href="book.php"
                    class="booking-btn appointment-book-btn"
                >

                    BOOK AN APPOINTMENT

                </a>


            </div>


        <?php endif; ?>



        <!-- BACK -->

        <div class="booking-back">


            <a href="index.php">

                ← Back to Home

            </a>


        </div>


    </div>


</section>


</body>

</html>