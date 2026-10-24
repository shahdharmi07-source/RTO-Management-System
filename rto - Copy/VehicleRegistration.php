<?php

error_reporting(E_ALL);
ini_set("display_errors", 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

mysqli_report(MYSQLI_REPORT_OFF);

include("connect.php");

if (!$con || !($con instanceof mysqli)) {
    http_response_code(503);
    exit("Database unavailable. Please start MySQL and try again.");
}


/* =====================================================
   LOGIN CHECK
===================================================== */

if (!isset($_SESSION["user_id"])) {
    header("Location: Login.php");
    exit();
}

$user_id = intval($_SESSION["user_id"]);

$message = "";
$messageType = "";
$payment_method = "";

if (isset($_POST["pay_registration"])) {
    $vehicle_id = (int) ($_SESSION["reg_vehicle_id"] ?? 0);
    $payment_method = trim($_POST["payment_method"] ?? "");
    $allowed_methods = ["UPI", "Debit Card", "Credit Card", "Net Banking"];
    $payment_error = "";
    $bank_name = null;
    $bank_reference = null;

    if ($vehicle_id < 1) {
        $payment_error = "Vehicle registration could not be found.";
    } elseif (!in_array($payment_method, $allowed_methods, true)) {
        $payment_error = "Please select a payment method.";
    } elseif (in_array($payment_method, ["Debit Card", "Credit Card"], true)) {
        $card_name = trim($_POST["card_name"] ?? "");
        $card_number = preg_replace('/\\s+/', '', $_POST["card_number"] ?? "");
        $expiry = trim($_POST["expiry"] ?? "");
        $cvv = trim($_POST["cvv"] ?? "");

        if ($card_name === "") {
            $payment_error = "Please enter the card holder name.";
        } elseif (!preg_match('/^[0-9]{16}$/', $card_number)) {
            $payment_error = "Card number must be exactly 16 digits.";
        } elseif (!preg_match('/^(0[1-9]|1[0-2])\\/[0-9]{2}$/', $expiry)) {
            $payment_error = "Enter expiry date in MM/YY format.";
        } elseif (!preg_match('/^[0-9]{3}$/', $cvv)) {
            $payment_error = "CVV must be exactly 3 digits.";
        }
    } elseif ($payment_method === "UPI") {
        $upi_id = trim($_POST["upi_id"] ?? "");
        if (!preg_match('/^[a-zA-Z0-9._-]+@[a-zA-Z]{2,}$/', $upi_id)) {
            $payment_error = "Please enter a valid UPI ID.";
        }
    } elseif ($payment_method === "Net Banking") {
        $bank_name = trim($_POST["bank"] ?? "");
        $bank_reference = strtoupper(trim($_POST["bank_reference"] ?? ""));
        if (!in_array($bank_name, ["SBI", "HDFC", "ICICI", "Axis", "BOB"], true)) {
            $payment_error = "Please select a valid bank.";
        } elseif (!preg_match('/^[A-Z0-9-]{6,24}$/', $bank_reference)) {
            $payment_error = "Enter a demo transaction reference (6-24 letters, numbers or hyphens).";
        }
    }

    if ($payment_error !== "") {
        $message = $payment_error;
        $messageType = "error";
    } else {
        $payment_reference = "RTO-VEH-" . strtoupper(bin2hex(random_bytes(5)));
        $payment_stmt = mysqli_prepare(
            $con,
            "UPDATE vehicles
             SET registration_payment_status = 'Paid',
                 registration_payment_method = ?,
                 registration_payment_reference = ?,
                 registration_bank_name = ?,
                 registration_bank_reference = ?,
                 registration_paid_at = CURRENT_TIMESTAMP
             WHERE id = ? AND user_id = ?
               AND (registration_payment_status IS NULL OR registration_payment_status = 'Pending')"
        );

        if ($payment_stmt) {
            mysqli_stmt_bind_param(
                $payment_stmt,
                "ssssii",
                $payment_method,
                $payment_reference,
                $bank_name,
                $bank_reference,
                $vehicle_id,
                $user_id
            );
            mysqli_stmt_execute($payment_stmt);
            $payment_saved = mysqli_stmt_affected_rows($payment_stmt) === 1;
            mysqli_stmt_close($payment_stmt);

            if ($payment_saved) {
                header("Location: payment_success.php?type=vehicle&vehicle_id=" . $vehicle_id);
                exit();
            }
        }

        $message = "Payment could not be recorded. Please check the vehicle payment status and retry.";
        $messageType = "error";
    }
}


/* =====================================================
   STEP 4 - INSPECTION BOOKING
===================================================== */

if (isset($_POST["book_inspection"])) {

    $rto_center      = trim($_POST["rto_center"] ?? "");
    $inspection_date = trim($_POST["inspection_date"] ?? "");
    $inspection_time = trim($_POST["inspection_time"] ?? "");
    $vehicle_id      = intval($_POST["vehicle_id"] ?? 0);

    if ($rto_center == "" || $inspection_date == "" || $inspection_time == "") {

        $message = "Please fill all inspection fields.";
        $messageType = "error";

    } elseif (date("l", strtotime($inspection_date)) == "Sunday") {

        $message = "Sunday slots are not available.";
        $messageType = "error";

    } elseif (strtotime($inspection_date) < strtotime(date("Y-m-d"))) {

        $message = "Please select a future date.";
        $messageType = "error";

    } else {

        $sql = "
            UPDATE vehicles
            SET
                inspection_center = ?,
                inspection_date = ?,
                inspection_time = ?,
                status = 'Inspection Scheduled'
            WHERE id = ?
            AND user_id = ?
        ";

        $stmt = mysqli_prepare($con, $sql);

        if ($stmt) {

            mysqli_stmt_bind_param(
                $stmt,
                "sssii",
                $rto_center,
                $inspection_date,
                $inspection_time,
                $vehicle_id,
                $user_id
            );

            if (mysqli_stmt_execute($stmt)) {

                $_SESSION["reg_success_vehicle_id"] = $vehicle_id;

                mysqli_stmt_close($stmt);

                header("Location: Dashboard.php?registered=1");
                exit();

            } else {

                $message = "Unable to book inspection: " .
                           mysqli_stmt_error($stmt);

                $messageType = "error";

                mysqli_stmt_close($stmt);
            }

        } else {

            $message = "Database Error: " . mysqli_error($con);
            $messageType = "error";
        }
    }
}


/* =====================================================
   STEP 1 - VEHICLE REGISTRATION SUBMIT
===================================================== */

if (isset($_POST["btnsubmit"])) {

    $vehicle_no = strtoupper(
        trim($_POST["vehicle_no"] ?? "")
    );

    $owner_name = trim(
        $_POST["owner_name"] ?? ""
    );

    $vehicle_type = trim(
        $_POST["vehicle_type"] ?? ""
    );

    $color = trim(
        $_POST["color"] ?? ""
    );

    $mobile = trim(
        $_POST["Mobile"] ?? ""
    );

    $vehicle_model = trim(
        $_POST["vehicle_model"] ?? ""
    );

    $fuel_type = trim(
        $_POST["fuel_type"] ?? ""
    );

    $chassis_no = trim(
        $_POST["chassis_no"] ?? ""
    );

    $engine_no = trim(
        $_POST["engine_no"] ?? ""
    );


    /* =================================================
       VALIDATION
    ================================================= */

    if ($vehicle_no == "") {

        $message = "Please enter vehicle number.";
        $messageType = "error";

    } elseif (
        !preg_match(
            "/^[A-Z]{2}[0-9]{1,2}[A-Z]{1,3}[0-9]{1,4}$/",
            $vehicle_no
        )
    ) {

        $message = "Enter valid vehicle number. Example: GJ03AB1234";
        $messageType = "error";

    } elseif ($owner_name == "") {

        $message = "Please enter owner name.";
        $messageType = "error";

    } elseif (
        !preg_match("/^[a-zA-Z ]+$/", $owner_name)
    ) {

        $message = "Owner name should contain only letters.";
        $messageType = "error";

    } elseif ($vehicle_type == "") {

        $message = "Please select vehicle type.";
        $messageType = "error";

    } elseif ($color == "") {

        $message = "Please enter vehicle color.";
        $messageType = "error";

    } elseif (
        !preg_match("/^[a-zA-Z ]+$/", $color)
    ) {

        $message = "Vehicle color should contain only letters.";
        $messageType = "error";

    } elseif ($mobile == "") {

        $message = "Please enter mobile number.";
        $messageType = "error";

    } elseif (
        !preg_match("/^[0-9]{10}$/", $mobile)
    ) {

        $message = "Mobile number must contain exactly 10 digits.";
        $messageType = "error";

    } elseif (
        !isset($_FILES["proof_identity"]) ||
        !isset($_FILES["vehicle_invoice"]) ||
        !isset($_FILES["certificate"])
    ) {

        $message = "Please upload all required documents.";
        $messageType = "error";

    } else {

        /* =================================================
           FILE INFORMATION
        ================================================= */

        $proof_name = $_FILES["proof_identity"]["name"];
        $proof_tmp = $_FILES["proof_identity"]["tmp_name"];
        $proof_error = $_FILES["proof_identity"]["error"];

        $invoice_name = $_FILES["vehicle_invoice"]["name"];
        $invoice_tmp = $_FILES["vehicle_invoice"]["tmp_name"];
        $invoice_error = $_FILES["vehicle_invoice"]["error"];

        $certificate_name = $_FILES["certificate"]["name"];
        $certificate_tmp = $_FILES["certificate"]["tmp_name"];
        $certificate_error = $_FILES["certificate"]["error"];


        $proof_ext = strtolower(
            pathinfo($proof_name, PATHINFO_EXTENSION)
        );

        $invoice_ext = strtolower(
            pathinfo($invoice_name, PATHINFO_EXTENSION)
        );

        $certificate_ext = strtolower(
            pathinfo($certificate_name, PATHINFO_EXTENSION)
        );


        $allowed_extensions = [
            "pdf",
            "jpg",
            "jpeg",
            "png"
        ];


        if (
            $proof_error != 0 ||
            $invoice_error != 0 ||
            $certificate_error != 0
        ) {

            $message = "Please select all required documents.";
            $messageType = "error";

        } elseif (
            !in_array($proof_ext, $allowed_extensions) ||
            !in_array($invoice_ext, $allowed_extensions) ||
            !in_array($certificate_ext, $allowed_extensions)
        ) {

            $message =
                "Only PDF, JPG, JPEG and PNG files are allowed.";

            $messageType = "error";

        } else {

            /* =================================================
               CHECK DUPLICATE VEHICLE
            ================================================= */

            $check_sql = "
                SELECT id
                FROM vehicles
                WHERE vehicle_no = ?
                LIMIT 1
            ";

            $check_stmt = mysqli_prepare(
                $con,
                $check_sql
            );


            if (!$check_stmt) {

                $message =
                    "Database Error: " . mysqli_error($con);

                $messageType = "error";

            } else {

                mysqli_stmt_bind_param(
                    $check_stmt,
                    "s",
                    $vehicle_no
                );

                mysqli_stmt_execute(
                    $check_stmt
                );

                $check_result =
                    mysqli_stmt_get_result($check_stmt);


                if (
                    mysqli_num_rows($check_result) > 0
                ) {

                    $message =
                        "This vehicle number is already registered.";

                    $messageType = "error";

                } else {

                    /* =================================================
                       CREATE UPLOAD FOLDER
                    ================================================= */

                    $upload_folder = "uploads/";

                    if (!is_dir($upload_folder)) {

                        mkdir(
                            $upload_folder,
                            0777,
                            true
                        );
                    }


                    $unique =
                        time() . "_" . rand(1000, 9999);


                    $proof_new_name =
                        "proof_" .
                        $unique .
                        "." .
                        $proof_ext;


                    $invoice_new_name =
                        "invoice_" .
                        $unique .
                        "." .
                        $invoice_ext;


                    $certificate_new_name =
                        "certificate_" .
                        $unique .
                        "." .
                        $certificate_ext;


                    $proof_path =
                        $upload_folder .
                        $proof_new_name;


                    $invoice_path =
                        $upload_folder .
                        $invoice_new_name;


                    $certificate_path =
                        $upload_folder .
                        $certificate_new_name;


                    /* =================================================
                       UPLOAD FILES
                    ================================================= */

                    $proof_uploaded =
                        move_uploaded_file(
                            $proof_tmp,
                            $proof_path
                        );


                    $invoice_uploaded =
                        move_uploaded_file(
                            $invoice_tmp,
                            $invoice_path
                        );


                    $certificate_uploaded =
                        move_uploaded_file(
                            $certificate_tmp,
                            $certificate_path
                        );


                    if (
                        !$proof_uploaded ||
                        !$invoice_uploaded ||
                        !$certificate_uploaded
                    ) {

                        $message =
                            "Document upload failed. Please try again.";

                        $messageType = "error";

                    } else {

                        /* =================================================
                           INSERT VEHICLE
                        ================================================= */

                        $registration_date =
                            date("Y-m-d");

                        $status = "Pending";
                        $registration_payment_status = "Pending";


                        $insert_sql = "
                            INSERT INTO vehicles
                            (
                                user_id,
                                vehicle_no,
                                owner_name,
                                vehicle_type,
                                vehicle_model,
                                fuel_type,
                                chassis_no,
                                engine_no,
                                registration_date,
                                status,
                                registration_payment_status
                            )
                            VALUES
                            (
                                ?,
                                ?,
                                ?,
                                ?,
                                ?,
                                ?,
                                ?,
                                ?,
                                ?,
                                ?,
                                ?
                            )
                        ";


                        $insert_stmt =
                            mysqli_prepare(
                                $con,
                                $insert_sql
                            );


                        if (!$insert_stmt) {

                            $message =
                                "Database Error: " .
                                mysqli_error($con);

                            $messageType = "error";

                        } else {

                            mysqli_stmt_bind_param(
                                $insert_stmt,
                                "issssssssss",
                                $user_id,
                                $vehicle_no,
                                $owner_name,
                                $vehicle_type,
                                $vehicle_model,
                                $fuel_type,
                                $chassis_no,
                                $engine_no,
                                $registration_date,
                                $status,
                                $registration_payment_status
                            );


                            if (
                                mysqli_stmt_execute(
                                    $insert_stmt
                                )
                            ) {

                                $new_vehicle_id =
                                    mysqli_insert_id($con);


                                $_SESSION["reg_vehicle_id"] =
                                    $new_vehicle_id;


                                mysqli_stmt_close(
                                    $insert_stmt
                                );

                                mysqli_stmt_close(
                                    $check_stmt
                                );


                                header(
                                    "Location: vehicleRegistration.php?step=3"
                                );

                                exit();

                            } else {

                                $message =
                                    "Database Error: " .
                                    mysqli_stmt_error(
                                        $insert_stmt
                                    );

                                $messageType = "error";
                            }


                            mysqli_stmt_close(
                                $insert_stmt
                            );
                        }
                    }
                }


                mysqli_stmt_close(
                    $check_stmt
                );
            }
        }
    }
}


/* =====================================================
   CURRENT STEP
===================================================== */

$currentStep = isset($_GET["step"])
    ? intval($_GET["step"])
    : 1;


if ($currentStep < 1 || $currentStep > 4) {
    $currentStep = 1;
}


/* =====================================================
   GET REGISTERED VEHICLE
===================================================== */

$vehicle = null;

if (isset($_SESSION["reg_vehicle_id"])) {

    $reg_vehicle_id =
        intval($_SESSION["reg_vehicle_id"]);

    $vehicle_sql = "
        SELECT *
        FROM vehicles
        WHERE id = ?
        AND user_id = ?
        LIMIT 1
    ";

    $vehicle_stmt =
        mysqli_prepare(
            $con,
            $vehicle_sql
        );

    if ($vehicle_stmt) {

        mysqli_stmt_bind_param(
            $vehicle_stmt,
            "ii",
            $reg_vehicle_id,
            $user_id
        );

        mysqli_stmt_execute(
            $vehicle_stmt
        );

        $vehicle_result =
            mysqli_stmt_get_result(
                $vehicle_stmt
            );

        $vehicle =
            mysqli_fetch_assoc(
                $vehicle_result
            );

        mysqli_stmt_close(
            $vehicle_stmt
        );
    }
}

if (
    $currentStep === 4 &&
    $vehicle &&
    ($vehicle["registration_payment_status"] ?? "") !== "Paid"
) {
    header("Location: VehicleRegistration.php?step=3");
    exit();
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

    <title>Vehicle Registration</title>

    <link
        rel="stylesheet"
        href="VehicleRegistration.css?v=2"
    >

</head>


<body>


<!-- =====================================================
     PAGE CONTAINER
===================================================== -->

<div class="registration-container">

    <div class="registration-top-header">

        <!-- =================================================
             HEADER
        ================================================== -->

        <div class="registration-header">

            <h1>Vehicle Registration</h1>

            <p>
                Complete all steps to register your vehicle.
            </p>

        </div>


        <!-- =================================================
             STEP INDICATOR
        ================================================== -->

        <div class="steps-container">

            <div class="step <?php echo ($currentStep >= 1) ? 'active' : ''; ?>">

                <div class="step-number">1</div>

                <span>Application Details</span>

            </div>


            <div class="step-line"></div>


            <div class="step <?php echo ($currentStep >= 2) ? 'active' : ''; ?>">

                <div class="step-number">2</div>

                <span>Documents</span>

            </div>


            <div class="step-line"></div>


            <div class="step <?php echo ($currentStep >= 3) ? 'active' : ''; ?>">

                <div class="step-number">3</div>

                <span>Payment</span>

            </div>


            <div class="step-line"></div>


            <div class="step <?php echo ($currentStep >= 4) ? 'active' : ''; ?>">

                <div class="step-number">4</div>

                <span>Inspection</span>

            </div>

        </div>
    </div>


    <!-- =================================================
         MESSAGE
    ================================================== -->

    <?php if ($message != "") { ?>

        <div class="message <?php echo $messageType; ?>">

            <?php echo htmlspecialchars($message); ?>

        </div>

    <?php } ?>


    <!-- =================================================
         STEP 1
    ================================================== -->

    <?php if ($currentStep == 1) { ?>

        <div class="registration-card">

            <h2>Vehicle & Owner Details</h2>

            <p class="section-description">
                Enter your vehicle and owner information carefully.
            </p>


            <form
                method="POST"
                enctype="multipart/form-data"
            >


                <div class="form-grid">


                    <div class="form-group">

                        <label>
                            Vehicle Number
                        </label>

                        <input
                            type="text"
                            name="vehicle_no"
                            placeholder="Example: GJ03AB1234"
                            maxlength="12"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Owner Name
                        </label>

                        <input
                            type="text"
                            name="owner_name"
                            placeholder="Enter owner name"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Vehicle Type
                        </label>

                        <select
                            name="vehicle_type"
                            required
                        >

                            <option value="">
                                Select Vehicle Type
                            </option>

                            <option value="Two Wheeler">
                                Two Wheeler
                            </option>

                            <option value="Four Wheeler">
                                Four Wheeler
                            </option>

                            <option value="Transport Vehicle">
                                Transport Vehicle
                            </option>

                        </select>

                    </div>


                    <div class="form-group">

                        <label>
                            Vehicle Model
                        </label>

                        <input
                            type="text"
                            name="vehicle_model"
                            placeholder="Example: Honda City"
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Fuel Type
                        </label>

                        <select
                            name="fuel_type"
                        >

                            <option value="">
                                Select Fuel Type
                            </option>

                            <option value="Petrol">
                                Petrol
                            </option>

                            <option value="Diesel">
                                Diesel
                            </option>

                            <option value="CNG">
                                CNG
                            </option>

                            <option value="Electric">
                                Electric
                            </option>

                        </select>

                    </div>


                    <div class="form-group">

                        <label>
                            Vehicle Color
                        </label>

                        <input
                            type="text"
                            name="color"
                            placeholder="Example: White"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Mobile Number
                        </label>

                        <input
                            type="text"
                            name="Mobile"
                            placeholder="10 digit mobile number"
                            maxlength="10"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Chassis Number
                        </label>

                        <input
                            type="text"
                            name="chassis_no"
                            placeholder="Enter chassis number"
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Engine Number
                        </label>

                        <input
                            type="text"
                            name="engine_no"
                            placeholder="Enter engine number"
                        >

                    </div>


                    <!-- =================================
                         DOCUMENTS
                    ================================== -->

                    <div class="form-group full-width">

                        <label>
                            Identity Proof
                        </label>

                        <input
                            type="file"
                            name="proof_identity"
                            accept=".pdf,.jpg,.jpeg,.png"
                            required
                        >

                    </div>


                    <div class="form-group full-width">

                        <label>
                            Vehicle Invoice
                        </label>

                        <input
                            type="file"
                            name="vehicle_invoice"
                            accept=".pdf,.jpg,.jpeg,.png"
                            required
                        >

                    </div>


                    <div class="form-group full-width">

                        <label>
                            Vehicle Certificate
                        </label>

                        <input
                            type="file"
                            name="certificate"
                            accept=".pdf,.jpg,.jpeg,.png"
                            required
                        >

                    </div>


                </div>


                <div class="form-actions">

                    <a
                        href="Dashboard.php"
                        class="secondary-button"
                    >
                        Cancel
                    </a>


                    <button
                        type="submit"
                        name="btnsubmit"
                        class="primary-button"
                    >
                        Submit & Continue
                    </button>

                </div>


            </form>

        </div>

    <?php } ?>


    <!-- =================================================
         STEP 3 - PAYMENT
    ================================================== -->

    <?php if ($currentStep == 3) { ?>

        <div class="registration-card">

            <h2>Registration Fee Payment</h2>

            <p class="section-description">
                Review the registration fee and continue to vehicle inspection.
            </p>


            <div class="payment-box">

                <div class="payment-row">

                    <span>
                        Vehicle Registration Fee
                    </span>

                    <strong>
                        ₹1500
                    </strong>

                </div>


                <div class="payment-row total-row">

                    <span>
                        Total Amount
                    </span>

                    <strong>
                        ₹1500
                    </strong>

                </div>

            </div>


            <?php if ($vehicle) { ?>
            <form method="POST" action="VehicleRegistration.php?step=3" id="registrationPaymentForm">
                <div class="payment-methods">
                    <h3>Select Payment Method</h3>
                    <label><input type="radio" name="payment_method" value="UPI" required onchange="updateRegistrationPaymentFields()"> UPI</label>
                    <label><input type="radio" name="payment_method" value="Debit Card" required onchange="updateRegistrationPaymentFields()"> Debit Card</label>
                    <label><input type="radio" name="payment_method" value="Credit Card" required onchange="updateRegistrationPaymentFields()"> Credit Card</label>
                    <label><input type="radio" name="payment_method" value="Net Banking" required onchange="updateRegistrationPaymentFields()"> Net Banking</label>
                </div>

                <div class="registration-payment-fields" data-registration-fields="upi" hidden>
                    <label>UPI ID *<input type="text" name="upi_id" placeholder="example@upi" autocomplete="off"></label>
                </div>

                <div class="registration-payment-fields" data-registration-fields="card" hidden>
                    <label>Card Holder Name *<input type="text" name="card_name" autocomplete="cc-name"></label>
                    <label>Card Number *<input type="text" name="card_number" inputmode="numeric" maxlength="19" placeholder="1234 5678 9012 3456" autocomplete="cc-number"></label>
                    <div class="registration-payment-columns">
                        <label>Expiry Date *<input type="text" name="expiry" maxlength="5" placeholder="MM/YY" autocomplete="cc-exp"></label>
                        <label>CVV *<input type="password" name="cvv" inputmode="numeric" maxlength="3" autocomplete="cc-csc"></label>
                    </div>
                    <p class="registration-demo-note">Demo payment only. Card number and CVV are validated but never stored.</p>
                </div>

                <div class="registration-payment-fields" data-registration-fields="bank" hidden>
                    <label>Select Bank *
                        <select name="bank" id="registration_bank">
                            <option value="">Select your bank</option>
                            <option value="SBI">State Bank of India</option>
                            <option value="HDFC">HDFC Bank</option>
                            <option value="ICICI">ICICI Bank</option>
                            <option value="Axis">Axis Bank</option>
                            <option value="BOB">Bank of Baroda</option>
                        </select>
                    </label>
                    <div class="registration-bank-details" id="registrationBankDetails" hidden>
                        <h3>Demo Bank Transfer Details</h3>
                        <p>Demo data only. No real bank account or money transfer is used.</p>
                        <p>Beneficiary: <strong data-bank-holder></strong></p>
                        <p>Account Number: <strong data-bank-account></strong></p>
                        <p>IFSC: <strong data-bank-ifsc></strong></p>
                        <p>Branch: <strong data-bank-branch></strong></p>
                        <label>Demo Transaction Reference *<input type="text" name="bank_reference" id="registration_bank_reference" minlength="6" maxlength="24" pattern="[A-Za-z0-9-]{6,24}" placeholder="Example: DEMO123456"></label>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="Dashboard.php" class="secondary-button">Back to Dashboard</a>
                    <button type="submit" name="pay_registration" class="primary-button">Pay ₹1500 & Continue</button>
                </div>
            </form>
            <?php } else { ?>
                <p class="section-description">No active vehicle registration was found. Start a new registration first.</p>
            <?php } ?>

        </div>

    <?php } ?>


    <!-- =================================================
         STEP 4 - INSPECTION
    ================================================== -->

    <?php if ($currentStep == 4) { ?>

        <div class="registration-card">

            <h2>Vehicle Inspection</h2>

            <p class="section-description">
                Select a convenient date and time for vehicle inspection.
            </p>


            <?php if ($vehicle) { ?>

                <div class="vehicle-summary">

                    <h3>
                        Vehicle Details
                    </h3>

                    <p>
                        <strong>Vehicle No:</strong>
                        <?php
                        echo htmlspecialchars(
                            $vehicle["vehicle_no"]
                        );
                        ?>
                    </p>


                    <p>
                        <strong>Owner:</strong>
                        <?php
                        echo htmlspecialchars(
                            $vehicle["owner_name"]
                        );
                        ?>
                    </p>


                    <p>
                        <strong>Vehicle Type:</strong>
                        <?php
                        echo htmlspecialchars(
                            $vehicle["vehicle_type"]
                        );
                        ?>
                    </p>

                </div>


                <form method="POST">

                    <input
                        type="hidden"
                        name="vehicle_id"
                        value="<?php echo intval($vehicle["id"]); ?>"
                    >


                    <div class="form-group">

                        <label>
                            RTO Inspection Center
                        </label>

                        <select
                            name="rto_center"
                            required
                        >

                            <option value="">
                                Select RTO Center
                            </option>

                            <option value="RTO Surat">
                                RTO Surat
                            </option>

                            <option value="RTO Surat East">
                                RTO Surat East
                            </option>

                            <option value="RTO Surat West">
                                RTO Surat West
                            </option>

                        </select>

                    </div>


                    <div class="form-grid">


                        <div class="form-group">

                            <label>
                                Inspection Date
                            </label>

                            <input
                                type="date"
                                name="inspection_date"
                                min="<?php echo date("Y-m-d"); ?>"
                                required
                            >

                        </div>


                        <div class="form-group">

                            <label>
                                Inspection Time
                            </label>

                            <select
                                name="inspection_time"
                                required
                            >

                                <option value="">
                                    Select Time
                                </option>

                                <option value="10:00 AM">
                                    10:00 AM
                                </option>

                                <option value="11:00 AM">
                                    11:00 AM
                                </option>

                                <option value="12:00 PM">
                                    12:00 PM
                                </option>

                                <option value="02:00 PM">
                                    02:00 PM
                                </option>

                                <option value="03:00 PM">
                                    03:00 PM
                                </option>

                                <option value="04:00 PM">
                                    04:00 PM
                                </option>

                            </select>

                        </div>


                    </div>


                    <div class="form-actions">

                        <a
                            href="vehicleRegistration.php?step=3"
                            class="secondary-button"
                        >
                            Back
                        </a>


                        <button
                            type="submit"
                            name="book_inspection"
                            class="primary-button"
                        >
                            Book Inspection
                        </button>

                    </div>


                </form>

            <?php } else { ?>

                <div class="message error">

                    Vehicle registration record not found.

                    <br><br>

                    Please start the registration again.

                </div>


                <div class="form-actions">

                    <a
                        href="vehicleRegistration.php"
                        class="primary-button"
                    >
                        Start Registration
                    </a>

                </div>

            <?php } ?>

        </div>

    <?php } ?>


</div>


<script>
const registrationForm = document.getElementById("registrationPaymentForm");
if (registrationForm) {
    const bankSelect = document.getElementById("registration_bank");
    const bankDetailsPanel = document.getElementById("registrationBankDetails");
    const demoBanks = {
        SBI: { holder: "RTO Demo Collection", account: "123456789012", ifsc: "SBIN0001234", branch: "Ahmedabad Demo" },
        HDFC: { holder: "RTO Demo Collection", account: "234567890123", ifsc: "HDFC0001234", branch: "Ahmedabad Demo" },
        ICICI: { holder: "RTO Demo Collection", account: "345678901234", ifsc: "ICIC0001234", branch: "Ahmedabad Demo" },
        Axis: { holder: "RTO Demo Collection", account: "456789012345", ifsc: "UTIB0001234", branch: "Ahmedabad Demo" },
        BOB: { holder: "RTO Demo Collection", account: "567890123456", ifsc: "BARB0DEMOAH", branch: "Ahmedabad Demo" }
    };

    function updateRegistrationBankDetails() {
        const bank = bankSelect ? demoBanks[bankSelect.value] : null;
        bankDetailsPanel.hidden = !bank;
        bankDetailsPanel.querySelector("[data-bank-holder]").textContent = bank?.holder ?? "";
        bankDetailsPanel.querySelector("[data-bank-account]").textContent = bank?.account ?? "";
        bankDetailsPanel.querySelector("[data-bank-ifsc]").textContent = bank?.ifsc ?? "";
        bankDetailsPanel.querySelector("[data-bank-branch]").textContent = bank?.branch ?? "";
        document.getElementById("registration_bank_reference").required = Boolean(bank);
    }

    function updateRegistrationPaymentFields() {
        const selected = registrationForm.querySelector('input[name="payment_method"]:checked');
        const method = selected?.value ?? "";
        const activeFields = method === "UPI"
            ? "upi"
            : (method === "Debit Card" || method === "Credit Card")
                ? "card"
                : method === "Net Banking"
                    ? "bank"
                    : "";

        registrationForm.querySelectorAll("[data-registration-fields]").forEach((section) => {
            const active = section.dataset.registrationFields === activeFields;
            section.hidden = !active;
            section.querySelectorAll("input, select").forEach((field) => {
                field.required = active && field.id !== "registration_bank_reference";
            });
        });

        if (activeFields !== "bank") {
            bankDetailsPanel.hidden = true;
            document.getElementById("registration_bank_reference").required = false;
        } else {
            updateRegistrationBankDetails();
        }
    }

    registrationForm.querySelectorAll('input[name="payment_method"]').forEach((radio) => {
        radio.addEventListener("change", updateRegistrationPaymentFields);
    });
    if (bankSelect) bankSelect.addEventListener("change", updateRegistrationBankDetails);
    updateRegistrationPaymentFields();
}
</script>
<script src="mobile-validation.js?v=2" defer></script>
<script src="site-back-button.js?v=2" defer></script>
</body>

</html>