<?php
include "Navbar.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transport Services</title>

    <!-- CSS -->
    <link rel="stylesheet" href="Homepage.css">

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>

<div class="homepage-container">
<?php if (isset($_SESSION["admin_id"])): ?>
    <section class="admin-home-banner">
        <div>
            <strong>Admin mode is active</strong>
            <span>Manage RTO users, appointments, vehicles and documents from the admin dashboard.</span>
        </div>
        <a href="admin_dashboard.php">Open Admin Dashboard →</a>
    </section>
<?php endif; ?>

    <!-- =========================
         HERO SECTION
    ========================== -->

    <header class="hero-section">

        <div class="hero-left-bg">
            <img src="rto-home.jpg" alt="RTO Office Background">
        </div>

        <div class="hero-content-wrapper">

            <div class="hero-right-text">

                <h2>Welcome to</h2>

                <h3>Transport Services</h3>

                <p class="hero-desc">
                    <strong>
                        Your one-stop solution for all vehicle and
                        <br>
                        driving related services
                    </strong>
                </p>

                <!-- Search Box -->

                <div class="search-container">

    <i
        data-lucide="map-pin"
        class="search-icon-left"
    ></i>

    <select
        class="search-input state-select"
        name="state"
        id="state"
    >

        <option value="" selected disabled>
            Select Your State
        </option>

        <option value="Andaman and Nicobar Islands">
            Andaman & Nicobar Islands
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

        <option value="Chandigarh">
            Chandigarh
        </option>

        <option value="Chhattisgarh">
            Chhattisgarh
        </option>

        <option value="Dadra and Nagar Haveli">
            Dadra & Nagar Haveli
        </option>

        <option value="Daman and Diu">
            Daman & Diu
        </option>

        <option value="Delhi">
            Delhi
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

        <option value="Jammu and Kashmir">
            Jammu & Kashmir
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

        <option value="Ladakh">
            Ladakh
        </option>

        <option value="Lakshadweep">
            Lakshadweep
        </option>

        <option value="Madhya Pradesh">
            Madhya Pradesh
        </option>

        <option value="Maharashtra">
            Maharashtra
        </option>

        <option value="Manipur">
            Manipur
        </option>

        <option value="Meghalaya">
            Meghalaya
        </option>

        <option value="Mizoram">
            Mizoram
        </option>

        <option value="Nagaland">
            Nagaland
        </option>

        <option value="Odisha">
            Odisha
        </option>

        <option value="Puducherry">
            Puducherry
        </option>

        <option value="Punjab">
            Punjab
        </option>

        <option value="Rajasthan">
            Rajasthan
        </option>

        <option value="Sikkim">
            Sikkim
        </option>

        <option value="Tamil Nadu">
            Tamil Nadu
        </option>

        <option value="Telangana">
            Telangana
        </option>

        <option value="Tripura">
            Tripura
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

</div>
            </div>

        </div>

    </header>


    <!-- =========================
         MAIN SERVICES SECTION
    ========================== -->

    <div class="main-wrapper">

        <main class="services-container">

            <div class="services-header">

                <h4>Popular Services</h4>

                <a href="#all" class="view-all-link">
                    View All
                </a>

            </div>


            <div class="primary-services-grid">

                <!-- Challan -->

                <a href="Challan.php" class="service-link">

                    <div class="service-card">

                        <div class="service-icon-wrapper">

                            <img
                                src="traffic challan.jpeg"
                                alt="Check Challan"
                            >

                        </div>

                        <h5>Check Challan</h5>

                        <p>Check Now</p>

                    </div>

                </a>


                <!-- Driving License -->

                <a href="driving-license.php" class="service-link">

                    <div class="service-card">

                        <div class="service-icon-wrapper">

                            <img
                                src="driving license.jpg"
                                
                                alt="Driving License"
                            >

                        </div>

                        <h5>Driving License</h5>

                        <p>Apply / Renew</p>

                    </div>

                </a>


                <!-- Vehicle Registration -->

                <a href="VehicleRegistration.php" class="service-link">

                    <div class="service-card">

                        <div class="service-icon-wrapper">

                            <img
                                src="registration.jpg"
                                alt="Vehicle Registration"
                            >

                        </div>

                        <h5>Vehicle Registration</h5>

                        <p>Register Now</p>

                    </div>

                </a>


                <!-- Appointment -->

                <a href="Appointment.php" class="service-link">

                    <div class="service-card">

                        <div class="service-icon-wrapper">

                            <img
                                src="appointment.jpg"
                                alt="Appointments"
                            >

                        </div>

                        <h5>Appointments</h5>

                        <p>Book Slot</p>

                    </div>

                </a>

            </div>

        </main>

    </div>

</div>

<script>
    lucide.createIcons();
</script>

<script src="site-back-button.js?v=3" defer></script>
</body>
</html>