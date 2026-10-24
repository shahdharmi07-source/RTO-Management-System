<?php

session_start();

require_once "connect.php";

if (!$con || !($con instanceof mysqli)) {
    http_response_code(503);
    exit("Database unavailable. Please start MySQL and try again.");
}
mysqli_report(MYSQLI_REPORT_OFF);

$message = "";
$amount = 500;
$payment_method = "";


if (!isset($_SESSION['user_id'])) {
    header("Location: Login.php");
    exit();
}

if (
    !isset($_SESSION['documents_completed']) ||
    $_SESSION['documents_completed'] !== true
) {
    header("Location: documents.php");
    exit();
}

if (!empty($_SESSION['payment_completed'])) {
    header('Location: slot_booking.php');
    exit();
}

if (isset($_POST['submit_payment'])) {
    $payment_method = trim($_POST['payment_method'] ?? '');
    $allowedMethods = array('UPI', 'Debit Card', 'Credit Card', 'Net Banking');

    if (!in_array($payment_method, $allowedMethods, true)) {
        $message = "Please select a valid payment method.";
    } elseif (!isset($_POST['payment_declaration'])) {
        $message = "Please confirm the payment information.";
    } else {
        $methodError = "";
        $bankReference = null;
        $bankName = null;

        if (in_array($payment_method, array('Debit Card', 'Credit Card'), true)) {
            $cardName = trim($_POST['card_name'] ?? '');
            $cardNumber = preg_replace('/\s+/', '', $_POST['card_number'] ?? '');
            $expiry = trim($_POST['expiry'] ?? '');
            $cvv = trim($_POST['cvv'] ?? '');

            if ($cardName === '') {
                $methodError = "Please enter the card holder name.";
            } elseif (!preg_match('/^[0-9]{16}$/', $cardNumber)) {
                $methodError = "Card number must be exactly 16 digits.";
            } elseif (!preg_match('/^(0[1-9]|1[0-2])\/[0-9]{2}$/', $expiry)) {
                $methodError = "Enter expiry date in MM/YY format.";
            } elseif (!preg_match('/^[0-9]{3}$/', $cvv)) {
                $methodError = "CVV must be exactly 3 digits.";
            }
        } elseif ($payment_method === 'UPI') {
            $upiId = trim($_POST['upi_id'] ?? '');
            if (!preg_match('/^[a-zA-Z0-9._-]+@[a-zA-Z]{2,}$/', $upiId)) {
                $methodError = "Please enter a valid UPI ID.";
            }
        } elseif ($payment_method === 'Net Banking') {
            $bank = $_POST['bank'] ?? '';
            $bankName = $bank;
            $bankReference = strtoupper(trim($_POST['bank_reference'] ?? ''));
            if (!in_array($bank, array('SBI', 'HDFC', 'ICICI', 'Axis', 'BOB'), true)) {
                $methodError = "Please select a valid bank.";
            } elseif (!preg_match('/^[A-Z0-9-]{6,24}$/', $bankReference)) {
                $methodError = "Enter a demo transaction reference (6-24 letters, numbers or hyphens).";
            }
        }

        if ($methodError !== '') {
            $message = $methodError;
        } else {
            $transactionReference = "RTO-" . strtoupper(bin2hex(random_bytes(5)));
            $applicationId = (int) ($_SESSION['licence_application_id'] ?? 0);
            $userId = (int) $_SESSION['user_id'];

            $stmt = mysqli_prepare(
                $con,
                "INSERT INTO payments (user_id, application_id, amount, payment_method, bank_reference, bank_name, transaction_reference) VALUES (?, ?, ?, ?, ?, ?, ?)"
            );

            if (!$stmt) {
                $message = "Database query error: " . mysqli_error($con);
            } else {
                mysqli_stmt_bind_param($stmt, "iidssss", $userId, $applicationId, $amount, $payment_method, $bankReference, $bankName, $transactionReference);

                if (mysqli_stmt_execute($stmt)) {
                    $paymentId = mysqli_insert_id($con);
                    $_SESSION['payment_completed'] = true;
                    $_SESSION['payment_method'] = $payment_method;
                    $_SESSION['payment_amount'] = $amount;
                    header("Location: payment_success.php?payment_id=" . $paymentId);
                    exit();
                }

                $message = "Database error while saving payment: " . mysqli_stmt_error($stmt);
                mysqli_stmt_close($stmt);
            }
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

    <title>
        Driving Licence Payment
    </title>


    <link
        rel="stylesheet"
        href="payment.css"
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

            <div class="step completed">

                <div class="step-circle">
                    ✓
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


            <div class="line completed-line"></div>


            <!-- STEP 2 -->

            <div class="step completed">

                <div class="step-circle">
                    ✓
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


            <div class="line completed-line"></div>


            <!-- STEP 3 -->

            <div class="step active">

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
         PAGE HEADER
    ================================================= -->

    <div class="page-header">

        <div>

            <h2>
                Driving Licence Payment
            </h2>

            <p>
                Step 03 - Complete your application payment
            </p>

        </div>


        <div class="application-icon">
            💳
        </div>

    </div>



    <!-- =================================================
         ERROR MESSAGE
    ================================================= -->

    <?php if ($message != "") { ?>

        <div class="error">

            <?php
            echo htmlspecialchars($message);
            ?>

        </div>

    <?php } ?>



    <!-- =================================================
         PAYMENT FORM
    ================================================= -->

    <form
        method="POST"
        action=""
        id="paymentForm"
    >


        <!-- =================================================
             PAYMENT DETAILS
        ================================================= -->

        <div class="form-card">


            <div class="section-title">

                <span>
                    03
                </span>

                <div>

                    <h2>
                        Payment Details
                    </h2>

                    <p>
                        Select your preferred payment method
                    </p>

                </div>

            </div>



            <!-- =================================================
                 APPLICATION FEE
            ================================================= -->

            <div class="fee-box">

                <div class="fee-title">
                    Application Fee
                </div>


                <div class="fee-amount">

                    ₹ <?php echo $amount; ?>

                </div>

            </div>


            <div class="form-group">

                <label for="payment_method">
                    Select Payment Method *
                </label>

                <select
                    name="payment_method"
                    id="payment_method"
                >

                    <option value="">
                        Select Payment Method
                    </option>

                    <option value="UPI">UPI</option>

                    <option value="Debit Card">Debit Card</option>

                    <option value="Credit Card">Credit Card</option>

                    <option value="Net Banking">Net Banking</option>

                </select>

                <div
                    class="field-error"
                    id="paymentMethodError"
                ></div>

            </div>



            <div class="form-group payment-method-fields" data-payment-fields="card" hidden>
                <label for="card_name">Card Holder Name *</label>
                <input type="text" id="card_name" name="card_name" autocomplete="cc-name">
                <label for="card_number">Debit / Credit Card Number *</label>
                <input type="text" id="card_number" name="card_number" inputmode="numeric" maxlength="19" placeholder="1234 5678 9012 3456" autocomplete="cc-number">
                <div class="two-columns">
                    <div>
                        <label for="expiry">Expiry Date *</label>
                        <input type="text" id="expiry" name="expiry" maxlength="5" placeholder="MM/YY" autocomplete="cc-exp">
                    </div>
                    <div>
                        <label for="cvv">CVV *</label>
                        <input type="password" id="cvv" name="cvv" inputmode="numeric" maxlength="3" autocomplete="cc-csc">
                    </div>
                </div>
            </div>

            <div class="form-group payment-method-fields" data-payment-fields="upi" hidden>
                <label for="upi_id">UPI ID *</label>
                <input type="text" id="upi_id" name="upi_id" placeholder="example@upi" autocomplete="off">
            </div>

            <div class="form-group payment-method-fields" data-payment-fields="bank" hidden>
                <label for="bank">Select Bank *</label>
                <select id="bank" name="bank">
                    <option value="">Select your bank</option>
                    <option value="SBI">State Bank of India</option>
                    <option value="HDFC">HDFC Bank</option>
                    <option value="ICICI">ICICI Bank</option>
                    <option value="Axis">Axis Bank</option>
                    <option value="BOB">Bank of Baroda</option>
                </select>
            </div>

            <div class="payment-info bank-demo-details" id="bankDemoDetails" hidden>
                <h3>Demo Bank Transfer Details</h3>
                <p>Demo data only. No real bank account or money transfer is used.</p>
                <div class="demo-bank-grid">
                    <p>Beneficiary <strong data-demo-bank-holder></strong></p>
                    <p>Account Number <strong data-demo-bank-account></strong></p>
                    <p>IFSC <strong data-demo-bank-ifsc></strong></p>
                    <p>Branch <strong data-demo-bank-branch></strong></p>
                </div>
                <label for="bank_reference">Demo Transaction Reference *</label>
                <input type="text" id="bank_reference" name="bank_reference" minlength="6" maxlength="24" pattern="[A-Za-z0-9-]{6,24}" placeholder="Example: DEMO123456">
            </div>

            <!-- =================================================
                 PAYMENT INFORMATION
            ================================================= -->

            <div class="payment-info">

                <h3>
                    Payment Information
                </h3>

                <p>
                    This is a mini project demo payment. The selected method is saved with your application when you continue.
                </p>

            </div>


        </div>

        <!-- =================================================
             DECLARATION
        ================================================= -->

        <div class="declaration">

            <input
                type="checkbox"
                name="payment_declaration"
                id="payment_declaration"
            >


            <label for="payment_declaration">

                I confirm that the payment information
                provided by me is correct.

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
                href="documents.php"
                class="cancel-btn"
            >

                ← Back

            </a>


            <button
                type="submit"
                name="submit_payment"
                class="submit-btn"
            >

                Pay ₹500 & Continue →

            </button>


        </div>


    </form>


</div>



<!-- =====================================================
     JAVASCRIPT
===================================================== -->

<script>

const paymentMethodSelect = document.getElementById("payment_method");
const paymentFieldSections = document.querySelectorAll("[data-payment-fields]");
const bankSelect = document.getElementById("bank");
const bankDemoDetails = document.getElementById("bankDemoDetails");
const demoBanks = {
    SBI: { holder: "RTO Demo Collection", account: "123456789012", ifsc: "SBIN0001234", branch: "Ahmedabad Demo" },
    HDFC: { holder: "RTO Demo Collection", account: "234567890123", ifsc: "HDFC0001234", branch: "Ahmedabad Demo" },
    ICICI: { holder: "RTO Demo Collection", account: "345678901234", ifsc: "ICIC0001234", branch: "Ahmedabad Demo" },
    Axis: { holder: "RTO Demo Collection", account: "456789012345", ifsc: "UTIB0001234", branch: "Ahmedabad Demo" },
    BOB: { holder: "RTO Demo Collection", account: "567890123456", ifsc: "BARB0DEMOAH", branch: "Ahmedabad Demo" }
};

function updateBankDemoDetails() {
    const details = paymentMethodSelect.value === "Net Banking" ? demoBanks[bankSelect.value] : null;
    bankDemoDetails.hidden = !details;
    bankDemoDetails.querySelector("[data-demo-bank-holder]").textContent = details?.holder ?? "";
    bankDemoDetails.querySelector("[data-demo-bank-account]").textContent = details?.account ?? "";
    bankDemoDetails.querySelector("[data-demo-bank-ifsc]").textContent = details?.ifsc ?? "";
    bankDemoDetails.querySelector("[data-demo-bank-branch]").textContent = details?.branch ?? "";
    document.getElementById("bank_reference").required = Boolean(details);
}

function updatePaymentFields() {
    const method = paymentMethodSelect.value;
    const activeFields = method === "UPI"
        ? "upi"
        : (method === "Debit Card" || method === "Credit Card")
            ? "card"
                : method === "Net Banking"
                    ? "bank"
                    : "";

    paymentFieldSections.forEach((section) => {
        const active = section.dataset.paymentFields === activeFields;
        section.hidden = !active;
        section.querySelectorAll("input, select").forEach((field) => {
            field.required = active;
        });
    });
    updateBankDemoDetails();
}

paymentMethodSelect.addEventListener("change", updatePaymentFields);
bankSelect.addEventListener("change", updateBankDemoDetails);
updatePaymentFields();
updateBankDemoDetails();

document
.getElementById("paymentForm")
.addEventListener(
    "submit",
    function(event) {
        let valid = true;
        const paymentMethod = document.getElementById("payment_method");
        const paymentMethodError = document.getElementById("paymentMethodError");
        const declaration = document.getElementById("payment_declaration");
        const declarationError = document.getElementById("declarationError");

        if (paymentMethod.value === "") {
            paymentMethodError.innerText = "Please select a payment method.";
            paymentMethodError.style.display = "block";
            paymentMethod.style.borderColor = "#d92d20";
            valid = false;
        } else {
            paymentMethodError.innerText = "";
            paymentMethodError.style.display = "none";
            paymentMethod.style.borderColor = "";
        }

        if (!declaration.checked) {
            declarationError.innerText = "Please confirm the payment information.";
            declarationError.style.display = "block";
            valid = false;
        } else {
            declarationError.innerText = "";
            declarationError.style.display = "none";
        }

        if (!valid) {
            event.preventDefault();
        }
    }
);

</script>


<script src="site-back-button.js?v=2" defer></script>
</body>

</html>