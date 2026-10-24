<?php
session_start();

include "connect.php";
mysqli_report(MYSQLI_REPORT_OFF);

if (!$con || !($con instanceof mysqli)) {
    http_response_code(503);
    exit("Database unavailable. Please start MySQL and try again.");
}

if (!isset($_SESSION['user_id'])) {
    header("Location: Login.php");
    exit();
}

if (!isset($_SESSION['appointment_service']) ||
    !isset($_SESSION['appointment_name']) ||
    !isset($_SESSION['appointment_date'])) {
    header("Location: appointments.php");
    exit();
}

$message = "";
$success = false;
$booking_id = 0;

/* =====================================================
   CONFIRM BOOKING
===================================================== */
if (isset($_POST['confirm_booking'])) {

    $slot = $_POST['slot'] ?? '';

    $allowed_slots = [
        "09:00 AM - 10:00 AM",
        "10:00 AM - 11:00 AM",
        "11:00 AM - 12:00 PM",
        "12:00 PM - 01:00 PM",
        "02:00 PM - 03:00 PM",
        "03:00 PM - 04:00 PM"
    ];

    if ($slot == "") {
        $message = "Please select an appointment slot.";
    } elseif (!in_array($slot, $allowed_slots)) {
        $message = "Please select a valid appointment slot.";
    } else {

        $_SESSION['appointment_slot'] = $slot;

        /* Save to DB */
        $user_id   = $_SESSION['user_id'];
        $service   = $_SESSION['appointment_service'];
        $name      = $_SESSION['appointment_name'];
        $mobile    = $_SESSION['appointment_mobile']  ?? '';
        $email     = $_SESSION['appointment_email']   ?? '';
        $appt_date = $_SESSION['appointment_date'];

        $stmt = mysqli_prepare($con,
            "INSERT INTO appointment_bookings
             (user_id, service, applicant_name, mobile_no, email, appointment_date, appointment_slot, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'Confirmed')"
        );

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "issssss",
                $user_id, $service, $name, $mobile, $email, $appt_date, $slot
            );
            if (mysqli_stmt_execute($stmt)) {
                $booking_id = mysqli_stmt_insert_id($stmt);
                $_SESSION['last_booking_id'] = $booking_id;
                $success = true;
            }
            mysqli_stmt_close($stmt);
        }

        if (!$success) {
            $message = "The appointment could not be saved. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Confirm Appointment Slot</title>
<link rel="stylesheet" href="appointment_slot.css">

<style>
/* ── SUCCESS OVERLAY ── */
.appt-success-wrap {
    min-height: 80vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
}

.appt-success-card {
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 16px 50px rgba(0,0,0,.12);
    max-width: 560px;
    width: 100%;
    overflow: hidden;
    animation: scaleIn .4s ease;
}

@keyframes scaleIn {
    from { transform: scale(.85); opacity: 0; }
    to   { transform: scale(1);   opacity: 1; }
}

.appt-success-top {
    background: linear-gradient(135deg, #00897B, #00796B);
    padding: 44px 40px 36px;
    text-align: center;
    color: #fff;
}

.appt-check-circle {
    width: 80px; height: 80px;
    background: rgba(255,255,255,.2);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 20px;
    font-size: 40px;
    animation: popIn .5s .2s ease both;
}

@keyframes popIn {
    from { transform: scale(0); }
    to   { transform: scale(1); }
}

.appt-success-top h2 {
    margin: 0 0 8px;
    font-size: 26px;
}

.appt-success-top p {
    margin: 0;
    opacity: .85;
    font-size: 15px;
}

/* booking details */
.appt-success-body {
    padding: 30px 36px 28px;
}

.appt-detail-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px 24px;
    margin-bottom: 24px;
}

.appt-detail-item .detail-label {
    font-size: 11px;
    font-weight: 700;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: .6px;
    margin-bottom: 4px;
}

.appt-detail-item .detail-value {
    font-size: 15px;
    font-weight: 600;
    color: #0f172a;
}

.appt-ref {
    background: #e0f2f1;
    border: 1px solid #80cbc4;
    border-radius: 10px;
    padding: 12px 16px;
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 22px;
    font-size: 13px;
    color: #00695C;
}

.appt-ref strong { font-size: 15px; }

/* redirect bar */
.appt-redirect-bar {
    background: #f8fafc;
    padding: 14px 36px;
    border-top: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
}

.appt-redirect-text {
    font-size: 13px;
    color: #64748b;
}

.appt-redirect-text span {
    font-weight: 700;
    color: #00897B;
}

.appt-progress {
    height: 4px;
    background: #e2e8f0;
    border-radius: 2px;
    overflow: hidden;
    width: 120px;
}

.appt-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #00897B, #4db6ac);
    border-radius: 2px;
    animation: countdown 3s linear forwards;
}

@keyframes countdown {
    from { width: 100%; }
    to   { width: 0%; }
}

.btn-go-dash {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: linear-gradient(135deg, #0284c7, #075985);
    color: #fff;
    padding: 10px 20px;
    border-radius: 9px;
    font-weight: 700;
    font-size: 14px;
    text-decoration: none;
    transition: .2s;
}

.btn-go-dash:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(2,132,199,.35);
}

.btn-view-receipt {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #e0f2f1;
    color: #00897B;
    border: 1px solid #80cbc4;
    padding: 10px 20px;
    border-radius: 9px;
    font-weight: 700;
    font-size: 14px;
    text-decoration: none;
    transition: .2s;
}

.btn-view-receipt:hover { background: #e0f2f1; }
</style>
</head>

<body>

<div class="slot-page">

    <?php if ($success): ?>

    <!-- =====================================================
         GREEN SUCCESS SCREEN
    ===================================================== -->
    <div class="appt-success-wrap">
    <div class="appt-success-card">

        <!-- Top green area -->
        <div class="appt-success-top">
            <div class="appt-check-circle">✅</div>
            <h2>Appointment Confirmed!</h2>
            <p>Your RTO appointment has been successfully booked.</p>
        </div>

        <!-- Details -->
        <div class="appt-success-body">

            <div class="appt-detail-grid">

                <div class="appt-detail-item">
                    <div class="detail-label">Service</div>
                    <div class="detail-value">
                        <?php echo htmlspecialchars($_SESSION['appointment_service'] ?? '—'); ?>
                    </div>
                </div>

                <div class="appt-detail-item">
                    <div class="detail-label">Applicant</div>
                    <div class="detail-value">
                        <?php echo htmlspecialchars($_SESSION['appointment_name'] ?? '—'); ?>
                    </div>
                </div>

                <div class="appt-detail-item">
                    <div class="detail-label">Date</div>
                    <div class="detail-value">
                        <?php
                        $d = $_SESSION['appointment_date'] ?? '';
                        echo $d ? date("d M Y", strtotime($d)) : '—';
                        ?>
                    </div>
                </div>

                <div class="appt-detail-item">
                    <div class="detail-label">Time Slot</div>
                    <div class="detail-value">
                        <?php echo htmlspecialchars($_SESSION['appointment_slot'] ?? '—'); ?>
                    </div>
                </div>

                <div class="appt-detail-item">
                    <div class="detail-label">Mobile</div>
                    <div class="detail-value">
                        <?php echo htmlspecialchars($_SESSION['appointment_mobile'] ?? '—'); ?>
                    </div>
                </div>

                <div class="appt-detail-item">
                    <div class="detail-label">Status</div>
                    <div class="detail-value" style="color:#00897B;">✅ Confirmed</div>
                </div>

            </div>

            <?php if (!empty($_SESSION['last_booking_id']) && $_SESSION['last_booking_id'] > 0): ?>
            <div class="appt-ref">
                <span>🎫</span>
                <div>
                    Booking Reference: &nbsp;
                    <strong>RTO-APPT-<?php echo str_pad($_SESSION['last_booking_id'], 6, "0", STR_PAD_LEFT); ?></strong>
                </div>
            </div>
            <?php endif; ?>

            <div style="display:flex;gap:12px;flex-wrap:wrap;">
                <?php if (!empty($_SESSION['last_booking_id']) && $_SESSION['last_booking_id'] > 0): ?>
                <a href="appointment_receipt.php?id=<?php echo intval($_SESSION['last_booking_id']); ?>"
                   class="btn-view-receipt">
                    🧾 View Receipt
                </a>
                <?php endif; ?>
                <a href="Dashboard.php" class="btn-go-dash">
                    🏠 Go to Dashboard
                </a>
            </div>

        </div>

        <!-- Auto redirect bar -->
        <div class="appt-redirect-bar">
            <div class="appt-redirect-text">
                Redirecting to Dashboard in <span id="countNum">3</span> seconds...
            </div>
            <div class="appt-progress">
                <div class="appt-progress-fill"></div>
            </div>
        </div>

    </div>
    </div>

    <script>
    /* countdown + redirect */
    var sec = 3;
    var el  = document.getElementById('countNum');
    var timer = setInterval(function(){
        sec--;
        if (el) el.textContent = sec;
        if (sec <= 0) {
            clearInterval(timer);
            window.location.href = 'Dashboard.php?appt=1';
        }
    }, 1000);
    </script>

    <?php else: ?>

    <!-- =====================================================
         SLOT BOOKING FORM
    ===================================================== -->


    <!-- HEADER -->

    <section class="slot-header">

        <span>
            STEP 4
        </span>

        <h1>
            Confirm Appointment Slot
        </h1>

        <p>
            Select your preferred appointment slot and confirm your booking.
        </p>

    </section>


    <main class="slot-main">


        <!-- 4 STEP PROCESS -->

        <div class="slot-steps">

            <!-- STEP 1 -->

            <div class="slot-step completed">

                <div class="slot-circle">
                    ✓
                </div>

                <span>
                    Service
                </span>

            </div>


            <div class="slot-line"></div>


            <!-- STEP 2 -->

            <div class="slot-step completed">

                <div class="slot-circle">
                    ✓
                </div>

                <span>
                    Verify
                </span>

            </div>


            <div class="slot-line"></div>


            <!-- STEP 3 -->

            <div class="slot-step completed">

                <div class="slot-circle">
                    ✓
                </div>

                <span>
                    Date
                </span>

            </div>


            <div class="slot-line"></div>


            <!-- STEP 4 -->

            <div class="slot-step active">

                <div class="slot-circle">
                    4
                </div>

                <span>
                    Slot
                </span>

            </div>

        </div>


        <!-- FINAL CARD -->

        <div class="slot-card">


            <div class="slot-card-header">

                <span>
                    STEP 4
                </span>

                <h2>
                    Appointment Booking Slot
                </h2>

                <p>
                    Choose your preferred time slot for the selected date.
                </p>

            </div>


            <!-- APPOINTMENT SUMMARY -->

            <div class="booking-summary">

                <div class="summary-item">

                    <small>
                        SERVICE
                    </small>

                    <strong>
                        <?php
                        echo htmlspecialchars(
                            $_SESSION['appointment_service']
                        );
                        ?>
                    </strong>

                </div>


                <div class="summary-item">

                    <small>
                        APPLICANT
                    </small>

                    <strong>
                        <?php
                        echo htmlspecialchars(
                            $_SESSION['appointment_name']
                        );
                        ?>
                    </strong>

                </div>


                <div class="summary-item">

                    <small>
                        DATE
                    </small>

                    <strong>
                        <?php
                        echo htmlspecialchars(
                            $_SESSION['appointment_date']
                        );
                        ?>
                    </strong>

                </div>

            </div>


            <?php if ($message != "") { ?>

                <div class="slot-error">
                    <?php echo htmlspecialchars($message); ?>
                </div>

            <?php } ?>


            <form method="POST">


                <div class="slot-heading">

                    <h3>
                        Select Available Slot
                    </h3>

                    <p>
                        Choose one available appointment time.
                    </p>

                </div>


                <!-- SLOTS -->

                <div class="slots-grid">


                    <label class="slot-option">

                        <input
                            type="radio"
                            name="slot"
                            value="09:00 AM - 10:00 AM"
                            required>

                        <span>
                            09:00 AM
                            <small>
                                - 10:00 AM
                            </small>
                        </span>

                    </label>


                    <label class="slot-option">

                        <input
                            type="radio"
                            name="slot"
                            value="10:00 AM - 11:00 AM">

                        <span>
                            10:00 AM
                            <small>
                                - 11:00 AM
                            </small>
                        </span>

                    </label>


                    <label class="slot-option">

                        <input
                            type="radio"
                            name="slot"
                            value="11:00 AM - 12:00 PM">

                        <span>
                            11:00 AM
                            <small>
                                - 12:00 PM
                            </small>
                        </span>

                    </label>


                    <label class="slot-option">

                        <input
                            type="radio"
                            name="slot"
                            value="12:00 PM - 01:00 PM">

                        <span>
                            12:00 PM
                            <small>
                                - 01:00 PM
                            </small>
                        </span>

                    </label>


                    <label class="slot-option">

                        <input
                            type="radio"
                            name="slot"
                            value="02:00 PM - 03:00 PM">

                        <span>
                            02:00 PM
                            <small>
                                - 03:00 PM
                            </small>
                        </span>

                    </label>


                    <label class="slot-option">

                        <input
                            type="radio"
                            name="slot"
                            value="03:00 PM - 04:00 PM">

                        <span>
                            03:00 PM
                            <small>
                                - 04:00 PM
                            </small>
                        </span>

                    </label>


                </div>


                <!-- BUTTONS -->

                <div class="slot-buttons">

                    <a href="appointment_date.php">
                        ← Back
                    </a>

                    <button
                        type="submit"
                        name="confirm_booking">

                        Confirm Appointment ✓

                    </button>

                </div>


            </form>

        </div>

    </main>

    <?php endif; /* success */ ?>

</div>

<script src="site-back-button.js?v=2" defer></script>
</body>
</html>