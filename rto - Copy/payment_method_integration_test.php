<?php

$flow = $argv[1] ?? "";
$method = $argv[2] ?? "";
$target = $argv[3] ?? "";
$valid = ($argv[4] ?? "valid") === "valid";
$sessionId = "RTOMETHODTEST" . strtoupper($flow) . strtoupper(str_replace(" ", "", $method));

session_id($sessionId);
session_start();
$_SESSION["user_id"] = 1;
$_SESSION["full_name"] = "Payment Test";
$_SESSION["documents_completed"] = true;
$_SESSION["payment_completed"] = false;
if ($flow === "vehicle") {
    $_SESSION["reg_vehicle_id"] = (int) $target;
}
session_write_close();

$_SERVER["REQUEST_METHOD"] = "POST";
$_GET = [];
$_POST = [];

if ($flow === "vehicle") {
    $_GET["step"] = 3;
    $_POST["pay_registration"] = "1";
    $_POST["payment_method"] = $method;
} elseif ($flow === "challan") {
    $_GET["challan_no"] = $target;
    $_POST["challan_no"] = $target;
    $_POST["make_payment"] = "1";
    $_POST["payment_method"] = strtolower($method === "UPI" ? "upi" : ($method === "Net Banking" ? "netbanking" : "card"));
} elseif ($flow === "application") {
    $_POST["submit_payment"] = "1";
    $_POST["payment_declaration"] = "1";
    $_POST["payment_method"] = $method;
} else {
    fwrite(STDERR, "Unknown flow\n");
    exit(2);
}

if ($method === "UPI") {
    $_POST["upi_id"] = $valid ? "demo.user@upi" : "invalid-upi";
} elseif ($method === "Debit Card" || $method === "Credit Card" || $method === "card") {
    $_POST["card_name"] = "Demo User";
    $_POST["card_number"] = "4111111111111111";
    $_POST["expiry"] = "12/30";
    $_POST["cvv"] = "123";
} elseif ($method === "Net Banking" || $method === "netbanking") {
    $_POST["bank"] = "HDFC";
    $_POST["bank_reference"] = "DEMO-TEST-1234";
}

ob_start();
if ($flow === "vehicle") {
    include __DIR__ . "/VehicleRegistration.php";
} elseif ($flow === "challan") {
    include __DIR__ . "/challan_payment.php";
} else {
    include __DIR__ . "/payment.php";
}
$output = ob_get_clean();

if (!$valid) {
    echo (strpos($output, "valid UPI ID") !== false) ? "Invalid UPI rejected\n" : "Invalid UPI validation failed\n";
} else {
    echo "Handler returned without success redirect\n";
}