<?php include "Navbar.php"; ?>
<?php
// DrivingLicense.php
?>
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Driving Licence Services</title>

<link rel="stylesheet" href="DrivingLicense.css">

</head>


<body>


<div class="dl-page">



<!-- =========================================
     PAGE HERO
========================================= -->

<section class="dl-hero">

<div class="dl-hero-content">


<span class="dl-badge">
RTO TRANSPORT SERVICES
</span>

<h1>
Driving Licence Services
</h1>


<p>
Apply for a new driving licence, renew your existing licence,
or update your licence details easily.
</p>


</div>

</section>






<!-- =========================================
     MAIN CONTENT
========================================= -->


<main class="dl-main">



<!-- PAGE HEADING -->

<div class="dl-section-heading">


<div>


<span class="dl-small-title">
DRIVING LICENCE
</span>


<h2>
Choose Your Service
</h2>


<p>
Select the service you want to continue with.
</p>


</div>


</div>

<!-- =========================================
     PROCESS SECTION
========================================= -->


<section class="dl-process-card">


<div class="process-heading">


<div>

<h3>
Easy 4-Step Process
</h3>


<p>
Complete your driving licence application in simple steps.
</p>


</div>


</div>

<div class="dl-steps">



<div class="dl-step active">


<div class="step-circle">
📄
</div>


<div class="step-text">

<span>
Step 01
</span>

<h4>
Application
</h4>

</div>


</div>




<div class="step-connector"></div>





<div class="dl-step">


<div class="step-circle">
📤
</div>


<div class="step-text">

<span>
Step 02
</span>

<h4>
Documents
</h4>

</div>


</div>




<div class="step-connector"></div>





<div class="dl-step">


<div class="step-circle">
💳
</div>


<div class="step-text">

<span>
Step 03
</span>

<h4>
Payment
</h4>

</div>


</div>




<div class="step-connector"></div>





<div class="dl-step">


<div class="step-circle">
📅
</div>


<div class="step-text">

<span>
Step 04
</span>

<h4>
Slot Booking
</h4>

</div>


</div>




</div>


</section>

<!-- =========================================
     SERVICE CARDS
========================================= -->


<section class="dl-services-grid">

<!-- NEW LICENSE -->


<div class="dl-service-card">


<div class="service-top">


<div class="service-icon">
🚗
</div>


<span class="service-label">
NEW APPLICATION
</span>


</div>




<h3>
New Driving Licence
</h3>

<p>
Apply for a new Learner's Licence or Permanent Driving
Licence. You can also add a new vehicle class to your licence.
</p>

<ul class="service-features">


<li>
✔ New Learner's Licence
</li>


<li>
✔ Permanent Driving Licence
</li>


<li>
✔ Add Vehicle Class
</li>


</ul>





<a class="dl-action-btn" href="new_licence.php">

Apply Now →

</a>




</div>









<!-- RENEWAL -->


<div class="dl-service-card">


<div class="service-top">


<div class="service-icon">
🔄
</div>


<span class="service-label">
RENEWAL & UPDATE
</span>


</div>

<h3>Renew / Update Driving Licence</h3>

<p>
Renew your existing driving licence or update your personal
information and address details.
</p>

<ul class="service-features">


<li>
✔ Renew Expired Licence
</li>


<li>
✔ Update Address
</li>


<li>
✔ Update Personal Details
</li>


</ul>
<button type="button"
        class="dl-action-btn"
        onclick="window.location.href='renew_licence.php';">
    Renew Now →
</button> 

</div>

</section>

<!-- =========================================
     INFORMATION BOX
========================================= -->


<section class="dl-info-box">


<div class="info-icon">

🛡️

</div>

<div>


<h3>
Secure & Easy Online Service
</h3>



<p>
Keep your documents ready and follow the simple application
process to complete your driving licence service.
</p>



</div>



</section>


</main>



</div>



<script src="site-back-button.js?v=3" defer></script>
</body>

</html>
