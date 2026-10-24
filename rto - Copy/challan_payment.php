<?php
session_start();
include "connect.php";

$challan_no = trim($_POST['challan_no'] ?? $_GET['challan_no'] ?? '');

if (!isset($_SESSION['user_id'])) {
    if ($challan_no !== '') {
        $_SESSION['post_login_redirect'] = "challan_payment.php?challan_no=" . rawurlencode($challan_no);
    }
    header("Location: Login.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];

if ($challan_no === '') {
    header("Location: Challan.php");
    exit();
}

$challanStatement = mysqli_prepare(
    $con,
    "SELECT c.vehicle_no, c.challan_no, c.violation, c.amount
     FROM challans c
     WHERE c.challan_no = ? AND c.status = 'Pending' AND (c.user_id IS NULL OR c.user_id = ?)
       AND EXISTS (
           SELECT 1 FROM vehicles v
           WHERE v.user_id = ? AND UPPER(v.vehicle_no) = UPPER(c.vehicle_no)
       )
     LIMIT 1"
);

if (!$challanStatement) {
    die("Unable to load challan details.");
}

mysqli_stmt_bind_param($challanStatement, "sii", $challan_no, $user_id, $user_id);
mysqli_stmt_execute($challanStatement);
$challanResult = mysqli_stmt_get_result($challanStatement);
$challan = mysqli_fetch_assoc($challanResult);
mysqli_stmt_close($challanStatement);

if (!$challan) {
    header("Location: Challan.php");
    exit();
}

$amount = (float) $challan['amount'];
$vehicle_no = $challan['vehicle_no'];
$violation = $challan['violation'];

$success = false;
$message = "";
$bank_reference = null;
$bank_name = null;

if (isset($_POST['make_payment'])) {

    $payment_method = $_POST['payment_method'] ?? '';

    if ($payment_method == "") {
        $message = "Please select a payment method.";
    }

    // ================= CARD =================
    elseif ($payment_method == "card") {

        $card_name = trim($_POST['card_name'] ?? '');
        $card_number = preg_replace('/\s+/', '', $_POST['card_number'] ?? '');
        $expiry = trim($_POST['expiry'] ?? '');
        $cvv = trim($_POST['cvv'] ?? '');

        if ($card_name == "") {
            $message = "Please enter card holder name.";
        }
        elseif (!preg_match('/^[0-9]{16}$/', $card_number)) {
            $message = "Card number must be exactly 16 digits.";
        }
        elseif (!preg_match('/^(0[1-9]|1[0-2])\/([0-9]{2})$/', $expiry)) {
            $message = "Enter expiry date in MM/YY format.";
        }
        elseif (!preg_match('/^[0-9]{3}$/', $cvv)) {
            $message = "CVV must be exactly 3 digits.";
        }
        else {
            $success = true;
        }
    }

    // ================= UPI =================
    elseif ($payment_method == "upi") {

        $upi_id = trim($_POST['upi_id'] ?? '');

        if (!preg_match('/^[a-zA-Z0-9._-]+@[a-zA-Z]{2,}$/', $upi_id)) {
            $message = "Please enter a valid UPI ID.";
        }
        else {
            $success = true;
        }
    }

    // ================= NET BANKING =================
    elseif ($payment_method == "netbanking") {

        $bank = $_POST['bank'] ?? '';
        $bank_name = $bank;
        $bank_reference = strtoupper(trim($_POST['bank_reference'] ?? ''));

        if (!in_array($bank, ['SBI', 'HDFC', 'ICICI', 'Axis', 'BOB'], true)) {
            $message = "Please select your bank.";
        } elseif (!preg_match('/^[A-Z0-9-]{6,24}$/', $bank_reference)) {
            $message = "Enter a demo transaction reference (6-24 letters, numbers or hyphens).";
        }
        else {
            $success = true;
        }
    }

    else {
        $message = "Invalid payment method.";
    }

    // ================= SUCCESS =================

    if ($success) {
        $transaction_id = "RTO" . date("YmdHis") . strtoupper(bin2hex(random_bytes(4)));
        $updateStatement = mysqli_prepare(
            $con,
            "UPDATE challans AS c
             SET status = 'Paid', user_id = ?, paid_at = CURRENT_TIMESTAMP,
                 payment_reference = ?, bank_reference = ?, bank_name = ?
             WHERE c.challan_no = ? AND c.status = 'Pending' AND (c.user_id IS NULL OR c.user_id = ?)
               AND EXISTS (
                   SELECT 1 FROM vehicles v
                   WHERE v.user_id = ? AND UPPER(v.vehicle_no) = UPPER(c.vehicle_no)
               )"
        );

        if ($updateStatement) {
            mysqli_stmt_bind_param($updateStatement, "issssii", $user_id, $transaction_id, $bank_reference, $bank_name, $challan_no, $user_id, $user_id);
            mysqli_stmt_execute($updateStatement);
            $paymentRecorded = mysqli_stmt_affected_rows($updateStatement) === 1;
            mysqli_stmt_close($updateStatement);
        } else {
            $paymentRecorded = false;
        }

        if ($paymentRecorded) {
            $_SESSION['challan_paid'] = true;
            $_SESSION['challan_payment_amount'] = $amount;
            $_SESSION['challan_vehicle'] = $vehicle_no;
            $_SESSION['challan_number'] = $challan_no;
            $_SESSION['challan_transaction_id'] = $transaction_id;

            header("Location: challan_payment_success.php?challan_no=" . urlencode($challan_no));
            exit();
        }

        $success = false;
        $message = "This challan is no longer pending. Please search again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Pay Challan</title>

    <link rel="stylesheet"
          href="challan_payment.css">

</head>

<body>

<div class="payment-page">

    <!-- ================= HERO ================= -->

    <section class="payment-hero">

        <div class="hero-content">

            <span class="hero-badge">
                RTO TRANSPORT SERVICES
            </span>

            <h1>
                Challan Payment
            </h1>

            <p>
                Pay your pending traffic challan securely
                and conveniently.
            </p>

        </div>

    </section>


    <!-- ================= MAIN ================= -->

    <main class="payment-container">


        <!-- CHALLAN SUMMARY -->

        <div class="challan-summary">

            <div class="summary-header">

                <div class="summary-icon">
                    ⚠️
                </div>

                <div>

                    <h2>
                        Pending Challan
                    </h2>

                    <p>
                        Complete your payment to clear the challan.
                    </p>

                </div>

            </div>


            <div class="summary-details">

                <div class="summary-item">

                    <span>
                        Vehicle / Challan Number
                    </span>

                    <strong>
                        <?php echo htmlspecialchars($vehicle_no); ?>
                    </strong>

                </div>


                <div class="summary-item">

                    <span>
                        Violation
                    </span>

                    <strong>
                        <?php echo htmlspecialchars($violation); ?>
                    </strong>

                </div>


                <div class="summary-item amount">

                    <span>
                        Amount Payable
                    </span>

                    <strong>
                        ₹ <?php echo $amount; ?>
                    </strong>

                </div>

            </div>

        </div>


        <!-- PAYMENT CARD -->

        <div class="payment-card">

            <div class="payment-heading">

                <span>
                    PAYMENT
                </span>

                <h2>
                    Choose Payment Method
                </h2>

                <p>
                    Select your preferred payment method below.
                </p>

            </div>


            <?php if ($message != "") { ?>

                <div class="error-message">

                    <?php echo htmlspecialchars($message); ?>

                </div>

            <?php } ?>


            <form method="POST"
                  id="paymentForm">

                                <input type="hidden" name="challan_no" value="<?php echo htmlspecialchars($challan_no, ENT_QUOTES, 'UTF-8'); ?>">


                <!-- PAYMENT METHODS -->

                <div class="payment-methods">


                    <label class="method-option">

                        <input type="radio"
                               name="payment_method"
                               value="card"
                               onchange="showPayment('card')">

                        <div class="method-box">

                            <div class="method-icon">
                                💳
                            </div>

                            <div>

                                <strong>
                                    Debit / Credit Card
                                </strong>

                                <small>
                                    Visa, Mastercard, RuPay
                                </small>

                            </div>

                        </div>

                    </label>


                    <label class="method-option">

                        <input type="radio"
                               name="payment_method"
                               value="upi"
                               onchange="showPayment('upi')">

                        <div class="method-box">

                            <div class="method-icon">
                                📱
                            </div>

                            <div>

                                <strong>
                                    UPI
                                </strong>

                                <small>
                                    Pay using UPI ID
                                </small>

                            </div>

                        </div>

                    </label>


                    <label class="method-option">

                        <input type="radio"
                               name="payment_method"
                               value="netbanking"
                               onchange="showPayment('netbanking')">

                        <div class="method-box">

                            <div class="method-icon">
                                🏦
                            </div>

                            <div>

                                <strong>
                                    Net Banking
                                </strong>

                                <small>
                                    Pay using your bank
                                </small>

                            </div>

                        </div>

                    </label>


                </div>


                <!-- ================= CARD ================= -->

                <div id="card-payment"
                     class="payment-section">

                    <h3>
                        💳 Card Payment
                    </h3>

                    <p class="section-info">
                        Enter your card details to continue.
                    </p>


                    <div class="form-group">

                        <label>
                            Card Holder Name
                        </label>

                        <input type="text"
                               name="card_name"
                               placeholder="Enter card holder name">

                    </div>


                    <div class="form-group">

                        <label>
                            Debit / Credit Card Number
                        </label>

                        <input type="text"
                               name="card_number"
                               id="card_number"
                               maxlength="19"
                               placeholder="1234 5678 9012 3456">

                    </div>


                    <div class="two-columns">

                        <div class="form-group">

                            <label>
                                Expiry Date
                            </label>

                            <input type="text"
                                   name="expiry"
                                   maxlength="5"
                                   placeholder="MM/YY">

                        </div>


                        <div class="form-group">

                            <label>
                                CVV
                            </label>

                            <input type="password"
                                   name="cvv"
                                   maxlength="3"
                                   placeholder="***">

                        </div>

                    </div>


                    <div class="security-note">

                        🔒 Your card details are used only for this
                        demo payment process.

                    </div>

                </div>


                <!-- ================= UPI ================= -->

                <div id="upi-payment"
                     class="payment-section">

                    <h3>
                        📱 UPI Payment
                    </h3>

                    <p class="section-info">
                        Enter your UPI ID.
                    </p>


                    <div class="form-group">

                        <label>
                            UPI ID
                        </label>

                        <input type="text"
                               name="upi_id"
                               placeholder="example@upi">

                    </div>

                </div>


                <!-- ================= NET BANKING ================= -->

                <div id="netbanking-payment"
                     class="payment-section">

                    <h3>
                        🏦 Net Banking
                    </h3>

                    <p class="section-info">
                        Select your bank to continue.
                    </p>


                    <div class="form-group">

                        <label>
                            Select Bank
                        </label>

                        <select name="bank" id="challan_bank">
                            <option value="">
                                Select your bank
                            </option>
                            <option value="SBI">
                                State Bank of India
                            </option>
                            <option value="HDFC">
                                HDFC Bank
                            </option>
                            <option value="ICICI">ICICI Bank</option>
                            <option value="Axis">Axis Bank</option>
                            <option value="BOB">Bank of Baroda</option>
                        </select>
                    </div>

                    <div class="form-group bank-demo-details" id="challanBankDemoDetails" hidden>
                        <h4>Demo Bank Transfer Details</h4>
                        <p>Demo data only. No real bank account or money transfer is used.</p>
                        <p>Beneficiary: <strong data-demo-bank-holder></strong></p>
                        <p>Account Number: <strong data-demo-bank-account></strong></p>
                        <p>IFSC: <strong data-demo-bank-ifsc></strong></p>
                        <p>Branch: <strong data-demo-bank-branch></strong></p>
                        <label>Demo Transaction Reference *
                            <input type="text" name="bank_reference" id="challan_bank_reference" minlength="6" maxlength="24" pattern="[A-Za-z0-9-]{6,24}" placeholder="Example: DEMO123456">
                        </label>
                    </div>

                </div>


                <!-- BUTTONS -->

                <div class="payment-navigation">

                    <a href="Challan.php"
                       class="back-btn">

                        ← Back

                    </a>


                    <button type="submit"
                            name="make_payment"
                            class="pay-btn">

                        Pay ₹ <?php echo $amount; ?>

                        <span>
                            →
                        </span>

                    </button>

                </div>


            </form>

        </div>

    </main>

</div>


<script>

function showPayment(type) {

    const sections = document.querySelectorAll(".payment-section");

    sections.forEach(function(section) {
        const active = section.id === type + "-payment";
        section.classList.toggle("active", active);
        section.querySelectorAll("input, select").forEach(function(field) {
            field.required = active && field.id !== "challan_bank_reference";
        });
    });
    if (type === "netbanking") {
        updateChallanBankDetails();
    } else {
        challanBankDetails.hidden = true;
        document.getElementById("challan_bank_reference").required = false;
    }
}

const challanBankSelect = document.getElementById("challan_bank");
const challanBankDetails = document.getElementById("challanBankDemoDetails");
const demoBanks = {
    SBI: { holder: "RTO Demo Collection", account: "123456789012", ifsc: "SBIN0001234", branch: "Ahmedabad Demo" },
    HDFC: { holder: "RTO Demo Collection", account: "234567890123", ifsc: "HDFC0001234", branch: "Ahmedabad Demo" },
    ICICI: { holder: "RTO Demo Collection", account: "345678901234", ifsc: "ICIC0001234", branch: "Ahmedabad Demo" },
    Axis: { holder: "RTO Demo Collection", account: "456789012345", ifsc: "UTIB0001234", branch: "Ahmedabad Demo" },
    BOB: { holder: "RTO Demo Collection", account: "567890123456", ifsc: "BARB0DEMOAH", branch: "Ahmedabad Demo" }
};

function updateChallanBankDetails() {
    const details = document.getElementById("netbanking-payment").classList.contains("active")
        ? demoBanks[challanBankSelect.value]
        : null;
    challanBankDetails.hidden = !details;
    challanBankDetails.querySelector("[data-demo-bank-holder]").textContent = details?.holder ?? "";
    challanBankDetails.querySelector("[data-demo-bank-account]").textContent = details?.account ?? "";
    challanBankDetails.querySelector("[data-demo-bank-ifsc]").textContent = details?.ifsc ?? "";
    challanBankDetails.querySelector("[data-demo-bank-branch]").textContent = details?.branch ?? "";
    document.getElementById("challan_bank_reference").required = Boolean(details);
}

challanBankSelect.addEventListener("change", updateChallanBankDetails);
updateChallanBankDetails();


// Card number formatting

const cardNumber =
    document.getElementById("card_number");

if (cardNumber) {

    cardNumber.addEventListener("input", function(e) {

        let value =
            e.target.value.replace(/\D/g, "");

        value =
            value.substring(0, 16);

        let formatted =
            value.match(/.{1,4}/g);

        e.target.value =
            formatted ? formatted.join(" ") : "";

    });

}

</script>

<script src="site-back-button.js?v=2" defer></script>
</body>
</html>