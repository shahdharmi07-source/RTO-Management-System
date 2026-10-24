<?php

include("connect.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$message = "";
$messageType = "";

$licence = null;
$userId = (int) ($_SESSION['user_id'] ?? 0);

if ($userId < 1) {
    header("Location: Login.php");
    exit();
}


/* =====================================================
   STEP 1 : VERIFY LICENCE
===================================================== */

if (isset($_POST['search_licence'])) {

    $mobileInput = $_POST['mobile_no'] ?? "";
    $mobile_no = trim(is_string($mobileInput) ? $mobileInput : "");
    $aadhaar_no = trim($_POST['aadhaar_no']);

    if (!ctype_digit($mobile_no) || strlen($mobile_no) !== 10) {
        $message = "Please enter a valid 10-digit mobile number using digits only.";
        $messageType = "error";
    } else {

    /* =================================================
       CHECK LICENCE TABLE
    ================================================= */

    $sql = "
        SELECT *
        FROM licence
        WHERE mobile_no = ?
        AND aadhaar_no = ?
        AND (user_id IS NULL OR user_id = ?)
        LIMIT 1
    ";

    $stmt = mysqli_prepare($con, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "ssi",
        $mobile_no,
        $aadhaar_no,
        $userId
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);


    /* =================================================
       LICENCE FOUND
    ================================================= */

    if (mysqli_num_rows($result) > 0) {

        $licence = mysqli_fetch_assoc($result);


        /* =============================================
           SAVE MOBILE + AADHAAR INTO renewlicence
        ============================================= */

        $checkSql = "
            SELECT id
            FROM renewlicence
            WHERE mobile_no = ?
            AND aadhaar_no = ?
            LIMIT 1
        ";

        $checkStmt = mysqli_prepare(
            $con,
            $checkSql
        );

        mysqli_stmt_bind_param(
            $checkStmt,
            "ss",
            $mobile_no,
            $aadhaar_no
        );

        mysqli_stmt_execute($checkStmt);

        $checkResult = mysqli_stmt_get_result(
            $checkStmt
        );


        /* =============================================
           INSERT ONLY IF NOT ALREADY EXISTS
        ============================================= */

        if (mysqli_num_rows($checkResult) == 0) {

            $insertSql = "
                INSERT INTO renewlicence
                (
                    mobile_no,
                    aadhaar_no
                )
                VALUES
                (
                    ?,
                    ?
                )
            ";

            $insertStmt = mysqli_prepare(
                $con,
                $insertSql
            );

            mysqli_stmt_bind_param(
                $insertStmt,
                "ss",
                $mobile_no,
                $aadhaar_no
            );

            if (mysqli_stmt_execute($insertStmt)) {

                $message =
                    "Licence verified successfully and renewal record saved.";

                $messageType = "success";

            } else {

                $message =
                    "Licence verified, but renewal record could not be saved.";

                $messageType = "error";
            }

        } else {

            $message =
                "Licence verified successfully.";

            $messageType = "success";
        }


        /* SAVE IN SESSION */

        $_SESSION['renew_mobile_no'] = $mobile_no;
        $_SESSION['renew_aadhaar_no'] = $aadhaar_no;
        $_SESSION['renew_licence_id'] = $licence['id'];

    }

    /* =================================================
       LICENCE NOT FOUND
    ================================================= */

    else {

        $message =
            "Licence not found. Please check your Aadhaar Number and Mobile Number.";

        $messageType = "error";
    }
    }
}


/* =====================================================
   STEP 2 : UPDATE LICENCE
===================================================== */

if (isset($_POST['update_licence'])) {

    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

    $full_name = trim($_POST['full_name']);
    $mobileInput = $_POST['mobile_no'] ?? "";
    $mobile_no = trim(is_string($mobileInput) ? $mobileInput : "");
    $email = trim($_POST['email']);

    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $state = trim($_POST['state']);
    $pincode = trim($_POST['pincode']);

    $licence_type = trim($_POST['licence_type']);
    $vehicle_class = trim($_POST['vehicle_class']);

    $document_type = trim($_POST['document_type']);

    if (
        $id === false ||
        $id < 1 ||
        (int) ($_SESSION['renew_licence_id'] ?? 0) !== $id
    ) {
        $message = "Please verify your licence again before updating it.";
        $messageType = "error";
    } elseif (!ctype_digit($mobile_no) || strlen($mobile_no) !== 10) {
        $message = "Please enter a valid 10-digit mobile number using digits only.";
        $messageType = "error";
    } else {

    /* =================================================
       UPDATE LICENCE TABLE
    ================================================= */

    $updateSql = "
        UPDATE licence
        SET
            full_name = ?,
            mobile_no = ?,
            email = ?,
            address = ?,
            city = ?,
            state = ?,
            pincode = ?,
            licence_type = ?,
            vehicle_class = ?,
            document_type = ?,
            user_id = ?

        WHERE id = ?
        AND (user_id IS NULL OR user_id = ?)
    ";

    $transactionStarted = false;
    try {
        if (!$con->begin_transaction()) {
            throw new RuntimeException("Could not start the licence renewal transaction.");
        }
        $transactionStarted = true;

        $updateStmt = mysqli_prepare($con, $updateSql);
        if (!$updateStmt) {
            throw new RuntimeException("Could not prepare the licence renewal update.");
        }

        mysqli_stmt_bind_param(
            $updateStmt,
            "ssssssssssiii",
            $full_name,
            $mobile_no,
            $email,
            $address,
            $city,
            $state,
            $pincode,
            $licence_type,
            $vehicle_class,
            $document_type,
            $userId,
            $id,
            $userId
        );

        if (!mysqli_stmt_execute($updateStmt)) {
            $updateError = mysqli_stmt_error($updateStmt);
            mysqli_stmt_close($updateStmt);
            throw new RuntimeException("Licence could not be renewed: " . $updateError);
        }
        mysqli_stmt_close($updateStmt);

        $getStmt = mysqli_prepare($con, "SELECT * FROM licence WHERE id = ? AND user_id = ? LIMIT 1");
        if (!$getStmt) {
            throw new RuntimeException("Could not load the renewed licence details.");
        }
        mysqli_stmt_bind_param($getStmt, "ii", $id, $userId);
        if (!mysqli_stmt_execute($getStmt)) {
            mysqli_stmt_close($getStmt);
            throw new RuntimeException("Could not load the renewed licence details.");
        }
        $licence = mysqli_fetch_assoc(mysqli_stmt_get_result($getStmt));
        mysqli_stmt_close($getStmt);

        if (!$licence) {
            throw new RuntimeException("The renewed licence could not be found for this account.");
        }

        $licenceData = json_encode($licence);
        if ($licenceData === false) {
            throw new RuntimeException("The renewal receipt details could not be prepared.");
        }

        $receiptStmt = mysqli_prepare(
            $con,
            "INSERT INTO licence_renewals (user_id, licence_id, licence_data) VALUES (?, ?, ?)"
        );
        if (!$receiptStmt) {
            throw new RuntimeException("Renewal receipt storage is unavailable. Run the database migration and try again.");
        }
        mysqli_stmt_bind_param($receiptStmt, "iis", $userId, $id, $licenceData);
        if (!mysqli_stmt_execute($receiptStmt)) {
            $receiptError = mysqli_stmt_error($receiptStmt);
            mysqli_stmt_close($receiptStmt);
            throw new RuntimeException("Renewal receipt could not be saved: " . $receiptError);
        }
        mysqli_stmt_close($receiptStmt);

        $renewStmt = mysqli_prepare(
            $con,
            "UPDATE renewlicence SET mobile_no = ?, aadhaar_no = ? WHERE mobile_no = ? AND aadhaar_no = ?"
        );
        if (!$renewStmt) {
            throw new RuntimeException("Could not update the renewal verification record.");
        }

        $oldMobile = $_SESSION['renew_mobile_no'] ?? "";
        $oldAadhaar = $_SESSION['renew_aadhaar_no'] ?? "";
        mysqli_stmt_bind_param($renewStmt, "ssss", $mobile_no, $oldAadhaar, $oldMobile, $oldAadhaar);
        if (!mysqli_stmt_execute($renewStmt)) {
            mysqli_stmt_close($renewStmt);
            throw new RuntimeException("Could not update the renewal verification record.");
        }
        mysqli_stmt_close($renewStmt);

        if (!$con->commit()) {
            throw new RuntimeException("Could not save the licence renewal.");
        }
        $transactionStarted = false;

        $_SESSION['renew_mobile_no'] = $mobile_no;
        $_SESSION['renew_licence_id'] = (int) $id;
        $message = "Driving Licence Renewed / Updated Successfully!";
        $messageType = "success";
    } catch (Throwable $exception) {
        if ($transactionStarted) {
            $con->rollback();
        }
        $licence = null;
        $message = $exception->getMessage();
        $messageType = "error";
    }
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        Renew / Update Driving Licence
    </title>

    <link rel="stylesheet"
          href="renew_licence.css">

</head>


<body>


<div class="page-container">


    <!-- =================================================
         HEADER
    ================================================= -->

    <div class="page-header">

        <div>

            <h1>
                Renew / Update Driving Licence
            </h1>

            <p>
                Renew your existing driving licence or update your personal information
            </p>

        </div>

        <div class="application-icon">
            🔄
        </div>

    </div>


    <!-- =================================================
         MESSAGE
    ================================================= -->

    <!-- =================================================
         MESSAGE / SUCCESS CARD
    ================================================= -->

    <?php if ($message != "" && $messageType == "success" && strpos($message, "Renewed") !== false) { ?>

        <!-- FULL SUCCESS CARD after renewal -->
        <div style="max-width:680px;margin:40px auto;background:white;border-radius:18px;box-shadow:0 10px 35px rgba(0,0,0,.08);overflow:hidden;">

            <!-- Green top bar -->
            <div style="background:linear-gradient(135deg,#00897B,#00796B);padding:36px 40px;text-align:center;color:white;">
                <div style="width:72px;height:72px;background:rgba(255,255,255,.2);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:36px;margin:0 auto 16px;">✅</div>
                <h2 style="margin:0 0 8px;font-size:24px;">Licence Renewed Successfully!</h2>
                <p style="margin:0;opacity:.85;font-size:15px;">Your driving licence has been updated.</p>
            </div>

            <!-- Details -->
            <div style="padding:30px 40px;">

                <?php if ($licence): ?>
                <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:20px;margin-bottom:24px;">
                    <p style="margin:0 0 4px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;">Licence Details</p>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:14px;">
                        <div>
                            <p style="margin:0;font-size:12px;color:#94a3b8;">Full Name</p>
                            <p style="margin:4px 0 0;font-weight:600;color:#0f172a;"><?php echo htmlspecialchars($licence['full_name'] ?? ''); ?></p>
                        </div>
                        <div>
                            <p style="margin:0;font-size:12px;color:#94a3b8;">Licence Type</p>
                            <p style="margin:4px 0 0;font-weight:600;color:#0f172a;"><?php echo htmlspecialchars($licence['licence_type'] ?? ''); ?></p>
                        </div>
                        <div>
                            <p style="margin:0;font-size:12px;color:#94a3b8;">Mobile Number</p>
                            <p style="margin:4px 0 0;font-weight:600;color:#0f172a;"><?php echo htmlspecialchars($licence['mobile_no'] ?? ''); ?></p>
                        </div>
                        <div>
                            <p style="margin:0;font-size:12px;color:#94a3b8;">Vehicle Class</p>
                            <p style="margin:4px 0 0;font-weight:600;color:#0f172a;"><?php echo htmlspecialchars($licence['vehicle_class'] ?? ''); ?></p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <a href="Dashboard.php"
                   style="display:flex;align-items:center;justify-content:center;gap:10px;background:linear-gradient(135deg,#0284c7,#0369a1);color:white;padding:14px 24px;border-radius:12px;font-weight:700;font-size:16px;text-decoration:none;box-shadow:0 4px 14px rgba(2,132,199,.35);">
                    🏠 Go to Dashboard
                </a>

            </div>
        </div>

    <?php } elseif ($message != "") { ?>

        <div class="<?php echo $messageType; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>

    <?php } ?>


    <!-- =================================================
         STEP 1 : SEARCH LICENCE
    ================================================= -->

    <?php
    $renewalDone = ($message != "" && $messageType == "success" && strpos($message, "Renewed") !== false);
    ?>

    <?php if (!$renewalDone): ?>

    <?php if ($licence == null) { ?>

    <div class="form-card">

        <div class="section-title">

            <span>01</span>

            <div>

                <h2>
                    Find Your Licence
                </h2>

                <p>
                    Enter your registered mobile number and Aadhaar number
                </p>

            </div>

        </div>


        <form method="POST"
              action="renew_licence.php">


            <div class="form-grid">


                <!-- MOBILE -->

                <div class="form-group">

                    <label>
                        Mobile Number *
                    </label>

                    <input
                        type="text"
                        name="mobile_no"
                        maxlength="10"
                        pattern="[0-9]{10}"
                        placeholder="Enter registered mobile number"
                        required>

                </div>


                <!-- AADHAAR -->

                <div class="form-group">

                    <label>
                        Aadhaar Number *
                    </label>

                    <input
                        type="text"
                        name="aadhaar_no"
                        maxlength="12"
                        pattern="[0-9]{12}"
                        placeholder="Enter Aadhaar number"
                        required>

                </div>


            </div>


            <div class="form-buttons">


                <a                 href="licence_services.php"
                   class="cancel-btn">

                    ← Back

                </a>


                <button
                    type="submit"
                    name="search_licence"
                    class="submit-btn">

                    Verify Licence →

                </button>


            </div>


        </form>

    </div>

    <?php } ?>


    <!-- =================================================
         STEP 2 : LICENCE DETAILS
    ================================================= -->

    <?php if ($licence != null) { ?>

    <div class="form-card">


        <div class="section-title">

            <span>02</span>

            <div>

                <h2>
                    Licence Details
                </h2>

                <p>
                    Update your personal and licence information
                </p>

            </div>

        </div>


        <form method="POST"
              action="renew_licence.php">


            <!-- ID -->

            <input
                type="hidden"
                name="id"
                value="<?php echo $licence['id']; ?>">


            <div class="form-grid">


                <!-- FULL NAME -->

                <div class="form-group full">

                    <label>
                        Full Name *
                    </label>

                    <input
                        type="text"
                        name="full_name"
                        value="<?php echo htmlspecialchars($licence['full_name']); ?>"
                        required>

                </div>


                <!-- MOBILE -->

                <div class="form-group">

                    <label>
                        Mobile Number *
                    </label>

                    <input
                        type="text"
                        name="mobile_no"
                        maxlength="10"
                        value="<?php echo htmlspecialchars($licence['mobile_no']); ?>"
                        required>

                </div>


                <!-- EMAIL -->

                <div class="form-group">

                    <label>
                        Email Address *
                    </label>

                    <input
                        type="email"
                        name="email"
                        value="<?php echo htmlspecialchars($licence['email']); ?>"
                        required>

                </div>


                <!-- ADDRESS -->

                <div class="form-group full">

                    <label>
                        Complete Address *
                    </label>

                    <textarea
                        name="address"
                        required><?php echo htmlspecialchars($licence['address']); ?></textarea>

                </div>


                <!-- CITY -->

                <div class="form-group">

                    <label>
                        City *
                    </label>

                    <input
                        type="text"
                        name="city"
                        value="<?php echo htmlspecialchars($licence['city']); ?>"
                        required>

                </div>


                <!-- STATE -->

                <div class="form-group">

                    <label>
                        State *
                    </label>

                    <select
                        name="state"
                        required>

                        <option value="">
                            Select State
                        </option>

                        <?php

                        $states = [

                            "Andhra Pradesh",
                            "Arunachal Pradesh",
                            "Assam",
                            "Bihar",
                            "Chhattisgarh",
                            "Goa",
                            "Gujarat",
                            "Haryana",
                            "Himachal Pradesh",
                            "Jharkhand",
                            "Karnataka",
                            "Kerala",
                            "Madhya Pradesh",
                            "Maharashtra",
                            "Manipur",
                            "Meghalaya",
                            "Mizoram",
                            "Nagaland",
                            "Odisha",
                            "Punjab",
                            "Rajasthan",
                            "Sikkim",
                            "Tamil Nadu",
                            "Telangana",
                            "Tripura",
                            "Uttar Pradesh",
                            "Uttarakhand",
                            "West Bengal"

                        ];


                        foreach ($states as $state) {

                            $selected =
                                ($licence['state'] == $state)
                                ? "selected"
                                : "";

                            echo
                                '<option value="' .
                                htmlspecialchars($state) .
                                '" ' .
                                $selected .
                                '>' .
                                htmlspecialchars($state) .
                                '</option>';

                        }

                        ?>

                    </select>

                </div>


                <!-- PINCODE -->

                <div class="form-group">

                    <label>
                        PIN Code *
                    </label>

                    <input
                        type="text"
                        name="pincode"
                        maxlength="6"
                        value="<?php echo htmlspecialchars($licence['pincode']); ?>"
                        required>

                </div>


                <!-- LICENCE TYPE -->

                <div class="form-group">

                    <label>
                        Licence Type *
                    </label>

                    <select
                        name="licence_type"
                        required>

                        <option value="Learner Licence"
                            <?php
                            if ($licence['licence_type'] == "Learner Licence")
                                echo "selected";
                            ?>>
                            Learner's Licence
                        </option>

                        <option value="Permanent Licence"
                            <?php
                            if ($licence['licence_type'] == "Permanent Licence")
                                echo "selected";
                            ?>>
                            Permanent Driving Licence
                        </option>

                    </select>

                </div>


                <!-- VEHICLE CLASS -->

                <div class="form-group">

                    <label>
                        Vehicle Class *
                    </label>

                    <select
                        name="vehicle_class"
                        required>

                        <option value="">
                            Select Vehicle Class
                        </option>

                        <option value="Motorcycle"
                            <?php
                            if ($licence['vehicle_class'] == "Motorcycle")
                                echo "selected";
                            ?>>
                            Motorcycle
                        </option>

                        <option value="LMV"
                            <?php
                            if ($licence['vehicle_class'] == "LMV")
                                echo "selected";
                            ?>>
                            LMV - Light Motor Vehicle
                        </option>

                        <option value="Transport"
                            <?php
                            if ($licence['vehicle_class'] == "Transport")
                                echo "selected";
                            ?>>
                            Transport Vehicle
                        </option>

                    </select>

                </div>


                <!-- DOCUMENT -->

                <div class="form-group full">

                    <label>
                        Document Type *
                    </label>

                    <select
                        name="document_type"
                        required>

                        <option value="Aadhaar Card"
                            <?php
                            if ($licence['document_type'] == "Aadhaar Card")
                                echo "selected";
                            ?>>
                            Aadhaar Card
                        </option>

                        <option value="Passport"
                            <?php
                            if ($licence['document_type'] == "Passport")
                                echo "selected";
                            ?>>
                            Passport
                        </option>

                        <option value="Voter ID"
                            <?php
                            if ($licence['document_type'] == "Voter ID")
                                echo "selected";
                            ?>>
                            Voter ID
                        </option>

                    </select>

                </div>


            </div>


            <div class="form-buttons">


                <a                 href="licence_services.php"
                   class="cancel-btn">

                    ← Back

                </a>


                <button
                    type="submit"
                    name="update_licence"
                    class="submit-btn">

                    Renew / Update Licence →

                </button>


            </div>


        </form>

    </div>

    <?php } ?>

    <?php endif; /* !$renewalDone */ ?>

</div>

<script src="mobile-validation.js?v=1" defer></script>

<script src="site-back-button.js?v=2" defer></script>
</body>

</html>