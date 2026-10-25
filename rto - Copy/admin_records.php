<?php
session_start();
require_once "connect.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php");
    exit();
}

mysqli_report(MYSQLI_REPORT_OFF);
if (!$con || !($con instanceof mysqli)) {
    http_response_code(503);
    exit("The database is currently unavailable. Please start MySQL in XAMPP and try again.");
}

$recordTypes = [
    "users" => [
        "label" => "Users",
        "table" => "users",
        "fields" => [
            "full_name" => ["Full name", "text", true],
            "username" => ["Username", "text", true],
            "mobile" => ["Mobile", "mobile", true],
            "email" => ["Email", "email", true],
        ],
    ],
    "vehicles" => [
        "label" => "Vehicles",
        "table" => "vehicles",
        "fields" => [
            "user_id" => ["Account owner", "user", true],
            "vehicle_no" => ["Vehicle number", "text", true],
            "owner_name" => ["Owner name", "text", true],
            "vehicle_type" => ["Vehicle type", "text", true],
            "vehicle_model" => ["Model", "text", false],
            "fuel_type" => ["Fuel type", "text", false],
            "chassis_no" => ["Chassis number", "text", false],
            "engine_no" => ["Engine number", "text", false],
            "registration_date" => ["Registration date", "date", false],
            "status" => ["Status", "text", true],
            "inspection_center" => ["Inspection center", "text", false],
            "inspection_date" => ["Inspection date", "date", false],
            "inspection_time" => ["Inspection time", "text", false],
        ],
    ],
    "appointments" => [
        "label" => "Appointments",
        "table" => "appointment_bookings",
        "fields" => [
            "user_id" => ["Account owner", "user", true],
            "service" => ["Service", "text", true],
            "applicant_name" => ["Applicant name", "text", true],
            "mobile_no" => ["Mobile", "mobile", true],
            "email" => ["Email", "email", true],
            "appointment_date" => ["Appointment date", "date", true],
            "appointment_slot" => ["Appointment slot", "text", true],
            "status" => ["Status", "text", true],
        ],
    ],
    "licences" => [
        "label" => "Licence applications",
        "table" => "licence",
        "fields" => [
            "user_id" => ["Account owner", "user", false],
            "full_name" => ["Applicant name", "text", true],
            "date_of_birth" => ["Date of birth", "date", true],
            "gender" => ["Gender", "text", true],
            "mobile_no" => ["Mobile", "mobile", true],
            "email" => ["Email", "email", true],
            "address" => ["Address", "textarea", true],
            "city" => ["City", "text", true],
            "state" => ["State", "text", true],
            "pincode" => ["Pincode", "number", true],
            "licence_type" => ["Licence type", "text", true],
            "vehicle_class" => ["Vehicle class", "text", true],
            "aadhaar_no" => ["Aadhaar number", "text", true],
            "document_type" => ["Document type", "text", true],
        ],
    ],
    "challans" => [
        "label" => "Challans",
        "table" => "challans",
        "fields" => [
            "vehicle_no" => ["Vehicle number", "text", true],
            "challan_no" => ["Challan number", "text", true],
            "violation" => ["Violation", "text", true],
            "amount" => ["Amount", "decimal", true],
            "status" => ["Status", "challan_status", true],
            "challan_date" => ["Challan date", "date", true],
        ],
    ],
    "documents" => [
        "label" => "Uploaded documents",
        "table" => "documents",
        "fields" => [
            "user_id" => ["Account owner", "user", false],
            "application_id" => ["Licence application ID", "number", false],
            "applicant_name" => ["Applicant name", "text", false],
        ],
        "readonly" => ["aadhaar_file", "address_proof_file", "passport_photo_file", "signature_file", "created_at"],
    ],
];

$documentFileFields = [
    "aadhaar_file" => ["Aadhaar card", "replacement_aadhaar_file"],
    "address_proof_file" => ["Address proof", "replacement_address_proof_file"],
    "passport_photo_file" => ["Passport photo", "replacement_passport_photo_file"],
    "signature_file" => ["Signature", "replacement_signature_file"],
];

function bindRecordValues(mysqli_stmt $stmt, string $types, array &$values): bool
{
    $references = [];
    foreach ($values as $index => &$value) {
        $references[$index] = &$value;
    }
    array_unshift($references, $types);
    return $stmt->bind_param(...$references);
}

function validRecordDate(string $value): bool
{
    $date = DateTime::createFromFormat("Y-m-d", $value);
    return $date instanceof DateTime && $date->format("Y-m-d") === $value;
}

function runRecordStatement(mysqli $con, string $sql, string $types, array $values): void
{
    $stmt = $con->prepare($sql);
    if (!$stmt || !bindRecordValues($stmt, $types, $values) || !$stmt->execute()) {
        if ($stmt) {
            $stmt->close();
        }
        throw new RuntimeException("The requested change could not be completed. Check the record and try again.");
    }
    $stmt->close();
}

function getDocumentPaths(mysqli $con, string $condition, string $types, array $values): array
{
    $stmt = $con->prepare("SELECT aadhaar_file, address_proof_file, passport_photo_file, signature_file FROM documents WHERE $condition");
    if (!$stmt || !bindRecordValues($stmt, $types, $values) || !$stmt->execute()) {
        if ($stmt) {
            $stmt->close();
        }
        throw new RuntimeException("Uploaded file details could not be loaded.");
    }
    $result = $stmt->get_result();
    $paths = [];
    while ($row = $result->fetch_assoc()) {
        foreach ($row as $path) {
            if (is_string($path) && $path !== "") {
                $paths[] = $path;
            }
        }
    }
    $stmt->close();
    return $paths;
}

function deleteDocumentFiles(array $paths): bool
{
    $directory = realpath(__DIR__ . DIRECTORY_SEPARATOR . "uploads" . DIRECTORY_SEPARATOR . "documents");
    if ($directory === false) {
        return true;
    }
    $prefix = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    $success = true;
    foreach (array_unique($paths) as $path) {
        $candidate = __DIR__ . DIRECTORY_SEPARATOR . str_replace(["/", "\\"], DIRECTORY_SEPARATOR, $path);
        $resolved = realpath($candidate);
        if ($resolved !== false && stripos($resolved, $prefix) === 0 && is_file($resolved) && !unlink($resolved)) {
            $success = false;
        }
    }
    return $success;
}

function storeReplacementDocument(string $inputName, string $label): ?string
{
    if (!isset($_FILES[$inputName]) || !is_array($_FILES[$inputName])) {
        return null;
    }

    $file = $_FILES[$inputName];
    $error = $file["error"] ?? UPLOAD_ERR_NO_FILE;
    if ($error === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (
        $error !== UPLOAD_ERR_OK ||
        !isset($file["tmp_name"], $file["name"], $file["size"]) ||
        !is_string($file["tmp_name"]) ||
        !is_string($file["name"]) ||
        !is_numeric($file["size"])
    ) {
        throw new RuntimeException("The {$label} could not be uploaded. Please try again.");
    }
    if (!is_uploaded_file($file["tmp_name"])) {
        throw new RuntimeException("The selected {$label} file is invalid.");
    }
    if ((int) $file["size"] < 1 || (int) $file["size"] > 2 * 1024 * 1024) {
        throw new RuntimeException("The {$label} file must be no larger than 2 MB.");
    }

    $extension = strtolower(pathinfo((string) $file["name"], PATHINFO_EXTENSION));
    $allowedMimeTypes = [
        "jpg" => ["image/jpeg"],
        "jpeg" => ["image/jpeg"],
        "png" => ["image/png"],
        "pdf" => ["application/pdf"],
    ];
    $fileInfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $fileInfo->file($file["tmp_name"]);
    if (!isset($allowedMimeTypes[$extension]) || !in_array($mimeType, $allowedMimeTypes[$extension], true)) {
        throw new RuntimeException("The {$label} must be a JPG, PNG or PDF file.");
    }

    $uploadDirectory = __DIR__ . DIRECTORY_SEPARATOR . "uploads" . DIRECTORY_SEPARATOR . "documents";
    if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
        throw new RuntimeException("The document upload folder could not be created.");
    }
    $fileName = pathinfo($inputName, PATHINFO_FILENAME) . "_" . bin2hex(random_bytes(16)) . "." . $extension;
    $destination = $uploadDirectory . DIRECTORY_SEPARATOR . $fileName;
    if (!move_uploaded_file($file["tmp_name"], $destination)) {
        throw new RuntimeException("The {$label} could not be saved. Please try again.");
    }

    return "uploads/documents/" . $fileName;
}

$type = (string) ($_GET["type"] ?? $_POST["type"] ?? "users");
if (!isset($recordTypes[$type])) {
    $type = "users";
}
$isEmbedded = isset($_GET["embed"]) || isset($_POST["embed"]);
$config = $recordTypes[$type];
$recordIdValue = filter_var($_GET["id"] ?? $_POST["id"] ?? null, FILTER_VALIDATE_INT);
$recordId = $recordIdValue !== false && $recordIdValue !== null && $recordIdValue > 0 ? $recordIdValue : null;
$searchInput = $_GET["search"] ?? $_POST["search"] ?? "";
$searchQuery = is_string($searchInput) ? trim(substr($searchInput, 0, 100)) : "";
$errorMessage = "";
$successMessage = (string) ($_SESSION["admin_records_message"] ?? "");
unset($_SESSION["admin_records_message"]);

if (!isset($_SESSION["admin_records_csrf"])) {
    $_SESSION["admin_records_csrf"] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION["admin_records_csrf"];
$record = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!hash_equals($csrfToken, (string) ($_POST["csrf_token"] ?? ""))) {
        $errorMessage = "Session validation failed. Refresh the page and try again.";
    } elseif ($recordId === null) {
        $errorMessage = "The selected record is invalid.";
    } else {
        $lookup = $con->prepare("SELECT * FROM `{$config["table"]}` WHERE id = ? LIMIT 1");
        if ($lookup) {
            $lookup->bind_param("i", $recordId);
            $lookup->execute();
            $record = $lookup->get_result()->fetch_assoc() ?: null;
            $lookup->close();
        }
        if (!$record) {
            $errorMessage = "The selected record was not found.";
        }
    }

    if ($errorMessage === "" && ($_POST["action"] ?? "") === "delete") {
        $filesToRemove = [];
        $transactionStarted = false;
        try {
            if (!$con->begin_transaction()) {
                throw new RuntimeException("A database transaction could not be started. No records were deleted.");
            }
            $transactionStarted = true;

            if ($type === "users") {
                $userLookup = $con->prepare("SELECT mobile FROM users WHERE id = ? LIMIT 1 FOR UPDATE");
                if (!$userLookup) {
                    throw new RuntimeException("The user details could not be loaded for deletion.");
                }
                $userLookup->bind_param("i", $recordId);
                if (!$userLookup->execute()) {
                    $userLookup->close();
                    throw new RuntimeException("The user details could not be loaded for deletion.");
                }
                $userRow = $userLookup->get_result()->fetch_assoc();
                $userLookup->close();
                if (!$userRow) {
                    throw new RuntimeException("The selected user was not found.");
                }

                $filesToRemove = getDocumentPaths(
                    $con,
                    "user_id = ? OR application_id IN (SELECT id FROM licence WHERE user_id = ?)",
                    "ii",
                    [$recordId, $recordId]
                );
                runRecordStatement(
                    $con,
                    "DELETE FROM challans WHERE vehicle_no IN (SELECT vehicle_no FROM vehicles WHERE user_id = ?)",
                    "i",
                    [$recordId]
                );
                runRecordStatement(
                    $con,
                    "DELETE FROM renewlicence WHERE mobile_no = ? OR aadhaar_no IN (SELECT aadhaar_no FROM licence WHERE user_id = ?)",
                    "si",
                    [(string) $userRow["mobile"], $recordId]
                );
                runRecordStatement(
                    $con,
                    "DELETE FROM payments WHERE user_id = ? OR application_id IN (SELECT id FROM licence WHERE user_id = ?)",
                    "ii",
                    [$recordId, $recordId]
                );
                runRecordStatement(
                    $con,
                    "DELETE FROM licence_renewals WHERE user_id = ? OR licence_id IN (SELECT id FROM licence WHERE user_id = ?)",
                    "ii",
                    [$recordId, $recordId]
                );
                runRecordStatement($con, "DELETE FROM documents WHERE user_id = ? OR application_id IN (SELECT id FROM licence WHERE user_id = ?)", "ii", [$recordId, $recordId]);
                runRecordStatement($con, "DELETE FROM vehicles WHERE user_id = ?", "i", [$recordId]);
                runRecordStatement($con, "DELETE FROM appointment_bookings WHERE user_id = ?", "i", [$recordId]);
                runRecordStatement($con, "DELETE FROM licence WHERE user_id = ?", "i", [$recordId]);
            } elseif ($type === "licences") {
                $filesToRemove = getDocumentPaths($con, "application_id = ?", "i", [$recordId]);
                runRecordStatement($con, "DELETE FROM licence_renewals WHERE licence_id = ?", "i", [$recordId]);
                runRecordStatement($con, "DELETE FROM documents WHERE application_id = ?", "i", [$recordId]);
            } elseif ($type === "documents") {
                $filesToRemove = getDocumentPaths($con, "id = ?", "i", [$recordId]);
            }
            runRecordStatement($con, "DELETE FROM `{$config["table"]}` WHERE id = ?", "i", [$recordId]);
            if (!$con->commit()) {
                throw new RuntimeException("The database could not complete the deletion. Please try again.");
            }
            $transactionStarted = false;

            $filesRemoved = deleteDocumentFiles($filesToRemove);
            $_SESSION["admin_records_message"] = $filesRemoved
                ? "Record deleted successfully."
                : "Record deleted, but one or more uploaded files could not be removed.";
            header("Location: admin_records.php?type=" . urlencode($type) . ($isEmbedded ? "&embed=1" : "") . ($searchQuery !== "" ? "&search=" . urlencode($searchQuery) : ""));
            exit();
        } catch (Throwable $exception) {
            if ($transactionStarted) {
                $con->rollback();
            }
            $errorMessage = $exception->getMessage();
        }
    } elseif ($errorMessage === "" && ($_POST["action"] ?? "") === "save") {
        $values = [];
        $types = "";
        $validationErrors = [];
        foreach ($config["fields"] as $field => [$label, $kind, $required]) {
            $value = trim((string) ($_POST[$field] ?? ""));
            if ($required && $value === "") {
                $validationErrors[] = $label . " is required.";
            } elseif ($kind === "mobile" && (!ctype_digit($value) || strlen($value) !== 10)) {
                $validationErrors[] = "Enter a valid 10-digit mobile number using digits only.";
            } elseif ($kind === "email" && $value !== "" && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $validationErrors[] = "Enter a valid " . strtolower($label) . ".";
            } elseif ($kind === "date" && $value !== "" && !validRecordDate($value)) {
                $validationErrors[] = "Enter a valid " . strtolower($label) . ".";
            } elseif ($kind === "number" && $value !== "" && filter_var($value, FILTER_VALIDATE_INT) === false) {
                $validationErrors[] = $label . " must be a whole number.";
            } elseif ($kind === "decimal" && $value !== "" && (!is_numeric($value) || (float) $value < 0)) {
                $validationErrors[] = $label . " must be a non-negative number.";
            } elseif ($kind === "challan_status" && !in_array($value, ["Pending", "Paid"], true)) {
                $validationErrors[] = "Choose Pending or Paid for challan status.";
            }

            if ($kind === "user" && $value !== "") {
                $ownerId = filter_var($value, FILTER_VALIDATE_INT);
                $ownerCheck = $con->prepare("SELECT id FROM users WHERE id = ? LIMIT 1");
                if ($ownerId === false || !$ownerCheck) {
                    $validationErrors[] = "Choose a valid account owner.";
                } else {
                    $ownerCheck->bind_param("i", $ownerId);
                    $ownerCheck->execute();
                    if ($ownerCheck->get_result()->num_rows === 0) {
                        $validationErrors[] = "Choose an existing account owner.";
                    }
                    $ownerCheck->close();
                    $value = (string) $ownerId;
                }
            }
            if ($value === "" && !$required) {
                $value = null;
            }
            $values[] = $value;
            $types .= $kind === "decimal" ? "d" : (in_array($kind, ["number", "user"], true) ? "i" : "s");
        }

        if ($validationErrors) {
            $errorMessage = implode(" ", $validationErrors);
        } else {
            $replacementFiles = [];
            $oldFilesToRemove = [];
            $transactionStarted = false;
            try {
                if ($type === "documents") {
                    foreach ($documentFileFields as $column => [$label, $inputName]) {
                        $replacementPath = storeReplacementDocument($inputName, $label);
                        if ($replacementPath !== null) {
                            $replacementFiles[$column] = $replacementPath;
                            $oldFilesToRemove[] = (string) ($record[$column] ?? "");
                        }
                    }
                }

                if (!$con->begin_transaction()) {
                    throw new RuntimeException("A database transaction could not be started. The record was not updated.");
                }
                $transactionStarted = true;

                $setParts = [];
                foreach (array_keys($config["fields"]) as $field) {
                    $setParts[] = "`$field` = ?";
                }
                foreach ($replacementFiles as $column => $_path) {
                    $setParts[] = "`$column` = ?";
                }
                $updateValues = array_merge($values, array_values($replacementFiles), [$recordId]);
                $updateTypes = $types . str_repeat("s", count($replacementFiles)) . "i";
                $stmt = $con->prepare("UPDATE `{$config["table"]}` SET " . implode(", ", $setParts) . " WHERE id = ?");
                if (!$stmt || !bindRecordValues($stmt, $updateTypes, $updateValues) || !$stmt->execute()) {
                    if ($stmt) {
                        $stmt->close();
                    }
                    throw new RuntimeException("Record could not be updated. Check the details and try again.");
                }
                $stmt->close();
                if (!$con->commit()) {
                    throw new RuntimeException("The database could not save the update. Please try again.");
                }
                $transactionStarted = false;

                $oldFilesRemoved = deleteDocumentFiles($oldFilesToRemove);
                $_SESSION["admin_records_message"] = "Record updated successfully.";
                if (!$oldFilesRemoved) {
                    $_SESSION["admin_records_message"] .= " One or more replaced files could not be removed.";
                }
                header("Location: admin_records.php?type=" . urlencode($type) . ($isEmbedded ? "&embed=1" : "") . ($searchQuery !== "" ? "&search=" . urlencode($searchQuery) : ""));
                exit();
            } catch (Throwable $exception) {
                if ($transactionStarted) {
                    $con->rollback();
                }
                deleteDocumentFiles(array_values($replacementFiles));
                $errorMessage = $exception->getMessage();
            }
        }
        $record = array_merge($record ?? [], array_combine(array_keys($config["fields"]), $values));
    }
}

$users = [];
$userResult = $con->query("SELECT id, full_name, username FROM users ORDER BY full_name");
if ($userResult) {
    while ($userRow = $userResult->fetch_assoc()) {
        $users[] = $userRow;
    }
}

$rows = [];
if ($recordId === null) {
    $columns = array_unique(array_merge(["id"], array_keys($config["fields"]), $config["readonly"] ?? []));
    $selectColumns = "`" . implode("`, `", $columns) . "`";
    if ($searchQuery !== "") {
        $searchableColumns = array_unique(array_merge(["id"], array_keys($config["fields"]), $config["readonly"] ?? []));
        $searchConditions = array_map(
            static fn(string $column): string => "CAST(`$column` AS CHAR) LIKE ?",
            $searchableColumns
        );
        $searchStmt = $con->prepare(
            "SELECT $selectColumns FROM `{$config["table"]}` WHERE " . implode(" OR ", $searchConditions) . " ORDER BY id DESC"
        );
        if (!$searchStmt) {
            $errorMessage = "Records could not be searched. Please try again.";
            $result = false;
        } else {
            $searchValue = "%" . $searchQuery . "%";
            $searchValues = array_fill(0, count($searchConditions), $searchValue);
            if (!bindRecordValues($searchStmt, str_repeat("s", count($searchValues)), $searchValues) || !$searchStmt->execute()) {
                $errorMessage = "Records could not be searched. Please try again.";
                $result = false;
            } else {
                $result = $searchStmt->get_result();
            }
        }
    } else {
        $result = $con->query("SELECT $selectColumns FROM `{$config["table"]}` ORDER BY id DESC");
    }
    if (!$result) {
        if ($errorMessage === "") {
            $errorMessage = "Records could not be loaded from the database.";
        }
    } else {
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
    }
    if (isset($searchStmt) && $searchStmt instanceof mysqli_stmt) {
        $searchStmt->close();
    }
} elseif (!$record) {
    $lookup = $con->prepare("SELECT * FROM `{$config["table"]}` WHERE id = ? LIMIT 1");
    if ($lookup) {
        $lookup->bind_param("i", $recordId);
        $lookup->execute();
        $record = $lookup->get_result()->fetch_assoc() ?: null;
        $lookup->close();
    }
    if (!$record && $errorMessage === "") {
        $errorMessage = "The selected record was not found.";
    }
}

function displayRecordValue($value): string
{
    return $value === null || $value === ""
        ? "—"
        : htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function safeDocumentUrl(string $path): ?string
{
    $normalizedPath = str_replace("\\", "/", $path);
    return preg_match('/\Auploads\/documents\/[A-Za-z0-9][A-Za-z0-9._-]*\z/', $normalizedPath) === 1
        ? $normalizedPath
        : null;
}

$deletePrompt = "Delete this record? This cannot be undone.";
if ($type === "users") {
    $deletePrompt = "Delete this user and linked vehicles, appointments, licence applications and documents? Payment history will be kept.";
} elseif ($type === "licences") {
    $deletePrompt = "Delete this licence application and its uploaded documents?";
} elseif ($type === "documents") {
    $deletePrompt = "Delete this document record and its uploaded files?";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage <?php echo htmlspecialchars($config["label"]); ?> - RTO Admin</title>
    <link rel="stylesheet" href="admin_dashboard.css?v=7">
    <link rel="stylesheet" href="admin_records.css?v=5">
</head>
<body class="admin-page<?php echo $isEmbedded ? " admin-records-embedded" : ""; ?>">
    <?php if (!$isEmbedded): ?>
    <header class="admin-header">
        <a class="admin-brand" href="admin_dashboard.php"><span>RTO</span> Admin Dashboard</a>
        <div class="admin-actions">
            <a class="outline-btn" href="admin_dashboard.php">Dashboard</a>
            <a class="danger-btn" href="admin_logout.php">Logout</a>
        </div>
    </header>
    <?php endif; ?>
    <main class="admin-content records-content">
        <?php if (!$isEmbedded): ?>
        <section class="admin-hero">
            <div>
                <p class="eyebrow">RECORD MANAGEMENT</p>
                <h1><?php echo $recordId ? "Edit " . htmlspecialchars($config["label"]) : "Manage User Data"; ?></h1>
                <p class="muted">Update records or remove entries from the administration dashboard.</p>
            </div>
            <?php if ($recordId): ?><a class="records-back-link" href="admin_records.php?type=<?php echo urlencode($type) . ($isEmbedded ? "&amp;embed=1" : "") . ($searchQuery !== "" ? "&amp;search=" . urlencode($searchQuery) : ""); ?>">← Back to <?php echo htmlspecialchars($config["label"]); ?></a><?php endif; ?>
        </section>
        <?php elseif ($recordId): ?>
            <div class="embedded-edit-heading">
                <a class="records-back-link" href="admin_records.php?type=<?php echo urlencode($type); ?>&amp;embed=1<?php echo $searchQuery !== "" ? "&amp;search=" . urlencode($searchQuery) : ""; ?>">← Back to <?php echo htmlspecialchars($config["label"]); ?></a>
                <strong>Edit record #<?php echo (int) $recordId; ?></strong>
            </div>
        <?php endif; ?>
        <?php if ($successMessage !== ""): ?><div class="records-alert success-alert"><?php echo htmlspecialchars($successMessage); ?></div><?php endif; ?>
        <?php if ($errorMessage !== ""): ?><div class="records-alert error-alert"><?php echo htmlspecialchars($errorMessage); ?></div><?php endif; ?>

        <?php if (!$recordId): ?>
            <?php if (!$isEmbedded): ?>
            <nav class="record-type-nav" aria-label="Record types">
                <?php foreach ($recordTypes as $key => $definition): ?><a class="<?php echo $type === $key ? "active" : ""; ?>" href="admin_records.php?type=<?php echo urlencode($key) . ($isEmbedded ? "&amp;embed=1" : ""); ?>"><?php echo htmlspecialchars($definition["label"]); ?></a><?php endforeach; ?>
            </nav>
            <?php endif; ?>
            <section class="data-panel record-list-panel">
                <div class="panel-heading">
                    <h2><?php echo htmlspecialchars($config["label"]); ?></h2>
                    <span><?php echo count($rows); ?><?php echo $searchQuery !== "" ? " matching" : ""; ?> records</span>
                </div>
                <form class="records-search-form" method="get" action="admin_records.php" role="search">
                    <input type="hidden" name="type" value="<?php echo htmlspecialchars($type, ENT_QUOTES, "UTF-8"); ?>">
                    <?php if ($isEmbedded): ?><input type="hidden" name="embed" value="1"><?php endif; ?>
                    <label class="visually-hidden" for="records-search">Search <?php echo htmlspecialchars($config["label"]); ?></label>
                    <input
                        id="records-search"
                        class="records-search-input"
                        type="search"
                        name="search"
                        value="<?php echo htmlspecialchars($searchQuery, ENT_QUOTES, "UTF-8"); ?>"
                        placeholder="Search <?php echo htmlspecialchars($config["label"]); ?> by any detail or ID"
                        maxlength="100"
                    >
                    <button class="records-search-btn" type="submit">Search</button>
                    <?php if ($searchQuery !== ""): ?>
                        <a class="records-search-clear" href="admin_records.php?type=<?php echo urlencode($type) . ($isEmbedded ? "&amp;embed=1" : ""); ?>">Clear</a>
                    <?php endif; ?>
                </form>
                <?php if ($rows): ?>
                    <div class="table-wrap"><table class="records-table">
                        <thead><tr><th>ID</th><?php foreach ($config["fields"] as [$label]): ?><th><?php echo htmlspecialchars($label); ?></th><?php endforeach; ?><?php foreach (($config["readonly"] ?? []) as $readonly): ?><th><?php echo htmlspecialchars(ucwords(str_replace("_", " ", $readonly))); ?></th><?php endforeach; ?><th>Actions</th></tr></thead>
                        <tbody><?php foreach ($rows as $row): ?>
                            <tr><td><?php echo (int) $row["id"]; ?></td>
                                <?php foreach ($config["fields"] as $field => $_definition): ?><td><?php echo displayRecordValue($row[$field] ?? null); ?></td><?php endforeach; ?>
                                <?php foreach (($config["readonly"] ?? []) as $readonly): ?><td><?php echo displayRecordValue($row[$readonly] ?? null); ?></td><?php endforeach; ?>
                                <td class="record-actions">
                                    <a class="edit-record-btn" href="admin_records.php?type=<?php echo urlencode($type); ?>&amp;id=<?php echo (int) $row["id"]; ?><?php echo $isEmbedded ? "&amp;embed=1" : ""; ?><?php echo $searchQuery !== "" ? "&amp;search=" . urlencode($searchQuery) : ""; ?>">Edit</a>
                                    <form method="post" data-confirm="<?php echo htmlspecialchars($deletePrompt, ENT_QUOTES, "UTF-8"); ?>" onsubmit="return confirm(this.dataset.confirm);">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                        <input type="hidden" name="type" value="<?php echo htmlspecialchars($type); ?>">
                                        <input type="hidden" name="id" value="<?php echo (int) $row["id"]; ?>">
                                        <?php if ($searchQuery !== ""): ?><input type="hidden" name="search" value="<?php echo htmlspecialchars($searchQuery, ENT_QUOTES, "UTF-8"); ?>"><?php endif; ?>
                                        <?php if ($isEmbedded): ?><input type="hidden" name="embed" value="1"><?php endif; ?>
                                        <input type="hidden" name="action" value="delete">
                                        <button class="delete-record-btn" type="submit">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?></tbody>
                    </table></div>
                <?php else: ?><p class="empty-state">No records found.</p><?php endif; ?>
            </section>
        <?php elseif ($record): ?>
            <section class="data-panel edit-record-panel">
                <div class="panel-heading"><h2><?php echo htmlspecialchars($config["label"]); ?> record #<?php echo (int) $recordId; ?></h2></div>
                <form method="post"<?php echo $type === "documents" ? ' enctype="multipart/form-data"' : ""; ?>>
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="type" value="<?php echo htmlspecialchars($type); ?>">
                    <input type="hidden" name="id" value="<?php echo (int) $recordId; ?>">
                    <?php if ($searchQuery !== ""): ?><input type="hidden" name="search" value="<?php echo htmlspecialchars($searchQuery, ENT_QUOTES, "UTF-8"); ?>"><?php endif; ?>
                    <?php if ($isEmbedded): ?><input type="hidden" name="embed" value="1"><?php endif; ?>
                    <input type="hidden" name="action" value="save">
                    <div class="record-fields-grid">
                        <?php foreach ($config["fields"] as $field => [$label, $kind, $required]): ?>
                            <label class="record-field" for="field-<?php echo htmlspecialchars($field); ?>"><span><?php echo htmlspecialchars($label); ?><?php echo $required ? " *" : ""; ?></span>
                                <?php if ($kind === "user"): ?>
                                    <select id="field-<?php echo htmlspecialchars($field); ?>" name="<?php echo htmlspecialchars($field); ?>" <?php echo $required ? "required" : ""; ?>>
                                        <?php if (!$required): ?><option value="">Unassigned</option><?php endif; ?>
                                        <?php foreach ($users as $user): ?><option value="<?php echo (int) $user["id"]; ?>" <?php echo (string) ($record[$field] ?? "") === (string) $user["id"] ? "selected" : ""; ?>><?php echo htmlspecialchars($user["full_name"] . " (" . $user["username"] . ")"); ?></option><?php endforeach; ?>
                                    </select>
                                <?php elseif ($kind === "challan_status"): ?>
                                    <select id="field-<?php echo htmlspecialchars($field); ?>" name="<?php echo htmlspecialchars($field); ?>" required>
                                        <?php foreach (["Pending", "Paid"] as $status): ?><option value="<?php echo $status; ?>" <?php echo (string) ($record[$field] ?? "") === $status ? "selected" : ""; ?>><?php echo $status; ?></option><?php endforeach; ?>
                                    </select>
                                <?php elseif ($kind === "textarea"): ?>
                                    <textarea id="field-<?php echo htmlspecialchars($field); ?>" name="<?php echo htmlspecialchars($field); ?>" rows="3" required><?php echo htmlspecialchars((string) ($record[$field] ?? ""), ENT_QUOTES, "UTF-8"); ?></textarea>
                                <?php else: ?>
                                    <input id="field-<?php echo htmlspecialchars($field); ?>" name="<?php echo htmlspecialchars($field); ?>" type="<?php echo in_array($kind, ["decimal", "number"], true) ? "number" : ($kind === "mobile" ? "tel" : htmlspecialchars($kind)); ?>" <?php echo $kind === "decimal" ? 'step="0.01" min="0"' : ""; ?> value="<?php echo htmlspecialchars((string) ($record[$field] ?? ""), ENT_QUOTES, "UTF-8"); ?>" <?php echo $required ? "required" : ""; ?>>
                                <?php endif; ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($type === "documents"): ?>
                        <section class="document-replacement-panel" aria-labelledby="document-replacement-title">
                            <div class="document-replacement-heading">
                                <div>
                                    <h3 id="document-replacement-title">Uploaded documents</h3>
                                    <p>Keep the current file or choose a replacement. New files must be JPG, PNG or PDF and no larger than 2 MB.</p>
                                </div>
                            </div>
                            <div class="document-replacement-grid">
                                <?php foreach ($documentFileFields as $column => [$label, $inputName]): ?>
                                    <?php $currentPath = (string) ($record[$column] ?? ""); ?>
                                    <?php $currentUrl = safeDocumentUrl($currentPath); ?>
                                    <div class="document-replacement-item">
                                        <div class="document-current-file">
                                            <strong><?php echo htmlspecialchars($label); ?></strong>
                                            <?php if ($currentPath !== ""): ?>
                                                <?php if ($currentUrl !== null): ?><a href="<?php echo htmlspecialchars($currentUrl, ENT_QUOTES, "UTF-8"); ?>" target="_blank" rel="noopener noreferrer">View current file</a><?php endif; ?>
                                                <span><?php echo htmlspecialchars(basename(str_replace("\\", "/", $currentPath)), ENT_QUOTES, "UTF-8"); ?></span>
                                            <?php else: ?>
                                                <span class="document-missing-file">No file uploaded</span>
                                            <?php endif; ?>
                                        </div>
                                        <label class="record-field" for="<?php echo htmlspecialchars($inputName); ?>">
                                            <span>Replace <?php echo htmlspecialchars(strtolower($label)); ?></span>
                                            <input id="<?php echo htmlspecialchars($inputName); ?>" name="<?php echo htmlspecialchars($inputName); ?>" type="file" accept=".jpg,.jpeg,.png,.pdf,application/pdf,image/jpeg,image/png">
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="document-record-meta"><strong>Created:</strong> <?php echo displayRecordValue($record["created_at"] ?? null); ?></div>
                        </section>
                    <?php elseif (!empty($config["readonly"])): ?>
                        <div class="readonly-record-fields"><strong>Additional record details</strong><?php foreach ($config["readonly"] as $readonly): ?><span><?php echo htmlspecialchars(ucwords(str_replace("_", " ", $readonly))); ?>: <?php echo displayRecordValue($record[$readonly] ?? null); ?></span><?php endforeach; ?></div>
                    <?php endif; ?>
                    <div class="record-form-actions"><button class="primary-btn" type="submit">Save changes</button><a class="records-back-link" href="admin_records.php?type=<?php echo urlencode($type) . ($isEmbedded ? "&amp;embed=1" : "") . ($searchQuery !== "" ? "&amp;search=" . urlencode($searchQuery) : ""); ?>">Cancel</a></div>
                </form>
            </section>
        <?php endif; ?>
    </main>
<?php if ($isEmbedded): ?>
<script>
    const sendRecordsHeight = () => {
        window.parent.postMessage(
            { type: "admin-records-height", height: document.documentElement.scrollHeight },
            window.location.origin
        );
    };
    window.addEventListener("load", sendRecordsHeight);
    window.addEventListener("resize", sendRecordsHeight);
    new MutationObserver(sendRecordsHeight).observe(document.body, { childList: true, subtree: true });
    sendRecordsHeight();
</script>
<?php endif; ?>
<script src="mobile-validation.js?v=1" defer></script>
<script src="site-back-button.js?v=3" defer></script>
</body>
</html>
