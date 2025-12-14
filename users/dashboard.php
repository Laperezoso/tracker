<?php
session_start();
require_once "../class/db_connect.php";


if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "user") {
    header("Location: ../login.php");
    exit;
}

$username = $_SESSION["username"];
$user_id = $_SESSION["user_id"];

$expiredQuery = $conn->prepare("SELECT COUNT(*) AS total FROM appliances WHERE user_id = ? AND status = 'Expired'");
$expiredQuery->bind_param("i", $user_id);
$expiredQuery->execute();
$expiredResult = $expiredQuery->get_result()->fetch_assoc()["total"];

$totalQuery = $conn->prepare("SELECT COUNT(*) AS total FROM appliances WHERE user_id = ?");
$totalQuery->bind_param("i", $user_id);
$totalQuery->execute();
$totalResult = $totalQuery->get_result()->fetch_assoc()["total"];

$workingQuery = $conn->prepare("SELECT COUNT(*) AS total FROM appliances WHERE user_id = ? AND status = 'Working'");
$workingQuery->bind_param("i", $user_id);
$workingQuery->execute();
$workingResult = $workingQuery->get_result()->fetch_assoc()["total"];

$brokenQuery = $conn->prepare("SELECT COUNT(*) AS total FROM appliances WHERE user_id = ? AND status = 'Broken'");
$brokenQuery->bind_param("i", $user_id);
$brokenQuery->execute();
$brokenResult = $brokenQuery->get_result()->fetch_assoc()["total"];

$repairQuery = $conn->prepare("SELECT COUNT(*) AS total FROM appliances WHERE user_id = ? AND status = 'Under Repair'");
$repairQuery->bind_param("i", $user_id);
$repairQuery->execute();
$repairResult = $repairQuery->get_result()->fetch_assoc()["total"];

$date = date("F j, Y");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard | Warranty Tracker</title>
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
        <a href="dashboard.php" class="nav-link active">Dashboard</a>
        <a href="view_appliances.php" class="nav-link">My Appliances</a>
        <a href="add_appliance.php" class="nav-link">Add Appliance</a>
    </div>
    <div class="flex items-center gap-4">
        <span style="font-weight: 500; font-size: 0.9rem;">Hello, <?php echo htmlspecialchars($username); ?></span>
        <a href="../logout.php" class="btn btn-sm btn-secondary">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</nav>

<div class="container">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h2>User Dashboard</h2>
            <div class="text-secondary">Welcome back, get an overview of your appliances.</div>
        </div>
        <div class="text-secondary"><?php echo $date; ?></div>
    </div>

    <!-- Stats Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2.5rem;">
        <div class="card flex items-center gap-4">
            <div style="background: #e0f2fe; color: #0284c7; width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                <i class="fas fa-cube"></i>
            </div>
            <div>
                <div style="font-size: 0.875rem; color: var(--text-secondary); font-weight: 500;">Total Appliances</div>
                <div style="font-size: 1.5rem; font-weight: 700; color: var(--text-primary);"><?php echo $totalResult; ?></div>
            </div>
        </div>

        <div class="card flex items-center gap-4">
            <div style="background: #e0e7ff; color: #4f46e5; width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                <i class="fas fa-check-circle"></i>
            </div>
            <div>
                <div style="font-size: 0.875rem; color: var(--text-secondary); font-weight: 500;">Working</div>
                <div style="font-size: 1.5rem; font-weight: 700; color: var(--text-primary);"><?php echo $workingResult; ?></div>
            </div>
        </div>

        <div class="card flex items-center gap-4">
            <div style="background: #fee2e2; color: #dc2626; width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                <i class="fas fa-times-circle"></i>
            </div>
            <div>
                <div style="font-size: 0.875rem; color: var(--text-secondary); font-weight: 500;">Broken</div>
                <div style="font-size: 1.5rem; font-weight: 700; color: var(--text-primary);"><?php echo $brokenResult; ?></div>
            </div>
        </div>

        <div class="card flex items-center gap-4">
            <div style="background: #fffbeb; color: #d97706; width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                <i class="fas fa-tools"></i>
            </div>
            <div>
                <div style="font-size: 0.875rem; color: var(--text-secondary); font-weight: 500;">In Repair</div>
                <div style="font-size: 1.5rem; font-weight: 700; color: var(--text-primary);"><?php echo $repairResult; ?></div>
            </div>
        </div>
    </div>

    <div class="card" style="border-left: 4px solid var(--accent-color);">
        <div class="flex items-center gap-4">
            <i class="fas fa-bell text-secondary" style="font-size: 1.5rem;"></i>
            <div>
                <h3 style="margin-bottom: 0.25rem;">Reminder</h3>
                <p class="text-secondary" style="margin: 0;">Keep your appliance details up-to-date. Always check your warranty expiry before requesting service.</p>
            </div>
        </div>
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
