<?php
session_start();
require_once "../class/db_connect.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Report | Warranty Tracker</title>
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
        <a href="manage_appliance.php" class="nav-link">Appliances</a>
        <a href="print_report.php" class="nav-link active">Reports</a>
    </div>
    <div class="flex items-center gap-4">
        <span style="font-weight: 500; font-size: 0.9rem;">Hello, <?php echo htmlspecialchars($_SESSION["username"]); ?></span>
        <a href="../logout.php" class="btn btn-sm btn-secondary">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</nav>

<div class="container" style="max-width: 800px;">
    <div class="flex items-center justify-between mb-4">
        <h2>Generate Reports</h2>
    </div>

    <div class="card">
        <div style="text-align: center; margin-bottom: 2rem;">
            <div style="width: 64px; height: 64px; background: #e0f2fe; color: #0284c7; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin: 0 auto 1rem;">
                <i class="fas fa-print"></i>
            </div>
            <h3>Configure Printable Report</h3>
            <p class="text-secondary">Select sections and filters to generate a PDF-ready report.</p>
        </div>

        <form method="GET" action="print_preview.php" target="_blank">
            <div style="margin-bottom: 2rem;">
                <h4 class="mb-4" style="font-size: 1.1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">Report Sections</h4>
                <div class="flex gap-6">
                    <label class="flex items-center gap-2" style="font-weight: 500;">
                        <input type="checkbox" name="sections[]" value="users" checked style="width: 1.2em; height: 1.2em;"> Users List
                    </label>
                    <label class="flex items-center gap-2" style="font-weight: 500;">
                        <input type="checkbox" name="sections[]" value="appliances" checked style="width: 1.2em; height: 1.2em;"> Appliances List
                    </label>
                    <label class="flex items-center gap-2" style="font-weight: 500;">
                        <input type="checkbox" name="sections[]" value="charts" style="width: 1.2em; height: 1.2em;"> Charts Summary
                    </label>
                </div>
            </div>

            <div style="margin-bottom: 2rem;">
                <h4 class="mb-4" style="font-size: 1.1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">Filters</h4>
                <div class="flex gap-4">
                    <div style="flex: 1;">
                        <label class="form-label">Month</label>
                        <select name="month" class="form-control">
                            <option value="">All Months</option>
                            <?php
                            for ($m = 1; $m <= 12; $m++) {
                                $monthName = date('F', mktime(0, 0, 0, $m, 1));
                                echo "<option value='$m'>$monthName</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div style="flex: 1;">
                        <label class="form-label">Year</label>
                        <select name="year" class="form-control">
                            <option value="">All Years</option>
                            <?php
                            $currentYear = date("Y");
                            for ($y = $currentYear; $y >= 2020; $y--) {
                                echo "<option value='$y'>$y</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="flex justify-between items-center pt-4" style="border-top: 1px solid var(--border-color);">
                <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-file-pdf"></i> Generate Report</button>
            </div>
        </form>
    </div>
</div>

</body>
</html>
