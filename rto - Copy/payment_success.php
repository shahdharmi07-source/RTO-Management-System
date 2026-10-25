<?php

session_start();
include "connect.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: Login.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];
$type = ($_GET['type'] ?? '') === 'vehicle' ? 'vehicle' : 'application';
$receipt = null;
$continue_url = "slot_booking.php";
$payment_label = "Application Fee";

if ($type === 'vehicle') {
    $vehicle_id = (int) ($_GET['vehicle_id'] ?? 0);
    $statement = mysqli_prepare(
        $con,
        "SELECT vehicle_no, registration_payment_method AS payment_method,
                registration_payment_reference AS transaction_reference,
            registration_bank_name AS bank_name,
                registration_bank_reference AS bank_reference,
                registration_paid_at AS paid_at
         FROM vehicles
         WHERE id = ? AND user_id = ? AND registration_payment_status = 'Paid'
           AND registration_payment_reference IS NOT NULL
         LIMIT 1"
    );

    if ($statement) {
        mysqli_stmt_bind_param($statement, "ii", $vehicle_id, $user_id);
        mysqli_stmt_execute($statement);
        $result = mysqli_stmt_get_result($statement);
        $receipt = mysqli_fetch_assoc($result);
        mysqli_stmt_close($statement);
    }

    if ($receipt) {
        $receipt['amount'] = 1500;
    }
    $continue_url = "VehicleRegistration.php?step=4";
    $payment_label = "Vehicle Registration Fee";
} else {
    $payment_id = (int) ($_GET['payment_id'] ?? 0);
    $statement = mysqli_prepare(
        $con,
        "SELECT amount, payment_method, transaction_reference, bank_reference, bank_name, created_at AS paid_at
         FROM payments
         WHERE id = ? AND user_id = ? AND status = 'Paid'
         LIMIT 1"
    );

    if ($statement) {
        mysqli_stmt_bind_param($statement, "ii", $payment_id, $user_id);
        mysqli_stmt_execute($statement);
        $result = mysqli_stmt_get_result($statement);
        $receipt = mysqli_fetch_assoc($result);
        mysqli_stmt_close($statement);
    }
}

if (!$receipt) {
    header("Location: Dashboard.php");
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt</title>
    <link rel="stylesheet" href="challan_payment_success.css">
</head>
<body>
<div class="success-page">
    <div class="success-card">
        <div class="success-icon">✓</div>
        <span class="success-label">PAYMENT SUCCESSFUL</span>
        <h1>Payment Receipt</h1>
        <p>Your payment has been completed successfully.</p>

        <div class="receipt">
            <?php if (!empty($receipt['vehicle_no'])) { ?>
            <div class="receipt-row">
                <span>Vehicle Number</span>
                <strong><?php echo htmlspecialchars($receipt['vehicle_no']); ?></strong>
            </div>
            <?php } ?>
            <div class="receipt-row">
                <span>Payment For</span>
                <strong><?php echo htmlspecialchars($payment_label); ?></strong>
            </div>
            <div class="receipt-row">
                <span>Payment Method</span>
                <strong><?php echo htmlspecialchars($receipt['payment_method']); ?></strong>
            </div>
            <div class="receipt-row">
                <span>Amount Paid</span>
                <strong>₹ <?php echo number_format((float) $receipt['amount'], 2); ?></strong>
            </div>
            <div class="receipt-row">
                <span>Transaction ID</span>
                <strong><?php echo htmlspecialchars($receipt['transaction_reference']); ?></strong>
            </div>
            <?php if (!empty($receipt['bank_name'])) { ?>
            <div class="receipt-row">
                <span>Bank</span>
                <strong><?php echo htmlspecialchars($receipt['bank_name']); ?></strong>
            </div>
            <?php } ?>
            <?php if (!empty($receipt['bank_reference'])) { ?>
            <div class="receipt-row">
                <span>Demo Bank Reference</span>
                <strong><?php echo htmlspecialchars($receipt['bank_reference']); ?></strong>
            </div>
            <?php } ?>
            <div class="receipt-row">
                <span>Paid On</span>
                <strong><?php echo $receipt['paid_at'] ? date("d M Y, h:i A", strtotime($receipt['paid_at'])) : "-"; ?></strong>
            </div>
            <div class="receipt-row">
                <span>Status</span>
                <strong class="paid">PAID</strong>
            </div>
        </div>

        <a href="<?php echo htmlspecialchars($continue_url, ENT_QUOTES, 'UTF-8'); ?>" class="dashboard-btn">Continue →</a>
    </div>
</div>
<script src="site-back-button.js?v=3" defer></script>
</body>
</html>