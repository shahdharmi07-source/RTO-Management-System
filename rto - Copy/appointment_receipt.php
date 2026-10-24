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

$user_id = intval($_SESSION["user_id"]);

/* =====================================================
   GET BOOKING BY ID
===================================================== */
$appt_id = intval($_GET["id"] ?? 0);

if ($appt_id === 0) {
    header("Location: Dashboard.php");
    exit();
}

$appt = null;

$stmt = mysqli_prepare($con,
    "SELECT * FROM appointment_bookings
     WHERE id = ? AND user_id = ?
     LIMIT 1"
);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ii", $appt_id, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $appt   = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

if (!$appt) {
    header("Location: Dashboard.php");
    exit();
}

/* =====================================================
   COMPUTED VALUES
===================================================== */
$ref_no      = "RTO-APPT-" . str_pad($appt["id"], 6, "0", STR_PAD_LEFT);
$appt_date   = !empty($appt["appointment_date"])
                ? date("d M Y", strtotime($appt["appointment_date"])) : "—";
$booked_on   = !empty($appt["created_at"])
                ? date("d M Y, h:i A", strtotime($appt["created_at"])) : "—";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Receipt – <?php echo htmlspecialchars($ref_no); ?></title>

    <style>
    /* ── RESET ── */
    *, *::before, *::after { box-sizing: border-box; }

    body {
        margin: 0;
        padding: 0;
        font-family: "Segoe UI", Arial, sans-serif;
        background: #e0f2f1;
        color: #1f2937;
    }

    /* ── HERO ── */
    .rr-hero {
        background: linear-gradient(135deg, #00695c, #00897b);
        padding: 48px 24px 40px;
        color: #fff;
        text-align: center;
    }

    .rr-hero-badge {
        display: inline-block;
        padding: 6px 18px;
        background: rgba(255,255,255,.18);
        border: 1px solid rgba(255,255,255,.28);
        border-radius: 25px;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 1.2px;
        margin-bottom: 14px;
    }

    .rr-hero h1  { margin: 0 0 8px; font-size: 30px; }
    .rr-hero p   { margin: 0; opacity: .82; font-size: 15px; }

    /* ── WRAPPER ── */
    .rr-wrapper {
        max-width: 720px;
        margin: 40px auto 70px;
        padding: 0 20px;
    }

    /* ── BACK LINK ── */
    .rr-back {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        color: #00897b;
        text-decoration: none;
        font-size: 14px;
        font-weight: 600;
        margin-bottom: 22px;
    }
    .rr-back:hover { text-decoration: underline; }

    /* ── RECEIPT CARD ── */
    .rr-card {
        background: #fff;
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 10px 40px rgba(0,0,0,.10);
        border: 1px solid #b2dfdb;
    }

    /* ── CARD HEADER ── */
    .rr-card-header {
        background: linear-gradient(135deg, #00695c, #00897b);
        padding: 32px 40px;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        flex-wrap: wrap;
        gap: 16px;
        color: #fff;
    }

    .rr-org        { font-size: 11px; font-weight: 700; letter-spacing: 1.5px; color: #b2dfdb; margin-bottom: 6px; }
    .rr-card-header h2  { margin: 0 0 4px; font-size: 24px; }
    .rr-card-header .sub { font-size: 13px; color: #b2dfdb; }

    .rr-ref-box { text-align: right; }
    .rr-ref-box .rl { font-size: 11px; color: #b2dfdb; margin-bottom: 4px; }
    .rr-ref-box .rv { font-size: 20px; font-weight: 700; }

    /* ── STATUS BANNER ── */
    .rr-status-banner {
        background: #e0f2f1;
        border-bottom: 1px solid #b2dfdb;
        padding: 18px 40px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
    }

    .rr-status-left {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .rr-status-icon {
        width: 44px; height: 44px;
        background: #00897B;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 22px;
        color: #fff;
    }

    .rr-status-text p  { margin: 0; }
    .rr-status-text .st { font-weight: 700; font-size: 16px; color: #00695C; }
    .rr-status-text .ss { font-size: 12px; color: #64748b; margin-top: 2px; }

    .rr-confirmed-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #e0f2f1;
        color: #00695C;
        border: 1px solid #80cbc4;
        border-radius: 25px;
        padding: 7px 18px;
        font-size: 13px;
        font-weight: 700;
    }

    /* ── BODY ── */
    .rr-body { padding: 32px 40px; }

    .rr-section-title {
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 1px;
        color: #00897b;
        text-transform: uppercase;
        padding-bottom: 8px;
        border-bottom: 2px solid #b2dfdb;
        margin: 0 0 18px;
    }

    .rr-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 18px 30px;
        margin-bottom: 30px;
    }

    .rr-field .fl {
        font-size: 11px;
        color: #94a3b8;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .7px;
        margin-bottom: 4px;
    }

    .rr-field .fv {
        font-size: 15px;
        color: #0f172a;
        font-weight: 600;
    }

    /* ── SLOT HIGHLIGHT ── */
    .rr-slot-highlight {
        background: linear-gradient(135deg, #e0f2f1, #b2dfdb);
        border: 1px solid #b2dfdb;
        border-radius: 12px;
        padding: 20px 24px;
        display: flex;
        align-items: center;
        gap: 18px;
        margin-bottom: 30px;
    }

    .rr-slot-icon {
        font-size: 36px;
        flex-shrink: 0;
    }

    .rr-slot-details p { margin: 0; }
    .rr-slot-details .sd { font-size: 13px; color: #64748b; }
    .rr-slot-details .sv { font-size: 22px; font-weight: 800; color: #00695c; margin-top: 4px; }
    .rr-slot-details .sdate { font-size: 14px; color: #00897b; font-weight: 600; margin-top: 6px; }

    /* ── INSTRUCTIONS ── */
    .rr-instructions {
        background: #fff7ed;
        border: 1px solid #fed7aa;
        border-radius: 10px;
        padding: 16px 20px;
        margin-bottom: 20px;
    }

    .rr-instructions h4 { margin: 0 0 10px; color: #9a3412; font-size: 14px; }

    .rr-instructions ul {
        margin: 0;
        padding-left: 18px;
        color: #7c2d12;
        font-size: 13px;
        line-height: 1.9;
    }

    /* ── FOOTER ── */
    .rr-card-footer {
        background: #00695c;
        color: #b2dfdb;
        text-align: center;
        padding: 16px;
        font-size: 12px;
    }

    /* ── ACTIONS ── */
    .rr-actions {
        display: flex;
        gap: 12px;
        margin-top: 24px;
        flex-wrap: wrap;
    }

    .btn-print {
        flex: 1;
        padding: 14px 0;
        background: linear-gradient(135deg, #00897b, #00695c);
        color: #fff;
        border: none;
        border-radius: 10px;
        font-size: 15px;
        font-weight: 700;
        cursor: pointer;
        transition: .2s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    .btn-print:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,137,123,.35); }

    .btn-dash {
        flex: 1;
        padding: 14px 0;
        background: #f1f5f9;
        color: #334155;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        font-size: 15px;
        font-weight: 700;
        text-decoration: none;
        text-align: center;
        transition: .2s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    .btn-dash:hover { background: #e2e8f0; }

    /* ── PRINT STYLES ── */
    @media print {
        body { background: #fff; }
        .rr-hero, .rr-back, .rr-actions { display: none !important; }
        .rr-card { box-shadow: none; border: 1px solid #ccc; }
        .rr-wrapper { margin: 0; padding: 0; max-width: 100%; }
    }

    /* ── RESPONSIVE ── */
    @media (max-width: 600px) {
        .rr-card-header { padding: 24px 20px; }
        .rr-ref-box     { text-align: left; }
        .rr-status-banner { padding: 14px 20px; }
        .rr-body        { padding: 24px 20px; }
        .rr-grid        { grid-template-columns: 1fr; }
        .rr-slot-highlight { flex-direction: column; gap: 10px; }
    }
    </style>
</head>
<body>

<!-- ── HERO ── -->
<section class="rr-hero">
    <div class="rr-hero-badge">RTO TRANSPORT SERVICES</div>
    <h1>Appointment Receipt</h1>
    <p>Official confirmation receipt for your RTO appointment.</p>
</section>

<div class="rr-wrapper">

    <!-- Back -->
    <a href="Dashboard.php" class="rr-back">← Back to Dashboard</a>

    <div class="rr-card">

        <!-- ── HEADER ── -->
        <div class="rr-card-header">
            <div>
                <div class="rr-org">TRANSPORT DEPARTMENT – RTO</div>
                <h2>Appointment Confirmation</h2>
                <div class="sub">Official Booking Receipt</div>
            </div>
            <div class="rr-ref-box">
                <div class="rl">REFERENCE NO.</div>
                <div class="rv"><?php echo htmlspecialchars($ref_no); ?></div>
            </div>
        </div>

        <!-- ── STATUS BANNER ── -->
        <div class="rr-status-banner">
            <div class="rr-status-left">
                <div class="rr-status-icon">✓</div>
                <div class="rr-status-text">
                    <p class="st">Appointment Confirmed</p>
                    <p class="ss">Booked on <?php echo $booked_on; ?></p>
                </div>
            </div>
            <span class="rr-confirmed-badge">
                ✅ <?php echo htmlspecialchars($appt["status"]); ?>
            </span>
        </div>

        <!-- ── BODY ── -->
        <div class="rr-body">

            <!-- Slot Highlight -->
            <div class="rr-slot-highlight">
                <div class="rr-slot-icon">📅</div>
                <div class="rr-slot-details">
                    <p class="sd">Your Appointment Slot</p>
                    <p class="sv"><?php echo htmlspecialchars($appt["appointment_slot"]); ?></p>
                    <p class="sdate">📆 <?php echo $appt_date; ?></p>
                </div>
            </div>

            <!-- Applicant Details -->
            <div class="rr-section-title">Applicant Details</div>
            <div class="rr-grid">

                <div class="rr-field">
                    <div class="fl">Full Name</div>
                    <div class="fv"><?php echo htmlspecialchars($appt["applicant_name"]); ?></div>
                </div>

                <div class="rr-field">
                    <div class="fl">Mobile No.</div>
                    <div class="fv"><?php echo htmlspecialchars($appt["mobile_no"]); ?></div>
                </div>

                <div class="rr-field">
                    <div class="fl">Email Address</div>
                    <div class="fv"><?php echo htmlspecialchars($appt["email"]); ?></div>
                </div>

                <div class="rr-field">
                    <div class="fl">Service</div>
                    <div class="fv"><?php echo htmlspecialchars($appt["service"]); ?></div>
                </div>

            </div>

            <!-- Appointment Details -->
            <div class="rr-section-title">Appointment Details</div>
            <div class="rr-grid">

                <div class="rr-field">
                    <div class="fl">Appointment Date</div>
                    <div class="fv"><?php echo $appt_date; ?></div>
                </div>

                <div class="rr-field">
                    <div class="fl">Time Slot</div>
                    <div class="fv"><?php echo htmlspecialchars($appt["appointment_slot"]); ?></div>
                </div>

                <div class="rr-field">
                    <div class="fl">Reference No.</div>
                    <div class="fv" style="color:#00897b;"><?php echo htmlspecialchars($ref_no); ?></div>
                </div>

                <div class="rr-field">
                    <div class="fl">Booking Date</div>
                    <div class="fv"><?php echo $booked_on; ?></div>
                </div>

                <div class="rr-field">
                    <div class="fl">Status</div>
                    <div class="fv" style="color:#00897B;">✅ <?php echo htmlspecialchars($appt["status"]); ?></div>
                </div>

                <div class="rr-field">
                    <div class="fl">Booking ID</div>
                    <div class="fv">#<?php echo intval($appt["id"]); ?></div>
                </div>

            </div>

            <!-- Instructions -->
            <div class="rr-instructions">
                <h4>⚠️ Important Instructions</h4>
                <ul>
                    <li>Please arrive at the RTO office <strong>15 minutes</strong> before your scheduled slot.</li>
                    <li>Carry this receipt (printed or on mobile) for verification.</li>
                    <li>Bring all <strong>original documents</strong> related to your service.</li>
                    <li>Appointment is <strong>non-transferable</strong> and valid only for the booked date.</li>
                    <li>For rescheduling, visit the RTO office or contact helpline at least 24 hours prior.</li>
                </ul>
            </div>

        </div>

        <!-- ── FOOTER ── -->
        <div class="rr-card-footer">
            Transport Department – RTO &nbsp;|&nbsp;
            Ref: <?php echo htmlspecialchars($ref_no); ?> &nbsp;|&nbsp;
            This is a computer-generated receipt. No signature required.
        </div>

    </div>

    <!-- ── ACTIONS ── -->
    <div class="rr-actions">
        <button class="btn-print" onclick="window.print()">
            🖨️ Print / Download Receipt
        </button>
        <a href="Dashboard.php" class="btn-dash">
            🏠 Back to Dashboard
        </a>
    </div>

</div>

<script src="site-back-button.js?v=2" defer></script>
</body>
</html>
