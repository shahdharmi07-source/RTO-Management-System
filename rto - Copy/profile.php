<?php
include "connect.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["user_id"])) {
    header("Location: Login.php");
    exit();
}

$userId = (int) $_SESSION["user_id"];
$message = "";
$messageType = "";
$csrfToken = $_SESSION["profile_csrf_token"] ?? "";

if ($csrfToken === "") {
    $csrfToken = bin2hex(random_bytes(32));
    $_SESSION["profile_csrf_token"] = $csrfToken;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $postedToken = $_POST["csrf_token"] ?? "";
    $fullNameInput = $_POST["full_name"] ?? "";
    $mobileInput = $_POST["mobile"] ?? "";
    $emailInput = $_POST["email"] ?? "";
    $fullName = trim(is_string($fullNameInput) ? $fullNameInput : "");
    $mobile = trim(is_string($mobileInput) ? $mobileInput : "");
    $email = trim(is_string($emailInput) ? $emailInput : "");

    if (!is_string($postedToken) || !hash_equals($csrfToken, $postedToken)) {
        $message = "Your session expired. Please refresh the page and try again.";
        $messageType = "error";
    } elseif ($fullName === "" || strlen($fullName) > 150) {
        $message = "Please enter your name (up to 150 characters).";
        $messageType = "error";
    } elseif (!preg_match("/^[0-9]{10}$/", $mobile)) {
        $message = "Please enter a valid 10-digit mobile number.";
        $messageType = "error";
    } elseif (strlen($email) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
        $messageType = "error";
    } else {
        $duplicateStmt = $con->prepare(
            "SELECT id FROM users
             WHERE (mobile = ? OR email = ?) AND id <> ?
             LIMIT 1"
        );

        if (!$duplicateStmt) {
            $message = "Could not check your account details. Please try again.";
            $messageType = "error";
        } else {
            $duplicateStmt->bind_param("ssi", $mobile, $email, $userId);
            if (!$duplicateStmt->execute()) {
                $message = "Could not check your account details. Please try again.";
                $messageType = "error";
            } else {
                $duplicateResult = $duplicateStmt->get_result();
                if ($duplicateResult && $duplicateResult->num_rows > 0) {
                    $message = "That mobile number or email is already used by another account.";
                    $messageType = "error";
                } else {
                    $updateStmt = $con->prepare(
                        "UPDATE users SET full_name = ?, mobile = ?, email = ? WHERE id = ?"
                    );

                    if (!$updateStmt) {
                        $message = "Could not prepare your profile update. Please try again.";
                        $messageType = "error";
                    } else {
                        $updateStmt->bind_param("sssi", $fullName, $mobile, $email, $userId);
                        if ($updateStmt->execute()) {
                            $_SESSION["full_name"] = $fullName;
                            $_SESSION["mobile"] = $mobile;
                            $message = "Your profile has been updated.";
                            $messageType = "success";
                        } else {
                            $message = "Could not save your profile. Please try again.";
                            $messageType = "error";
                        }
                        $updateStmt->close();
                    }
                }
            }
            $duplicateStmt->close();
        }
    }
}

$profileStmt = $con->prepare(
    "SELECT full_name, username, mobile, email FROM users WHERE id = ? LIMIT 1"
);

if (!$profileStmt) {
    http_response_code(500);
    exit("Could not load your profile. Please try again later.");
}

$profileStmt->bind_param("i", $userId);
if (!$profileStmt->execute()) {
    $profileStmt->close();
    http_response_code(500);
    exit("Could not load your profile. Please try again later.");
}

$profileResult = $profileStmt->get_result();
$profile = $profileResult ? $profileResult->fetch_assoc() : null;
$profileStmt->close();

if (!$profile) {
    http_response_code(404);
    exit("The profile for this account could not be found.");
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && $messageType === "error") {
    $profile["full_name"] = $fullName;
    $profile["mobile"] = $mobile;
    $profile["email"] = $email;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - RTO</title>
    <link rel="stylesheet" href="profile.css">
</head>
<body>
    <main class="profile-page">
        <a class="back-link" href="Dashboard.php">← Back to Dashboard</a>

        <section class="profile-card">
            <header class="profile-header">
                <div class="profile-avatar" aria-hidden="true">👤</div>
                <div>
                    <p class="eyebrow">RTO CITIZEN ACCOUNT</p>
                    <h1>My Profile</h1>
                    <p>View and update your account information.</p>
                </div>
            </header>

            <?php if ($message !== ""): ?>
                <div class="form-message <?php echo htmlspecialchars($messageType); ?>" role="status">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form method="post" class="profile-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

                <div class="form-grid">
                    <label class="form-field">
                        <span>Full Name</span>
                        <input
                            type="text"
                            name="full_name"
                            maxlength="150"
                            value="<?php echo htmlspecialchars($profile["full_name"]); ?>"
                            autocomplete="name"
                            required
                        >
                    </label>

                    <label class="form-field">
                        <span>Username</span>
                        <input
                            type="text"
                            value="<?php echo htmlspecialchars($profile["username"]); ?>"
                            autocomplete="username"
                            readonly
                        >
                        <small>Username cannot be changed.</small>
                    </label>

                    <label class="form-field">
                        <span>Mobile Number</span>
                        <input
                            type="tel"
                            name="mobile"
                            pattern="[0-9]{10}"
                            maxlength="10"
                            value="<?php echo htmlspecialchars($profile["mobile"]); ?>"
                            autocomplete="tel"
                            required
                        >
                    </label>

                    <label class="form-field">
                        <span>Email Address</span>
                        <input
                            type="email"
                            name="email"
                            maxlength="150"
                            value="<?php echo htmlspecialchars($profile["email"]); ?>"
                            autocomplete="email"
                            required
                        >
                    </label>
                </div>

                <div class="form-actions">
                    <a class="cancel-button" href="Dashboard.php">Cancel</a>
                    <button class="save-button" type="submit">Save Changes</button>
                </div>
            </form>
        </section>
    </main>
<script src="mobile-validation.js?v=1" defer></script>
<script src="site-back-button.js?v=3" defer></script>
</body>
</html>