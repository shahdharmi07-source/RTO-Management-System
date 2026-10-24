<?php

session_start();

include "connect.php";

$error = "";


/* =====================================================
   LOGIN PROCESS
===================================================== */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";


    /* =================================================
       VALIDATION
    ================================================= */

    if ($username == "" || $password == "") {

        $error = "Please enter username/mobile number and password.";

    } else {


        /* =================================================
           FIND USER
           Username OR Mobile Number
        ================================================= */

        $stmt = $con->prepare(
            "SELECT id, full_name, username, mobile, password
             FROM users
             WHERE username = ? OR mobile = ?
             LIMIT 1"
        );


        if (!$stmt) {

            $error = "Database error: " . $con->error;

        } else {

            $stmt->bind_param(
                "ss",
                $username,
                $username
            );

            $stmt->execute();

            $result = $stmt->get_result();


            /* =================================================
               USER FOUND
            ================================================= */

            if ($result->num_rows == 1) {

                $user = $result->fetch_assoc();


                /*
                 * PASSWORD VERIFICATION
                 *
                 * Supports both:
                 * - bcrypt hashed passwords ($2y$...)
                 * - legacy plain text passwords (old records)
                 */

                $stored = $user["password"];

                $passwordOk = false;

                if (str_starts_with($stored, '$2y$') || str_starts_with($stored, '$2b$')) {
                    // bcrypt hash — use password_verify
                    $passwordOk = password_verify($password, $stored);
                } else {
                    // legacy plain text
                    $passwordOk = ($password === $stored);
                }

                if ($passwordOk) {


                    /* =========================================
                       CREATE SESSION
                    ========================================== */

                    $_SESSION["user_id"] = $user["id"];

                    $_SESSION["full_name"] = $user["full_name"];

                    $_SESSION["username"] = $user["username"];

                    $_SESSION["mobile"] = $user["mobile"];


                    /* =========================================
                       LOGIN SUCCESS
                    ========================================== */

                    $redirect = $_SESSION["post_login_redirect"] ?? "home.php";
                    unset($_SESSION["post_login_redirect"]);
                    if (!preg_match('/^challan_payment\.php\?challan_no=[A-Z0-9-]+$/i', $redirect)) {
                        $redirect = "home.php";
                    }

                    header("Location: " . $redirect);

                    exit();


                } else {

                    $error = "Invalid username or password.";

                }


            } else {

                $error = "Account not found. Please register first.";

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

    <title>RTO Citizen Login</title>


    <!-- YOUR CSS -->

    <link
        rel="stylesheet"
        href="Login.css"
    >

</head>


<body>


<div class="login-page">


    <!-- =====================================================
         LEFT INFORMATION SECTION
    ====================================================== -->

    <div class="login-info">

        <div class="info-content">


            <div class="rto-logo">

                🏛️

            </div>


            <span class="welcome-text">

                WELCOME TO

            </span>


            <h1>

                RTO Transport<br>

                Services

            </h1>


            <p>

                Access your transport services easily and securely.

                Manage your driving licence, vehicle registration,

                challan and appointments from one place.

            </p>


            <div class="service-list">


                <div class="service-item">

                    <span>✓</span>

                    <p>

                        Driving Licence Services

                    </p>

                </div>


                <div class="service-item">

                    <span>✓</span>

                    <p>

                        Vehicle Registration

                    </p>

                </div>


                <div class="service-item">

                    <span>✓</span>

                    <p>

                        Online Challan Services

                    </p>

                </div>


                <div class="service-item">

                    <span>✓</span>

                    <p>

                        Appointment Booking

                    </p>

                </div>


            </div>


        </div>

    </div>



    <!-- =====================================================
         RIGHT LOGIN SECTION
    ====================================================== -->

    <div class="login-section">


        <div class="login-card">


            <!-- =================================================
                 LOGIN HEADER
            ================================================== -->

            <div class="login-header">


                <div class="login-icon">

                    👤

                </div>


                <h2>

                    Citizen Login

                </h2>


                <p>

                    Login to access your RTO services

                </p>


            </div>



            <!-- =================================================
                 ERROR MESSAGE
            ================================================== -->

            <?php if ($error != "") { ?>

                <div class="error-message">

                    <?php

                    echo htmlspecialchars($error);

                    ?>

                </div>

            <?php } ?>



            <!-- =================================================
                 LOGIN FORM
            ================================================== -->

            <form
                action=""
                method="POST"
                class="login-form"
            >


                <!-- =================================================
                     USERNAME / MOBILE
                ================================================== -->

                <div class="input-group">


                    <label>

                        Username / Mobile Number

                    </label>


                    <div class="input-wrapper">


                        <span class="input-icon">

                            👤

                        </span>


                        <input
                            type="text"
                            name="username"
                            placeholder="Enter username or mobile number"
                            required
                        >


                    </div>

                </div>



                <!-- =================================================
                     PASSWORD
                ================================================== -->

                <div class="input-group">


                    <label>

                        Password

                    </label>


                    <div class="input-wrapper">


                        <span class="input-icon">

                            🔒

                        </span>


                        <input
                            type="password"
                            name="password"
                            id="password"
                            placeholder="Enter your password"
                            required
                        >


                        <button
                            type="button"
                            class="password-toggle"
                            onclick="togglePassword()"
                        >

                            👁

                        </button>


                    </div>

                </div>



                <!-- =================================================
                     LOGIN OPTIONS
                ================================================== -->

                <div class="login-options">


                    <label class="remember">


                        <input
                            type="checkbox"
                            name="remember"
                        >


                        <span>

                            Remember Me

                        </span>


                    </label>


                    <a
                        href="forgot-password.php"
                        class="forgot-link"
                    >

                        Forgot Password?

                    </a>


                </div>



                <!-- =================================================
                     LOGIN BUTTON
                ================================================== -->

                <button
                    type="submit"
                    class="login-button"
                >

                    Login to Account

                    <span>

                        →

                    </span>

                </button>



                <!-- =================================================
                     REGISTER
                ================================================== -->

                <div class="register-text">


                    Don't have an account?


                    <a href="register.php">

                        Register Now

                    </a>


                </div>


            </form>



            <!-- =================================================
                 SECURITY INFORMATION
            ================================================== -->

            <div class="security-info">


                <span>

                    🛡️

                </span>


                <p>

                    Your information is protected with

                    secure login authentication.

                </p>


            </div>


        </div>

    </div>


</div>



<!-- =========================================================
     SHOW / HIDE PASSWORD
========================================================== -->

<script>

function togglePassword() {

    const passwordInput =
        document.getElementById("password");


    if (passwordInput.type === "password") {

        passwordInput.type = "text";

    } else {

        passwordInput.type = "password";

    }

}

</script>


<script src="site-back-button.js?v=2" defer></script>
</body>

</html>