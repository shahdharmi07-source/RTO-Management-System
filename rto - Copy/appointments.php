<?php

session_start();

include "connect.php";

/* =====================================================
   LOGIN CHECK
===================================================== */

if (!isset($_SESSION["user_id"])) {

    header("Location: Login.php");
    exit();

}

if (isset($_GET["new"]) && $_GET["new"] === "1") {
    unset(
        $_SESSION["appointment_service"],
        $_SESSION["appointment_step1_completed"],
        $_SESSION["appointment_step"],
        $_SESSION["appointment_name"],
        $_SESSION["appointment_applicant_name"],
        $_SESSION["appointment_mobile"],
        $_SESSION["appointment_email"],
        $_SESSION["appointment_date"],
        $_SESSION["appointment_slot"],
        $_SESSION["last_booking_id"]
    );
}

$user_id = $_SESSION["user_id"];

$message = "";


/* =====================================================
   FETCH LOGGED-IN USER
===================================================== */

$stmtUser = $con->prepare(
    "SELECT full_name, mobile
     FROM users
     WHERE id = ?
     LIMIT 1"
);

if ($stmtUser) {

    $stmtUser->bind_param(
        "i",
        $user_id
    );

    $stmtUser->execute();

    $userResult = $stmtUser->get_result();

    if ($userResult->num_rows == 1) {

        $user = $userResult->fetch_assoc();

        /*
         * Save user details in session.
         * These will be used in Step 2 and Step 4.
         */

        $_SESSION["appointment_applicant_name"] =
            $user["full_name"];
        $_SESSION["appointment_name"] =
            $user["full_name"];

        $_SESSION["appointment_mobile"] =
            $user["mobile"];

    }

}


/* =====================================================
   STEP 1 SUBMIT
===================================================== */

if (isset($_POST["continue_step"])) {

    $service = trim(
        $_POST["service"] ?? ""
    );


    /* =================================================
       ALLOWED SERVICES
    ================================================= */

    $allowed_services = [

        "Driving Licence",

        "Vehicle Registration",

        "Licence Renewal",

        "Vehicle Transfer",

        "Driving Test",

        "Other RTO Service"

    ];


    /* =================================================
       VALIDATION
    ================================================= */

    if ($service == "") {

        $message =
            "Please select a service.";

    }

    elseif (!in_array(
        $service,
        $allowed_services
    )) {

        $message =
            "Please select a valid RTO service.";

    }

    else {

        /* =============================================
           SAVE STEP 1 DATA
        ============================================= */

        $_SESSION["appointment_service"] =
            $service;

        $_SESSION["appointment_step1_completed"] =
            true;

        $_SESSION["appointment_step"] =
            2;


        /* =============================================
           GO TO STEP 2
        ============================================= */

        header(
            "Location: appointment_verify.php"
        );

        exit();

    }

}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        RTO Appointment Booking - Step 1
    </title>


    <link
        rel="stylesheet"
        href="appointments.css"
    >

</head>


<body>


<div class="appointment-page">


    <!-- =================================================
         HERO
    ================================================== -->

    <section class="appointment-hero">


        <div class="hero-content">


            <div class="hero-badge">

                RTO TRANSPORT SERVICES

            </div>


            <h1>

                Appointment Booking

            </h1>


            <p>

                Book your appointment for RTO services
                quickly and easily.

                Follow the simple four-step process below.

            </p>


        </div>


    </section>



    <!-- =================================================
         MAIN
    ================================================== -->

    <main class="appointment-main">


        <!-- PAGE HEADING -->

        <div class="page-heading">


            <span>

                ONLINE APPOINTMENT

            </span>


            <h2>

                Book Your RTO Appointment

            </h2>


            <p>

                Complete the following four steps
                to schedule your appointment.

            </p>


        </div>



        <!-- =================================================
             STEP PROCESS
        ================================================== -->

        <div class="steps-wrapper">


            <!-- STEP 1 -->

            <div class="appointment-step active">


                <div class="step-circle">

                    1

                </div>


                <div class="step-text">


                    <small>

                        STEP 1

                    </small>


                    <strong>

                        Select Service

                    </strong>


                </div>


            </div>



            <div class="step-line"></div>



            <!-- STEP 2 -->

            <div class="appointment-step">


                <div class="step-circle">

                    2

                </div>


                <div class="step-text">


                    <small>

                        STEP 2

                    </small>


                    <strong>

                        Verify Applicant

                    </strong>


                </div>


            </div>



            <div class="step-line"></div>



            <!-- STEP 3 -->

            <div class="appointment-step">


                <div class="step-circle">

                    3

                </div>


                <div class="step-text">


                    <small>

                        STEP 3

                    </small>


                    <strong>

                        Choose Date

                    </strong>


                </div>


            </div>



            <div class="step-line"></div>



            <!-- STEP 4 -->

            <div class="appointment-step">


                <div class="step-circle">

                    4

                </div>


                <div class="step-text">


                    <small>

                        STEP 4

                    </small>


                    <strong>

                        Confirm Slot

                    </strong>


                </div>


            </div>


        </div>



        <!-- =================================================
             SERVICE CARD
        ================================================== -->

        <div class="service-card">


            <!-- LEFT -->

            <div class="service-left">


                <div class="service-icon">

                    📅

                </div>


                <span>

                    STEP 1

                </span>


                <h2>

                    Select Service

                </h2>


                <p>

                    Choose the RTO service for which
                    you want to book an appointment.

                </p>


            </div>



            <!-- RIGHT -->

            <div class="service-right">


                <h3>

                    Choose RTO Service

                </h3>


                <p class="subtitle">

                    Select the service you want to book.

                </p>



                <!-- ERROR -->

                <?php if ($message != "") { ?>


                    <div class="error-message">


                        <?php

                        echo htmlspecialchars(
                            $message
                        );

                        ?>


                    </div>


                <?php } ?>



                <!-- FORM -->

                <form method="POST">


                    <div class="form-group">


                        <label for="service">

                            RTO Service *

                        </label>


                        <select
                            name="service"
                            id="service"
                            required
                        >


                            <option value="">

                                Select Service

                            </option>


                            <option
                                value="Driving Licence"
                                <?php
                                if (
                                    ($_POST["service"] ?? "")
                                    == "Driving Licence"
                                ) {
                                    echo "selected";
                                }
                                ?>
                            >

                                Driving Licence

                            </option>


                            <option
                                value="Vehicle Registration"
                                <?php
                                if (
                                    ($_POST["service"] ?? "")
                                    == "Vehicle Registration"
                                ) {
                                    echo "selected";
                                }
                                ?>
                            >

                                Vehicle Registration

                            </option>


                            <option
                                value="Licence Renewal"
                                <?php
                                if (
                                    ($_POST["service"] ?? "")
                                    == "Licence Renewal"
                                ) {
                                    echo "selected";
                                }
                                ?>
                            >

                                Licence Renewal

                            </option>


                            <option
                                value="Vehicle Transfer"
                                <?php
                                if (
                                    ($_POST["service"] ?? "")
                                    == "Vehicle Transfer"
                                ) {
                                    echo "selected";
                                }
                                ?>
                            >

                                Vehicle Transfer

                            </option>


                            <option
                                value="Driving Test"
                                <?php
                                if (
                                    ($_POST["service"] ?? "")
                                    == "Driving Test"
                                ) {
                                    echo "selected";
                                }
                                ?>
                            >

                                Driving Test

                            </option>


                            <option
                                value="Other RTO Service"
                                <?php
                                if (
                                    ($_POST["service"] ?? "")
                                    == "Other RTO Service"
                                ) {
                                    echo "selected";
                                }
                                ?>
                            >

                                Other RTO Service

                            </option>


                        </select>


                    </div>



                    <!-- NAVIGATION -->

                    <div class="navigation">


                        <a
                            href="Dashboard.php"
                            class="back-btn"
                        >

                            ← Back

                        </a>


                        <button
                            type="submit"
                            name="continue_step"
                            class="next-btn"
                        >

                            Continue →

                        </button>


                    </div>


                </form>


            </div>


        </div>



        <!-- =================================================
             INFORMATION
        ================================================== -->

        <div class="info-box">


            <div class="info-icon">

                i

            </div>


            <div>


                <h3>

                    Appointment Information

                </h3>


                <p>

                    Please keep your personal information
                    and required documents ready before
                    booking your appointment.

                </p>


            </div>


        </div>


    </main>


</div>


<script src="site-back-button.js?v=3" defer></script>
</body>

</html>