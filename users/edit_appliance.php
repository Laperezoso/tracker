<?php
session_start();
require_once "../class/db_connect.php";
require_once "../class/appliances.php";

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "user") {
    header("Location: ../login.php");
    exit;
}

$applianceObj = new Appliance($conn);
$message = "";

if (!isset($_GET["id"]) || empty($_GET["id"])) {
    header("Location: view_appliances.php");
    exit;
}

$appliance_id = $_GET["id"];
$user_id = $_SESSION["user_id"];

// --- SELECT appliance with warranty info ---
$stmt = $conn->prepare("
    SELECT a.*, w.purchase_date, w.warranty_expiry
    FROM appliances a
    LEFT JOIN warranty w ON a.appliance_id = w.appliance_id
    WHERE a.appliance_id = ? AND a.user_id = ?
");
$stmt->bind_param("ii", $appliance_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$appliance = $result->fetch_assoc();

if (!$appliance) {
    $message = "❌ Appliance not found or not yours.";
} else {
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["appliance_name"]);
    $brand = trim($_POST["brand"]);
    $model = trim($_POST["model"]);
    $purchase = $_POST["purchase_date"];
    $expiry = $_POST["warranty_expiry"];
    $current_date = date("Y-m-d");

    if ($purchase > $current_date) {
        $message = "❌ Purchase date cannot be in the future.";
    } elseif ($expiry < $current_date) {
        $message = "⚠️ Warranty already expired. Please check the expiry date.";
    } else {
        $status = "Pending";

        // --- Update appliances table ---
        $sql_app = "UPDATE appliances 
                    SET appliance_name = ?, brand = ?, model = ?, status = ?
                    WHERE appliance_id = ? AND user_id = ?";
        $stmt_app = $conn->prepare($sql_app);
        $stmt_app->bind_param("ssssii", $name, $brand, $model, $status, $appliance_id, $user_id);
        $stmt_app->execute();  // only execute once

        // --- Check if warranty row exists ---
        $stmt_check = $conn->prepare("SELECT appliance_id FROM warranty WHERE appliance_id = ?");
        $stmt_check->bind_param("i", $appliance_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            // Update existing warranty
            $sql_w = "UPDATE warranty 
                      SET purchase_date = ?, warranty_expiry = ?
                      WHERE appliance_id = ?";
            $stmt_w = $conn->prepare($sql_w);
            $stmt_w->bind_param("ssi", $purchase, $expiry, $appliance_id);
            $stmt_w->execute();
        } else {
            // Insert new warranty row
            $sql_w_insert = "INSERT INTO warranty (appliance_id, purchase_date, warranty_expiry) VALUES (?, ?, ?)";
            $stmt_w_insert = $conn->prepare($sql_w_insert);
            $stmt_w_insert->bind_param("iss", $appliance_id, $purchase, $expiry);
            $stmt_w_insert->execute();
        }

        $message = "✅ Appliance updated successfully! (Status set to Pending)";

        // Refresh appliance data
        $stmt_refresh = $conn->prepare("
            SELECT a.*, w.purchase_date, w.warranty_expiry
            FROM appliances a
            LEFT JOIN warranty w ON a.appliance_id = w.appliance_id
            WHERE a.appliance_id = ? AND a.user_id = ?
        ");
        $stmt_refresh->bind_param("ii", $appliance_id, $user_id);
        $stmt_refresh->execute();
        $appliance = $stmt_refresh->get_result()->fetch_assoc();
    }
}

}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Appliance | Warranty Tracker</title>
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
        <a href="view_appliances.php" class="nav-link active">My Appliances</a>
        <a href="add_appliance.php" class="nav-link">Add Appliance</a>
    </div>
    <div class="flex items-center gap-4">
        <span style="font-weight: 500; font-size: 0.9rem;">Hello, <?php echo htmlspecialchars($_SESSION["username"]); ?></span>
        <a href="../logout.php" class="btn btn-sm btn-secondary">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</nav>

<div class="container" style="max-width: 600px;">
    <div class="card">
        <div class="flex items-center justify-between mb-4">
            <h3>Edit Appliance</h3>
            <a href="view_appliances.php" class="text-secondary hover:text-primary"><i class="fas fa-arrow-left"></i> Back</a>
        </div>

        <?php if (!empty($message)): ?>
            <div class="text-center mb-4 <?php echo (strpos($message, '✅') !== false || strpos($message, 'successfully') !== false) ? 'text-success' : 'text-danger'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($appliance): ?>
        <form method="POST">
            <div class="form-group">
                <label class="form-label">Appliance Name</label>
                <input type="text" name="appliance_name" class="form-control" value="<?php echo htmlspecialchars($appliance['appliance_name']); ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">Brand</label>
                <input type="text" name="brand" class="form-control" value="<?php echo htmlspecialchars($appliance['brand']); ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">Model</label>
                <input type="text" name="model" class="form-control" value="<?php echo htmlspecialchars($appliance['model']); ?>" required>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Purchase Date</label>
                    <input type="date" name="purchase_date" class="form-control" value="<?php echo htmlspecialchars($appliance['purchase_date']); ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Warranty Expiry</label>
                    <input type="date" name="warranty_expiry" class="form-control" value="<?php echo htmlspecialchars($appliance['warranty_expiry']); ?>" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-full mt-4">
                <i class="fas fa-save"></i> Update Appliance
            </button>
        </form>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
