<?php
session_start();
require_once "../class/db_connect.php";
require_once "../class/appliances.php";

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../login.php");
    exit;
}

$applianceObj = new Appliance($conn);
$error = "";
$success = "";

$id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;
if ($id <= 0) {
    echo "Invalid appliance ID.";
    exit;
}

$appliance = $applianceObj->getApplianceById($id);

if (!$appliance) {
    echo "Appliance not found.";
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $status = $_POST["status"];

    $current_date = date('Y-m-d');
    if ($appliance['warranty_expiry'] < $current_date) {
        $error = "Cannot update — this appliance’s warranty is expired.";
    } else {
        if ($applianceObj->updateApplianceStatus($id, $status)) {

    // Redirect to email notification page
    header("Location: ../notify_status_update.php?appliance_id=" . $id);
    exit;

} else {
    $error = "Failed to update status.";
}

    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Appliance (Admin)</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            padding: 20px;
            color: #111;
            background: url("../image/logo.png") no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            margin: 0;
        }

        h2 {
            color: #0d47a1;
            text-align: center;
            margin-bottom: 20px;
        }

        a {
            color: #0d47a1;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        form {
            max-width: 500px;
            margin: 0 auto;
            background-color: #ffffffcc;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            box-sizing: border-box;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #0d47a1;
        }

        input[type="text"], select {
            width: 100%;
            padding: 8px 10px;
            margin-bottom: 15px;
            border: 1px solid #0d47a1;
            border-radius: 6px;
            background-color: #f9f9f9;
            box-sizing: border-box;
        }

        input[disabled] {
            background-color: #e3f2fd;
            color: #555;
        }

        button {
            display: block;
            width: 100%;
            background-color: #0d47a1;
            color: #fff;
            border: none;
            padding: 10px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
        }

        button:hover {
            background-color: #1565c0;
        }

        p.error {
            color: #d32f2f;
            font-weight: bold;
            text-align: center;
        }

        p.success {
            color: #388e3c;
            font-weight: bold;
            text-align: center;
        }
    </style>
</head>
<body>
    <h2>🛠 Edit Appliance (Admin)</h2>
    <a href="manage_appliance.php">← Back to Appliance List</a><br><br>

    <?php if ($error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <?php if ($success): ?>
        <p class="success"><?= htmlspecialchars($success) ?></p>
    <?php endif; ?>

    <form method="POST">
        <fieldset disabled>
            <label>Appliance Name:</label>
            <input type="text" value="<?= htmlspecialchars($appliance['appliance_name']) ?>">

            <label>Brand:</label>
            <input type="text" value="<?= htmlspecialchars($appliance['brand']) ?>">

            <label>Model:</label>
            <input type="text" value="<?= htmlspecialchars($appliance['model']) ?>">

            <label>Purchase Date:</label>
            <input type="text" value="<?= htmlspecialchars($appliance['purchase_date']) ?>">

            <label>Warranty Expiry:</label>
            <input type="text" value="<?= htmlspecialchars($appliance['warranty_expiry']) ?>">
        </fieldset>

        <label>Status:</label>
        <select name="status" required>
            <option value="Working" <?= ($appliance['status'] === 'Working') ? 'selected' : '' ?>>Working</option>
            <option value="Broken" <?= ($appliance['status'] === 'Broken') ? 'selected' : '' ?>>Broken</option>
            <option value="Under Repair" <?= ($appliance['status'] === 'Under Repair') ? 'selected' : '' ?>>Under Repair</option>
        </select>

        <button type="submit">Update Status</button>
    </form>
</body>
</html>
