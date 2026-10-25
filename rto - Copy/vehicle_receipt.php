<?php
include("connect.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* Stop mysqli from throwing fatal exceptions */
mysqli_report(MYSQLI_REPORT_OFF);

/* =====================================================
   LOGIN CHECK
===================================================== */
if (!isset($_SESSION["user_id"])) {
    header("Location: Login.php");
    exit();
}

$user_id = $_SESSION["user_id"];

/* =====================================================
   ENSURE INSPECTION COLUMNS EXIST
===================================================== */
/* =====================================================
   GET VEHICLE
===================================================== */
$vehicle_no = trim($_GET["vehicle_no"] ?? "");

if ($vehicle_no === "") {
    header("Location: vehicle_services.php");
    exit();
}

$vehicle = null;
$stmt = mysqli_prepare($con,
    "SELECT v.id, v.vehicle_no, v.owner_name, v.vehicle_type, v.vehicle_model,
            v.fuel_type, v.chassis_no, v.engine_no,
            v.registration_date, v.status,
            v.inspection_center, v.inspection_date, v.inspection_time
     FROM vehicles v
     WHERE v.vehicle_no = ? AND v.user_id = ?
     LIMIT 1"
);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "si", $vehicle_no, $user_id);
    mysqli_stmt_execute($stmt);
    $result  = mysqli_stmt_get_result($stmt);
    $vehicle = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

if (!$vehicle) {
    header("Location: vehicle_services.php");
    exit();
}

/* =====================================================
   RECEIPT NUMBER (deterministic from vehicle id)
===================================================== */
$receipt_no = "RTO-VR-" . str_pad($vehicle["id"], 6, "0", STR_PAD_LEFT);

/* =====================================================
   FEE BREAKDOWN
===================================================== */
$reg_fee  = 2000;
$proc_fee = 100;
$total    = $reg_fee + $proc_fee;

/* =====================================================
   STATUS BADGE CLASS
===================================================== */
function statusClass($s) {
    $s = strtolower($s);
    if (strpos($s, "pending") !== false)    return "pending";
    if (strpos($s, "schedul") !== false)    return "scheduled";
    if (strpos($s, "approv") !== false ||
        strpos($s, "complet") !== false)    return "completed";
    if (strpos($s, "reject") !== false)     return "rejected";
    return "pending";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehicle Registration Receipt – <?php echo htmlspecialchars($vehicle["vehicle_no"]); ?></title>
    <link rel="stylesheet" href="vehicle_services.css">
</head>
<body>

<div class="receipt-wrapper">

    <!-- Back link (hidden on print) -->
    <a href="vehicle_services.php" class="vs-back">← Back to My Vehicles</a>

    <!-- =====================================================
         RECEIPT CARD
    ===================================================== -->
    <div class="receipt-card" id="receipt-print">

        <!-- Header -->
        <div class="receipt-header">
            <div>
                <div class="org-name">GOVERNMENT OF GUJARAT – TRANSPORT DEPARTMENT</div>
                <h2>Vehicle Registration Receipt</h2>
                <div class="receipt-sub">Regional Transport Office (RTO)</div>
            </div>
            <div class="receipt-number">
                <div class="label">RECEIPT NO.</div>
                <div class="value"><?php echo $receipt_no; ?></div>
                <div style="font-size:12px;color:#b2dfdb;margin-top:6px;">
                    <?php echo date("d M Y"); ?>
                </div>
            </div>
        </div>

        <!-- Vehicle Number Banner -->
        <div class="vehicle-no-banner">
            <div>
                <div style="font-size:11px;color:#64748b;font-weight:600;
                            letter-spacing:1px;margin-bottom:4px;">VEHICLE NUMBER</div>
                <div class="plate"><?php echo htmlspecialchars($vehicle["vehicle_no"]); ?></div>
            </div>
            <div class="reg-date">
                <div style="font-size:11px;color:#94a3b8;margin-bottom:3px;">REGISTRATION DATE</div>
                <div style="font-size:15px;font-weight:700;color:#374151;">
                    <?php echo $vehicle["registration_date"]
                        ? date("d M Y", strtotime($vehicle["registration_date"]))
                        : date("d M Y"); ?>
                </div>
            </div>
        </div>

        <!-- Body -->
        <div class="receipt-body">

            <!-- Vehicle Details -->
            <div class="receipt-section-title">Vehicle Details</div>

            <div class="receipt-grid">

                <div class="receipt-field">
                    <div class="field-label">Owner Name</div>
                    <div class="field-value"><?php echo htmlspecialchars($vehicle["owner_name"]); ?></div>
                </div>

                <div class="receipt-field">
                    <div class="field-label">Vehicle Type</div>
                    <div class="field-value"><?php echo htmlspecialchars($vehicle["vehicle_type"]); ?></div>
                </div>

                <div class="receipt-field">
                    <div class="field-label">Vehicle Model</div>
                    <div class="field-value"><?php echo htmlspecialchars($vehicle["vehicle_model"] ?: "—"); ?></div>
                </div>

                <div class="receipt-field">
                    <div class="field-label">Fuel Type</div>
                    <div class="field-value"><?php echo htmlspecialchars($vehicle["fuel_type"] ?: "—"); ?></div>
                </div>

                <div class="receipt-field">
                    <div class="field-label">Chassis Number</div>
                    <div class="field-value"><?php echo htmlspecialchars($vehicle["chassis_no"] ?: "—"); ?></div>
                </div>

                <div class="receipt-field">
                    <div class="field-label">Engine Number</div>
                    <div class="field-value"><?php echo htmlspecialchars($vehicle["engine_no"] ?: "—"); ?></div>
                </div>

            </div>

            <!-- Inspection Details -->
            <?php if (!empty($vehicle["inspection_center"])) { ?>
            <div class="receipt-section-title">Inspection Details</div>

            <div class="receipt-grid" style="margin-bottom:28px;">

                <div class="receipt-field">
                    <div class="field-label">Inspection Centre</div>
                    <div class="field-value"><?php echo htmlspecialchars($vehicle["inspection_center"]); ?></div>
                </div>

                <div class="receipt-field">
                    <div class="field-label">Inspection Date</div>
                    <div class="field-value">
                        <?php echo $vehicle["inspection_date"]
                            ? date("d M Y", strtotime($vehicle["inspection_date"]))
                            : "—"; ?>
                    </div>
                </div>

                <div class="receipt-field">
                    <div class="field-label">Time Slot</div>
                    <div class="field-value"><?php echo htmlspecialchars($vehicle["inspection_time"] ?: "—"); ?></div>
                </div>

            </div>
            <?php } ?>

            <!-- Fee Breakdown -->
            <div class="receipt-section-title">Fee Breakdown</div>

            <table class="fee-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Description</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1</td>
                        <td>Vehicle Registration Fee</td>
                        <td>₹<?php echo number_format($reg_fee); ?></td>
                    </tr>
                    <tr>
                        <td>2</td>
                        <td>Processing &amp; Administrative Fee</td>
                        <td>₹<?php echo number_format($proc_fee); ?></td>
                    </tr>
                    <tr class="total-row">
                        <td colspan="2"><strong>Total Amount Paid</strong></td>
                        <td><strong>₹<?php echo number_format($total); ?></strong></td>
                    </tr>
                </tbody>
            </table>

            <!-- Note -->
            <div style="background:#e0f2f1;border-radius:9px;padding:14px 18px;
                        font-size:13px;color:#374151;line-height:1.7;
                        border-left:4px solid #00897B;">
                <strong>Note:</strong> This is a computer-generated receipt.
                Please bring this receipt and original vehicle documents on the day of inspection.
                The RTO office number is <strong>0261-2234567</strong>.
            </div>

        </div>

        <!-- Status Row -->
        <div class="receipt-status-row">
            <span class="receipt-status-label">Application Status</span>
            <span class="vs-status <?php echo statusClass($vehicle["status"]); ?>"
                  style="font-size:13px;padding:6px 16px;">
                <?php echo htmlspecialchars($vehicle["status"]); ?>
            </span>
        </div>

        <!-- Footer -->
        <div class="receipt-footer">
            Regional Transport Office · Government of Gujarat, India &nbsp;|&nbsp;
            Receipt ID: <?php echo $receipt_no; ?> &nbsp;|&nbsp;
            Generated: <?php echo date("d M Y, h:i A"); ?>
        </div>

    </div>
    <!-- End receipt-card -->

    <!-- Actions (hidden on print) -->
    <div class="receipt-actions">
        <button class="btn-print" onclick="window.print()">
            🖨️ Print / Download Receipt
        </button>
        <a href="vehicle_services.php" class="btn-back-dash">
            ← My Vehicles
        </a>
        <a href="Dashboard.php" class="btn-back-dash">
            🏠 Dashboard
        </a>
    </div>

</div>

<script src="site-back-button.js?v=3" defer></script>
</body>
</html>
