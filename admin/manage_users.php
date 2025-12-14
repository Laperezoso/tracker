<?php
session_start();
require_once "../class/db_connect.php";


if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../login.php");
    exit;
}


$search = isset($_GET['search']) ? "%{$_GET['search']}%" : "%%";


$add_error = $add_success = "";
if (isset($_POST['add_user'])) {
    $new_username = trim($_POST['new_username']);
    $new_password = trim($_POST['new_password']);
    $new_email = trim($_POST['new_email']);
    $new_role = "user";

    if (!empty($new_username) && !empty($new_password) && !empty($new_email)) {

        // Check duplicate username
        $check_user_sql = "SELECT * FROM user_accounts WHERE LOWER(username) = LOWER(?)";
        $check_user_stmt = $conn->prepare($check_user_sql);
        $check_user_stmt->bind_param("s", $new_username);
        $check_user_stmt->execute();
        $result_user = $check_user_stmt->get_result();

        // Check duplicate email
        $check_email_sql = "SELECT * FROM user_accounts WHERE LOWER(email) = LOWER(?)";
        $check_email_stmt = $conn->prepare($check_email_sql);
        $check_email_stmt->bind_param("s", $new_email);
        $check_email_stmt->execute();
        $result_email = $check_email_stmt->get_result();

        if ($result_user->num_rows > 0) {
            $add_error = "Username already exists!";
        } elseif ($result_email->num_rows > 0) {
            $add_error = "Email already exists!";
        } else {
            $insert_sql = "INSERT INTO user_accounts (username, email, password, role, active, created_at)
                           VALUES (?, ?, ?, ?, 1, NOW())";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("ssss", $new_username, $new_email, $new_password, $new_role);

            if ($insert_stmt->execute()) {
                $add_success = "User added successfully!";
            } else {
                $add_error = "Error adding user.";
            }
        }

    } else {
        $add_error = "All fields are required!";
    }
}



$sql = "SELECT user_id, username, email, role, created_at,
        CASE WHEN active = 1 THEN 'Active' ELSE 'Inactive' END AS status
        FROM user_accounts
        WHERE username LIKE ? OR role LIKE ? OR email LIKE ?";


$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $search, $search, $search);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Users | Warranty Tracker</title>
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
        <a href="manage_users.php" class="nav-link active">Users</a>
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
        <h2>Manage Users</h2>
    </div>

    <!-- Add User Form -->
    <div class="card mb-4">
        <h3 class="mb-4">Register New User</h3>
        
        <?php if (!empty($add_success)) echo "<div class='text-success mb-4'>$add_success</div>"; ?>
        <?php if (!empty($add_error)) echo "<div class='text-danger mb-4'>$add_error</div>"; ?>

        <form method="POST" class="flex gap-4 items-center flex-wrap">
            <div style="flex: 1; min-width: 200px;">
                <input type="text" name="new_username" class="form-control" placeholder="Username" required>
            </div>
            <div style="flex: 1; min-width: 200px;">
                <input type="password" name="new_password" class="form-control" placeholder="Password" required>
            </div>
            <div style="flex: 1; min-width: 200px;">
                <input type="email" name="new_email" class="form-control" placeholder="Email Address" required>
            </div>
            <div>
                <button type="submit" name="add_user" class="btn btn-primary"><i class="fas fa-user-plus"></i> Add User</button>
            </div>
        </form>
    </div>

    <!-- Search & List -->
    <div class="card">
        <div class="flex items-center justify-between mb-4 flex-wrap gap-4">
            <h3>Registered Users</h3>
            <form method="GET" class="flex gap-4">
                <input type="text" name="search" class="form-control" placeholder="Search user..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                <button type="submit" class="btn btn-primary">Search</button>
            </form>
        </div>

        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>User Details</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Joined Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $status_badge = ($row['status'] == 'Active') ? 'badge-success' : 'badge-danger';
                            
                            echo "<tr>
                                <td>#{$row['user_id']}</td>
                                <td>
                                    <div style='font-weight: 500;'>{$row['username']}</div>
                                    <div style='color: var(--text-secondary); font-size: 0.85rem;'>{$row['email']}</div>
                                </td>
                                <td><span class='badge' style='background: #e0f2fe; color: #0369a1;'>".strtoupper($row['role'])."</span></td>
                                <td><span class='badge {$status_badge}'>{$row['status']}</span></td>
                                <td>{$row['created_at']}</td>
                                <td>";
                            
                            if ($row['user_id'] == $_SESSION['user_id']) {
                                echo "<span class='badge' style='background: #f1f5f9; color: var(--text-secondary);'>YOU</span>";
                            } else {
                                echo "<div class='flex gap-2'>
                                        <a href='toggle_status.php?id={$row['user_id']}' class='btn btn-sm btn-secondary' title='Toggle Status'><i class='fas fa-sync-alt'></i></a>
                                        <a href='delete_user.php?id={$row['user_id']}' class='btn btn-sm btn-danger' onclick=\"return confirm('Delete this user?');\" title='Delete'><i class='fas fa-trash'></i></a>
                                      </div>";
                            }
                            echo "</td></tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6' style='text-align:center;'>No users found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>
