<?php

session_start();

require_once "connect.php";

if (!$con || !($con instanceof mysqli)) {
    http_response_code(503);
    exit("Database unavailable. Please start MySQL and try again.");
}
mysqli_report(MYSQLI_REPORT_OFF);

/* =====================================================
   STEP 1 COMPLETE CHECK
===================================================== */

if (!isset($_SESSION['application_completed']) ||
    $_SESSION['application_completed'] !== true) {

    header("Location: new_licence.php");
    exit();
}

$message = "";


/* =====================================================
   UPLOAD FOLDER
===================================================== */

$uploadFolder = "uploads/documents/";

if (!is_dir($uploadFolder)) {
    mkdir($uploadFolder, 0777, true);
}


/* =====================================================
   DOCUMENT UPLOAD
===================================================== */

if (isset($_POST['submit_documents'])) {

    $allowedExtensions = array(
        "jpg",
        "jpeg",
        "png",
        "pdf"
    );

    $allowedMimeTypes = array(
        "image/jpeg",
        "image/png",
        "application/pdf"
    );

    $maxFileSize = 2 * 1024 * 1024; // 2 MB


    /* =================================================
       DOCUMENT NAMES
    ================================================= */

    $documents = array(

        "aadhaar" => "Aadhaar Card",

        "address_proof" => "Address Proof",

        "photo" => "Passport Size Photo",

        "signature" => "Signature"

    );


    $uploadedFiles = array();

    $errorFound = false;


    /* =================================================
       CHECK EACH DOCUMENT
    ================================================= */

    foreach ($documents as $fieldName => $documentName) {

        /* FILE SELECT CHECK */

        if (
            !isset($_FILES[$fieldName]) ||
            $_FILES[$fieldName]['error'] == UPLOAD_ERR_NO_FILE
        ) {

            $message = "Please upload " . $documentName . ".";

            $errorFound = true;

            break;
        }


        $fileName = $_FILES[$fieldName]['name'];

        $fileSize = $_FILES[$fieldName]['size'];

        $tmpName = $_FILES[$fieldName]['tmp_name'];

        $fileError = $_FILES[$fieldName]['error'];


        /* FILE ERROR */

        if ($fileError != UPLOAD_ERR_OK) {

            $message = "Error uploading " . $documentName . ".";

            $errorFound = true;

            break;
        }


        /* FILE SIZE */

        if ($fileSize > $maxFileSize) {

            $message =
                $documentName .
                " must be less than or equal to 2 MB.";

            $errorFound = true;

            break;
        }


        /* FILE EXTENSION */

        $extension = strtolower(
            pathinfo($fileName, PATHINFO_EXTENSION)
        );


        if (!in_array($extension, $allowedExtensions)) {

            $message =
                $documentName .
                " must be JPG, JPEG, PNG or PDF.";

            $errorFound = true;

            break;
        }


        /* MIME CHECK */

        $fileMime = mime_content_type($tmpName);


        if (!in_array($fileMime, $allowedMimeTypes)) {

            $message =
                "Invalid file selected for " .
                $documentName . ".";

            $errorFound = true;

            break;
        }


        /* UNIQUE FILE NAME */

        $newFileName =
            $fieldName . "_" .
            time() . "_" .
            rand(1000, 9999) .
            "." .
            $extension;


        $destination =
            $uploadFolder . $newFileName;


        /* MOVE FILE */

        if (!move_uploaded_file($tmpName, $destination)) {

            $message =
                "Unable to upload " .
                $documentName . ".";

            $errorFound = true;

            break;
        }


        $uploadedFiles[$fieldName] = $destination;
    }


    /* =====================================================
       SAVE DOCUMENTS INTO DATABASE
       
       TABLE:
       documents

       COLUMNS:
       id
       aadhaar_file
       address_proof_file
       passport_photo_file
       signature_file
    ===================================================== */

    if (!$errorFound) {

        $aadhaar_file =
            $uploadedFiles['aadhaar'];

        $address_proof_file =
            $uploadedFiles['address_proof'];

        $passport_photo_file =
            $uploadedFiles['photo'];

        $signature_file =
            $uploadedFiles['signature'];


        /* =================================================
           INSERT DOCUMENTS

           NO application_id
        ================================================= */

        $insertSql = "

            INSERT INTO documents
            (
                user_id,
                application_id,
                applicant_name,
                aadhaar_file,
                address_proof_file,
                passport_photo_file,
                signature_file
            )

            VALUES
            (
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?
            )

        ";


        $insertStmt = mysqli_prepare(
            $con,
            $insertSql
        );


        if (!$insertStmt) {

            $message =
                "Database query error: " .
                mysqli_error($con);

        } else {

            $documentUserId = (int) $_SESSION["user_id"];
            $documentApplicationId = (int) ($_SESSION["licence_application_id"] ?? 0);
            $documentApplicantName = (string) ($_SESSION["full_name"] ?? "Registered User");

            mysqli_stmt_bind_param(
                $insertStmt,
                "iisssss",
                $documentUserId,
                $documentApplicationId,
                $documentApplicantName,
                $aadhaar_file,
                $address_proof_file,
                $passport_photo_file,
                $signature_file
            );


            if (mysqli_stmt_execute($insertStmt)) {

                /* STEP 2 COMPLETE */

                $_SESSION['documents_completed'] = true;

                $_SESSION['uploaded_documents'] =
                    $uploadedFiles;


                /* GO TO STEP 3 */

                header("Location: payment.php");

                exit();

            } else {

                $message =
                    "Database error while saving documents: " .
                    mysqli_stmt_error($insertStmt);
            }


            mysqli_stmt_close($insertStmt);
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

    <title>Documents - Step 2</title>

    <link
        rel="stylesheet"
        href="documents.css"
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

            <div class="step active">

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
         PAGE HEADER
    ================================================= -->

    <div class="page-header">

        <div>

            <h2>
                Upload Documents
            </h2>

            <p>
                Step 02 - Upload the required documents.
            </p>

        </div>


        <div class="application-icon">
            📄
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
         DOCUMENT FORM
    ================================================= -->

    <form
        method="POST"
        enctype="multipart/form-data"
    >


        <!-- =================================================
             DOCUMENT CARD
        ================================================= -->

        <div class="form-card">


            <div class="section-title">

                <span>
                    2
                </span>

                <div>

                    <h2>
                        Required Documents
                    </h2>

                    <p>
                        Upload all required documents.
                    </p>

                </div>

            </div>



            <div class="document-grid">


                <!-- =================================================
                     AADHAAR
                ================================================= -->

                <div class="document-box">

                    <div class="document-icon">
                        🪪
                    </div>


                    <div class="document-content">

                        <h3>
                            Aadhaar Card
                        </h3>

                        <p>
                            Upload your Aadhaar card.
                        </p>

                        <small>
                            JPG, JPEG, PNG or PDF | Max 2 MB
                        </small>


                        <label class="upload-btn">

                            Choose File

                            <input
                                type="file"
                                name="aadhaar"
                                accept=".jpg,.jpeg,.png,.pdf"
                                onchange="showFileName(this, 'aadhaarName')"
                            >

                        </label>


                        <div
                            class="file-name"
                            id="aadhaarName"
                        >
                            No file selected
                        </div>

                    </div>

                </div>



                <!-- =================================================
                     ADDRESS PROOF
                ================================================= -->

                <div class="document-box">

                    <div class="document-icon">
                        🏠
                    </div>


                    <div class="document-content">

                        <h3>
                            Address Proof
                        </h3>

                        <p>
                            Upload your address proof.
                        </p>

                        <small>
                            JPG, JPEG, PNG or PDF | Max 2 MB
                        </small>


                        <label class="upload-btn">

                            Choose File

                            <input
                                type="file"
                                name="address_proof"
                                accept=".jpg,.jpeg,.png,.pdf"
                                onchange="showFileName(this, 'addressName')"
                            >

                        </label>


                        <div
                            class="file-name"
                            id="addressName"
                        >
                            No file selected
                        </div>

                    </div>

                </div>



                <!-- =================================================
                     PASSPORT PHOTO
                ================================================= -->

                <div class="document-box">

                    <div class="document-icon">
                        📷
                    </div>


                    <div class="document-content">

                        <h3>
                            Passport Size Photo
                        </h3>

                        <p>
                            Upload your passport size photo.
                        </p>

                        <small>
                            JPG, JPEG or PNG | Max 2 MB
                        </small>


                        <label class="upload-btn">

                            Choose File

                            <input
                                type="file"
                                name="photo"
                                accept=".jpg,.jpeg,.png"
                                onchange="showFileName(this, 'photoName')"
                            >

                        </label>


                        <div
                            class="file-name"
                            id="photoName"
                        >
                            No file selected
                        </div>

                    </div>

                </div>



                <!-- =================================================
                     SIGNATURE
                ================================================= -->

                <div class="document-box">

                    <div class="document-icon">
                        ✍️
                    </div>


                    <div class="document-content">

                        <h3>
                            Signature
                        </h3>

                        <p>
                            Upload your signature.
                        </p>

                        <small>
                            JPG, JPEG or PNG | Max 2 MB
                        </small>


                        <label class="upload-btn">

                            Choose File

                            <input
                                type="file"
                                name="signature"
                                accept=".jpg,.jpeg,.png"
                                onchange="showFileName(this, 'signatureName')"
                            >

                        </label>


                        <div
                            class="file-name"
                            id="signatureName"
                        >
                            No file selected
                        </div>

                    </div>

                </div>


            </div>



            <!-- =================================================
                 UPLOAD INFORMATION
            ================================================= -->

            <div class="upload-info">

                <strong>
                    📌 Upload Instructions
                </strong>

                <ul>

                    <li>
                        Maximum file size: 2 MB
                    </li>

                    <li>
                        Aadhaar and Address Proof can be JPG, PNG or PDF
                    </li>

                    <li>
                        Passport Photo and Signature should be JPG or PNG
                    </li>

                    <li>
                        Make sure all documents are clear and readable
                    </li>

                </ul>

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
                required
            >

            <label for="declaration">

                I confirm that all uploaded documents
                are correct and belong to me.

            </label>

        </div>



        <!-- =================================================
             BUTTONS
        ================================================= -->

        <div class="form-buttons">

            <a
                href="new_licence.php"
                class="cancel-btn"
            >
                ← Back
            </a>


            <button
                type="submit"
                name="submit_documents"
                class="submit-btn"
            >

                Upload Documents & Continue →

            </button>

        </div>


    </form>


</div>



<!-- =====================================================
     JAVASCRIPT
===================================================== -->

<script>

function showFileName(input, targetId) {

    const target =
        document.getElementById(targetId);


    if (input.files.length > 0) {

        target.innerText =
            input.files[0].name;

        target.classList.add("selected");

    } else {

        target.innerText =
            "No file selected";

        target.classList.remove("selected");

    }

}

</script>


<script src="site-back-button.js?v=3" defer></script>
</body>

</html>