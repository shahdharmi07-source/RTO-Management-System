<?php
session_start();
require_once "connect.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php");
    exit();
}

mysqli_report(MYSQLI_REPORT_OFF);
if (!$con || !($con instanceof mysqli)) {
    http_response_code(503);
    exit("The database is currently unavailable. Please start MySQL in XAMPP and try again.");
}

function tableCount(mysqli $con, string $table): int
{
    $allowed = ["users", "vehicles", "appointment_bookings", "licence", "documents", "challans"];
    if (!in_array($table, $allowed, true)) {
        return 0;
    }
    $result = $con->query("SELECT COUNT(*) AS total FROM `$table`");
    if (!$result) {
        return 0;
    }
    $row = $result->fetch_assoc();
    return (int) ($row["total"] ?? 0);
}

$stats = [
    "users" => tableCount($con, "users"),
    "vehicles" => tableCount($con, "vehicles"),
    "appointments" => tableCount($con, "appointment_bookings"),
    "licences" => tableCount($con, "licence"),
    "documents" => tableCount($con, "documents"),
    "challans" => tableCount($con, "challans"),
];

$challanStatusResult = $con->query(
    "SELECT
        COALESCE(SUM(status = 'Pending'), 0) AS pending,
        COALESCE(SUM(status = 'Paid'), 0) AS paid
     FROM challans"
);
if ($challanStatusResult) {
    $challanStatuses = $challanStatusResult->fetch_assoc();
    $stats["pending_challans"] = (int) ($challanStatuses["pending"] ?? 0);
    $stats["paid_challans"] = (int) ($challanStatuses["paid"] ?? 0);
} else {
    $stats["pending_challans"] = 0;
    $stats["paid_challans"] = 0;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - RTO</title>
    <link rel="stylesheet" href="admin_dashboard.css?v=10">
</head>
<body class="admin-page">
    <header class="admin-header">
        <a class="admin-brand" href="home.php"><span>RTO</span> Admin Dashboard</a>
        <div class="admin-actions">
            <span>Welcome, <?php echo htmlspecialchars($_SESSION["admin_name"] ?? "Administrator"); ?></span>
            <a class="outline-btn" href="home.php">View Website</a>
            <a class="danger-btn" href="admin_logout.php">Logout</a>
        </div>
    </header>
    <div class="admin-layout">
        <aside class="admin-sidebar" aria-label="Admin navigation">
            <div class="admin-sidebar-title"><span>📊</span><span>Admin Menu</span></div>
            <nav class="admin-sidebar-menu" aria-label="Record categories">
                <button type="button" class="admin-sidebar-item" data-record-type="users" aria-pressed="false"><span>👥</span><span>Users</span></button>
                <button type="button" class="admin-sidebar-item" data-record-type="vehicles" aria-pressed="false"><span>🚗</span><span>Vehicles</span></button>
                <button type="button" class="admin-sidebar-item" data-record-type="appointments" aria-pressed="false"><span>📅</span><span>Appointments</span></button>
                <button type="button" class="admin-sidebar-item" data-record-type="documents" aria-pressed="false"><span>📄</span><span>Uploaded documents</span></button>
                <button type="button" class="admin-sidebar-item" data-record-type="challans" aria-pressed="false"><span>⚠️</span><span>Challans</span></button>
                <button type="button" class="admin-sidebar-item" data-record-type="licences" aria-pressed="false"><span>🪪</span><span>Licence applications</span></button>
            </nav>
        </aside>
    <main class="admin-content">
        <section class="admin-hero">
            <div>
                <p class="eyebrow">CONTROL CENTER</p>
                <h1>RTO operations overview</h1>
                <p class="muted">Monitor users, vehicles, appointments and submitted documents from one place.</p>
            </div>
            <a class="primary-btn" href="home.php">Open Home Page</a>
        </section>
        <section class="stats-grid" aria-label="RTO statistics">
            <button type="button" class="stat-card" data-record-type="users" aria-label="View <?php echo $stats["users"]; ?> registered users">
                <span class="stat-icon blue">👥</span><div><strong><?php echo $stats["users"]; ?></strong><span>Registered users</span></div>
            </button>
            <button type="button" class="stat-card" data-record-type="vehicles" aria-label="View <?php echo $stats["vehicles"]; ?> registered vehicles">
                <span class="stat-icon green">🚗</span><div><strong><?php echo $stats["vehicles"]; ?></strong><span>Registered vehicles</span></div>
            </button>
            <button type="button" class="stat-card" data-record-type="appointments" aria-label="View <?php echo $stats["appointments"]; ?> appointments">
                <span class="stat-icon orange">📅</span><div><strong><?php echo $stats["appointments"]; ?></strong><span>Appointments</span></div>
            </button>
            <button type="button" class="stat-card" data-record-type="documents" aria-label="View <?php echo $stats["documents"]; ?> uploaded documents">
                <span class="stat-icon purple">📄</span><div><strong><?php echo $stats["documents"]; ?></strong><span>Documents uploaded</span></div>
            </button>
            <button type="button" class="stat-card" data-record-type="challans" aria-label="View <?php echo $stats["challans"]; ?> challans">
                <span class="stat-icon red">⚠</span><div><strong><?php echo $stats["challans"]; ?></strong><span>Challans</span></div>
            </button>
            <button type="button" class="stat-card" data-record-type="challans" aria-label="View <?php echo $stats["pending_challans"]; ?> pending challans">
                <span class="stat-icon orange">⏳</span><div><strong><?php echo $stats["pending_challans"]; ?></strong><span>Pending Challans</span></div>
            </button>
            <button type="button" class="stat-card" data-record-type="challans" aria-label="View <?php echo $stats["paid_challans"]; ?> paid challans">
                <span class="stat-icon green">✓</span><div><strong><?php echo $stats["paid_challans"]; ?></strong><span>Paid Challans</span></div>
            </button>
            <button type="button" class="stat-card" data-record-type="licences" aria-label="View <?php echo $stats["licences"]; ?> licence applications">
                <span class="stat-icon teal">🪪</span><div><strong><?php echo $stats["licences"]; ?></strong><span>Licence applications</span></div>
            </button>
        </section>
        <section class="manage-records-section" id="manage-user-data" hidden>
            <div class="manage-records-card">
                <iframe
                    class="manage-records-frame"
                    id="manage-records-frame"
                    title="Manage user records"
                    loading="lazy"
                ></iframe>
            </div>
        </section>
    </main>
    </div>
    <script>
        const recordCards = document.querySelectorAll(".stat-card[data-record-type]");
        const recordMenuItems = document.querySelectorAll(".admin-sidebar-item[data-record-type]");
        const manageRecordsSection = document.getElementById("manage-user-data");
        const manageRecordsFrame = document.getElementById("manage-records-frame");

        const loadRecordType = (recordType) => {
            manageRecordsFrame.style.height = "900px";
            manageRecordsFrame.src = `admin_records.php?type=${encodeURIComponent(recordType)}&embed=1`;
        };

        const showRecords = (recordType) => {
            manageRecordsSection.hidden = false;
            recordMenuItems.forEach((item) => {
                const active = item.dataset.recordType === recordType;
                item.classList.toggle("active", active);
                item.setAttribute("aria-pressed", String(active));
            });
            loadRecordType(recordType);
            manageRecordsSection.scrollIntoView({ behavior: "smooth", block: "start" });
        };

        [recordCards, recordMenuItems].forEach((items) => {
            items.forEach((item) => {
                item.addEventListener("click", () => {
                    if (item.dataset.recordType) {
                        showRecords(item.dataset.recordType);
                    }
                });
            });
        });

        window.addEventListener("message", (event) => {
            if (event.origin !== window.location.origin || event.source !== manageRecordsFrame.contentWindow) {
                return;
            }
            if (event.data && event.data.type === "admin-records-height" && Number.isFinite(event.data.height)) {
                manageRecordsFrame.style.height = `${Math.max(event.data.height, 900)}px`;
            }
        });
    </script>
<script src="site-back-button.js?v=3" defer></script>
</body>
</html>