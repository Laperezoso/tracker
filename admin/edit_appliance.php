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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Appliance (Admin) | Warranty Tracker</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<nav class="navbar">
    <div class="nav-brand flex items-center gap-2">
        <img src="../image/Clearbglogo.png" alt="Logo" style="height: 40px;">
        Warranty Tracker
    </div>
    <div class="nav-links">
        <a href="dashboard.php" class="nav-link">Dashboard</a>
        <a href="manage_users.php" class="nav-link">Users</a>
        <a href="manage_appliance.php" class="nav-link active">Appliances</a>
        <a href="print_report.php" class="nav-link">Reports</a>
    </div>
    <div class="flex items-center gap-4">
        <span style="font-weight: 500; font-size: 0.9rem;">Admin</span>
        <a href="../logout.php" class="btn btn-sm btn-secondary">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</nav>

<div class="container" style="max-width: 600px;">
    <div class="card">
        <div class="flex items-center justify-between mb-4">
            <h3>Edit Appliance Status</h3>
            <a href="manage_appliance.php" class="text-secondary hover:text-primary"><i class="fas fa-arrow-left"></i> Back</a>
        </div>

        <?php if ($error): ?>
            <div class="text-center mb-4 text-danger">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="text-center mb-4 text-success">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <fieldset disabled style="border: none; padding: 0; margin: 0;">
                <div class="form-group">
                    <label class="form-label">Appliance Name</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($appliance['appliance_name']) ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Brand</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($appliance['brand']) ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Model</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($appliance['model']) ?>">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Purchase Date</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($appliance['purchase_date']) ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Warranty Expiry</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($appliance['warranty_expiry']) ?>">
                    </div>
                </div>
            </fieldset>

            <hr style="border: 0; border-top: 1px solid var(--border-color); margin: 1.5rem 0;">

            <div class="form-group">
                <label class="form-label">Update Status</label>
                <select name="status" class="form-control" required>
                    <option value="Working" <?= ($appliance['status'] === 'Working') ? 'selected' : '' ?>>Working</option>
                    <option value="Broken" <?= ($appliance['status'] === 'Broken') ? 'selected' : '' ?>>Broken</option>
                    <option value="Under Repair" <?= ($appliance['status'] === 'Under Repair') ? 'selected' : '' ?>>Under Repair</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary w-full">
                <i class="fas fa-save"></i> Update Status
            </button>
        </form>
    </div>
</div>

</body>
</html>
