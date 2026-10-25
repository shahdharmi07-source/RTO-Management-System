<?php

session_start();
include "connect.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: Login.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];
$challan_number = trim($_GET['challan_no'] ?? $_SESSION['challan_number'] ?? '');
$receipt = null;

if ($challan_number !== '') {
    $receiptStatement = mysqli_prepare(
        $con,
        "SELECT vehicle_no, challan_no, amount, paid_at, payment_reference, bank_reference, bank_name
         FROM challans
         WHERE challan_no = ? AND user_id = ? AND status = 'Paid'
           AND payment_reference IS NOT NULL
         LIMIT 1"
    );

    if ($receiptStatement) {
        mysqli_stmt_bind_param($receiptStatement, "si", $challan_number, $user_id);
        mysqli_stmt_execute($receiptStatement);
        $receiptResult = mysqli_stmt_get_result($receiptStatement);
        $receipt = mysqli_fetch_assoc($receiptResult);
        mysqli_stmt_close($receiptStatement);
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
    <title>Challan Receipt - <?php echo htmlspecialchars($receipt['challan_no']); ?></title>
    <link rel="stylesheet" href="vehicle_services.css">
    <style>
        .challan-receipt-card {
            width: min(320px, 100%);
            margin: 0;
        }
        .challan-receipt-card .vs-card-header {
            flex-wrap: wrap;
            gap: 10px;
        }
        .challan-receipt-card .vs-card-header .vehicle-no {
            font-size: 17px;
            letter-spacing: 1px;
            overflow-wrap: anywhere;
        }
        .challan-receipt-grid {
            grid-template-columns: minmax(0, 320px);
            justify-content: start;
        }
        @media print {
            body { background: #fff; }
            .vs-hero, .vs-back, .vs-toolbar, .challan-receipt-actions { display: none !important; }
            .challan-receipt-card { box-shadow: none; }
            .vs-page { margin: 0; padding: 0; }
        }
    </style>
</head>
<body>
<section class="vs-hero">
    <div class="vs-hero-inner">
        <span class="vs-badge">RTO CHALLAN SERVICES</span>
        <h1>Paid Challan Receipt</h1>
        <p>Your traffic challan payment has been completed successfully.</p>
    </div>
</section>

<main class="vs-page">
    <a href="Dashboard.php" class="vs-back">← Back to Dashboard</a>

    <div class="vs-toolbar">
        <div>
            <h2>Challan Receipt</h2>
            <p>Payment completed for 1 challan.</p>
        </div>
    </div>

    <div class="vs-grid challan-receipt-grid">
    <div class="vs-card challan-receipt-card" id="challan-receipt">
        <div class="vs-card-header">
            <span class="vehicle-no"><?php echo htmlspecialchars($receipt['challan_no']); ?></span>
            <span class="vehicle-type-badge">Traffic Challan</span>
        </div>

        <div class="vs-card-body">
            <div class="vs-info-row">
                <span class="label">Vehicle Number</span>
                <span class="value"><?php echo htmlspecialchars($receipt['vehicle_no']); ?></span>
            </div>
            <div class="vs-info-row">
                <span class="label">Challan Number</span>
                <span class="value"><?php echo htmlspecialchars($receipt['challan_no']); ?></span>
            </div>
            <div class="vs-info-row">
                <span class="label">Amount Paid</span>
                <span class="value">₹ <?php echo number_format((float) $receipt['amount'], 2); ?></span>
            </div>
            <div class="vs-info-row">
                <span class="label">Transaction ID</span>
                <span class="value"><?php echo htmlspecialchars($receipt['payment_reference']); ?></span>
            </div>
            <?php if (!empty($receipt['bank_name'])) { ?>
            <div class="vs-info-row">
                <span class="label">Bank</span>
                <span class="value"><?php echo htmlspecialchars($receipt['bank_name']); ?></span>
            </div>
            <?php } ?>
            <?php if (!empty($receipt['bank_reference'])) { ?>
            <div class="vs-info-row">
                <span class="label">Demo Bank Reference</span>
                <span class="value"><?php echo htmlspecialchars($receipt['bank_reference']); ?></span>
            </div>
            <?php } ?>
            <div class="vs-info-row">
                <span class="label">Paid On</span>
                <span class="value"><?php echo $receipt['paid_at'] ? date("d M Y, h:i A", strtotime($receipt['paid_at'])) : "-"; ?></span>
            </div>
            <div class="vs-info-row" style="border-bottom:none;">
                <span class="label">Payment Status</span>
                <span class="vs-status completed">Paid</span>
            </div>
        </div>

        <div class="vs-card-actions challan-receipt-actions">
            <button type="button" class="btn-receipt" onclick="window.print()">🧾 View / Print Receipt</button>
            <a href="Dashboard.php" class="btn-back">← Dashboard</a>
        </div>
    </div>
    </div>
</main>
<script src="site-back-button.js?v=3" defer></script>
</body>
</html>