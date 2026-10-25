<?php
include("connect.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* Prevent mysqli from throwing exceptions for optional/fallback queries */
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
   SUCCESS BANNER
===================================================== */
$reg_success = isset($_GET["registered"]) && $_GET["registered"] == "1";

/* =====================================================
   DYNAMIC COUNTS
   Wrapped in try/catch for tables that may not have
   user_id or may not exist yet
===================================================== */

/* My Vehicles count */
$vehicle_count = 0;
try {
    $vc = mysqli_prepare($con, "SELECT COUNT(*) FROM vehicles WHERE user_id = ?");
    if ($vc) {
        mysqli_stmt_bind_param($vc, "i", $user_id);
        mysqli_stmt_execute($vc);
        mysqli_stmt_bind_result($vc, $vehicle_count);
        mysqli_stmt_fetch($vc);
        mysqli_stmt_close($vc);
    }
} catch (Throwable $e) { $vehicle_count = 0; }

/* Applications count (same as vehicles) */
$app_count = $vehicle_count;

/* Pending challans — table/column may not exist yet */
$challan_count = 0;
try {
    $cc = mysqli_prepare(
        $con,
        "SELECT COUNT(*)
         FROM challans c
         WHERE c.status = 'Pending'
           AND (
               c.user_id = ?
               OR EXISTS (
                   SELECT 1 FROM vehicles v
                   WHERE v.user_id = ? AND UPPER(v.vehicle_no) = UPPER(c.vehicle_no)
               )
           )"
    );
    if ($cc) {
        mysqli_stmt_bind_param($cc, "ii", $user_id, $user_id);
        mysqli_stmt_execute($cc);
        mysqli_stmt_bind_result($cc, $challan_count);
        mysqli_stmt_fetch($cc);
        mysqli_stmt_close($cc);
    }
} catch (Throwable $e) { $challan_count = 0; }

$paid_challan_count = 0;
try {
    $pc = mysqli_prepare(
        $con,
        "SELECT COUNT(*)
         FROM challans c
         WHERE c.status = 'Paid'
           AND (
               c.user_id = ?
               OR EXISTS (
                   SELECT 1 FROM vehicles v
                   WHERE v.user_id = ? AND UPPER(v.vehicle_no) = UPPER(c.vehicle_no)
               )
           )"
    );
    if ($pc) {
        mysqli_stmt_bind_param($pc, "ii", $user_id, $user_id);
        mysqli_stmt_execute($pc);
        mysqli_stmt_bind_result($pc, $paid_challan_count);
        mysqli_stmt_fetch($pc);
        mysqli_stmt_close($pc);
    }
} catch (Throwable $e) { $paid_challan_count = 0; }

/* Appointment count */
$appointment_count = 0;
try {
    $ac = mysqli_prepare($con, "SELECT COUNT(*) FROM appointment_bookings WHERE user_id = ?");
    if ($ac) {
        mysqli_stmt_bind_param($ac, "i", $user_id);
        mysqli_stmt_execute($ac);
        mysqli_stmt_bind_result($ac, $appointment_count);
        mysqli_stmt_fetch($ac);
        mysqli_stmt_close($ac);
    }
} catch (Throwable $e) { $appointment_count = 0; }

/* =====================================================
   RECENT APPOINTMENTS (from appointment_bookings)
===================================================== */
$recent_appointments = [];
try {
    $ra = mysqli_prepare($con,
        "SELECT id, service, applicant_name, mobile_no, email, appointment_date,
                appointment_slot, status, created_at
         FROM appointment_bookings
         WHERE user_id = ?
         ORDER BY id DESC
         LIMIT 5"
    );
    if ($ra) {
        mysqli_stmt_bind_param($ra, "i", $user_id);
        mysqli_stmt_execute($ra);
        $ra_result = mysqli_stmt_get_result($ra);
        while ($row = mysqli_fetch_assoc($ra_result)) {
            $recent_appointments[] = $row;
        }
        mysqli_stmt_close($ra);
    }
} catch (Throwable $e) { $recent_appointments = []; }

/* Appointment success banner flag */
$appt_success = isset($_GET["appt"]) && $_GET["appt"] == "1";
$recent_vehicles = [];
try {
    $rv = mysqli_prepare($con,
        "SELECT vehicle_no, vehicle_type, status, registration_date
         FROM vehicles
         WHERE user_id = ?
         ORDER BY id DESC
         LIMIT 3"
    );
    if ($rv) {
        mysqli_stmt_bind_param($rv, "i", $user_id);
        mysqli_stmt_execute($rv);
        $rv_result = mysqli_stmt_get_result($rv);
        while ($row = mysqli_fetch_assoc($rv_result)) {
            $recent_vehicles[] = $row;
        }
        mysqli_stmt_close($rv);
    }
} catch (Throwable $e) { $recent_vehicles = []; }

$recent_challans = [];
try {
    $rc = mysqli_prepare(
        $con,
        "SELECT c.vehicle_no, c.challan_no, c.violation, c.amount, c.status,
                c.challan_date, c.paid_at, c.payment_reference
         FROM challans c
         WHERE c.user_id = ?
            OR EXISTS (
                SELECT 1 FROM vehicles v
                WHERE v.user_id = ? AND UPPER(v.vehicle_no) = UPPER(c.vehicle_no)
            )
         ORDER BY CASE WHEN c.status = 'Pending' THEN 0 ELSE 1 END,
                  c.challan_date DESC, c.id DESC
         LIMIT 10"
    );
    if ($rc) {
        mysqli_stmt_bind_param($rc, "ii", $user_id, $user_id);
        mysqli_stmt_execute($rc);
        $challan_result = mysqli_stmt_get_result($rc);
        while ($row = mysqli_fetch_assoc($challan_result)) {
            $recent_challans[] = $row;
        }
        mysqli_stmt_close($rc);
    }
} catch (Throwable $e) { $recent_challans = []; }

/* =====================================================
   USER NAME
===================================================== */
$user_name = "User";
try {
    $un = mysqli_prepare($con, "SELECT full_name FROM users WHERE id = ? LIMIT 1");
    if ($un) {
        mysqli_stmt_bind_param($un, "i", $user_id);
        mysqli_stmt_execute($un);
        $un_result = mysqli_stmt_get_result($un);
        if ($un_row = mysqli_fetch_assoc($un_result)) {
            $user_name = $un_row["full_name"];
        }
        mysqli_stmt_close($un);
    }
} catch (Throwable $e) { $user_name = "User"; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard – RTO</title>
    <link rel="stylesheet" href="Dashboard.css?v=4">
    <style>
        .success-banner {
            background: #e0f2f1;
            color: #00695C;
            border: 1px solid #80cbc4;
            border-radius: 10px;
            padding: 16px 24px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 600;
        }
        .success-banner span.icon { font-size: 22px; }
    </style>
</head>
<body>

<div class="dashboard-page">

    <!-- ================= SIDEBAR ================= -->
    <aside class="dashboard-sidebar">

        <div class="sidebar-title">
            <span>📊</span>
            <span>Dashboard</span>
        </div>

        <nav class="sidebar-menu">

            <a href="Dashboard.php" class="sidebar-item active">
                <span>📊</span>
                <span>Dashboard</span>
            </a>

            <a href="profile.php" class="sidebar-item">
                <span>👤</span>
                <span>My Profile</span>
            </a>

            <a href="VehicleRegistration.php" class="sidebar-item">
                <span>🚗</span>
                <span>My Vehicles</span>
            </a>

            <a href="Challan.php" class="sidebar-item">
                <span>⚠️</span>
                <span>My Challans</span>
            </a>

            <a href="Appointment.php" class="sidebar-item">
                <span>📅</span>
                <span>Appointments</span>
            </a>

        </nav>

        <div class="sidebar-bottom">
            <a href="logout.php" class="sidebar-item logout-item">
                <span>🚪</span>
                <span>Logout</span>
            </a>
        </div>

    </aside>

    <!-- ================= MAIN CONTENT ================= -->
    <main class="dashboard-main">

        <!-- Top Header -->
        <div class="dashboard-topbar">
            <div>
                <h1>Welcome, <?php echo htmlspecialchars($user_name); ?> 👋</h1>
                <p>Manage your transport services easily from here.</p>
            </div>
            <div class="notification-icon">
                🔔
                <span class="notification-dot"></span>
            </div>
        </div>

        <!-- ================= SUCCESS BANNER ================= -->
        <?php if ($reg_success) { ?>
        <div class="success-banner">
            <span class="icon">✅</span>
            <div>
                <strong>Vehicle Registration Successful!</strong><br>
                Your vehicle has been registered and inspection slot has been booked.
                <a href="vehicle_services.php" style="color:#00695C;text-decoration:underline;margin-left:8px;">
                    View your vehicles →
                </a>
            </div>
        </div>
        <?php } ?>

        <?php if ($appt_success) { ?>
        <div class="success-banner">
            <span class="icon">📅</span>
            <div>
                <strong>Appointment Booked Successfully!</strong><br>
                Your RTO appointment has been confirmed.
                <a href="#appointments-section" style="color:#00695C;text-decoration:underline;margin-left:8px;">
                    View appointment details ↓
                </a>
            </div>
        </div>
        <?php } ?>

        <!-- ================= SUMMARY CARDS ================= -->
        <section class="dashboard-cards">

            <div class="dashboard-card">
                <div class="card-icon blue">🚗</div>
                <div class="card-content">
                    <h3>My Vehicles</h3>
                    <p><?php echo $vehicle_count; ?></p>
                </div>
            </div>

            <div class="dashboard-card">
                <div class="card-icon orange">⚠️</div>
                <div class="card-content">
                    <h3>Pending Challans</h3>
                    <p><?php echo $challan_count; ?></p>
                </div>
            </div>

            <div class="dashboard-card">
                <div class="card-icon green">✓</div>
                <div class="card-content">
                    <h3>Paid Challans</h3>
                    <p><?php echo $paid_challan_count; ?></p>
                </div>
            </div>

            <div class="dashboard-card">
                <div class="card-icon green">📅</div>
                <div class="card-content">
                    <h3>Appointments</h3>
                    <p><?php echo $appointment_count; ?></p>
                </div>
            </div>

        </section>

        <section class="dashboard-section" id="my-challans">
            <div class="section-heading">
                <h2>My Challans</h2>
                <p>View challan status and payment receipts.</p>
            </div>

            <div class="activity-box">
                <?php if ($recent_challans) { ?>
                    <?php foreach ($recent_challans as $challan) {
                        $statusClass = strtolower($challan["status"]) === "paid" ? "completed" : "pending";
                    ?>
                    <div class="activity-row">
                        <div class="activity-left">
                            <div class="activity-icon">⚠️</div>
                            <div>
                                <h4><?php echo htmlspecialchars($challan["challan_no"]); ?> · <?php echo htmlspecialchars($challan["vehicle_no"]); ?></h4>
                                <p><?php echo htmlspecialchars($challan["violation"]); ?> · ₹ <?php echo number_format((float) $challan["amount"], 2); ?> · <?php echo date("d M Y", strtotime($challan["challan_date"])); ?></p>
                            </div>
                        </div>

                        <div class="challan-actions">
                            <span class="status <?php echo $statusClass; ?>">
                                <?php echo htmlspecialchars($challan["status"]); ?>
                            </span>
                            <?php if ($challan["status"] === "Paid" && $challan["payment_reference"] !== null) { ?>
                                <a class="challan-action" href="challan_payment_success.php?challan_no=<?php echo urlencode($challan["challan_no"]); ?>">Receipt</a>
                            <?php } elseif ($challan["status"] === "Pending") { ?>
                                <a class="challan-action" href="challan_payment.php?challan_no=<?php echo urlencode($challan["challan_no"]); ?>">Pay Challan</a>
                            <?php } ?>
                        </div>
                    </div>
                    <?php } ?>
                <?php } else { ?>
                    <div class="challan-empty">No challans found for your registered vehicles.</div>
                <?php } ?>
            </div>
        </section>

        <!-- ================= QUICK SERVICES ================= -->
        <section class="dashboard-section">

            <div class="section-heading">
                <h2>Quick Services</h2>
                <p>Access frequently used transport services.</p>
            </div>

            <div class="quick-services">

                <div class="quick-service-card">
                    <div class="quick-icon">🚗</div>
                    <h3>Vehicle Services</h3>
                    <p>Manage your vehicle registration and related services.</p>
                    <button onclick="location.href='vehicle_services.php'">
                        View Services
                    </button>
                </div>

                <div class="quick-service-card">
                    <div class="quick-icon">📄</div>
                    <h3>Driving License</h3>
                    <p>Apply for or manage your driving licence services.</p>
                    <button onclick="location.href='licence_services.php'">
                        View Services
                    </button>
                </div>

                <div class="quick-service-card">
                    <div class="quick-icon">📅</div>
                    <h3>Appointments</h3>
                    <p>Book and manage your RTO office appointments.</p>
                    <button
                        type="button"
                        onclick="location.href='appointment_services.php'"
                    >
                        View Services
                    </button>
                </div>

            </div>

        </section>

        <!-- ================= RECENT ACTIVITY ================= -->
        <section class="dashboard-section">

            <div class="section-heading">
                <h2>Recent Activity</h2>
                <p>Your latest vehicle registrations.</p>
            </div>

            <div class="activity-box">

                <?php if (count($recent_vehicles) > 0) { ?>

                    <?php foreach ($recent_vehicles as $v) {
                        $statusClass = strtolower($v["status"]) === "pending" ? "pending" : "completed";
                    ?>
                    <div class="activity-row">

                        <div class="activity-left">
                            <div class="activity-icon">🚗</div>
                            <div>
                                <h4>
                                    <?php echo htmlspecialchars($v["vehicle_no"]); ?>
                                    — <?php echo htmlspecialchars($v["vehicle_type"]); ?>
                                </h4>
                                <p>Registered on <?php echo date("d M Y", strtotime($v["registration_date"])); ?></p>
                            </div>
                        </div>

                        <div style="display:flex;align-items:center;gap:12px;">
                            <span class="status <?php echo $statusClass; ?>">
                                <?php echo htmlspecialchars($v["status"]); ?>
                            </span>
                            <a href="vehicle_receipt.php?vehicle_no=<?php echo urlencode($v['vehicle_no']); ?>"
                               style="font-size:12px;color:#00897B;text-decoration:none;font-weight:600;
                                      padding:5px 10px;border:1px solid #00897B;border-radius:6px;">
                                🧾 Receipt
                            </a>
                        </div>

                    </div>
                    <?php } ?>

                <?php } else { ?>

                    <div style="padding:35px;text-align:center;color:#94a3b8;">
                        <div style="font-size:40px;margin-bottom:12px;">🚗</div>
                        <p style="margin:0;font-size:15px;">No vehicles registered yet.</p>
                        <a href="VehicleRegistration.php"
                           style="display:inline-block;margin-top:14px;padding:9px 18px;
                                  background:#00897B;color:#fff;border-radius:7px;
                                  text-decoration:none;font-size:13px;font-weight:600;">
                            Register a Vehicle
                        </a>
                    </div>

                <?php } ?>

            </div>

        </section>

    </main>

</div>

<script src="site-back-button.js?v=3" defer></script>
</body>
</html>
