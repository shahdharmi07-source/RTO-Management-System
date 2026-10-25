<?php
include("connect.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

mysqli_report(MYSQLI_REPORT_OFF);

/* =====================================================
   LOGIN CHECK
===================================================== */
if (!isset($_SESSION["user_id"])) {
    header("Location: Login.php");
    exit();
}

/* =====================================================
   GET LICENCE BY ID
===================================================== */
$dl_id = filter_var($_GET["id"] ?? null, FILTER_VALIDATE_INT);
$renewalId = filter_var($_GET["renewal_id"] ?? null, FILTER_VALIDATE_INT);

if (($dl_id === false || $dl_id < 1) && ($renewalId === false || $renewalId < 1)) {
    header("Location: licence_services.php");
    exit();
}

$dl = null;
$isRenewalReceipt = $renewalId !== false && $renewalId !== null && $renewalId > 0;

if ($isRenewalReceipt) {
    $stmt = mysqli_prepare($con, "SELECT licence_data FROM licence_renewals WHERE id = ? AND user_id = ? LIMIT 1");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ii", $renewalId, $_SESSION["user_id"]);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $renewal = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ($renewal) {
            $renewalData = json_decode($renewal["licence_data"], true);
            $dl = is_array($renewalData) ? $renewalData : null;
        }
    }
} else {
    $stmt = mysqli_prepare($con, "SELECT * FROM licence WHERE id = ? AND user_id = ? LIMIT 1");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ii", $dl_id, $_SESSION["user_id"]);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $dl = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
    }
}

if (!$dl) {
    header("Location: licence_services.php");
    exit();
}

/* =====================================================
   RECEIPT NUMBER
===================================================== */
$receipt_no = $isRenewalReceipt
    ? "RTO-DLR-" . str_pad((string) $renewalId, 6, "0", STR_PAD_LEFT)
    : "RTO-DL-" . str_pad((string) $dl["id"], 6, "0", STR_PAD_LEFT);

/* =====================================================
   FEE BREAKDOWN
===================================================== */
$app_fee  = 450;
$proc_fee = 50;
$total    = $app_fee + $proc_fee;

/* =====================================================
   STATUS
===================================================== */
$lt = strtolower($dl["licence_type"] ?? "");
if ($isRenewalReceipt || strpos($lt, "renew") !== false) {
    $status_label = "Renewed";
    $status_class = "completed";
} elseif (strpos($lt, "learner") !== false) {
    $status_label = "Applied – Learner Licence";
    $status_class = "scheduled";
} else {
    $status_label = "Applied – Permanent Licence";
    $status_class = "scheduled";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driving Licence Receipt – RTO</title>
    <link rel="stylesheet" href="vehicle_services.css">

    <style>
        /* Teal theme overrides */
        .vs-hero        { background: linear-gradient(135deg, #004D40, #00897B); }
        .vs-hero p      { color: #b2dfdb; }
        .vs-back        { color: #00897B; }
        .receipt-card   { background: #e0f2f1; border-color: #80cbc4; }
        .receipt-header { background: linear-gradient(135deg, #004D40, #00897B); }
        .receipt-section-title { color: #00897B; border-bottom-color: #b2dfdb; }
        .vehicle-no-banner { background: #e0f2f1; border-bottom-color: #b2dfdb; }
        .vehicle-no-banner .plate { color: #004D40; }
        .receipt-footer { background: #00695C; }
        .fee-table th   { background: #e0f2f1; }
        .fee-table .total-row td { color: #00695C; border-top-color: #b2dfdb; }
        .fee-table td   { border-bottom-color: #e0f2f1; }
        .btn-print      { background: #00897B; }
        .btn-print:hover{ background: #00695C; }

        /* Status badges */
        .dl-status { display:inline-block; padding:5px 14px; border-radius:20px; font-size:13px; font-weight:700; }
        .dl-status.scheduled { background:#e0f2f1; color:#00695C; }
        .dl-status.completed { background:#e0f2f1; color:#00695C; }
        .dl-status.pending   { background:#fef9c3; color:#854d0e; }

        @media print {
            body { background:#fff; }
            .receipt-actions, .vs-back, .vs-hero { display:none !important; }
            .receipt-card { box-shadow:none; border:none; }
            .receipt-wrapper { margin:0; padding:0; }
        }
    </style>
</head>
<body>

<!-- HERO -->
<section class="vs-hero">
    <div class="vs-hero-inner">
        <span class="vs-badge">RTO DRIVING LICENCE SERVICES</span>
        <h1><?php echo $isRenewalReceipt ? "Driving Licence Renewal Receipt" : "Driving Licence Receipt"; ?></h1>
        <p><?php echo $isRenewalReceipt ? "Official receipt for your licence renewal." : "Official application receipt for your driving licence."; ?></p>
    </div>
</section>

<div class="receipt-wrapper">

    <!-- Back -->
    <a href="Dashboard.php#driving-licences-section" class="vs-back">← Back to Dashboard</a>

    <div class="receipt-card">

        <!-- =====================
             RECEIPT HEADER
        ===================== -->
        <div class="receipt-header">
            <div>
                <div class="org-name">TRANSPORT DEPARTMENT – RTO</div>
                <h2><?php echo $isRenewalReceipt ? "Driving Licence Renewal" : "Driving Licence Application"; ?></h2>
                <div class="receipt-sub"><?php echo $isRenewalReceipt ? "Official Renewal Receipt" : "Official Application Receipt"; ?></div>
            </div>
            <div class="receipt-number">
                <div class="label">RECEIPT NO.</div>
                <div class="value"><?php echo htmlspecialchars($receipt_no); ?></div>
            </div>
        </div>

        <!-- =====================
             APPLICANT BANNER
        ===================== -->
        <div class="vehicle-no-banner">
            <div>
                <div class="plate"><?php echo htmlspecialchars(strtoupper($dl["full_name"] ?? "")); ?></div>
                <div class="reg-date">
                    <?php echo $isRenewalReceipt ? "Renewal Receipt ID: #" . (int) $renewalId : "Application ID: #" . (int) $dl["id"]; ?>
                    &nbsp;|&nbsp;
                    Licence Type: <?php echo htmlspecialchars($dl["licence_type"] ?? "—"); ?>
                </div>
            </div>
            <span class="dl-status <?php echo $status_class; ?>">
                <?php echo htmlspecialchars($status_label); ?>
            </span>
        </div>

        <!-- =====================
             RECEIPT BODY
        ===================== -->
        <div class="receipt-body">

            <!-- Personal Details -->
            <div class="receipt-section-title">Personal Details</div>
            <div class="receipt-grid">

                <div class="receipt-field">
                    <div class="field-label">Full Name</div>
                    <div class="field-value"><?php echo htmlspecialchars($dl["full_name"] ?? "—"); ?></div>
                </div>

                <div class="receipt-field">
                    <div class="field-label">Date of Birth</div>
                    <div class="field-value">
                        <?php echo !empty($dl["date_of_birth"])
                            ? date("d M Y", strtotime($dl["date_of_birth"]))
                            : "—"; ?>
                    </div>
                </div>

                <div class="receipt-field">
                    <div class="field-label">Gender</div>
                    <div class="field-value"><?php echo htmlspecialchars($dl["gender"] ?? "—"); ?></div>
                </div>

                <div class="receipt-field">
                    <div class="field-label">Mobile No.</div>
                    <div class="field-value"><?php echo htmlspecialchars($dl["mobile_no"] ?? "—"); ?></div>
                </div>

                <div class="receipt-field">
                    <div class="field-label">Email</div>
                    <div class="field-value"><?php echo htmlspecialchars($dl["email"] ?? "—"); ?></div>
                </div>

                <div class="receipt-field">
                    <div class="field-label">Aadhaar No.</div>
                    <div class="field-value">
                        <?php
                        $a = $dl["aadhaar_no"] ?? "";
                        /* Mask middle digits: XXXX-XXXX-1234 */
                        if (strlen($a) === 12) {
                            echo "XXXX-XXXX-" . substr($a, 8);
                        } else {
                            echo htmlspecialchars($a ?: "—");
                        }
                        ?>
                    </div>
                </div>

            </div>

            <!-- Address Details -->
            <div class="receipt-section-title">Address Details</div>
            <div class="receipt-grid">

                <div class="receipt-field">
                    <div class="field-label">Address</div>
                    <div class="field-value"><?php echo htmlspecialchars($dl["address"] ?? "—"); ?></div>
                </div>

                <div class="receipt-field">
                    <div class="field-label">City</div>
                    <div class="field-value"><?php echo htmlspecialchars($dl["city"] ?? "—"); ?></div>
                </div>

                <div class="receipt-field">
                    <div class="field-label">State</div>
                    <div class="field-value"><?php echo htmlspecialchars($dl["state"] ?? "—"); ?></div>
                </div>

                <div class="receipt-field">
                    <div class="field-label">Pincode</div>
                    <div class="field-value"><?php echo htmlspecialchars($dl["pincode"] ?? "—"); ?></div>
                </div>

            </div>

            <!-- Licence Details -->
            <div class="receipt-section-title">Licence Details</div>
            <div class="receipt-grid">

                <div class="receipt-field">
                    <div class="field-label">Licence Type</div>
                    <div class="field-value"><?php echo htmlspecialchars($dl["licence_type"] ?? "—"); ?></div>
                </div>

                <div class="receipt-field">
                    <div class="field-label">Vehicle Class</div>
                    <div class="field-value"><?php echo htmlspecialchars($dl["vehicle_class"] ?? "—"); ?></div>
                </div>

                <div class="receipt-field">
                    <div class="field-label">Document Type</div>
                    <div class="field-value"><?php echo htmlspecialchars($dl["document_type"] ?? "—"); ?></div>
                </div>

                <div class="receipt-field">
                    <div class="field-label">Application Status</div>
                    <div class="field-value">
                        <span class="dl-status <?php echo $status_class; ?>">
                            <?php echo htmlspecialchars($status_label); ?>
                        </span>
                    </div>
                </div>

            </div>

            <!-- Fee Table -->
            <div class="receipt-section-title">Fee Details</div>
            <table class="fee-table">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Application Fee</td>
                        <td>₹<?php echo number_format($app_fee); ?></td>
                    </tr>
                    <tr>
                        <td>Processing Fee</td>
                        <td>₹<?php echo number_format($proc_fee); ?></td>
                    </tr>
                    <tr class="total-row">
                        <td>Total Paid</td>
                        <td>₹<?php echo number_format($total); ?></td>
                    </tr>
                </tbody>
            </table>

        </div>

        <!-- STATUS ROW -->
        <div class="receipt-status-row">
            <span class="receipt-status-label">Application Status</span>
            <span class="dl-status <?php echo $status_class; ?>">
                <?php echo htmlspecialchars($status_label); ?>
            </span>
        </div>

        <!-- FOOTER -->
        <div class="receipt-footer">
            Transport Department – RTO &nbsp;|&nbsp; Receipt No: <?php echo htmlspecialchars($receipt_no); ?> &nbsp;|&nbsp; This is a computer-generated receipt.
        </div>

    </div>

    <!-- ACTIONS -->
    <div class="receipt-actions">
        <button class="btn-print" onclick="window.print()">
            🖨️ Print / Download Receipt
        </button>
        <a href="Dashboard.php" class="btn-back-dash">
            🏠 Dashboard
        </a>
    </div>

</div>

<script src="site-back-button.js?v=3" defer></script>
</body>
</html>
