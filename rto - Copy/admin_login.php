<?php
session_start();
require_once "connect.php";

mysqli_report(MYSQLI_REPORT_OFF);
$error = "";

if (!$con || !($con instanceof mysqli)) {
    $error = "The database is currently unavailable. Please start MySQL in XAMPP and try again.";
} elseif (!isset($_SESSION["admin_id"])) {
    $adminCheck = $con->query("SELECT id FROM admin_users LIMIT 1");
    if ($adminCheck && $adminCheck->num_rows === 0) {
        $hash = password_hash("admin123", PASSWORD_DEFAULT);
        $seed = $con->prepare(
            "INSERT INTO admin_users (username, password_hash, display_name) VALUES (?, ?, ?)"
        );
        if ($seed) {
            $username = "admin";
            $displayName = "RTO Administrator";
            $seed->bind_param("sss", $username, $hash, $displayName);
            $seed->execute();
            $seed->close();
        }
    }
}

if (isset($_SESSION["admin_id"])) {
    header("Location: admin_dashboard.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && $error === "") {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($username === "" || $password === "") {
        $error = "Please enter your admin username and password.";
    } else {
        $stmt = $con->prepare(
            "SELECT id, username, password_hash, display_name
             FROM admin_users WHERE username = ? LIMIT 1"
        );
        if ($stmt) {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $admin = $result ? $result->fetch_assoc() : null;
            $stmt->close();

            if ($admin && password_verify($password, $admin["password_hash"])) {
                session_regenerate_id(true);
                $_SESSION["admin_id"] = (int) $admin["id"];
                $_SESSION["admin_username"] = $admin["username"];
                $_SESSION["admin_name"] = $admin["display_name"];
                header("Location: admin_dashboard.php");
                exit();
            }
        }
        $error = "Invalid admin username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - RTO</title>
    <link rel="stylesheet" href="admin_dashboard.css?v=2">
</head>
<body class="admin-auth-page">
    <main class="admin-auth-layout">
        <section class="admin-auth-intro">
            <a class="admin-auth-brand" href="home.php" aria-label="RTO home">
                <span class="admin-logo">RTO</span>
                <span>Transport Department</span>
            </a>

            <div class="admin-auth-welcome">
                <span class="admin-auth-eyebrow">SECURE ADMINISTRATION</span>
                <h1>RTO Admin Portal</h1>
                <p>Manage transport services, appointments and citizen applications from one secure workspace.</p>

                <ul class="admin-auth-features">
                    <li><span>✓</span> Manage citizen applications</li>
                    <li><span>✓</span> Review vehicles and documents</li>
                    <li><span>✓</span> Coordinate RTO appointments</li>
                </ul>
            </div>

            <p class="admin-auth-footer">Authorized RTO administrators only</p>
        </section>

        <section class="admin-auth-form-section">
            <div class="admin-auth-card">
                <header class="admin-auth-card-header">
                    <div class="admin-auth-icon" aria-hidden="true">🛡️</div>
                    <span class="admin-auth-eyebrow">WELCOME BACK</span>
                    <h2>Admin Sign In</h2>
                    <p>Enter your administrator credentials to continue.</p>
                </header>

                <?php if ($error !== ""): ?>
                    <div class="alert error" role="alert"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="post" autocomplete="on">
                    <div class="admin-auth-field">
                        <label for="username">Username</label>
                        <div class="admin-auth-input-wrap">
                            <span aria-hidden="true">👤</span>
                            <input id="username" name="username" type="text" placeholder="Enter your username" autocomplete="username" required autofocus>
                        </div>
                    </div>

                    <div class="admin-auth-field">
                        <label for="password">Password</label>
                        <div class="admin-auth-input-wrap">
                            <span aria-hidden="true">🔒</span>
                            <input id="password" name="password" type="password" placeholder="Enter your password" autocomplete="current-password" required>
                        </div>
                    </div>

                    <button class="admin-auth-submit" type="submit">
                        <span>Sign in securely</span>
                        <span aria-hidden="true">→</span>
                    </button>
                </form>

                <a class="back-link" href="home.php">← Back to website</a>
            </div>
        </section>
    </main>
<script src="site-back-button.js?v=2" defer></script>
</body>
</html>