<?php include "Navbar.php"; ?>
<?php
include "connect.php";

$searchResult = null;
$inputValue = "";

$demoChallans = [
    ["vehicle" => "GJ03AB1234", "challan" => "CH2026-0001", "reason" => "Speed Limit Violation", "amount" => 1500, "date" => "2026-08-14"],
    ["vehicle" => "GJ03AB1234", "challan" => "CH2026-0002", "reason" => "Jumping Red Light", "amount" => 1000, "date" => "2026-09-02"],
    ["vehicle" => "GJ01CD5678", "challan" => "CH2026-0003", "reason" => "Driving Without Helmet", "amount" => 700, "date" => "2026-07-21"],
    ["vehicle" => "GJ01CD5678", "challan" => "CH2026-0004", "reason" => "No-Parking Zone", "amount" => 800, "date" => "2026-09-18"],
    ["vehicle" => "GJ05EF9012", "challan" => "CH2026-0005", "reason" => "Wrong-Side Driving", "amount" => 2000, "date" => "2026-08-09"],
    ["vehicle" => "GJ05EF9012", "challan" => "CH2026-0006", "reason" => "Driving Without Seat Belt", "amount" => 1500, "date" => "2026-09-25"],
    ["vehicle" => "GJ18GH3456", "challan" => "CH2026-0007", "reason" => "Speed Limit Violation", "amount" => 2500, "date" => "2026-06-30"],
    ["vehicle" => "GJ06JK7890", "challan" => "CH2026-0008", "reason" => "Driving Without Helmet", "amount" => 800, "date" => "2026-08-04"],
    ["vehicle" => "GJ27LM2345", "challan" => "CH2026-0009", "reason" => "No-Parking Zone", "amount" => 800, "date" => "2026-07-12"],
    ["vehicle" => "GJ04NP6789", "challan" => "CH2026-0010", "reason" => "Expired PUC Certificate", "amount" => 1000, "date" => "2026-09-11"],
    ["vehicle" => "GJ12QR0123", "challan" => "CH2026-0011", "reason" => "Jumping Red Light", "amount" => 1000, "date" => "2026-08-27"],
    ["vehicle" => "GJ15ST4567", "challan" => "CH2026-0012", "reason" => "Speed Limit Violation", "amount" => 1200, "date" => "2026-07-05"],
    ["vehicle" => "GJ08UV8901", "challan" => "CH2026-0013", "reason" => "Driving Without Seat Belt", "amount" => 1000, "date" => "2026-09-06"],
    ["vehicle" => "GJ07WX2340", "challan" => "CH2026-0014", "reason" => "Wrong-Side Driving", "amount" => 1500, "date" => "2026-08-19"],
    ["vehicle" => "GJ11YZ6782", "challan" => "CH2026-0015", "reason" => "No-Parking Zone", "amount" => 800, "date" => "2026-07-28"],
];

$seedStatement = mysqli_prepare(
    $con,
    "INSERT IGNORE INTO challans (vehicle_no, challan_no, violation, amount, status, challan_date)
     VALUES (?, ?, ?, ?, 'Pending', ?)"
);

if ($seedStatement) {
    foreach ($demoChallans as $demoChallan) {
        mysqli_stmt_bind_param(
            $seedStatement,
            "sssis",
            $demoChallan["vehicle"],
            $demoChallan["challan"],
            $demoChallan["reason"],
            $demoChallan["amount"],
            $demoChallan["date"]
        );
        mysqli_stmt_execute($seedStatement);
    }
    mysqli_stmt_close($seedStatement);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $inputValue = strtoupper(trim($_POST["vehicle"] ?? ""));
    $matchedChallans = [];

    if ($inputValue === "12345") {
        $inputValue = $demoChallans[0]["vehicle"];
    }

    $lookupVehicle = $inputValue;
    $lookupChallanNumber = "";
    $challanLookup = mysqli_prepare($con, "SELECT vehicle_no, challan_no FROM challans WHERE challan_no = ? LIMIT 1");
    if ($challanLookup) {
        mysqli_stmt_bind_param($challanLookup, "s", $inputValue);
        mysqli_stmt_execute($challanLookup);
        $challanLookupResult = mysqli_stmt_get_result($challanLookup);
        if ($matchedChallan = mysqli_fetch_assoc($challanLookupResult)) {
            $lookupVehicle = $matchedChallan["vehicle_no"];
            $lookupChallanNumber = $matchedChallan["challan_no"];
        }
        mysqli_stmt_close($challanLookup);
    }

    if (!isset($_SESSION["user_id"])) {
        $searchResult = [
            "status" => "login-required",
            "message" => "Login and register your vehicle before checking challans.",
            "vehicle" => $inputValue
        ];
    } else {
        $userId = (int) $_SESSION["user_id"];
        $vehicleStatement = mysqli_prepare(
            $con,
            "SELECT vehicle_no FROM vehicles WHERE user_id = ? AND UPPER(vehicle_no) = UPPER(?) LIMIT 1"
        );
        $registeredVehicle = null;

        if ($vehicleStatement) {
            mysqli_stmt_bind_param($vehicleStatement, "is", $userId, $lookupVehicle);
            mysqli_stmt_execute($vehicleStatement);
            $vehicleResult = mysqli_stmt_get_result($vehicleStatement);
            $registeredVehicle = mysqli_fetch_assoc($vehicleResult);
            mysqli_stmt_close($vehicleStatement);
        }

        if (!$registeredVehicle) {
            $searchResult = [
                "status" => "not-registered",
                "message" => "Register this vehicle in your account before checking its challans.",
                "vehicle" => $inputValue
            ];
        } else {
            $lookupVehicle = $registeredVehicle["vehicle_no"];
            $searchStatement = mysqli_prepare(
                $con,
                "SELECT vehicle_no AS vehicle, challan_no AS challan, violation AS reason,
                        amount, challan_date AS date
                 FROM challans
                 WHERE status = 'Pending' AND UPPER(vehicle_no) = UPPER(?)
                   AND (? = '' OR challan_no = ?)
                 ORDER BY challan_date DESC, id DESC"
            );

            if ($searchStatement) {
                mysqli_stmt_bind_param($searchStatement, "sss", $lookupVehicle, $lookupChallanNumber, $lookupChallanNumber);
                mysqli_stmt_execute($searchStatement);
                $searchRows = mysqli_stmt_get_result($searchStatement);
                while ($row = mysqli_fetch_assoc($searchRows)) {
                    $matchedChallans[] = $row;
                }
                mysqli_stmt_close($searchStatement);
            }

            $searchResult = [
                "status" => $matchedChallans ? "pending" : "no-challan",
                "message" => $matchedChallans
                    ? count($matchedChallans) . " Pending Challan" . (count($matchedChallans) === 1 ? "" : "s") . " Found"
                    : "No Challan Found",
                "challans" => $matchedChallans,
                "vehicle" => $lookupVehicle
            ];
        }
    }
}


?>


<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Check Challan</title>

<link rel="stylesheet" href="Challan.css?v=3">

</head>


<body>


<div class="challan-page">



<!-- HERO SECTION -->

<section class="challan-hero">

<div class="challan-hero-content">

<span class="challan-badge">
RTO TRANSPORT SERVICES
</span>


<h1>
Check Your Traffic Challan
</h1>


<p>
Check your vehicle challan status quickly and easily
using your vehicle number or challan number.
</p>


</div>

</section>





<!-- MAIN CONTENT -->

<main class="challan-main">

<div class="challan-card">



<!-- Header -->

<div class="challan-card-header">


<div class="challan-icon">
🚗
</div>


<div>

<h2>
Search Challan Details
</h2>


<p>
Enter your vehicle number or challan number below.
</p>


</div>


</div>




<!-- SEARCH FORM -->


<form 
method="POST"
class="challan-form"
>


<div class="challan-input-wrapper">


<span class="challan-input-icon">
📄
</span>


<input

type="text"

name="vehicle"

placeholder="Enter Vehicle Number or Challan Number"

value="<?php echo $inputValue; ?>"

oninput="this.value=this.value.toUpperCase()"

required

>


</div>



<div class="challan-form-actions">
    <button
        type="submit"
        class="challan-search-btn"
    >
        🔍 Search
    </button>

    <?php if ($searchResult === null): ?>
    <a href="home.php" class="challan-search-btn challan-back-home">
        ← Back
    </a>
    <?php endif; ?>
</div>


</form>





<!-- Demo Information -->


<div class="challan-demo-info">

🛡️

<span>
Demo vehicle numbers:
<strong>
GJ03AB1234, GJ01CD5678, GJ05EF9012, GJ18GH3456, GJ06JK7890, GJ27LM2345,
GJ04NP6789, GJ12QR0123, GJ15ST4567, GJ08UV8901, GJ07WX2340, GJ11YZ6782
</strong>
</span>


</div>






<!-- SEARCH RESULT -->

<?php if($searchResult): ?>


<div class="challan-result <?php echo $searchResult['status']; ?>">



<?php if($searchResult['status']=="pending"): ?>


<h3><?php echo htmlspecialchars($searchResult["message"]); ?></h3>


<?php foreach ($searchResult["challans"] as $challan): ?>

<div class="result-header">


<div class="result-status-icon pending-icon">

⚠️

</div>



<div>

<h3>
<?php echo htmlspecialchars($challan["challan"]); ?>
</h3>


<p>
Pending traffic challan for <?php echo htmlspecialchars($challan["vehicle"]); ?>.
</p>


</div>


</div>





<div class="challan-details-grid">


<div class="detail-item">

<span>
Vehicle / Challan Number
</span>


<strong>
<?php echo htmlspecialchars($challan["vehicle"]); ?>
</strong>


</div>





<div class="detail-item">


<span>
Challan Amount
</span>


<strong class="amount-text">

₹ <?php echo number_format($challan["amount"]); ?>

</strong>


</div>





<div class="detail-item">

<span>
Violation
</span>


<strong>

<?php echo htmlspecialchars($challan["reason"]); ?>

</strong>


</div>





<div class="detail-item">

<span>
Status
</span>


<strong class="pending-status">

Pending

</strong>


</div>


</div>




<a href="challan_payment.php?challan_no=<?php echo urlencode($challan["challan"]); ?>" class="pay-challan-btn">
       Pay Challan
</a>





<?php endforeach; ?>

<?php elseif ($searchResult["status"] === "login-required" || $searchResult["status"] === "not-registered"): ?>

<div class="success-result">

<div class="result-status-icon pending-icon">🚗</div>

<div>
<h3><?php echo $searchResult["status"] === "login-required" ? "Login Required" : "Vehicle Not Registered"; ?></h3>
<p><?php echo htmlspecialchars($searchResult["message"]); ?></p>
<p>Vehicle: <strong><?php echo htmlspecialchars($searchResult["vehicle"]); ?></strong></p>
</div>

</div>

<div class="unregistered-actions">
    <?php if ($searchResult["status"] === "login-required"): ?>
    <a href="Login.php" class="pay-challan-btn">Login to Continue</a>
    <?php else: ?>
    <a href="VehicleRegistration.php" class="pay-challan-btn">Register This Vehicle</a>
    <?php endif; ?>
</div>

<?php else: ?>

<div class="success-result">

<div class="result-status-icon success-icon">

✔️

</div>


<div>


<h3>
<?php echo $searchResult["message"]; ?>
</h3>


<p>

No pending traffic challan was found for

<strong>
<?php echo $searchResult["vehicle"]; ?>
</strong>



</p>


</div>


</div>

<!-- GO TO DASHBOARD BUTTON -->
<div style="margin-top:22px;padding:18px 20px;background:#e0f2f1;border:1px solid #bae6fd;border-radius:14px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;">
    <div style="display:flex;align-items:center;gap:12px;">
        <span style="font-size:28px;">🎉</span>
        <div>
            <p style="margin:0;font-weight:700;color:#0369a1;font-size:15px;">Your vehicle is challan-free!</p>
            <p style="margin:4px 0 0;font-size:13px;color:#64748b;">No pending dues. Go back to your dashboard.</p>
        </div>
    </div>
    <a href="Dashboard.php"
       style="display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,#0284c7,#0369a1);color:white;padding:11px 22px;border-radius:10px;font-weight:700;font-size:14px;text-decoration:none;box-shadow:0 4px 12px rgba(2,132,199,.3);"
       onmouseover="this.style.transform='translateY(-2px)'"
       onmouseout="this.style.transform='translateY(0)'">
        🏠 Go to Dashboard
    </a>
</div>

<?php endif; ?>


</div>



<div class="challan-result-actions">
    <a href="home.php" class="back-page-btn">
        ← Back
    </a>

    <a href="Challan.php" class="clear-btn">
        Check Another Number
    </a>
</div>



<?php endif; ?>






</div>






<!-- Bottom Information -->


<div class="challan-info-section">



<div class="info-item">


<div class="info-icon">

🔍

</div>


<div>

<h4>
Easy Search
</h4>




<p>
Search using your vehicle or challan number.
</p>


</div>


</div>





<div class="info-item">


<div class="info-icon">

🛡️

</div>


<div>

<h4>
Secure Service
</h4>


<p>
Your search information is handled securely.
</p>


</div>


</div>





<div class="info-item">


<div class="info-icon">

✔️

</div>


<div>

<h4>Quick Results</h4>
<p>Get your challan status instantly.</p>


</div>
</div>
</div>
</main>
</div>
<script src="site-back-button.js?v=2" defer></script>
</body>
</html>