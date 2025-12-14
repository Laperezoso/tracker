    <?php
    session_start();
    require_once "../class/db_connect.php";


    if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
        header("Location: ../login.php");
        exit;
    }


    $search = isset($_GET['search']) ? "%{$_GET['search']}%" : "%%";
    $status_filter = isset($_GET['status']) && $_GET['status'] !== '' ? $_GET['status'] : null;


    $add_error = $add_success = "";
    if (isset($_POST['add_appliance'])) {
        if (!CSRF::check($_POST['csrf_token'])) {
            die("CSRF validation failed.");
        }
        $name = trim($_POST['appliance_name']);
        $brand = trim($_POST['brand']);
        $model = trim($_POST['model']);
        $purchase_date = $_POST['purchase_date'];
        $expiry_date = $_POST['warranty_expiry'];
        $status = $_POST['status'];
        $user_id = $_POST['user_id'];

        if (!empty($name) && !empty($brand) && !empty($model) && !empty($purchase_date) && !empty($expiry_date) && !empty($user_id)) {
            $today = date('Y-m-d');

            if ($purchase_date > $today) {
                $add_error = "Purchase date cannot be in the future!";
            } elseif ($expiry_date < $today) {
                $add_error = "Warranty has already expired — appliance cannot be added!";
            } else {
                $sql = "INSERT INTO appliances (appliance_name, brand, model, purchase_date, warranty_expiry, status, user_id)
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssssi", $name, $brand, $model, $purchase_date, $expiry_date, $status, $user_id);

                if ($stmt->execute()) {
                    $add_success = "Appliance added successfully!";
                } else {
                    $add_error = "Error adding appliance.";
                }
            }
        } else {
            $add_error = "All fields are required!";
        }
    }


    $sql = "
        SELECT 
            a.appliance_id, 
            a.appliance_name, 
            a.brand, 
            a.model, 
            w.purchase_date, 
            w.warranty_expiry, 
            a.status, 
            u.username 
        FROM appliances a
        LEFT JOIN user_accounts u ON a.user_id = u.user_id
        LEFT JOIN warranty w ON a.appliance_id = w.appliance_id
        WHERE (a.appliance_name LIKE ? OR a.brand LIKE ? OR a.model LIKE ?)
    ";


    if ($status_filter) {
        $sql .= " AND a.status = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $search, $search, $search, $status_filter);
    } else {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $search, $search, $search);
    }

    $stmt->execute();
    $result = $stmt->get_result();


    $users = $conn->query("SELECT user_id, username FROM user_accounts WHERE role = 'user'");
    ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Appliances | Warranty Tracker</title>
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
        <a href="manage_appliance.php" class="nav-link active">Appliances</a>
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
        <h2>Manage Appliances</h2>
    </div>

    <!-- Add Appliance Form -->
    <div class="card mb-4">
        <h3 class="mb-4">Add New Appliance</h3>
        
        <?php if (!empty($add_success)) echo "<div class='text-success mb-4'>$add_success</div>"; ?>
        <?php if (!empty($add_error)) echo "<div class='text-danger mb-4'>$add_error</div>"; ?>

        <form method="POST">
            <?php echo CSRF::input(); ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <label class="form-label">Appliance Name</label>
                    <input type="text" name="appliance_name" class="form-control" placeholder="e.g. Microwave" required>
                </div>
                <div>
                    <label class="form-label">Brand</label>
                    <input type="text" name="brand" class="form-control" placeholder="e.g. Samsung" required>
                </div>
                <div>
                    <label class="form-label">Model</label>
                    <input type="text" name="model" class="form-control" placeholder="e.g. MW-1234" required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <label class="form-label">Purchase Date</label>
                    <input type="date" name="purchase_date" class="form-control" required>
                </div>
                <div>
                    <label class="form-label">Warranty Expiry</label>
                    <input type="date" name="warranty_expiry" class="form-control" required>
                </div>
                <div>
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control" required>
                        <option value="Working">Working</option>
                        <option value="Broken">Broken</option>
                        <option value="Under Repair">Under Repair</option>
                    </select>
                </div>
                 <div>
                    <label class="form-label">Assign User</label>
                    <input type="text" id="userSearch" class="form-control" placeholder="Search user..." list="userList" required>
                    <input type="hidden" name="user_id" id="userId">
                    <datalist id="userList">
                        <?php while ($u = $users->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($u['username']); ?>" data-id="<?php echo $u['user_id']; ?>"></option>
                        <?php endwhile; ?>
                    </datalist>
                </div>
            </div>

            <button type="submit" name="add_appliance" class="btn btn-primary"><i class="fas fa-plus"></i> Add Appliance</button>
        </form>
    </div>

    <!-- Search & List -->
    <div class="card">
        <div class="flex items-center justify-between mb-4 flex-wrap gap-4">
            <h3>Appliance List</h3>
            <form method="GET" class="flex gap-4">
                <input type="text" name="search" class="form-control" placeholder="Search appliance..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                <select name="status" class="form-control" style="width: auto;">
                    <option value="">All Statuses</option>
                    <option value="Working" <?php if ($status_filter === "Working") echo "selected"; ?>>Working</option>
                    <option value="Broken" <?php if ($status_filter === "Broken") echo "selected"; ?>>Broken</option>
                    <option value="Under Repair" <?php if ($status_filter === "Under Repair") echo "selected"; ?>>Under Repair</option>
                    <option value="Expired" <?php if ($status_filter === "Expired") echo "selected"; ?>>Expired</option>
                </select>
                <button type="submit" class="btn btn-primary">Search</button>
            </form>
        </div>

        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Appliance</th>
                        <th>Details</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Assigned To</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $status_badge = "badge-success"; 
                            if ($row['status'] == 'Broken') $status_badge = "badge-danger";
                            if ($row['status'] == 'Under Repair') $status_badge = "badge-warning";
                            
                            // Check expiry for badge logic override if needed, but status field is primary display
                             if ($row['warranty_expiry'] && $row['warranty_expiry'] < date("Y-m-d")) {
                                // optional: show expired badge? Keeping it simple to status column for now
                             }

                            echo "<tr>
                                <td>#{$row['appliance_id']}</td>
                                <td style='font-weight: 500;'>{$row['appliance_name']}</td>
                                <td style='color: var(--text-secondary); font-size: 0.9rem;'>
                                    {$row['brand']} {$row['model']}
                                </td>
                                <td style='font-size: 0.9rem;'>
                                    <div>Purchased: {$row['purchase_date']}</div>
                                    <div class='text-danger'>Expires: {$row['warranty_expiry']}</div>
                                </td>
                                <td><span class='badge {$status_badge}'>{$row['status']}</span></td>
                                <td>" . ($row['username'] ?? 'Unassigned') . "</td>
                                <td>
                                    <a href='edit_appliance.php?id={$row['appliance_id']}' class='btn btn-sm btn-secondary'><i class='fas fa-edit'></i></a>
                                    <a href='delete_appliance.php?id={$row['appliance_id']}' class='btn btn-sm btn-danger' onclick='return confirm(\"Are you sure you want to delete this appliance?\");'><i class='fas fa-trash'></i></a>
                                </td>
                            </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='7' style='text-align:center;'>No appliances found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // Simple script to handle datalist selection mapping to hidden ID
    document.getElementById('userSearch').addEventListener('input', function(e) {
        var val = this.value;
        var list = document.getElementById('userList');
        var options = list.children;
        for (var i = 0; i < options.length; i++) {
            if (options[i].value === val) {
                document.getElementById('userId').value = options[i].getAttribute('data-id');
                break;
            }
        }
    });
</script>

</body>
</html>
