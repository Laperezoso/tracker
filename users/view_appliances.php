<?php
session_start();
require_once "../class/db_connect.php";
require_once "../class/appliances.php";


if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "user") {
    header("Location: ../login.php");
    exit;
}

$applianceObj = new Appliance($conn);
$user_id = $_SESSION["user_id"];


$search = isset($_GET["search"]) ? trim($_GET["search"]) : "";
$result = $applianceObj->getAppliances($user_id, $search);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Appliances | Warranty Tracker</title>
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

<div class="container">
    <div class="flex items-center justify-between mb-4">
        <h2>My Appliances</h2>
    </div>

    <!-- Search -->
    <div class="card mb-4" style="padding: 1.5rem;">
        <form method="GET" action="" class="flex gap-4">
            <input type="text" name="search" class="form-control" placeholder="Search by name, brand, or model..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="btn btn-primary">Search</button>
        </form>
    </div>

    <!-- Table -->
    <div class="card table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Appliance</th>
                    <th>Details</th>
                    <th>Purchase Date</th>
                    <th>Warranty Expiry</th>
                    <th>Status</th> 
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0) { ?>
                    <?php while ($row = $result->fetch_assoc()) { 
                        $status_badge = "badge-success";
                        if (strtolower($row['status']) == 'broken') $status_badge = "badge-danger";
                        if (strtolower($row['status']) == 'under repair') $status_badge = "badge-warning";
                        if (strtolower($row['status']) == 'expired') $status_badge = "badge-danger";
                    ?>
                        <tr>
                            <td>#<?php echo $row['appliance_id']; ?></td>
                            <td style="font-weight: 500;"><?php echo htmlspecialchars($row['appliance_name']); ?></td>
                            <td style="color: var(--text-secondary); font-size: 0.9rem;">
                                <?php echo htmlspecialchars($row['brand']); ?> <?php echo htmlspecialchars($row['model']); ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['purchase_date'] ?? 'N/A'); ?></td>
                            <td class="text-danger"><?php echo htmlspecialchars($row['warranty_expiry'] ?? 'N/A'); ?></td>
                            <td><span class="badge <?php echo $status_badge; ?>"><?php echo htmlspecialchars($row['status']); ?></span></td>
                            <td>
                                <div class="flex gap-2">
                                    <a href="edit_appliance.php?id=<?php echo $row['appliance_id']; ?>" class="btn btn-sm btn-secondary" title="Edit"><i class="fas fa-edit"></i></a>
                                    <a href="delete_appliance.php?id=<?php echo $row['appliance_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this appliance?');" title="Delete"><i class="fas fa-trash"></i></a>
                                </div>
                            </td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="7" style="text-align:center;">No appliances found.</td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
