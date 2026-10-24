<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: Login.php");
    exit();
}

if (!isset($_SESSION['appointment_service']) ||
    !isset($_SESSION['appointment_name'])) {

    header("Location: appointments.php");
    exit();
}

$message = "";

if (isset($_POST['choose_date'])) {

    $date = $_POST['appointment_date'] ?? '';

    if ($date == "") {

        $message = "Please select appointment date.";

    } elseif ($date < date('Y-m-d')) {

        $message = "Please select a future date.";

    } else {

        $_SESSION['appointment_date'] = $date;
        $_SESSION['appointment_step'] = 4;

        header("Location: appointment_slot.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Choose Appointment Date</title>

<link rel="stylesheet" href="appointment_date.css">

</head>

<body>

<div class="date-page">

    <section class="date-header">

        <span>STEP 3</span>

        <h1>Choose Date</h1>

        <p>
            Select a suitable date for your RTO appointment.
        </p>

    </section>


    <main class="date-main">


        <!-- STEPS -->

        <div class="date-steps">

            <div class="date-step completed">
                <b>✓</b>
                <span>Service</span>
            </div>

            <div class="date-line"></div>

            <div class="date-step completed">
                <b>✓</b>
                <span>Verify</span>
            </div>

            <div class="date-line"></div>

            <div class="date-step active">
                <b>3</b>
                <span>Date</span>
            </div>

            <div class="date-line"></div>

            <div class="date-step">
                <b>4</b>
                <span>Slot</span>
            </div>

        </div>


        <!-- CARD -->

        <div class="date-card">

            <div class="date-card-header">

                <span>STEP 3</span>

                <h2>Choose Appointment Date</h2>

                <p>
                    Select the date on which you want to visit the RTO office.
                </p>

            </div>


            <?php if ($message != "") { ?>

                <div class="date-error">
                    <?php echo htmlspecialchars($message); ?>
                </div>

            <?php } ?>


            <form method="POST">

                <label>
                    Appointment Date *
                </label>

                <input
                    type="date"
                    name="appointment_date"
                    min="<?php echo date('Y-m-d'); ?>"
                    required>


                <div class="date-buttons">

                    <a href="appointment_verify.php">
                        ← Back
                    </a>

                    <button
                        type="submit"
                        name="choose_date">

                        Continue to Slot →

                    </button>

                </div>

            </form>

        </div>

    </main>

</div>

<script src="site-back-button.js?v=2" defer></script>
</body>
</html>