<?php
// Navbar.php
session_start();
$isHomePage = basename($_SERVER["SCRIPT_NAME"] ?? "") === "home.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RTO Navbar</title>

    <!-- CSS -->
    <link rel="stylesheet" href="Navbar.css?v=5">

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>

<nav class="navbar">

    <!-- =========================
         LEFT - LOGO & BRAND
    ========================== -->

    <a href="home.php" class="nav-brand">

        <img
            src="rto-logo.jpg"
            alt="RTO Logo"
            class="brand-logo"
        >

        <div class="brand-text">
            <h1>RTO</h1>
            <p>Transport Department</p>
        </div>

    </a>


    <!-- =========================
         CENTER - NAVIGATION
    ========================== -->

    <ul class="nav-links">

        <li>
            <a href="home.php">Home</a>
        </li>

        <li>
            <a href="Services.php">Services</a>
        </li>

        <li>
            <a href="about.php">About Us</a>
        </li>

        <li>
            <a href="Dashboard.php">Dashboard</a>
        </li>

        <li>
            <a href="Resources.php">Resources</a>
        </li>

    </ul>


    <!-- =========================
         RIGHT - LOGIN
    ========================== -->

    <div class="nav-actions">
        <?php if (isset($_SESSION["admin_id"]) || $isHomePage): ?>
        <a href="admin_dashboard.php" class="login-btn admin-dashboard-btn">
            <i data-lucide="layout-dashboard"></i>
            <span>Admin Dashboard</span>
        </a>
        <?php endif; ?>

        <?php if (isset($_SESSION["user_id"])): ?>
            <a href="logout.php" class="login-btn">
                <i data-lucide="user"></i>
                <span>Logout</span>
            </a>
        <?php elseif (!isset($_SESSION["admin_id"])): ?>
            <a href="Login.php" class="login-btn">
                <i data-lucide="user"></i>
                <span>Login</span>
            </a>
            <?php if (!$isHomePage): ?>
                <a href="admin_login.php" class="login-btn">
                    <i data-lucide="shield-check"></i>
                    <span>Admin</span>
                </a>
            <?php endif; ?>
        <?php endif; ?>
    </div>

   

</nav>

<script>
    lucide.createIcons();
</script>
<script src="site-back-button.js?v=2" defer></script>

</body>
</html>