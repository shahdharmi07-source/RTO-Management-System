<?php

include 'connect.php';

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $full_name = trim($_POST["full_name"] ?? "");
    $mobileInput = $_POST["mobile"] ?? "";
    $mobile = trim(is_string($mobileInput) ? $mobileInput : "");
    $email = trim($_POST["email"] ?? "");
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";


    /* =========================================
       VALIDATION
    ========================================= */

    if ($full_name == "") {

        $message = "Please enter your full name.";
        $messageType = "error";

    }

    elseif (!preg_match("/^[0-9]{10}$/", $mobile)) {

        $message = "Please enter a valid 10-digit mobile number.";
        $messageType = "error";

    }

    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";
        $messageType = "error";

    }

    elseif (!preg_match("/^[A-Za-z0-9_]{4,30}$/", $username)) {

        $message = "Username must contain 4-30 characters.";
        $messageType = "error";

    }

    elseif (strlen($password) < 6) {

        $message = "Password must be at least 6 characters.";
        $messageType = "error";

    }

    elseif ($password !== $confirm_password) {

        $message = "Password and confirm password do not match.";
        $messageType = "error";

    }

    else {


        /* =========================================
           CHECK EXISTING USER
        ========================================= */

        $check = $con->prepare(
            "SELECT id FROM users
             WHERE username = ?
             OR mobile = ?
             OR email = ?
             LIMIT 1"
        );

        if (!$check) {

            $message = "Database error: " . $con->error;
            $messageType = "error";

        } else {

            $check->bind_param(
                "sss",
                $username,
                $mobile,
                $email
            );

            $check->execute();

            $result = $check->get_result();


            if ($result->num_rows > 0) {

                $message = "Username, mobile number or email already exists.";
                $messageType = "error";

            }

            else {


                /* =========================================
                   PLAIN TEXT PASSWORD
                   Mini Project Demo
                ========================================= */

                $plain_password = $password;


                /* =========================================
                   INSERT USER
                ========================================= */

                $stmt = $con->prepare(
                    "INSERT INTO users
                    (full_name, mobile, email, username, password)
                    VALUES (?, ?, ?, ?, ?)"
                );


                if (!$stmt) {

                    $message = "Database error: " . $con->error;
                    $messageType = "error";

                }

                else {

                    $stmt->bind_param(
                        "sssss",
                        $full_name,
                        $mobile,
                        $email,
                        $username,
                        $plain_password
                    );


                    if ($stmt->execute()) {

                        /*
                         * Registration successful
                         * Redirect to Login page
                         */

                        header("Location: Login.php?registered=1");
                        exit();

                    }

                    else {

                        $message = "Registration failed. Please try again.";
                        $messageType = "error";

                    }

                    $stmt->close();
                }
            }

            $check->close();
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

    <title>Citizen Registration | RTO</title>


    <!-- IMPORTANT:
         ?v=2 forces browser to load latest CSS
    -->

    <link
        rel="stylesheet"
        href="register.css?v=2"
    >

</head>


<body>


<div class="register-page">


    <!-- =========================================
         LEFT INFORMATION SECTION
    ========================================= -->

    <div class="register-info">

        <div class="info-overlay">


            <div class="rto-badge">
                RTO
            </div>


            <h1>
                Create Your Citizen Account
            </h1>


            <p>
                Register with the RTO Transport Services portal
                and access various transport-related services
                quickly and conveniently.
            </p>


            <div class="info-features">


                <div class="feature">

                    <span>✓</span>

                    <p>
                        Access Online Transport Services
                    </p>

                </div>


                <div class="feature">

                    <span>✓</span>

                    <p>
                        Apply for Driving Licence Services
                    </p>

                </div>


                <div class="feature">

                    <span>✓</span>

                    <p>
                        Manage Vehicle Registration
                    </p>

                </div>


                <div class="feature">

                    <span>✓</span>

                    <p>
                        Book RTO Appointments Easily
                    </p>

                </div>


            </div>


        </div>

    </div>



    <!-- =========================================
         REGISTRATION FORM SECTION
    ========================================= -->

    <div class="register-form-section">


        <div class="register-card">


            <!-- HEADER -->

            <div class="register-header">


                <div class="register-icon">
                    👤
                </div>


                <h2>
                    Citizen Registration
                </h2>


                <p>
                    Create your account to continue
                </p>


            </div>



            <!-- MESSAGE -->

            <?php if (!empty($message)) { ?>

                <div class="message <?php echo $messageType; ?>">

                    <?php
                    echo htmlspecialchars($message);
                    ?>

                </div>

            <?php } ?>



            <!-- FORM -->

            <form
                action=""
                method="POST"
                class="register-form"
            >



                <!-- FULL NAME -->

                <div class="form-group">

                    <label>
                        Full Name
                    </label>

                    <input
                        type="text"
                        name="full_name"
                        placeholder="Enter your full name"
                        value="<?php
                        echo htmlspecialchars(
                            $_POST['full_name'] ?? ''
                        );
                        ?>"
                        required
                    >

                </div>



                <!-- MOBILE + EMAIL -->

                <div class="form-row">


                    <div class="form-group">

                        <label>
                            Mobile Number
                        </label>

                        <input
                            type="tel"
                            name="mobile"
                            placeholder="Enter mobile number"
                            maxlength="10"
                            inputmode="numeric"
                            value="<?php
                            echo htmlspecialchars(
                                $_POST['mobile'] ?? ''
                            );
                            ?>"
                            required
                        >

                    </div>



                    <div class="form-group">

                        <label>
                            Email Address
                        </label>

                        <input
                            type="email"
                            name="email"
                            placeholder="Enter email address"
                            value="<?php
                            echo htmlspecialchars(
                                $_POST['email'] ?? ''
                            );
                            ?>"
                            required
                        >

                    </div>


                </div>



                <!-- USERNAME -->

                <div class="form-group">

                    <label>
                        Username
                    </label>

                    <input
                        type="text"
                        name="username"
                        placeholder="Create a username"
                        value="<?php
                        echo htmlspecialchars(
                            $_POST['username'] ?? ''
                        );
                        ?>"
                        required
                    >

                </div>



                <!-- PASSWORD + CONFIRM PASSWORD -->

                <div class="form-row">


                    <div class="form-group">

                        <label>
                            Password
                        </label>

                        <input
                            type="password"
                            name="password"
                            placeholder="Create password"
                            required
                        >

                    </div>



                    <div class="form-group">

                        <label>
                            Confirm Password
                        </label>

                        <input
                            type="password"
                            name="confirm_password"
                            placeholder="Confirm password"
                            required
                        >

                    </div>


                </div>



                <!-- TERMS -->

                <div class="terms-box">


                    <input
                        type="checkbox"
                        id="terms"
                        required
                    >


                    <label for="terms">

                        I agree to the

                        <a href="#">
                            Terms & Conditions
                        </a>

                        and

                        <a href="#">
                            Privacy Policy
                        </a>

                    </label>


                </div>



                <!-- REGISTER BUTTON -->

                <button
                    type="submit"
                    class="register-btn"
                >

                    Create Account

                </button>



                <!-- LOGIN -->

                <div class="login-link">

                    Already have an account?

                    <a href="Login.php">
                        Login Now
                    </a>

                </div>


            </form>


        </div>

    </div>


</div>


<script src="mobile-validation.js?v=1" defer></script>
<script src="site-back-button.js?v=2" defer></script>
</body>

</html>