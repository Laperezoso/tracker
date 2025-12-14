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
    <title>User Dashboard</title>
    <style>
        body {
            font-family: "Segoe UI", Arial, sans-serif;
            background-color: #f5f8ff;
            color: #000;
            margin: 0;
            padding: 0;
        }

        nav {
            background-color: #000000ff;
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        nav a {
            color: white;
            text-decoration: none;
            margin: 0 15px;
            font-weight: 500;
        }

        nav a:hover {
            text-decoration: underline;
        }

        .dashboard {
            max-width: 900px;
            margin: 50px auto;
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }

        .header-info {
            text-align: center;
            margin-bottom: 40px;
        }

        .header-info h2 {
            color: #0d47a1;
            margin-bottom: 5px;
        }

        .overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            text-align: center;
        }

        .box {
            background: #e3f2fd;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            font-weight: bold;
        }

        .box span {
            display: block;
            font-size: 35px;
            color: #0d47a1;
            margin-top: 8px;
        }

        .reminder {
            margin-top: 50px;
            background: #bbdefb;
            padding: 20px;
            border-left: 6px solid #0d47a1;
            border-radius: 8px;
        }

        footer {
    text-align: center;
    background-color: #000;
    color: white;
    padding: 40px 0 20px;
    margin-top: 60px;
    font-size: 15px;
}

.social-icons {
    margin-bottom: 15px;
}

.social-icons a img {
    width: 40px;
    height: 40px;
    margin: 0 10px;
    border-radius: 50%;
    background-color: white;
    padding: 8px;
    transition: transform 0.2s ease, background-color 0.3s;
}

.social-icons a img:hover {
    transform: scale(1.1);
    background-color: #0d47a1;
}

footer p {
    margin: 6px 0;
    color: #ccc;
}

footer p strong {
    color: #fff;
}

footer a:hover {
    text-decoration: underline;
}

.copyright {
    color: #777;
    font-size: 13px;
    margin-top: 10px;
}

    </style>
</head>
<body>

    <nav>
        <div class="nav-left">
            <a href="add_appliance.php">Add Appliance</a>
            <a href="view_appliances.php">View Appliances</a>
        </div>
        <div class="nav-right">
            <a href="../logout.php">Logout</a>
        </div>
    </nav>

    <div class="dashboard">
        <div class="header-info">
            <h2>Welcome, <?php echo htmlspecialchars($username); ?>!</h2>
            <p><?php echo $date; ?></p>
        </div>

        <div class="overview">
            <div class="box">
                Total Appliances
                <span><?php echo $totalResult; ?></span>
            </div>
            <div class="box">
                Working
                <span><?php echo $workingResult; ?></span>
            </div>
            <div class="box">
                Broken
                <span><?php echo $brokenResult; ?></span>
            </div>
            <div class="box">
                Under Repair
                <span><?php echo $repairResult; ?></span>
            </div>
            <div class="box">
                Expired
                <span><?php echo $expiredResult; ?></span>
            </div>
        </div>

        <div class="reminder">
            <h3>🔔 Reminder</h3>
            <p>Keep your appliance details up-to-date. Always check your warranty expiry before requesting service.</p>
        </div>
    </div>

    <footer>
    <div class="social-icons">
        <a href="#" title="Facebook"><img src="../image/facebook.png" alt="Facebook"></a>
        <a href="mailto:support@myappliancetracker.com" title="Gmail"><img src="../image/gmail.png" alt="Gmail"></a>
        <a href="#" title="Twitter"><img src="../image/twitter.png" alt="Twitter"></a>
    </div>

    <p><strong>ApplianceServiceWarrantyTracker</strong> — Making home management easier.</p>
    <p>Need help? Contact us at 
        <a href="mailto:WarrantyTracker@gmail.com" style="color:#90caf9; text-decoration:none;">
            WarrantyTracker@gmail.com
        </a>
    </p>

    <p class="copyright">© <?php echo date("Y"); ?> ApplianceServiceWarrantyTracker</p>
</footer>




</body>
</html>
