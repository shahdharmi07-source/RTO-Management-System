<?php
include("connect.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

mysqli_report(MYSQLI_REPORT_OFF);

if (!isset($_SESSION["user_id"])) {
    header("Location: Login.php");
    exit();
}

$user_id = (int) $_SESSION["user_id"];
$appointments = [];
$statement = mysqli_prepare(
    $con,
    "SELECT id, service, applicant_name, mobile_no, appointment_date,
            appointment_slot, status, created_at
     FROM appointment_bookings
     WHERE user_id = ?
     ORDER BY id DESC"
);

if (!$statement) {
    http_response_code(503);
    exit("Unable to load your appointments. Please try again later.");
}

mysqli_stmt_bind_param($statement, "i", $user_id);

if (!mysqli_stmt_execute($statement)) {
    mysqli_stmt_close($statement);
    http_response_code(503);
    exit("Unable to load your appointments. Please try again later.");
}

$result = mysqli_stmt_get_result($statement);
if (!$result) {
    mysqli_stmt_close($statement);
    http_response_code(503);
    exit("Unable to load your appointments. Please try again later.");
}

while ($row = mysqli_fetch_assoc($result)) {
    $appointments[] = $row;
}

mysqli_stmt_close($statement);

function appointmentStatusClass($status) {
    $status = strtolower($status ?? "");
    if (strpos($status, "cancel") !== false || strpos($status, "reject") !== false) {
        return "rejected";
    }
    if (strpos($status, "confirm") !== false || strpos($status, "complete") !== false) {
        return "completed";
    }
    return "pending";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Appointments – RTO</title>
    <link rel="stylesheet" href="vehicle_services.css">
    <style>
        .appointment-id-badge {
            color: #fff;
            font-size: 15px;
            font-weight: 700;
        }

        .appointment-service-badge {
            max-width: 52%;
            padding: 5px 11px;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,.3);
            border-radius: 20px;
            background: rgba(255,255,255,.22);
            color: #fff;
            font-size: 12px;
            font-weight: 600;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .vs-status.completed { background: #e0f2f1; color: #00695C; }

        @media (max-width: 480px) {
            .vs-card-header { align-items: flex-start; flex-direction: column; gap: 10px; }
            .appointment-service-badge { max-width: 100%; }
        }
    </style>
</head>
<body>

<section class="vs-hero">
    <div class="vs-hero-inner">
        <span class="vs-badge">RTO TRANSPORT SERVICES</span>
        <h1>My Appointments</h1>
        <p>View your RTO appointment bookings and open or print their receipts.</p>
    </div>
</section>

<main class="vs-page">
    <a href="Dashboard.php" class="vs-back">← Back to Dashboard</a>

    <div class="vs-toolbar">
        <div>
            <h2>RTO Appointment Bookings</h2>
            <p><?php echo count($appointments); ?> appointment<?php echo count($appointments) !== 1 ? "s" : ""; ?> found</p>
        </div>
        <a href="appointments.php?new=1" class="btn-register">+ New Appointment</a>
    </div>

    <?php if (count($appointments) === 0) { ?>
        <div class="vs-empty">
            <div class="empty-icon">📅</div>
            <h3>No Appointment Found</h3>
            <p>Book an RTO appointment to see its details and receipt here.</p>
            <a href="appointments.php?new=1" class="btn-register" style="display:inline-flex;margin-top:8px;">
                + Book New Appointment
            </a>
        </div>
    <?php } else { ?>
        <div class="vs-grid">
            <?php foreach ($appointments as $appointment) { ?>
                <article class="vs-card">
                    <div class="vs-card-header">
                        <span class="appointment-id-badge">
                            RTO-APPT-<?php echo str_pad((string) $appointment["id"], 6, "0", STR_PAD_LEFT); ?>
                        </span>
                        <span class="appointment-service-badge">
                            <?php echo htmlspecialchars($appointment["service"] ?? "RTO Service"); ?>
                        </span>
                    </div>

                    <div class="vs-card-body">
                        <div class="vs-info-row">
                            <span class="label">Applicant</span>
                            <span class="value"><?php echo htmlspecialchars($appointment["applicant_name"] ?? "—"); ?></span>
                        </div>
                        <div class="vs-info-row">
                            <span class="label">Mobile No.</span>
                            <span class="value"><?php echo htmlspecialchars($appointment["mobile_no"] ?? "—"); ?></span>
                        </div>
                        <div class="vs-info-row">
                            <span class="label">Appointment Date</span>
                            <span class="value">
                                <?php echo !empty($appointment["appointment_date"])
                                    ? date("d M Y", strtotime($appointment["appointment_date"]))
                                    : "—"; ?>
                            </span>
                        </div>
                        <div class="vs-info-row">
                            <span class="label">Time Slot</span>
                            <span class="value"><?php echo htmlspecialchars($appointment["appointment_slot"] ?? "—"); ?></span>
                        </div>
                        <div class="vs-info-row" style="border-bottom:none;">
                            <span class="label">Status</span>
                            <span class="vs-status <?php echo appointmentStatusClass($appointment["status"] ?? ""); ?>">
                                <?php echo htmlspecialchars($appointment["status"] ?? "Pending"); ?>
                            </span>
                        </div>
                    </div>

                    <div class="vs-card-actions">
                        <a href="appointment_receipt.php?id=<?php echo (int) $appointment["id"]; ?>" class="btn-receipt">
                            🧾 View Appointment Receipt
                        </a>
                    </div>
                </article>
            <?php } ?>
        </div>
    <?php } ?>
</main>

<script src="site-back-button.js?v=3" defer></script>
</body>
</html>
