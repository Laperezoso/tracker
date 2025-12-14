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
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard | Warranty Tracker</title>
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
        <a href="manage_users.php" class="nav-link">Users</a>
        <a href="manage_appliance.php" class="nav-link">Appliances</a>
        <a href="print_report.php" class="nav-link">Reports</a>
    </div>
    <div class="flex items-center gap-4">
        <span style="font-weight: 500; font-size: 0.9rem;">Hello, <?php echo htmlspecialchars($_SESSION["username"]); ?></span>
        <a href="../logout.php" class="btn btn-sm btn-secondary">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</nav>

<div class="container">
    <div class="flex items-center justify-between mb-4">
        <h2>Dashboard Overview</h2>
        <div class="text-secondary"><?php echo date("l, F j, Y"); ?></div>
    </div>

    <!-- Stats Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-bottom: 2.5rem;">
        <div class="card flex items-center gap-4">
            <div style="background: #e0f2fe; color: #0284c7; width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                <i class="fas fa-users"></i>
            </div>
            <div>
                <div style="font-size: 0.875rem; color: var(--text-secondary); font-weight: 500;">Total Users</div>
                <div style="font-size: 1.5rem; font-weight: 700; color: var(--text-primary);"><?php echo $total_users; ?></div>
            </div>
        </div>

        <div class="card flex items-center gap-4">
            <div style="background: #e0e7ff; color: #4f46e5; width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                <i class="fas fa-tv"></i>
            </div>
            <div>
                <div style="font-size: 0.875rem; color: var(--text-secondary); font-weight: 500;">Total Appliances</div>
                <div style="font-size: 1.5rem; font-weight: 700; color: var(--text-primary);"><?php echo $total_appliances; ?></div>
            </div>
        </div>

        <div class="card flex items-center gap-4">
            <div style="background: #fffbeb; color: #d97706; width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <div>
                <div style="font-size: 0.875rem; color: var(--text-secondary); font-weight: 500;">Expiring Soon</div>
                <div style="font-size: 1.5rem; font-weight: 700; color: var(--text-primary);"><?php echo $expiring_soon; ?></div>
            </div>
        </div>

        <div class="card flex items-center gap-4">
            <div style="background: #fee2e2; color: #dc2626; width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                <i class="fas fa-times-circle"></i>
            </div>
            <div>
                <div style="font-size: 0.875rem; color: var(--text-secondary); font-weight: 500;">Expired</div>
                <div style="font-size: 1.5rem; font-weight: 700; color: var(--text-primary);"><?php echo $expired; ?></div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 1.5rem; margin-bottom: 2.5rem;">
        <div class="card">
            <h3 class="mb-4">Top 10 Users by Appliance Count</h3>
            <div style="position: relative; height: 300px; width: 100%;">
                <canvas id="userApplianceChart"></canvas>
            </div>
        </div>
        <div class="card">
            <h3 class="mb-4">Appliance Status Distribution</h3>
            <div style="position: relative; height: 300px; width: 100%;">
                <canvas id="statusChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Recent Data Tables -->
    <div style="display: grid; gap: 2.5rem;">
        
        <!-- Users List -->
        <div>
            <div class="flex items-center justify-between mb-4">
                <h3>Registered Users</h3>
                <a href="manage_users.php" class="btn btn-sm btn-secondary">Manage All</a>
            </div>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Joined Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $users = $conn->query("SELECT * FROM user_accounts WHERE role = 'user' LIMIT 5");
                        if ($users->num_rows > 0) {
                            while ($row = $users->fetch_assoc()) {
                                echo "<tr>
                                    <td>#{$row['user_id']}</td>
                                    <td>
                                        <div class='flex items-center gap-4'>
                                            <div style='background: #f1f5f9; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: bold; color: var(--text-secondary);'>" . strtoupper(substr($row['username'], 0, 1)) . "</div>
                                            {$row['username']}
                                        </div>
                                    </td>
                                    <td><span class='badge' style='background: #e0f2fe; color: #0369a1;'>USER</span></td>
                                    <td>" . date("M j, Y", strtotime($row['created_at'])) . "</td>
                                    <td><a href='delete_user.php?id={$row['user_id']}' class='text-danger' onclick='return confirm(\"Are you sure?\")'><i class='fas fa-trash'></i></a></td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5' style='text-align:center;'>No users found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Appliances List -->
        <div>
            <div class="flex items-center justify-between mb-4">
                <h3>Recent Appliances</h3>
                <a href="manage_appliance.php" class="btn btn-sm btn-secondary">Manage All</a>
            </div>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Appliance</th>
                            <th>User</th>
                            <th>Expiry Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "
                            SELECT a.*, u.username, w.warranty_expiry
                            FROM appliances a
                            JOIN user_accounts u ON a.user_id = u.user_id
                            LEFT JOIN warranty w ON a.appliance_id = w.appliance_id
                            ORDER BY a.appliance_id DESC LIMIT 5
                        ";
                        $result = $conn->query($query);

                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $status_badge = "badge-success";
                                $status_text = "Active";

                                if ($row['warranty_expiry'] && $row['warranty_expiry'] < date("Y-m-d")) {
                                    $status_text = "Expired";
                                    $status_badge = "badge-danger";
                                } elseif ($row['warranty_expiry'] && $row['warranty_expiry'] <= date("Y-m-d", strtotime("+7 days"))) {
                                    $status_text = "Expiring Soon";
                                    $status_badge = "badge-warning";
                                }

                                echo "<tr>
                                    <td>#{$row['appliance_id']}</td>
                                    <td>
                                        <div style='font-weight: 500;'>{$row['appliance_name']}</div>
                                        <div style='font-size: 0.8rem; color: var(--text-secondary);'>{$row['brand']} {$row['model']}</div>
                                    </td>
                                    <td>{$row['username']}</td>
                                    <td>" . ($row['warranty_expiry'] ? date("M j, Y", strtotime($row['warranty_expiry'])) : 'N/A') . "</td>
                                    <td><span class='badge {$status_badge}'>{$status_text}</span></td>
                                    <td>
                                        <a href='delete_appliance.php?id={$row['appliance_id']}' class='text-danger' onclick='return confirm(\"Are you sure?\")'>
                                            <i class='fas fa-trash'></i>
                                        </a>
                                    </td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6' style='text-align:center;'>No appliances found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Chart Config
const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false }
    },
    scales: {
        y: { beginAtZero: true, grid: { color: '#f1f5f9' } },
        x: { grid: { display: false } }
    }
};

// Users Appliances Count
<?php
$userCountResult = $conn->query("
    SELECT u.username, COUNT(a.appliance_id) AS total
    FROM user_accounts u
    LEFT JOIN appliances a ON u.user_id = a.user_id
    WHERE u.role = 'user'
    GROUP BY u.user_id
    ORDER BY total DESC
    LIMIT 10
");

$usernames = [];
$applianceCounts = [];
while ($row = $userCountResult->fetch_assoc()) {
    $usernames[] = $row['username'];
    $applianceCounts[] = $row['total'];
}

// Appliance Status counts
$statusResult = $conn->query("SELECT status, COUNT(*) AS total FROM appliances GROUP BY status");
$statusNames = [];
$statusTotals = [];
while ($row = $statusResult->fetch_assoc()) {
    $statusNames[] = $row['status'];
    $statusTotals[] = $row['total'];
}
?>

new Chart(document.getElementById('userApplianceChart'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($usernames); ?>,
        datasets: [{
            label: 'Appliances',
            data: <?php echo json_encode($applianceCounts); ?>,
            backgroundColor: '#3b82f6',
            borderRadius: 4
        }]
    },
    options: chartOptions
});

new Chart(document.getElementById('statusChart'), {
    type: 'pie', // Changed to Pie for variety
    data: {
        labels: <?php echo json_encode($statusNames); ?>,
        datasets: [{
            data: <?php echo json_encode($statusTotals); ?>,
            backgroundColor: ['#10b981', '#ef4444', '#f59e0b', '#6366f1'],
            borderWidth: 0
        }]
    },
    options: {
        plugins: { legend: { position: 'bottom' } }
    }
});
</script>
</body>
</html>
