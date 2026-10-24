<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: Login.php");
    exit();
}

if (!isset($_SESSION['appointment_service'])) {
    header("Location: appointments.php");
    exit();
}

$message = "";

if (isset($_POST['verify_applicant'])) {

    $name = trim($_POST['name'] ?? '');
    $mobileInput = $_POST['mobile'] ?? '';
    $mobile = trim(is_string($mobileInput) ? $mobileInput : '');
    $email = trim($_POST['email'] ?? '');

    if ($name == "" || $mobile == "" || $email == "") {

        $message = "Please fill all applicant details.";

    } elseif (!preg_match('/^[0-9]{10}$/', $mobile)) {

        $message = "Please enter a valid 10 digit mobile number.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";

    } else {

        $_SESSION['appointment_name'] = $name;
        $_SESSION['appointment_mobile'] = $mobile;
        $_SESSION['appointment_email'] = $email;

        $_SESSION['appointment_step'] = 3;

        header("Location: appointment_date.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Verify Applicant</title>

<link rel="stylesheet" href="appointment_verify.css">

</head>

<body>

<div class="verify-page">

    <section class="verify-header">

        <span>STEP 2</span>

        <h1>Verify Applicant</h1>

        <p>
            Enter your basic applicant information to continue.
        </p>

    </section>


    <main class="verify-main">

        <div class="verify-steps">

            <div class="mini-step completed">
                <b>✓</b>
                <span>Service</span>
            </div>

            <div class="mini-line"></div>

            <div class="mini-step active">
                <b>2</b>
                <span>Verify</span>
            </div>

            <div class="mini-line"></div>

            <div class="mini-step">
                <b>3</b>
                <span>Date</span>
            </div>

            <div class="mini-line"></div>

            <div class="mini-step">
                <b>4</b>
                <span>Slot</span>
            </div>

        </div>


        <div class="verify-card">

            <div class="verify-title">

                <span>STEP 2</span>

                <h2>Verify Applicant</h2>

                <p>
                    Please enter your basic information.
                </p>

            </div>


            <?php if ($message != "") { ?>

                <div class="verify-error">
                    <?php echo htmlspecialchars($message); ?>
                </div>

            <?php } ?>


            <form method="POST">

                <div class="input-group">

                    <label>Full Name *</label>

                    <input
                        type="text"
                        name="name"
                        placeholder="Enter your full name"
                        required>

                </div>


                <div class="input-group">

                    <label>Mobile Number *</label>

                    <input
                        type="text"
                        name="mobile"
                        maxlength="10"
                        placeholder="Enter 10 digit mobile number"
                        required>

                </div>


                <div class="input-group">

                    <label>Email Address *</label>

                    <input
                        type="email"
                        name="email"
                        placeholder="Enter your email"
                        required>

                </div>


                <div class="verify-buttons">

                    <a href="appointments.php">
                        ← Back
                    </a>

                    <button
                        type="submit"
                        name="verify_applicant">

                        Verify & Continue →

                    </button>

                </div>

            </form>

        </div>

    </main>

</div>

<script src="mobile-validation.js?v=1" defer></script>
<script src="site-back-button.js?v=2" defer></script>
</body>
</html>
