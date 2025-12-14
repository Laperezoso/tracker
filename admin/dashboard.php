<?php
session_start();
require_once "../class/db_connect.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../login.php");
    exit;
}

$total_users = 0;
$total_appliances = 0;
$expiring_soon = 0;
$expired = 0;

// Total users
$user_query = "SELECT COUNT(*) AS total_users FROM user_accounts WHERE role = 'user'";
$user_result = $conn->query($user_query);
if ($user_result && $user_result->num_rows > 0) {
    $total_users = $user_result->fetch_assoc()['total_users'];
}

// Total appliances
$app_query = "SELECT COUNT(*) AS total_appliances FROM appliances";
$app_result = $conn->query($app_query);
if ($app_result && $app_result->num_rows > 0) {
    $total_appliances = $app_result->fetch_assoc()['total_appliances'];
}

// Expiring soon
$expiring_query = "
    SELECT COUNT(*) AS expiring_soon 
    FROM warranty 
    WHERE warranty_expiry BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
$expiring_result = $conn->query($expiring_query);
if ($expiring_result && $expiring_result->num_rows > 0) {
    $expiring_soon = $expiring_result->fetch_assoc()['expiring_soon'];
}

// Expired
// Expired Warranty
$expired_query = "
    SELECT COUNT(*) AS expired 
    FROM warranty 
    WHERE warranty_expiry < CURDATE()
";
$expired_result = $conn->query($expired_query);
$expired = 0;
if ($expired_result && $expired_result->num_rows > 0) {
    $expired = $expired_result->fetch_assoc()['expired'];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>ADMIN DASHBOARD</title>
<style>
:root {
    --primary-blue: #007bff;
    --dark: #111;
    --light-gray: #f4f4f4;
    --white: #fff;
}

body {
    font-family: "Segoe UI", Arial, sans-serif;
    margin: 0;
    background-color: var(--light-gray);
    color: var(--dark);
}

.navbar {
    position: sticky;
    top: 0;
    background-color: var(--dark);
    padding: 14px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    z-index: 1000;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

.navbar .nav-links {
    display: flex;
    align-items: center;
    gap: 20px;
}

.navbar .nav-right {
    display: flex;
    align-items: center;
    gap: 16px;
}

.navbar a {
    color: var(--white);
    text-decoration: none;
    font-weight: 500;
    transition: color 0.2s ease;
}

.navbar a:hover {
    color: var(--primary-blue);
}

.navbar .welcome {
    color: var(--white);
    font-weight: 500;
    font-size: 15px;
}

.content {
    padding: 24px;
    max-width: 1200px;
    margin: auto;
}

h1 {
    text-transform: uppercase;
    color: var(--primary-blue);
    letter-spacing: 1px;
}

h2 {
    margin-top: 40px;
    border-left: 5px solid var(--primary-blue);
    padding-left: 10px;
    color: var(--dark);
}

ul {
    list-style-type: none;
    padding: 0;
    display: grid;
    gap: 10px;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
}

ul li {
    background-color: var(--white);
    padding: 14px;
    border-left: 4px solid var(--primary-blue);
    border-radius: 6px;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
    font-weight: 500;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 12px;
    background-color: var(--white);
    border-radius: 6px;
    overflow: hidden;
    box-shadow: 0 0 6px rgba(0, 0, 0, 0.1);
}

th, td {
    padding: 10px;
    border-bottom: 1px solid #eee;
    text-align: center;
}

th {
    background-color: var(--primary-blue);
    color: var(--white);
}

tr:hover {
    background-color: #eaf2ff;
}

a.action-link {
    color: var(--primary-blue);
    text-decoration: none;
    font-weight: 500;
}

a.action-link:hover {
    text-decoration: underline;
}

.status-active {
    color: #28a745;
    font-weight: 600;
}

.status-expiring {
    color: #ffc107;
    font-weight: 600;
}

.status-expired {
    color: #dc3545;
    font-weight: 600;
}

@media print {
    .navbar, button, a.action-link {
        display: none !important;
    }
    body {
        background: white;
    }
    .content {
        margin: 0;
        padding: 0;
    }
}
</style>

</style>
</head>
<body>

<div class="navbar">
    <div class="nav-links">
        <a href="dashboard.php">Dashboard</a>
        <a href="manage_users.php">Manage Users</a>
        <a href="manage_appliance.php">Manage Appliances</a>
        <a href="print_report.php">Print Report</a>
    </div>

    <div class="nav-right">
        <span class="welcome">Welcome, <strong><?php echo $_SESSION["username"]; ?></strong></span>
        <a href="../logout.php">Logout</a>
    </div>
</div>



<div class="content">
<h1>Admin Dashboard</h1>
<p>Welcome, <strong><?php echo $_SESSION["username"]; ?></strong>!</p>
<hr>

<h2>System Overview</h2>
<ul>
    <li><strong>Total Users:</strong> <?php echo $total_users; ?></li>
    <li><strong>Total Appliances:</strong> <?php echo $total_appliances; ?></li>
    <li><strong>Expiring Soon:</strong> <?php echo $expiring_soon; ?></li>
    <li><strong>Expired:</strong> <?php echo $expired; ?></li>
</ul>

<hr>

<h2>Users List</h2>
<table>
<tr>
    <th>User ID</th>
    <th>Username</th>
    <th>Role</th>
    <th>Created At</th>
    <th>Actions</th>
</tr>
<?php
$users = $conn->query("SELECT * FROM user_accounts WHERE role = 'user'");
while ($row = $users->fetch_assoc()) {
    echo "<tr>
        <td>{$row['user_id']}</td>
        <td>{$row['username']}</td>
        <td>{$row['role']}</td>
        <td>{$row['created_at']}</td>
        <td><a class='action-link' href='delete_user.php?id={$row['user_id']}'>Delete</a></td>
    </tr>";
}
?>
</table>

<hr>

<h2>All Appliances</h2>
<table>
<tr>
    <th>ID</th>
    <th>User</th>
    <th>Appliance</th>
    <th>Brand</th>
    <th>Model</th>
    <th>Purchase Date</th>
    <th>Expiry</th>
    <th>Status</th>
    <th>Action</th>
</tr>
<?php
$query = "
    SELECT a.*, u.username, w.purchase_date, w.warranty_expiry
    FROM appliances a
    JOIN user_accounts u ON a.user_id = u.user_id
    LEFT JOIN warranty w ON a.appliance_id = w.appliance_id
    ORDER BY a.appliance_id DESC
";
$result = $conn->query($query);

while ($row = $result->fetch_assoc()) {
    $status_class = "";
    $status_text = "";

    if ($row['warranty_expiry'] && $row['warranty_expiry'] < date("Y-m-d")) {
        $status_text = "Expired";
        $status_class = "status-expired";
    } elseif ($row['warranty_expiry'] && $row['warranty_expiry'] <= date("Y-m-d", strtotime("+7 days"))) {
        $status_text = "Expiring Soon";
        $status_class = "status-expiring";
    } else {
        $status_text = "Active";
        $status_class = "status-active";
    }

    echo "<tr>
        <td>{$row['appliance_id']}</td>
        <td>{$row['username']}</td>
        <td>{$row['appliance_name']}</td>
        <td>{$row['brand']}</td>
        <td>{$row['model']}</td>
        <td>".($row['purchase_date'] ?? '')."</td>
        <td>".($row['warranty_expiry'] ?? '')."</td>
        <td class='{$status_class}'>{$status_text}</td>
        <td><a class='action-link' href='delete_appliance.php?id={$row['appliance_id']}'>Delete</a></td>
    </tr>";
}
?>
</table>

<hr>

<h2>Charts Overview</h2>
<div style="display: flex; flex-wrap: wrap; gap: 30px;">
    <div style="flex: 1; min-width: 400px;">
        <canvas id="userApplianceChart"></canvas>
    </div>
    <div style="flex: 1; min-width: 400px;">
        <canvas id="statusChart"></canvas>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Users Appliances Count
<?php
$userCountQuery = "
    SELECT u.username, COUNT(a.appliance_id) AS total
    FROM user_accounts u
    LEFT JOIN appliances a ON u.user_id = a.user_id
    WHERE u.role = 'user'
    GROUP BY u.user_id
";
$userCountResult = $conn->query($userCountQuery);

$usernames = [];
$applianceCounts = [];
while ($row = $userCountResult->fetch_assoc()) {
    $usernames[] = $row['username'];
    $applianceCounts[] = $row['total'];
}

// Appliance Status counts
$statusQuery = "SELECT status, COUNT(*) AS total FROM appliances GROUP BY status";
$statusResult = $conn->query($statusQuery);

$statusNames = [];
$statusTotals = [];
while ($row = $statusResult->fetch_assoc()) {
    $statusNames[] = $row['status'];
    $statusTotals[] = $row['total'];
}
?>

const ctx1 = document.getElementById('userApplianceChart');
new Chart(ctx1, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($usernames); ?>,
        datasets: [{
            label: 'Appliances per User',
            data: <?php echo json_encode($applianceCounts); ?>,
            backgroundColor: 'rgba(0, 123, 255, 0.6)',
            borderColor: 'rgba(0, 123, 255, 1)',
            borderWidth: 1
        }]
    },
    options: {
        plugins: {
            title: { display: true, text: 'Users Appliances Counts', font: { size: 16, weight: 'bold' } },
            legend: { display: false }
        },
        scales: { y: { beginAtZero: true } }
    }
});

const ctx2 = document.getElementById('statusChart');
new Chart(ctx2, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($statusNames); ?>,
        datasets: [{
            label: 'Appliance Status Count',
            data: <?php echo json_encode($statusTotals); ?>,
            backgroundColor: 'rgba(0, 123, 255, 0.6)',
            borderWidth: 1
        }]
    },
    options: {
        plugins: {
            title: { display: true, text: 'Appliances Status Counts', font: { size: 16, weight: 'bold' } },
            legend: { display: false }
        },
        scales: { y: { beginAtZero: true, ticks: { stepSize: 1, precision: 0 } } }
    }
});
</script>
</body>
</html>
