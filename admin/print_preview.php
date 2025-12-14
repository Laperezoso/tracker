<?php
session_start();
require_once "../class/db_connect.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../login.php");
    exit;
}

$sections = isset($_GET['sections']) ? $_GET['sections'] : [];
$month = $_GET['month'] ?? '';
$year = $_GET['year'] ?? '';

/* --- DATE FILTER --- */
$dateFilter = "";
$params = [];
if ($month && $year) {
    $dateFilter = "WHERE MONTH(w.purchase_date) = ? AND YEAR(w.purchase_date) = ?";
    $params = [$month, $year];
} elseif ($year) {
    $dateFilter = "WHERE YEAR(w.purchase_date) = ?";
    $params = [$year];
} elseif ($month) {
    $dateFilter = "WHERE MONTH(w.purchase_date) = ?";
    $params = [$month];
}

/* --- Appliance Status counts (with filter) --- */
$status_counts = [];
$status_query = "
    SELECT a.status, COUNT(*) AS count 
    FROM appliances a
    LEFT JOIN warranty w ON a.appliance_id = w.appliance_id
    $dateFilter
    GROUP BY a.status
";
$stmt_status = $conn->prepare($status_query);
if (!empty($params)) {
    $types = str_repeat('i', count($params));
    $stmt_status->bind_param($types, ...$params);
}
$stmt_status->execute();
$status_result = $stmt_status->get_result();
if ($status_result->num_rows > 0) {
    while ($row = $status_result->fetch_assoc()) {
        $status_counts[$row['status']] = (int)$row['count'];
    }
}

/* --- User Appliance counts (with filter) --- */
$user_appliance_counts = [];
$user_appliance_query = "
    SELECT u.username, COUNT(a.appliance_id) AS count
    FROM user_accounts u
    LEFT JOIN appliances a ON u.user_id = a.user_id
    LEFT JOIN warranty w ON a.appliance_id = w.appliance_id
    WHERE u.role = 'user'
";
if ($dateFilter) {
    $user_appliance_query .= " AND " . str_replace("WHERE", "", $dateFilter);
}
$user_appliance_query .= " GROUP BY u.user_id";

$stmt_user = $conn->prepare($user_appliance_query);
if (!empty($params)) {
    $types = str_repeat('i', count($params));
    $stmt_user->bind_param($types, ...$params);
}
$stmt_user->execute();
$user_appliance_result = $stmt_user->get_result();
if ($user_appliance_result->num_rows > 0) {
    while ($row = $user_appliance_result->fetch_assoc()) {
        $user_appliance_counts[$row['username']] = (int)$row['count'];
    }
}

/* --- Appliances query (with filter) --- */
$appliance_query = "
    SELECT a.appliance_id, u.username, a.appliance_name, a.status, w.purchase_date, w.warranty_expiry
    FROM appliances a
    JOIN user_accounts u ON a.user_id = u.user_id
    LEFT JOIN warranty w ON a.appliance_id = w.appliance_id
    $dateFilter
";
$stmt = $conn->prepare($appliance_query);
if (!empty($params)) {
    $types = str_repeat('i', count($params));
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$appliance_result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Report Preview</title>
<style>
body {
    font-family: "Segoe UI", sans-serif;
    background-color: #f4f4f4;
    margin: 0;
    padding: 30px;
    color: #333;
}
.container {
    max-width: 900px;
    background: white;
    margin: auto;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    text-align: center;
}
.logo {
    width: 120px;
    margin-bottom: 10px;
}
h1 {
    color: #007bff;
    margin-bottom: 5px;
}
.subtitle {
    font-size: 18px;
    font-weight: 600;
    color: #555;
    margin-bottom: 25px;
}
.table-container {
    overflow-x: auto;
    margin-top: 20px;
}
table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}
th, td {
    padding: 12px;
    border: 1px solid #ddd;
    text-align: left;
}
th {
    background-color: #007bff;
    color: white;
}
tr:nth-child(even) {
    background-color: #f9f9f9;
}
.back-btn {
    background-color: #6c757d;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    font-weight: bold;
    cursor: pointer;
}
.back-btn:hover {
    background-color: #5a6268;
}
.section-title {
    color: #007bff;
    text-align: left;
    font-weight: bold;
    margin-top: 30px;
}
canvas {
    width: 100%;
    height: 400px;
    margin-bottom: 30px;
}
.chart-title {
    text-align: left;
    color: #007bff;
    margin-bottom: 5px;
}
.button-container {
    margin-top: 20px;
    display: flex;
    justify-content: center;
    gap: 15px;
}
</style>
</head>
<body>

<div class="container">
    <img src="../image/Clearbglogo.png" alt="Logo" class="logo">
    <h1>Report Preview</h1>
    <div class="subtitle">Appliances Service Warranty Tracker</div>

    <?php if (in_array('users', $sections)): ?>
        <div class="section-title">User Accounts</div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $user_query = "SELECT user_id, username, role, created_at FROM user_accounts";
                $user_result = $conn->query($user_query);
                if ($user_result->num_rows > 0) {
                    while ($row = $user_result->fetch_assoc()) {
                        echo "<tr>
                                <td>{$row['user_id']}</td>
                                <td>{$row['username']}</td>
                                <td>{$row['role']}</td>
                                <td>{$row['created_at']}</td>
                              </tr>";
                    }
                } else {
                    echo "<tr><td colspan='4'>No user records found.</td></tr>";
                }
                ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php if (in_array('appliances', $sections)): ?>
        <div class="section-title">Appliances</div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Appliance ID</th>
                        <th>User</th>
                        <th>Appliance Name</th>
                        <th>Status</th>
                        <th>Purchase Date</th>
                        <th>Warranty Expiry</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                if ($appliance_result->num_rows > 0) {
                    while ($row = $appliance_result->fetch_assoc()) {
                        echo "<tr>
                                <td>{$row['appliance_id']}</td>
                                <td>{$row['username']}</td>
                                <td>{$row['appliance_name']}</td>
                                <td>{$row['status']}</td>
                                <td>".($row['purchase_date'] ?? '')."</td>
                                <td>".($row['warranty_expiry'] ?? '')."</td>
                              </tr>";
                    }
                } else {
                    echo "<tr><td colspan='6'>No appliance records found.</td></tr>";
                }
                ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php if (in_array('charts', $sections)): ?>
        <div class="section-title">Charts Summary</div>

        <div class="chart-title">Appliance Status</div>
        <canvas id="statusChart"></canvas>

        <div class="chart-title">User Appliances Count</div>
        <canvas id="userChart"></canvas>

        <script>
        const statusData = <?php echo json_encode($status_counts); ?>;
        const userData = <?php echo json_encode($user_appliance_counts); ?>;

        function drawBarChart(canvasId, data) {
            const canvas = document.getElementById(canvasId);
            const ctx = canvas.getContext('2d');
            canvas.width = canvas.clientWidth;
            canvas.height = 400;
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            const padding = 50;
            const chartHeight = canvas.height - 2 * padding;
            const chartWidth = canvas.width - 2 * padding;
            const barSpacing = 30;
            const barWidth = (chartWidth - (Object.keys(data).length - 1) * barSpacing) / Object.keys(data).length;
            const maxCount = Math.max(...Object.values(data), 1);

            let i = 0;
            for (const [label, count] of Object.entries(data)) {
                const barHeight = (count / maxCount) * chartHeight;
                const x = padding + i * (barWidth + barSpacing);
                const y = canvas.height - padding - barHeight;

                ctx.fillStyle = '#007bff';
                ctx.fillRect(x, y, barWidth, barHeight);
                ctx.fillStyle = '#333';
                ctx.font = '16px sans-serif';
                ctx.textAlign = 'center';
                ctx.fillText(count, x + barWidth / 2, y - 5);
                ctx.fillText(label, x + barWidth / 2, canvas.height - padding + 20);
                i++;
            }
            ctx.strokeStyle = '#333';
            ctx.beginPath();
            ctx.moveTo(padding, padding);
            ctx.lineTo(padding, canvas.height - padding);
            ctx.lineTo(canvas.width - padding, canvas.height - padding);
            ctx.stroke();
        }

        function drawCharts() {
            drawBarChart('statusChart', statusData);
            drawBarChart('userChart', userData);
        }

        window.addEventListener('resize', drawCharts);
        drawCharts();
        </script>
    <?php endif; ?>

    <div class="button-container">
        <button class="back-btn" onclick="window.location.href='print_report.php'">← Back</button>
        <button class="back-btn" onclick="window.print()">🖨️ Print</button>
    </div>
</div>
</body>
</html>
