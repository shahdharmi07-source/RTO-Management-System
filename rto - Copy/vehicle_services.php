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
   Runs silently — safe to call every request
===================================================== */
/* =====================================================
   FETCH USER'S REGISTERED VEHICLES
===================================================== */
$vehicles = [];
$sql = "SELECT id, vehicle_no, owner_name, vehicle_type, vehicle_model,
               fuel_type, chassis_no, engine_no,
               registration_date, status,
               inspection_center, inspection_date, inspection_time
        FROM vehicles
        WHERE user_id = ?
        ORDER BY id DESC";

$stmt = mysqli_prepare($con, $sql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $vehicles[] = $row;
    }
    mysqli_stmt_close($stmt);
}

/* =====================================================
   STATUS BADGE HELPER
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
    <title>Vehicle Services – RTO</title>
    <link rel="stylesheet" href="vehicle_services.css">
</head>
<body>

<!-- =====================================================
     HERO
===================================================== -->
<section class="vs-hero">
    <div class="vs-hero-inner">
        <span class="vs-badge">RTO VEHICLE SERVICES</span>
        <h1>My Registered Vehicles</h1>
        <p>View and manage all your registered vehicles, check status and download receipts.</p>
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
            <h2>Registered Vehicles</h2>
            <p>
                <?php echo count($vehicles); ?>
                vehicle<?php echo count($vehicles) !== 1 ? "s" : ""; ?> registered
            </p>
        </div>
        <a href="VehicleRegistration.php" class="btn-register">
            + Register New Vehicle
        </a>
    </div>

    <!-- =====================================================
         VEHICLE CARDS / EMPTY STATE
    ===================================================== -->
    <?php if (count($vehicles) === 0) { ?>

        <div class="vs-empty">
            <div class="empty-icon">🚗</div>
            <h3>No Vehicles Registered Yet</h3>
            <p>Register your first vehicle to get started with RTO services.</p>
            <a href="VehicleRegistration.php" class="btn-register" style="display:inline-flex;">
                + Register a Vehicle
            </a>
        </div>

    <?php } else { ?>

        <div class="vs-grid">

            <?php foreach ($vehicles as $v) { ?>

            <div class="vs-card">

                <!-- Card Header -->
                <div class="vs-card-header">
                    <span class="vehicle-no"><?php echo htmlspecialchars($v["vehicle_no"]); ?></span>
                    <span class="vehicle-type-badge"><?php echo htmlspecialchars($v["vehicle_type"]); ?></span>
                </div>

                <!-- Card Body -->
                <div class="vs-card-body">

                    <div class="vs-info-row">
                        <span class="label">Owner Name</span>
                        <span class="value"><?php echo htmlspecialchars($v["owner_name"]); ?></span>
                    </div>

                    <div class="vs-info-row">
                        <span class="label">Vehicle Model</span>
                        <span class="value"><?php echo htmlspecialchars($v["vehicle_model"] ?: "—"); ?></span>
                    </div>

                    <div class="vs-info-row">
                        <span class="label">Fuel Type</span>
                        <span class="value"><?php echo htmlspecialchars($v["fuel_type"] ?: "—"); ?></span>
                    </div>

                    <div class="vs-info-row">
                        <span class="label">Registration Date</span>
                        <span class="value">
                            <?php echo $v["registration_date"]
                                ? date("d M Y", strtotime($v["registration_date"]))
                                : "—"; ?>
                        </span>
                    </div>

                    <?php if (!empty($v["inspection_center"])) { ?>
                    <div class="vs-info-row">
                        <span class="label">Inspection Centre</span>
                        <span class="value"><?php echo htmlspecialchars($v["inspection_center"]); ?></span>
                    </div>

                    <div class="vs-info-row">
                        <span class="label">Inspection Date</span>
                        <span class="value">
                            <?php echo $v["inspection_date"]
                                ? date("d M Y", strtotime($v["inspection_date"]))
                                : "—"; ?>
                            <?php echo $v["inspection_time"]
                                ? " at " . htmlspecialchars($v["inspection_time"])
                                : ""; ?>
                        </span>
                    </div>
                    <?php } ?>

                    <div class="vs-info-row" style="border-bottom:none;">
                        <span class="label">Status</span>
                        <span class="vs-status <?php echo statusClass($v["status"]); ?>">
                            <?php echo htmlspecialchars($v["status"]); ?>
                        </span>
                    </div>

                </div>

                <!-- Card Actions -->
                <div class="vs-card-actions">
                    <a href="vehicle_receipt.php?vehicle_no=<?php echo urlencode($v["vehicle_no"]); ?>"
                       class="btn-receipt">
                        🧾 View / Print Receipt
                    </a>
                    <a href="Dashboard.php" class="btn-back">← Dashboard</a>
                </div>

            </div>

            <?php } ?>

        </div>

    <?php } ?>

</div>

<script src="site-back-button.js?v=3" defer></script>
</body>
</html>
