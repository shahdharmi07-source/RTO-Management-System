<?php

session_start();

include "connect.php";

if (!$con || !($con instanceof mysqli)) {
    http_response_code(503);
    exit("Database unavailable. Please start MySQL and try again.");
}
mysqli_report(MYSQLI_REPORT_OFF);

$message = "";
$messageType = "";


/* =====================================================
   LOGIN CHECK
===================================================== */

if (!isset($_SESSION['user_id'])) {

    header("Location: Login.php");
    exit();

}


/* =====================================================
   DEFAULT VALUES
===================================================== */

$full_name = "";
$dob = "";
$gender = "";
$mobile = "";
$email = "";
$address = "";
$city = "";
$state = "";
$pincode = "";
$licence_type = "";
$vehicle_class = "";
$aadhaar = "";
$document_type = "";


/* =====================================================
   FORM SUBMIT
===================================================== */

if (isset($_POST['submit_application'])) {


    /* =================================================
       GET FORM DATA
    ================================================= */

    $full_name = trim($_POST['full_name'] ?? '');

    $dob = trim($_POST['dob'] ?? '');

    $gender = trim($_POST['gender'] ?? '');

    $mobileInput = $_POST['mobile'] ?? '';
    $mobile = trim(is_string($mobileInput) ? $mobileInput : '');

    $email = trim($_POST['email'] ?? '');

    $address = trim($_POST['address'] ?? '');

    $city = trim($_POST['city'] ?? '');

    $state = trim($_POST['state'] ?? '');

    $pincode = trim($_POST['pincode'] ?? '');

    $licence_type = trim($_POST['licence_type'] ?? '');

    $vehicle_class = trim($_POST['vehicle_class'] ?? '');

    $aadhaar = trim($_POST['aadhaar'] ?? '');

    $document_type = trim($_POST['document_type'] ?? '');


    /* =================================================
       VALIDATION
    ================================================= */


    /* FULL NAME */

    if ($full_name == "") {

        $message = "Please enter your full name.";
        $messageType = "error";

    }


    /* DOB */

    elseif ($dob == "") {

        $message = "Please select your date of birth.";
        $messageType = "error";

    }


    /* GENDER */

    elseif (!in_array(
        $gender,
        ["Male", "Female", "Other"]
    )) {

        $message = "Please select a valid gender.";
        $messageType = "error";

    }


    /* MOBILE */

    elseif (!preg_match('/^[0-9]{10}$/', $mobile)) {

        $message = "Mobile number must be exactly 10 digits.";
        $messageType = "error";

    }


    /* EMAIL */

    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";
        $messageType = "error";

    }


    /* ADDRESS */

    elseif ($address == "") {

        $message = "Please enter your address.";
        $messageType = "error";

    }


    /* CITY */

    elseif ($city == "") {

        $message = "Please enter your city.";
        $messageType = "error";

    }


    /* STATE */

    elseif ($state == "") {

        $message = "Please select your state.";
        $messageType = "error";

    }


    /* PINCODE */

    elseif (!preg_match('/^[0-9]{6}$/', $pincode)) {

        $message = "Pincode must be exactly 6 digits.";
        $messageType = "error";

    }


    /* LICENCE TYPE */

    elseif ($licence_type == "") {

        $message = "Please select licence type.";
        $messageType = "error";

    }


    /* VEHICLE CLASS */

    elseif (!in_array(
        $vehicle_class,
        [
            "With Gear",
            "Without Gear",
            "Car"
        ]
    )) {

        $message = "Please select a valid vehicle class.";
        $messageType = "error";

    }


    /* AADHAAR */

    elseif (!preg_match('/^[0-9]{12}$/', $aadhaar)) {

        $message = "Aadhaar number must be exactly 12 digits.";
        $messageType = "error";

    }


    /* DOCUMENT TYPE */

    elseif ($document_type == "") {

        $message = "Please select document type.";
        $messageType = "error";

    }


    /* DECLARATION */

    elseif (!isset($_POST['declaration'])) {

        $message = "Please accept the declaration.";
        $messageType = "error";

    }


    /* =================================================
       DATABASE INSERT
    ================================================= */

    else {


        /*
        EXACT DATABASE COLUMNS

        id                  AUTO_INCREMENT
        full_name
        date_of_birth
        gender
        mobile_no
        email
        address
        city
        state
        pincode
        licence_type
        vehicle_class
        aadhaar_no
        document_type
        */


        $sql = "

            INSERT INTO licence
            (
                user_id,
                full_name,
                date_of_birth,
                gender,
                mobile_no,
                email,
                address,
                city,
                state,
                pincode,
                licence_type,
                vehicle_class,
                aadhaar_no,
                document_type
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
                ?,
                ?,
                ?,
                ?
            )

        ";


        /* =================================================
           PREPARE
        ================================================= */

        $stmt = $con->prepare($sql);


        if (!$stmt) {

            $message =
                "Database query error: " . $con->error;

            $messageType = "error";

        }

        else {


            /* =================================================
               BIND PARAMETER

               The user ID is an integer; the remaining 13 values are strings.
            ================================================= */

            $stmt->bind_param(
                "isssssssssssss",

                $_SESSION["user_id"],
                $full_name,
                $dob,
                $gender,
                $mobile,
                $email,
                $address,
                $city,
                $state,
                $pincode,
                $licence_type,
                $vehicle_class,
                $aadhaar,
                $document_type
            );


            /* =================================================
               EXECUTE
            ================================================= */

            if ($stmt->execute()) {


                /* =========================================
                   APPLICATION SUCCESS
                ========================================= */

                $_SESSION['application_completed'] = true;


                /* =========================================
                   SAVE APPLICATION ID
                ========================================= */

                $_SESSION['licence_application_id'] =
                    $stmt->insert_id;
                $_SESSION['licence_id'] = $stmt->insert_id;


                /* =========================================
                   GO TO STEP 2
                ========================================= */

                header("Location: documents.php");

                exit();

            }

            else {

                $message =
                    "Database error: " . $stmt->error;

                $messageType = "error";

            }


            $stmt->close();

        }

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

    <title>New Driving Licence - Step 1</title>


    <link
        rel="stylesheet"
        href="new_licence.css"
    >

</head>


<body>


<div class="page-container">


    <!-- =================================================
         4 STEP PROCESS
    ================================================= -->

    <div class="steps-card">

        <h1>
            Easy 4-Step Process
        </h1>

        <p>
            Complete your driving licence application in simple steps.
        </p>


        <div class="steps">


            <!-- STEP 1 -->

            <div class="step active">

                <div class="step-circle">
                    1
                </div>

                <div>

                    <small>
                        STEP 01
                    </small>

                    <strong>
                        Application
                    </strong>

                </div>

            </div>


            <div class="line"></div>


            <!-- STEP 2 -->

            <div class="step">

                <div class="step-circle">
                    2
                </div>

                <div>

                    <small>
                        STEP 02
                    </small>

                    <strong>
                        Documents
                    </strong>

                </div>

            </div>


            <div class="line"></div>


            <!-- STEP 3 -->

            <div class="step">

                <div class="step-circle">
                    3
                </div>

                <div>

                    <small>
                        STEP 03
                    </small>

                    <strong>
                        Payment
                    </strong>

                </div>

            </div>


            <div class="line"></div>


            <!-- STEP 4 -->

            <div class="step">

                <div class="step-circle">
                    4
                </div>

                <div>

                    <small>
                        STEP 04
                    </small>

                    <strong>
                        Slot Booking
                    </strong>

                </div>

            </div>

        </div>

    </div>


    <!-- =================================================
         HEADER
    ================================================= -->

    <div class="page-header">

        <div>

            <h2>
                New Driving Licence Application
            </h2>

            <p>
                Step 01 - Enter your personal and licence information.
            </p>

        </div>


        <div class="application-icon">
            📄
        </div>

    </div>


    <!-- =================================================
         MESSAGE
    ================================================= -->

    <?php if ($message != "") { ?>

        <div class="<?php

            echo ($messageType == "success")
                ? "success"
                : "error";

        ?>">

            <?php

            echo htmlspecialchars($message);

            ?>

        </div>

    <?php } ?>


    <!-- =================================================
         FORM
    ================================================= -->

    <form
        method="POST"
        action=""
        id="licenceForm"
        autocomplete="off"
        novalidate
    >


        <!-- =================================================
             PERSONAL DETAILS
        ================================================= -->

        <div class="form-card">


            <div class="section-title">

                <span>
                    1
                </span>

                <div>

                    <h2>
                        Personal Details
                    </h2>

                    <p>
                        Enter your personal information.
                    </p>

                </div>

            </div>


            <div class="form-grid">


                <!-- FULL NAME -->

                <div class="form-group">

                    <label>
                        Full Name *
                    </label>

                    <input
                        type="text"
                        name="full_name"
                        id="full_name"
                        placeholder="Enter full name"
                        value="<?php
                            echo htmlspecialchars($full_name);
                        ?>"
                    >

                    <div
                        class="field-error"
                        id="fullNameError"
                    ></div>

                </div>


                <!-- DATE OF BIRTH -->

                <div class="form-group">

                    <label>
                        Date of Birth *
                    </label>

                    <input
                        type="date"
                        name="dob"
                        id="dob"
                        value="<?php
                            echo htmlspecialchars($dob);
                        ?>"
                    >

                    <div
                        class="field-error"
                        id="dobError"
                    ></div>

                </div>


                <!-- GENDER -->

                <div class="form-group">

                    <label>
                        Gender *
                    </label>

                    <select
                        name="gender"
                        id="gender"
                    >

                        <option value="">
                            Select Gender
                        </option>

                        <option value="Male">
                            Male
                        </option>

                        <option value="Female">
                            Female
                        </option>

                        <option value="Other">
                            Other
                        </option>

                    </select>

                    <div
                        class="field-error"
                        id="genderError"
                    ></div>

                </div>


                <!-- MOBILE -->

                <div class="form-group">

                    <label>
                        Mobile Number *
                    </label>

                    <input
                        type="text"
                        name="mobile"
                        id="mobile"
                        placeholder="Enter exactly 10 digits"
                        value="<?php
                            echo htmlspecialchars($mobile);
                        ?>"
                        maxlength="10"
                        inputmode="numeric"
                    >

                    <div
                        class="field-error"
                        id="mobileError"
                    ></div>

                </div>


                <!-- EMAIL -->

                <div class="form-group">

                    <label>
                        Email *
                    </label>

                    <input
                        type="email"
                        name="email"
                        id="email"
                        placeholder="Enter email address"
                        value="<?php
                            echo htmlspecialchars($email);
                        ?>"
                    >

                    <div
                        class="field-error"
                        id="emailError"
                    ></div>

                </div>


                <!-- ADDRESS -->

                <div class="form-group full">

                    <label>
                        Address *
                    </label>

                    <textarea
                        name="address"
                        id="address"
                        placeholder="Enter complete address"
                    ><?php
                        echo htmlspecialchars($address);
                    ?></textarea>

                    <div
                        class="field-error"
                        id="addressError"
                    ></div>

                </div>


                <!-- CITY -->

                <div class="form-group">

                    <label>
                        City *
                    </label>

                    <input
                        type="text"
                        name="city"
                        id="city"
                        placeholder="Enter city"
                        value="<?php
                            echo htmlspecialchars($city);
                        ?>"
                    >

                    <div
                        class="field-error"
                        id="cityError"
                    ></div>

                </div>


                <!-- STATE -->

                <div class="form-group">

                    <label>
                        State *
                    </label>

                    <select
                        name="state"
                        id="state"
                    >

                        <option value="">
                            Select State
                        </option>

                        <option value="Andhra Pradesh">
                            Andhra Pradesh
                        </option>

                        <option value="Arunachal Pradesh">
                            Arunachal Pradesh
                        </option>

                        <option value="Assam">
                            Assam
                        </option>

                        <option value="Bihar">
                            Bihar
                        </option>

                        <option value="Chhattisgarh">
                            Chhattisgarh
                        </option>

                        <option value="Goa">
                            Goa
                        </option>

                        <option value="Gujarat">
                            Gujarat
                        </option>

                        <option value="Haryana">
                            Haryana
                        </option>

                        <option value="Himachal Pradesh">
                            Himachal Pradesh
                        </option>

                        <option value="Jharkhand">
                            Jharkhand
                        </option>

                        <option value="Karnataka">
                            Karnataka
                        </option>

                        <option value="Kerala">
                            Kerala
                        </option>

                        <option value="Madhya Pradesh">
                            Madhya Pradesh
                        </option>

                        <option value="Maharashtra">
                            Maharashtra
                        </option>

                        <option value="Odisha">
                            Odisha
                        </option>

                        <option value="Punjab">
                            Punjab
                        </option>

                        <option value="Rajasthan">
                            Rajasthan
                        </option>

                        <option value="Tamil Nadu">
                            Tamil Nadu
                        </option>

                        <option value="Telangana">
                            Telangana
                        </option>

                        <option value="Uttar Pradesh">
                            Uttar Pradesh
                        </option>

                        <option value="Uttarakhand">
                            Uttarakhand
                        </option>

                        <option value="West Bengal">
                            West Bengal
                        </option>

                    </select>

                    <div
                        class="field-error"
                        id="stateError"
                    ></div>

                </div>


                <!-- PINCODE -->

                <div class="form-group">

                    <label>
                        Pincode *
                    </label>

                    <input
                        type="text"
                        name="pincode"
                        id="pincode"
                        placeholder="Enter exactly 6 digits"
                        value="<?php
                            echo htmlspecialchars($pincode);
                        ?>"
                        maxlength="6"
                        inputmode="numeric"
                    >

                    <div
                        class="field-error"
                        id="pincodeError"
                    ></div>

                </div>


            </div>

        </div>


        <!-- =================================================
             LICENCE DETAILS
        ================================================= -->

        <div class="form-card">


            <div class="section-title">

                <span>
                    2
                </span>

                <div>

                    <h2>
                        Licence Details
                    </h2>

                    <p>
                        Select your licence and vehicle information.
                    </p>

                </div>

            </div>


            <div class="form-grid">


                <!-- LICENCE TYPE -->

                <div class="form-group">

                    <label>
                        Licence Type *
                    </label>

                    <select
                        name="licence_type"
                        id="licence_type"
                    >

                        <option value="">
                            Select Licence Type
                        </option>

                        <option value="Learner Licence">
                            Learner Licence
                        </option>

                        <option value="Permanent Licence">
                            Permanent Licence
                        </option>

                        <option value="New Driving Licence">
                            New Driving Licence
                        </option>

                    </select>

                    <div
                        class="field-error"
                        id="licenceTypeError"
                    ></div>

                </div>


                <!-- VEHICLE CLASS -->

                <div class="form-group">

                    <label>
                        Vehicle Class *
                    </label>

                    <select
                        name="vehicle_class"
                        id="vehicle_class"
                    >

                        <option value="">
                            Select Vehicle Class
                        </option>

                        <option value="With Gear">
                            With Gear
                        </option>

                        <option value="Without Gear">
                            Without Gear
                        </option>

                        <option value="Car">
                            Car
                        </option>

                    </select>

                    <div
                        class="field-error"
                        id="vehicleClassError"
                    ></div>

                </div>


                <!-- AADHAAR -->

                <div class="form-group">

                    <label>
                        Aadhaar Card Number *
                    </label>

                    <input
                        type="text"
                        name="aadhaar"
                        id="aadhaar"
                        placeholder="Enter exactly 12 digits"
                        value="<?php
                            echo htmlspecialchars($aadhaar);
                        ?>"
                        maxlength="12"
                        inputmode="numeric"
                    >

                    <div
                        class="field-error"
                        id="aadhaarError"
                    ></div>

                </div>


                <!-- DOCUMENT TYPE -->

                <div class="form-group">

                    <label>
                        Document Type *
                    </label>

                    <select
                        name="document_type"
                        id="document_type"
                    >

                        <option value="">
                            Select Document Type
                        </option>

                        <option value="Aadhaar Card">
                            Aadhaar Card
                        </option>

                        <option value="Address Proof">
                            Address Proof
                        </option>

                        <option value="Identity Proof">
                            Identity Proof
                        </option>

                    </select>

                    <div
                        class="field-error"
                        id="documentTypeError"
                    ></div>

                </div>


            </div>

        </div>


        <!-- =================================================
             DECLARATION
        ================================================= -->

        <div class="declaration">

            <input
                type="checkbox"
                name="declaration"
                id="declaration"
            >

            <label for="declaration">

                I hereby declare that the information
                provided by me is true and correct.

            </label>

        </div>


        <div
            class="field-error declaration-error"
            id="declarationError"
        ></div>


        <!-- =================================================
             BUTTONS
        ================================================= -->

        <div class="form-buttons">

            <a
                href="Dashboard.php"
                class="cancel-btn"
            >
                Cancel
            </a>


            <button
                type="submit"
                name="submit_application"
                class="submit-btn"
            >
                Verify Application & Continue →
            </button>

        </div>


    </form>


</div>


<script>

/* =====================================================
   ERROR FUNCTIONS
===================================================== */

function showError(input, error, message) {

    input.classList.add("input-error");

    error.innerText = message;

    error.style.display = "block";

}


function hideError(input, error) {

    input.classList.remove("input-error");

    error.innerText = "";

    error.style.display = "none";

}


/* =====================================================
   MOBILE
===================================================== */

const mobile =
    document.getElementById("mobile");

const mobileError =
    document.getElementById("mobileError");


mobile.addEventListener("input", function () {

    this.value =
        this.value.replace(/[^0-9]/g, "");

    if (this.value.length !== 10) {

        showError(
            mobile,
            mobileError,
            "Mobile number must be exactly 10 digits."
        );

    } else {

        hideError(
            mobile,
            mobileError
        );

    }

});


/* =====================================================
   PINCODE
===================================================== */

const pincode =
    document.getElementById("pincode");

const pincodeError =
    document.getElementById("pincodeError");


pincode.addEventListener("input", function () {

    this.value =
        this.value.replace(/[^0-9]/g, "");

    if (this.value.length !== 6) {

        showError(
            pincode,
            pincodeError,
            "Pincode must be exactly 6 digits."
        );

    } else {

        hideError(
            pincode,
            pincodeError
        );

    }

});


/* =====================================================
   AADHAAR
===================================================== */

const aadhaar =
    document.getElementById("aadhaar");

const aadhaarError =
    document.getElementById("aadhaarError");


aadhaar.addEventListener("input", function () {

    this.value =
        this.value.replace(/[^0-9]/g, "");

    if (this.value.length !== 12) {

        showError(
            aadhaar,
            aadhaarError,
            "Aadhaar number must be exactly 12 digits."
        );

    } else {

        hideError(
            aadhaar,
            aadhaarError
        );

    }

});


/* =====================================================
   FORM SUBMIT
===================================================== */

document
.getElementById("licenceForm")
.addEventListener("submit", function(event) {

    let valid = true;


    const fullName =
        document.getElementById("full_name");

    const fullNameError =
        document.getElementById("fullNameError");


    if (fullName.value.trim() === "") {

        showError(
            fullName,
            fullNameError,
            "Please enter your full name."
        );

        if (valid) fullName.focus();

        valid = false;

    }


    /* DOB */

    const dob =
        document.getElementById("dob");

    const dobError =
        document.getElementById("dobError");


    if (dob.value === "") {

        showError(
            dob,
            dobError,
            "Please select your date of birth."
        );

        if (valid) dob.focus();

        valid = false;

    }


    /* GENDER */

    const gender =
        document.getElementById("gender");

    const genderError =
        document.getElementById("genderError");


    if (gender.value === "") {

        showError(
            gender,
            genderError,
            "Please select gender."
        );

        if (valid) gender.focus();

        valid = false;

    }


    /* MOBILE */

    if (!/^[0-9]{10}$/.test(mobile.value)) {

        showError(
            mobile,
            mobileError,
            "Mobile number must be exactly 10 digits."
        );

        if (valid) mobile.focus();

        valid = false;

    }


    /* EMAIL */

    const email =
        document.getElementById("email");

    const emailError =
        document.getElementById("emailError");


    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {

        showError(
            email,
            emailError,
            "Please enter a valid email address."
        );

        if (valid) email.focus();

        valid = false;

    }


    /* ADDRESS */

    const address =
        document.getElementById("address");

    const addressError =
        document.getElementById("addressError");


    if (address.value.trim() === "") {

        showError(
            address,
            addressError,
            "Please enter your address."
        );

        if (valid) address.focus();

        valid = false;

    }


    /* CITY */

    const city =
        document.getElementById("city");

    const cityError =
        document.getElementById("cityError");


    if (city.value.trim() === "") {

        showError(
            city,
            cityError,
            "Please enter your city."
        );

        if (valid) city.focus();

        valid = false;

    }


    /* STATE */

    const state =
        document.getElementById("state");

    const stateError =
        document.getElementById("stateError");


    if (state.value === "") {

        showError(
            state,
            stateError,
            "Please select your state."
        );

        if (valid) state.focus();

        valid = false;

    }


    /* PINCODE */

    if (!/^[0-9]{6}$/.test(pincode.value)) {

        showError(
            pincode,
            pincodeError,
            "Pincode must be exactly 6 digits."
        );

        if (valid) pincode.focus();

        valid = false;

    }


    /* LICENCE TYPE */

    const licenceType =
        document.getElementById("licence_type");

    const licenceTypeError =
        document.getElementById("licenceTypeError");


    if (licenceType.value === "") {

        showError(
            licenceType,
            licenceTypeError,
            "Please select licence type."
        );

        if (valid) licenceType.focus();

        valid = false;

    }


    /* VEHICLE CLASS */

    const vehicleClass =
        document.getElementById("vehicle_class");

    const vehicleClassError =
        document.getElementById("vehicleClassError");


    if (vehicleClass.value === "") {

        showError(
            vehicleClass,
            vehicleClassError,
            "Please select vehicle class."
        );

        if (valid) vehicleClass.focus();

        valid = false;

    }


    /* AADHAAR */

    if (!/^[0-9]{12}$/.test(aadhaar.value)) {

        showError(
            aadhaar,
            aadhaarError,
            "Aadhaar number must be exactly 12 digits."
        );

        if (valid) aadhaar.focus();

        valid = false;

    }


    /* DOCUMENT TYPE */

    const documentType =
        document.getElementById("document_type");

    const documentTypeError =
        document.getElementById("documentTypeError");


    if (documentType.value === "") {

        showError(
            documentType,
            documentTypeError,
            "Please select document type."
        );

        if (valid) documentType.focus();

        valid = false;

    }


    /* DECLARATION */

    const declaration =
        document.getElementById("declaration");

    const declarationError =
        document.getElementById("declarationError");


    if (!declaration.checked) {

        declarationError.innerText =
            "Please accept the declaration.";

        declarationError.style.display =
            "block";

        if (valid) declaration.focus();

        valid = false;

    } else {

        declarationError.innerText = "";

        declarationError.style.display =
            "none";

    }


    /* STOP */

    if (!valid) {

        event.preventDefault();

        return false;

    }

});

</script>


<script src="mobile-validation.js?v=1" defer></script>
<script src="site-back-button.js?v=2" defer></script>
</body>

</html>