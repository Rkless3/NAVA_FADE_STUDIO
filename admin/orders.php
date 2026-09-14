<?php
session_start();

/* =========================================
   ADMIN LOGIN CHECK
========================================= */
if (
    !isset($_SESSION["admin_logged_in"]) ||
    $_SESSION["admin_logged_in"] !== true
) {
    header("Location: ../login.php");
    exit;
}

require_once "../config/Database.php";
require_once "../classes/Order.php";

$database = new Database();
$db = $database->connect();

$orderModel = new Order($db);

$allowedStatuses = [
    "Pending",
    "Confirmed",
    "Processing",
    "Completed",
    "Cancelled"
];

$message = "";
$messageType = "success";

/* =========================================
   UPDATE ORDER / PAYMENT STATUS
========================================= */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $order_id = (int)($_POST["order_id"] ?? 0);

    /* ---------- MARK PAYMENT AS PAID ---------- */
    if (isset($_POST["mark_paid"])) {

        if ($order_id <= 0) {
            $message = "Invalid order information.";
            $messageType = "error";
        } else {
            $check = $db->prepare("
                SELECT
                    o.status,
                    p.id AS payment_id,
                    p.status AS payment_status
                FROM orders o
                LEFT JOIN payments p
                    ON p.order_id = o.id
                WHERE o.id = :order_id
                ORDER BY p.id DESC
                LIMIT 1
            ");
            $check->execute([":order_id" => $order_id]);
            $payment = $check->fetch(PDO::FETCH_ASSOC);

            if (!$payment) {
                $message = "Payment record not found for this order.";
                $messageType = "error";
            } elseif (
                $payment["status"] === "Completed" ||
                $payment["status"] === "Cancelled"
            ) {
                $message = "Payment cannot be changed because this order is locked.";
                $messageType = "error";
            } elseif ($payment["payment_status"] === "Cancelled") {
                $message = "This payment has already been cancelled.";
                $messageType = "error";
            } elseif ($payment["payment_status"] === "Paid") {
                $message = "This payment is already marked as paid.";
                $messageType = "error";
            } else {
                $stmt = $db->prepare("
                    UPDATE payments
                    SET status = 'Paid',
                        paid_at = NOW()
                    WHERE id = :payment_id
                      AND status = 'Pending'
                ");

                $stmt->execute([
                    ":payment_id" => $payment["payment_id"]
                ]);

                header("Location: orders.php?message=payment_paid");
                exit;
            }
        }
    }

    /* ---------- UPDATE ORDER STATUS ---------- */
    if (isset($_POST["update_status"])) {

        $status = $_POST["status"] ?? "";

        if ($order_id <= 0 || !in_array($status, $allowedStatuses, true)) {

            $message = "Invalid order information.";
            $messageType = "error";

        } else {

            $stmt = $db->prepare("
                SELECT
                    o.status,
                    p.status AS payment_status
                FROM orders o
                LEFT JOIN payments p
                    ON p.order_id = o.id
                WHERE o.id = :order_id
                ORDER BY p.id DESC
                LIMIT 1
            ");

            $stmt->execute([":order_id" => $order_id]);
            $current = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$current) {

                $message = "Order not found.";
                $messageType = "error";

            } elseif (
                $current["status"] === "Completed" ||
                $current["status"] === "Cancelled"
            ) {

                $message = "This order is locked and its status can no longer be changed.";
                $messageType = "error";

            } elseif (
                $status === "Completed" &&
                $current["payment_status"] !== "Paid"
            ) {

                $message = "The order cannot be marked Completed until the payment is Paid.";
                $messageType = "error";

            } elseif ($status === "Cancelled") {

                $db->beginTransaction();

                try {
                    $update = $db->prepare("
                        UPDATE orders
                        SET status = 'Cancelled'
                        WHERE id = :order_id
                          AND status NOT IN ('Completed', 'Cancelled')
                    ");
                    $update->execute([":order_id" => $order_id]);

                    $cancelPayment = $db->prepare("
                        UPDATE payments
                        SET status = 'Cancelled'
                        WHERE order_id = :order_id
                          AND status = 'Pending'
                    ");
                    $cancelPayment->execute([":order_id" => $order_id]);

                    $db->commit();

                    header("Location: orders.php?message=cancelled");
                    exit;

                } catch (Throwable $e) {
                    if ($db->inTransaction()) {
                        $db->rollBack();
                    }

                    $message = "Failed to cancel the order.";
                    $messageType = "error";
                }

            } else {

                if ($orderModel->updateStatus($order_id, $status)) {
                    header("Location: orders.php?message=updated");
                    exit;
                }

                $message = "Failed to update order status.";
                $messageType = "error";
            }
        }
    }
}

/* =========================================
   SUCCESS / ERROR MESSAGES
========================================= */
if (isset($_GET["message"])) {
    switch ($_GET["message"]) {
        case "updated":
            $message = "Order status updated successfully.";
            $messageType = "success";
            break;

        case "payment_paid":
            $message = "Payment marked as Paid successfully.";
            $messageType = "success";
            break;

        case "cancelled":
            $message = "Order cancelled successfully.";
            $messageType = "success";
            break;
    }
}

/* =========================================
   GET ORDERS
========================================= */
$orders = $orderModel->getAll();

$orderItems = [];
$payments = [];

foreach ($orders as $order) {

    $orderId = (int)$order["id"];

    $orderItems[$orderId] =
        $orderModel->getItemsByOrderId($orderId);

    $paymentQuery = $db->prepare("
        SELECT
            id,
            payment_method,
            reference_number,
            amount,
            status,
            paid_at,
            created_at
        FROM payments
        WHERE order_id = :order_id
        ORDER BY id DESC
        LIMIT 1
    ");

    $paymentQuery->execute([
        ":order_id" => $orderId
    ]);

    $payments[$orderId] =
        $paymentQuery->fetch(PDO::FETCH_ASSOC);
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

    <title>Orders | NAVA Fade Studio</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: Bahnschrift, "Myriad Pro", "Bahnschrift", sans-serif;
            background:
                linear-gradient(
                    rgba(8, 12, 22, 0.92),
                    rgba(8, 12, 22, 0.96)
                ),
                url("../assets/images/pattern3.png");
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: white;
            min-height: 100vh;
            margin: 0;
        }

        .main-content {
            flex: 1;
            min-width: 0;
            padding: 50px;
        }

        .page-header {
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 38px;
            margin: 0 0 8px;
        }

        .page-header p {
            color: #aeb5c3;
            font-size: 17px;
            margin: 0;
        }

        .message {
            padding: 15px 18px;
            border-radius: 9px;
            margin-bottom: 25px;
            font-weight: bold;
        }

        .message.success {
            background: rgba(46, 204, 113, 0.12);
            color: #2ecc71;
            border: 1px solid #2ecc71;
        }

        .message.error {
            background: rgba(244, 67, 54, 0.12);
            color: #ff6b61;
            border: 1px solid #f44336;
        }

        .table-container {
            background: rgba(14, 20, 35, 0.97);
            border: 1px solid #b8862c;
            border-radius: 15px;
            overflow-x: auto;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
        }

        table {
            width: 100%;
            min-width: 1600px;
            border-collapse: collapse;
        }

        thead {
            background: #b8862c;
            color: #0e1423;
        }

        th {
            padding: 18px 16px;
            text-align: left;
            font-size: 13px;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }

        td {
            padding: 18px 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            color: #e5e8ee;
            vertical-align: top;
        }

        tbody tr:hover {
            background: rgba(255, 255, 255, 0.03);
        }

        .order-number {
            color: #d19a2a;
            font-weight: bold;
            font-size: 16px;
        }

        .customer-name {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .customer-details {
            color: #9fa7b8;
            font-size: 13px;
            line-height: 1.5;
        }

        .product-list {
            list-style: none;
            padding: 0;
            margin: 0;
            min-width: 170px;
        }

        .product-list li {
            margin-bottom: 7px;
            color: #dfe3ea;
            font-size: 14px;
        }

        .product-list li:last-child {
            margin-bottom: 0;
        }

        .product-qty {
            color: #b8862c;
            font-weight: bold;
        }

        .order-total {
            color: #d19a2a;
            font-size: 17px;
            font-weight: bold;
            white-space: nowrap;
        }

        .status,
        .payment-status {
            display: inline-block;
            padding: 7px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            white-space: nowrap;
        }

        .status-pending,
        .payment-pending {
            background: rgba(255, 193, 7, 0.15);
            color: #ffc107;
        }

        .status-confirmed {
            background: rgba(0, 188, 212, 0.15);
            color: #00bcd4;
        }

        .status-processing {
            background: rgba(33, 150, 243, 0.15);
            color: #2196f3;
        }

        .status-completed,
        .payment-paid {
            background: rgba(76, 175, 80, 0.15);
            color: #4caf50;
        }

        .status-cancelled,
        .payment-cancelled,
        .payment-failed {
            background: rgba(244, 67, 54, 0.15);
            color: #f44336;
        }

        .fulfillment-box {
            min-width: 230px;
            line-height: 1.6;
        }

        .fulfillment-method {
            display: inline-block;
            padding: 6px 10px;
            margin-bottom: 8px;
            border-radius: 20px;
            background: rgba(184, 134, 44, 0.15);
            color: #d5a63a;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .fulfillment-row {
            margin-bottom: 7px;
        }

        .fulfillment-label {
            color: #8f98aa;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.7px;
        }

        .fulfillment-value {
            color: #f1f1f1;
            font-size: 13px;
            font-weight: 600;
            word-break: break-word;
        }

        .fulfillment-notes {
            color: #b9c0cd;
            font-size: 12px;
            line-height: 1.45;
        }

        .payment-box {
            min-width: 185px;
            line-height: 1.6;
        }

        .payment-row {
            margin-bottom: 6px;
        }

        .payment-label {
            color: #8f98aa;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.7px;
        }

        .payment-value {
            color: #f1f1f1;
            font-size: 13px;
            font-weight: 600;
        }

        .reference {
            color: #d5a63a;
            word-break: break-all;
        }

        .paid-date {
            color: #8f98aa;
            font-size: 11px;
        }

        .status-form {
            display: flex;
            flex-direction: column;
            gap: 8px;
            min-width: 145px;
        }

        .status-select {
            width: 100%;
            background: #151d30;
            color: white;
            border: 1px solid #4d5567;
            border-radius: 7px;
            padding: 9px 10px;
            font-family: inherit;
            font-size: 13px;
        }

        .update-btn,
        .paid-btn {
            border: none;
            border-radius: 7px;
            padding: 9px 12px;
            font-family: inherit;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }

        .update-btn {
            background: #b8862c;
            color: #0e1423;
        }

        .update-btn:hover {
            background: #d19a2a;
        }

        .paid-btn {
            background: #2ecc71;
            color: #0e1423;
        }

        .paid-btn:hover {
            background: #42e487;
        }

        .paid-btn:disabled,
        .update-btn:disabled,
        .status-select:disabled {
            opacity: 0.45;
            cursor: not-allowed;
        }

        .locked-box {
            display: flex;
            flex-direction: column;
            gap: 6px;
            min-width: 145px;
        }

        .locked-label {
            color: #8f98aa;
            font-size: 12px;
        }

        .empty-orders {
            padding: 60px 20px;
            text-align: center;
            color: #9fa7b8;
            font-size: 17px;
        }

        @media (max-width: 900px) {
            .main-content {
                padding: 35px 25px;
            }
        }

        @media (max-width: 700px) {
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

<div class="admin-layout">

    <?php include "../admin/sidebar.php"; ?>

    <main class="main-content">

        <div class="page-header">
            <h1>Orders</h1>
            <p>
                View and manage customer product orders and payments.
            </p>
        </div>

        <?php if ($message): ?>
            <div class="message <?= $messageType === "error" ? "error" : "success" ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="table-container">

            <?php if (empty($orders)): ?>

                <div class="empty-orders">
                    No orders have been placed yet.
                </div>

            <?php else: ?>

                <table>

                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Products</th>
                            <th>Total</th>
                            <th>Fulfillment</th>
                            <th>Payment</th>
                            <th>Payment Status</th>
                            <th>Order Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php foreach ($orders as $order): ?>

                        <?php
                        $orderId = (int)$order["id"];
                        $payment = $payments[$orderId] ?? null;

                        $orderStatusClass =
                            strtolower($order["status"]);

                        $paymentStatusClass =
                            $payment
                                ? strtolower($payment["status"])
                                : "pending";

                        $isLocked =
                            in_array(
                                $order["status"],
                                ["Completed", "Cancelled"],
                                true
                            );
                        ?>

                        <tr>

                            <!-- ORDER NUMBER -->
                            <td>
                                <div class="order-number">
                                    #<?= $orderId ?>
                                </div>
                            </td>

                            <!-- CUSTOMER -->
                            <td>
                                <div class="customer-name">
                                    <?= htmlspecialchars($order["full_name"]) ?>
                                </div>

                                <div class="customer-details">
                                    <?= htmlspecialchars($order["email"]) ?>
                                    <br>
                                    <?= htmlspecialchars($order["contact_number"]) ?>
                                </div>
                            </td>

                            <!-- PRODUCTS -->
                            <td>
                                <ul class="product-list">

                                    <?php foreach (
                                        $orderItems[$orderId] ?? []
                                        as $item
                                    ): ?>

                                        <li>
                                            <?= htmlspecialchars($item["product_name"]) ?>

                                            <span class="product-qty">
                                                × <?= (int)$item["quantity"] ?>
                                            </span>
                                        </li>

                                    <?php endforeach; ?>

                                </ul>
                            </td>

                            <!-- TOTAL -->
                            <td>
                                <div class="order-total">
                                    ₱<?= number_format(
                                        (float)$order["total_amount"],
                                        2
                                    ) ?>
                                </div>
                            </td>

                            <!-- FULFILLMENT -->
                            <td>

                                <div class="fulfillment-box">

                                    <span class="fulfillment-method">
                                        <?= ($order["delivery_method"] ?? "pickup") === "delivery"
                                            ? "Delivery"
                                            : "Pickup" ?>
                                    </span>


                                    <?php if (($order["delivery_method"] ?? "pickup") === "delivery"): ?>

                                        <?php if (!empty($order["delivery_address"])): ?>

                                            <div class="fulfillment-row">

                                                <div class="fulfillment-label">
                                                    Address
                                                </div>

                                                <div class="fulfillment-value">
                                                    <?= nl2br(htmlspecialchars($order["delivery_address"])) ?>
                                                </div>

                                            </div>

                                        <?php endif; ?>


                                        <?php if (!empty($order["delivery_contact"])): ?>

                                            <div class="fulfillment-row">

                                                <div class="fulfillment-label">
                                                    Contact
                                                </div>

                                                <div class="fulfillment-value">
                                                    <?= htmlspecialchars($order["delivery_contact"]) ?>
                                                </div>

                                            </div>

                                        <?php endif; ?>


                                        <?php if (!empty($order["delivery_landmark"])): ?>

                                            <div class="fulfillment-row">

                                                <div class="fulfillment-label">
                                                    Landmark
                                                </div>

                                                <div class="fulfillment-value">
                                                    <?= htmlspecialchars($order["delivery_landmark"]) ?>
                                                </div>

                                            </div>

                                        <?php endif; ?>


                                        <?php if (!empty($order["delivery_notes"])): ?>

                                            <div class="fulfillment-row">

                                                <div class="fulfillment-label">
                                                    Notes
                                                </div>

                                                <div class="fulfillment-notes">
                                                    <?= nl2br(htmlspecialchars($order["delivery_notes"])) ?>
                                                </div>

                                            </div>

                                        <?php endif; ?>

                                    <?php else: ?>

                                        <div class="fulfillment-value">
                                            Customer will pick up the order.
                                        </div>

                                    <?php endif; ?>

                                </div>

                            </td>


                            <!-- PAYMENT METHOD / REFERENCE -->
                            <td>
                                <?php if ($payment): ?>

                                    <div class="payment-box">

                                        <div class="payment-row">
                                            <div class="payment-label">
                                                Method
                                            </div>

                                            <div class="payment-value">
                                                <?= htmlspecialchars(
                                                    $payment["payment_method"]
                                                ) ?>
                                            </div>
                                        </div>

                                        <?php if (
                                            $payment["payment_method"] === "GCash"
                                        ): ?>

                                            <div class="payment-row">
                                                <div class="payment-label">
                                                    GCash Reference
                                                </div>

                                                <div class="payment-value reference">
                                                    <?= !empty($payment["reference_number"])
                                                        ? htmlspecialchars($payment["reference_number"])
                                                        : "Not provided" ?>
                                                </div>
                                            </div>

                                        <?php endif; ?>

                                        <div class="payment-row">
                                            <div class="payment-label">
                                                Amount
                                            </div>

                                            <div class="payment-value">
                                                ₱<?= number_format(
                                                    (float)$payment["amount"],
                                                    2
                                                ) ?>
                                            </div>
                                        </div>

                                    </div>

                                <?php else: ?>

                                    <span style="color:#9fa7b8;">
                                        No payment record
                                    </span>

                                <?php endif; ?>
                            </td>

                            <!-- PAYMENT STATUS -->
                            <td>

                                <?php if ($payment): ?>

                                    <span class="payment-status payment-<?= htmlspecialchars($paymentStatusClass) ?>">
                                        <?= htmlspecialchars($payment["status"]) ?>
                                    </span>

                                    <?php if (
                                        $payment["status"] === "Paid" &&
                                        !empty($payment["paid_at"])
                                    ): ?>

                                        <div class="paid-date">
                                            <?= date(
                                                "M d, Y h:i A",
                                                strtotime($payment["paid_at"])
                                            ) ?>
                                        </div>

                                    <?php endif; ?>

                                <?php else: ?>

                                    <span class="payment-status payment-pending">
                                        Pending
                                    </span>

                                <?php endif; ?>

                            </td>

                            <!-- ORDER STATUS -->
                            <td>

                                <span class="status status-<?= htmlspecialchars($orderStatusClass) ?>">
                                    <?= htmlspecialchars($order["status"]) ?>
                                </span>

                            </td>

                            <!-- DATE -->
                            <td>
                                <?= date(
                                    "M d, Y",
                                    strtotime($order["created_at"])
                                ) ?>

                                <br>

                                <span style="color:#9fa7b8;font-size:13px;">
                                    <?= date(
                                        "h:i A",
                                        strtotime($order["created_at"])
                                    ) ?>
                                </span>
                            </td>

                            <!-- ACTION -->
                            <td>

                                <?php if ($isLocked): ?>

                                    <div class="locked-box">
                                        <span class="locked-label">
                                            🔒 Status Locked
                                        </span>

                                        <?php if (
                                            $payment &&
                                            $payment["status"] === "Paid"
                                        ): ?>

                                            <span class="payment-status payment-paid">
                                                Payment Paid
                                            </span>

                                        <?php elseif (
                                            $payment &&
                                            $payment["status"] === "Cancelled"
                                        ): ?>

                                            <span class="payment-status payment-cancelled">
                                                Payment Cancelled
                                            </span>

                                        <?php endif; ?>
                                    </div>

                                <?php else: ?>

                                    <form
                                        method="POST"
                                        action="orders.php"
                                        class="status-form"
                                    >

                                        <input
                                            type="hidden"
                                            name="order_id"
                                            value="<?= $orderId ?>"
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
                                                    <?= $order["status"] === $status ? "selected" : "" ?>
                                                >
                                                    <?= htmlspecialchars($status) ?>
                                                </option>

                                            <?php endforeach; ?>

                                        </select>

                                        <button
                                            type="submit"
                                            name="update_status"
                                            class="update-btn"
                                        >
                                            Update Status
                                        </button>

                                    </form>

                                    <?php if (
                                        $payment &&
                                        $payment["status"] === "Pending"
                                    ): ?>

                                        <form
                                            method="POST"
                                            action="orders.php"
                                            style="margin-top:8px;"
                                        >

                                            <input
                                                type="hidden"
                                                name="order_id"
                                                value="<?= $orderId ?>"
                                            >

                                            <button
                                                type="submit"
                                                name="mark_paid"
                                                class="paid-btn"
                                            >
                                                ✓ Mark as Paid
                                            </button>

                                        </form>

                                    <?php elseif (
                                        $payment &&
                                        $payment["status"] === "Paid"
                                    ): ?>

                                        <span
                                            class="payment-status payment-paid"
                                            style="margin-top:8px;"
                                        >
                                            ✓ Payment Verified
                                        </span>

                                    <?php endif; ?>

                                <?php endif; ?>

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
