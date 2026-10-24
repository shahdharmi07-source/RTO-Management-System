<?php
$activeStep = isset($_GET['step']) ? (int)$_GET['step'] : 1;

if ($activeStep < 1) $activeStep = 1;
if ($activeStep > 4) $activeStep = 4;

$steps = [
    1 => [
        "title" => "Select Service",
        "description" => "Choose the RTO service for which you want to book an appointment."
    ],
    2 => [
        "title" => "Verify Applicant",
        "description" => "Enter your basic applicant details and verify your information."
    ],
    3 => [
        "title" => "Choose Date",
        "description" => "Select a suitable date and available appointment slot."
    ],
    4 => [
        "title" => "Confirm Slot",
        "description" => "Review your appointment details and confirm your booking."
    ]
];

$current = $steps[$activeStep];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Booking</title>
    <link rel="stylesheet" href="Appointment.css">
</head>
<body>

<div class="appointment-page">

    <!-- Header -->
    <section class="appointment-header">
        <div class="appointment-header-content">

            <span class="appointment-label">
                RTO TRANSPORT SERVICES
            </span>

            <h1>Appointment Booking</h1>

            <p>
                Book your appointment for RTO services quickly and easily.
                Follow the simple four-step process below.
            </p>

        </div>
    </section>

    <!-- Main Content -->
    <main class="appointment-main">

        <!-- Title -->
        <div class="appointment-title">

            <span>ONLINE APPOINTMENT</span>

            <h2>Book Your RTO Appointment</h2>

            <p>
                Complete the following four steps to schedule your appointment.
            </p>

        </div>

        <!-- Steps -->
        <div class="appointment-process">

            <?php foreach ($steps as $id => $step) { ?>

                <div class="appointment-step <?php echo ($activeStep == $id) ? 'active' : ''; ?>">

                    <a href="?step=<?php echo $id; ?>" style="text-decoration:none;color:inherit;display:block;">

                        <div class="step-number">
                            <?php
                            if ($activeStep > $id) {
                                echo "✓";
                            } else {
                                echo $id;
                            }
                            ?>
                        </div>

                        <div class="appointment-step-content">

                            <h3><?php echo $step['title']; ?></h3>

                            <p><?php echo $step['description']; ?></p>

                        </div>

                    </a>

                </div>

                <?php if ($id < 4) { ?>
                    <div class="step-arrow">
                        ➜
                    </div>
                <?php } ?>

            <?php } ?>

        </div>

        <!-- Selected Step -->
        <div class="selected-step-card">

            <div class="selected-step-content">

                <span>
                    STEP <?php echo $activeStep; ?>
                </span>

                <h3>
                    <?php echo $current['title']; ?>
                </h3>

                <p>
                    <?php echo $current['description']; ?>
                </p>

            </div>

            <?php if ($activeStep < 4) { ?>
       
                <a href="?step=<?php echo $activeStep + 1; ?>">
                    <button class="appointment-next-btn">
                        Continue →
                    </button>
                </a>

            <?php } else { ?>
                 <a class="dl-action-btn" href="appointments.php">
                <button class="appointment-next-btn">
                    Confirm Appointment
                </button>

            <?php } ?>

        </div>

        <!-- Information -->
        <div class="appointment-info">

            <p>
                ✓ Please keep your valid identity proof and required documents
                ready before starting the appointment booking process.
            </p>

        </div>

    </main>

</div>

<script src="site-back-button.js?v=2" defer></script>
</body>
</html>