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

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["appliance_name"]);
    $brand = trim($_POST["brand"]);
    $model = trim($_POST["model"]);
    $purchase = $_POST["purchase_date"];
    $expiry = $_POST["warranty_expiry"];
    $user_id = $_SESSION["user_id"];
    $status = "Broken"; 

    $current_date = date("Y-m-d");

    
    if ($purchase > $current_date) {
        $message = " Purchase date cannot be in the future.";
    } elseif ($expiry < $current_date) {
        $message = " Warranty already expired. Please check the expiry date.";
    } else {
       
        $sql = "INSERT INTO appliances (user_id, appliance_name, brand, model, status)
        VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("issss", $user_id, $name, $brand, $model, $status);

if ($stmt->execute()) {
    // Get the last inserted appliance_id
    $appliance_id = $stmt->insert_id;

    // Insert warranty info
    $warranty_sql = "INSERT INTO warranty (appliance_id, purchase_date, warranty_expiry) VALUES (?, ?, ?)";
    $warranty_stmt = $conn->prepare($warranty_sql);
    $warranty_stmt->bind_param("iss", $appliance_id, $purchase, $expiry);
    $warranty_stmt->execute();

    $message = "Appliance added successfully! (Default Status: Broken)";
} else {
    $message = "Failed to add appliance.";
}

    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Appliance | Warranty Tracker</title>
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
        <a href="view_appliances.php" class="nav-link">My Appliances</a>
        <a href="add_appliance.php" class="nav-link active">Add Appliance</a>
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
            <h3>Add New Appliance</h3>
        </div>

        <?php if (!empty($message)): ?>
            <div class="text-center mb-4 <?php echo (strpos($message, '✅') !== false || strpos($message, 'successfully') !== false) ? 'text-success' : 'text-danger'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label class="form-label">Appliance Name</label>
                <input type="text" name="appliance_name" class="form-control" placeholder="e.g. Washing Machine" required>
            </div>

            <div class="form-group">
                <label class="form-label">Brand</label>
                <input type="text" name="brand" class="form-control" placeholder="e.g. LG" required>
            </div>

            <div class="form-group">
                <label class="form-label">Model</label>
                <input type="text" name="model" class="form-control" placeholder="e.g. WM-123X" required>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Purchase Date</label>
                    <input type="date" name="purchase_date" class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Warranty Expiry</label>
                    <input type="date" name="warranty_expiry" class="form-control" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-full mt-4">
                <i class="fas fa-plus-circle"></i> Add Appliance
            </button>
        </form>
    </div>
</div>

<footer class="footer">
    <div class="flex justify-center gap-4 mb-4">
        <a href="#" class="text-secondary hover:text-primary" style="font-size: 1.25rem;"><i class="fab fa-facebook"></i></a>
        <a href="#" class="text-secondary hover:text-primary" style="font-size: 1.25rem;"><i class="fab fa-twitter"></i></a>
        <a href="mailto:WarrantyTracker@gmail.com" class="text-secondary hover:text-primary" style="font-size: 1.25rem;"><i class="fas fa-envelope"></i></a>
    </div>
    <p class="text-secondary" style="margin-bottom: 0.5rem;"><strong>Appliance Warranty Tracker</strong> — Making home management easier.</p>
    <p class="text-secondary" style="margin-bottom: 0.5rem;">razelherodias014@gmail.com</p>
    <p class="text-secondary" style="font-size: 0.9rem;">&copy; <?php echo date("Y"); ?> Appliance Service Warranty Tracker</p>
</footer>


</body>
</html>
