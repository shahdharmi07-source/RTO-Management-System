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

$user_id = (int) $_SESSION["user_id"];

/* =====================================================
   FETCH USER'S DRIVING LICENCES
===================================================== */
$licences = [];
$licenceStmt = mysqli_prepare($con, "SELECT * FROM licence WHERE user_id = ? ORDER BY id DESC LIMIT 10");
if ($licenceStmt) {
    mysqli_stmt_bind_param($licenceStmt, "i", $user_id);
    if (mysqli_stmt_execute($licenceStmt)) {
        $licenceResult = mysqli_stmt_get_result($licenceStmt);
        while ($row = mysqli_fetch_assoc($licenceResult)) {
            $licences[] = $row;
        }
    }
    mysqli_stmt_close($licenceStmt);
}

$licenceRenewals = [];
$renewalStmt = mysqli_prepare(
    $con,
    "SELECT id, licence_id, renewed_at FROM licence_renewals WHERE user_id = ? ORDER BY id DESC"
);
if ($renewalStmt) {
    mysqli_stmt_bind_param($renewalStmt, "i", $user_id);
    if (mysqli_stmt_execute($renewalStmt)) {
        $renewalResult = mysqli_stmt_get_result($renewalStmt);
        while ($row = mysqli_fetch_assoc($renewalResult)) {
            $licenceRenewals[(int) $row["licence_id"]][] = $row;
        }
    }
    mysqli_stmt_close($renewalStmt);
}

/* =====================================================
   STATUS BADGE HELPER
===================================================== */
function licenceStatusClass($s) {
    $s = strtolower($s ?? '');
    if (strpos($s, "pending")  !== false) return "pending";
    if (strpos($s, "schedul")  !== false) return "scheduled";
    if (strpos($s, "approv")   !== false ||
        strpos($s, "complet")  !== false) return "completed";
    if (strpos($s, "renew")    !== false) return "scheduled";
    if (strpos($s, "reject")   !== false) return "rejected";
    return "pending";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driving Licence Services – RTO</title>
    <link rel="stylesheet" href="vehicle_services.css">

    <style>
        /* Teal theme for driving licence services */
        .vs-hero          { background: linear-gradient(135deg, #004D40, #00897B); }
        .vs-badge         { background: rgba(255,255,255,.18); border-color: rgba(255,255,255,.28); }
        .vs-hero p        { color: #b2dfdb; }
        .vs-card          { border-color: #b2dfdb; }
        .vs-card:hover    { box-shadow: 0 10px 28px rgba(0,137,123,.15); }
        .vs-card-header   { background: linear-gradient(135deg, #004D40, #00897B); }
        .vs-info-row      { border-bottom-color: #e0f2f1; }
        .vs-back          { color: #00897B; }
        .btn-register     { background: #00897B; }
        .btn-register:hover { background: #00695C; }
        .btn-receipt      { background: #00897B; }
        .btn-receipt:hover { background: #00695C; }
        .vs-empty         { border-color: #b2dfdb; }
        .vs-card-actions  { border-top-color: #e0f2f1; }

        /* DL number style like vehicle number */
        .dl-id-badge {
            font-size: 16px;
            font-weight: 700;
            color: #fff;
            letter-spacing: 1px;
        }

        /* Licence type badge */
        .dl-type-badge {
            padding: 5px 11px;
            background: rgba(255,255,255,.22);
            border-radius: 20px;
            color: #fff;
            font-size: 12px;
            font-weight: 600;
        }

        /* Status colours */
        .vs-status.pending   { background: #fef9c3; color: #854d0e; }
        .vs-status.scheduled { background: #e0f2f1; color: #00695C; }
        .vs-status.completed { background: #e0f2f1; color: #00695C; }
        .vs-status.rejected  { background: #fee2e2; color: #991b1b; }

        /* Applied date chip */
        .dl-applied-chip {
            display: inline-block;
            background: #e0f2f1;
            color: #00695C;
            border: 1px solid #b2dfdb;
            border-radius: 20px;
            padding: 3px 12px;
            font-size: 12px;
            font-weight: 600;
        }
    </style>
</head>
<body>

<!-- =====================================================
     HERO
===================================================== -->
<section class="vs-hero">
    <div class="vs-hero-inner">
        <span class="vs-badge">RTO DRIVING LICENCE SERVICES</span>
        <h1>My Driving Licences</h1>
        <p>View and manage all your driving licence applications, check status and download receipts.</p>
    </div>
</section>

<!-- =====================================================
     PAGE CONTENT
===================================================== -->
<div class="vs-page">

    <!-- Back link -->
    <a href="Dashboard.php" class="vs-back">← Back to Dashboard</a>

    <!-- Toolbar -->
    <div class="vs-toolbar">
        <div>
            <h2>Driving Licence Applications</h2>
            <p>
                <?php echo count($licences); ?>
                licence application<?php echo count($licences) !== 1 ? "s" : ""; ?> found
            </p>
        </div>
        <a href="driving-license.php" class="btn-register">
            + Apply / Renew Licence
        </a>
    </div>

    <!-- =====================================================
         LICENCE CARDS / EMPTY STATE
    ===================================================== -->
    <?php if (count($licences) === 0) { ?>

        <div class="vs-empty">
            <div class="empty-icon">📄</div>
            <h3>No Driving Licence Application Found</h3>
            <p>Apply for a new driving licence or renew your existing one to get started.</p>
            <a href="driving-license.php" class="btn-register" style="display:inline-flex;margin-top:8px;">
                + Apply Now
            </a>
        </div>

    <?php } else { ?>

        <div class="vs-grid">

            <?php foreach ($licences as $dl) { ?>

            <div class="vs-card">

                <!-- Card Header -->
                <div class="vs-card-header">
                    <span class="dl-id-badge">
                        <?php echo htmlspecialchars($dl["full_name"] ?? "Applicant"); ?>
                    </span>
                    <span class="dl-type-badge">
                        <?php echo htmlspecialchars($dl["licence_type"] ?? "Licence"); ?>
                    </span>
                </div>

                <!-- Card Body -->
                <div class="vs-card-body">

                    <div class="vs-info-row">
                        <span class="label">Full Name</span>
                        <span class="value"><?php echo htmlspecialchars($dl["full_name"] ?? "—"); ?></span>
                    </div>

                    <div class="vs-info-row">
                        <span class="label">Date of Birth</span>
                        <span class="value">
                            <?php echo !empty($dl["date_of_birth"])
                                ? date("d M Y", strtotime($dl["date_of_birth"]))
                                : "—"; ?>
                        </span>
                    </div>

                    <div class="vs-info-row">
                        <span class="label">Gender</span>
                        <span class="value"><?php echo htmlspecialchars($dl["gender"] ?? "—"); ?></span>
                    </div>

                    <div class="vs-info-row">
                        <span class="label">Mobile No.</span>
                        <span class="value"><?php echo htmlspecialchars($dl["mobile_no"] ?? "—"); ?></span>
                    </div>

                    <div class="vs-info-row">
                        <span class="label">Vehicle Class</span>
                        <span class="value"><?php echo htmlspecialchars($dl["vehicle_class"] ?? "—"); ?></span>
                    </div>

                    <div class="vs-info-row">
                        <span class="label">Licence Type</span>
                        <span class="value"><?php echo htmlspecialchars($dl["licence_type"] ?? "—"); ?></span>
                    </div>

                    <div class="vs-info-row">
                        <span class="label">City / State</span>
                        <span class="value">
                            <?php
                            $loc = array_filter([
                                $dl["city"]  ?? "",
                                $dl["state"] ?? ""
                            ]);
                            echo htmlspecialchars(implode(", ", $loc) ?: "—");
                            ?>
                        </span>
                    </div>

                    <div class="vs-info-row">
                        <span class="label">Document Type</span>
                        <span class="value"><?php echo htmlspecialchars($dl["document_type"] ?? "—"); ?></span>
                    </div>

                    <div class="vs-info-row" style="border-bottom:none;">
                        <span class="label">Status</span>
                        <span class="vs-status <?php echo licenceStatusClass($dl["licence_type"] ?? ""); ?>">
                            <?php
                            /* Derive display status from licence_type */
                            $lt = strtolower($dl["licence_type"] ?? "");
                            if (strpos($lt, "renew") !== false) {
                                echo "Renewed";
                            } elseif (strpos($lt, "learner") !== false) {
                                echo "Applied – Learner";
                            } else {
                                echo "Applied – Permanent";
                            }
                            ?>
                        </span>
                    </div>

                </div>

                <!-- Card Actions -->
                <div class="vs-card-actions">
                    <a href="licence_receipt.php?id=<?php echo intval($dl["id"]); ?>"
                       class="btn-receipt">
                        🧾 New Licence Receipt
                    </a>
                    <?php foreach ($licenceRenewals[(int) $dl["id"]] ?? [] as $renewal) { ?>
                        <a href="licence_receipt.php?renewal_id=<?php echo (int) $renewal["id"]; ?>"
                           class="btn-receipt renewal-receipt-link">
                            🧾 Renewal Receipt<?php echo !empty($renewal["renewed_at"]) ? " · " . htmlspecialchars(date("d M Y", strtotime($renewal["renewed_at"]))) : ""; ?>
                        </a>
                    <?php } ?>
                    <a href="Dashboard.php" class="btn-back">← Dashboard</a>
                </div>

            </div>

            <?php } ?>

        </div>

    <?php } ?>

</div>

<script src="site-back-button.js?v=2" defer></script>
</body>
</html>
