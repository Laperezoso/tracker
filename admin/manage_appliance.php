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
        <title>Manage Appliances</title>
        <style>
        
            body {
                font-family: "Segoe UI", Arial, sans-serif;
                background-color: #f5f7fa;
                margin: 0;
            }

            .navbar {
                background-color: #000000ff;
                color: white;
                padding: 15px 30px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                position: sticky;
                top: 0;
                z-index: 100;
            }

            .navbar a {
                color: white;
                text-decoration: none;
                margin-right: 20px;
                font-weight: 500;
            }

            .navbar a.active {
                text-decoration: underline;
            }

            .navbar a:hover {
                text-decoration: underline;
            }

            .nav-right {
                display: flex;
                align-items: center;
                gap: 15px;
            }

            .logout {
                background: black;
                color: #0078D7;
                padding: 6px 12px;
                border-radius: 5px;
                font-weight: 600;
            }

        
            .content {
                padding: 30px 50px;
            }

            h1.page-title {
                color: rgb(0, 123, 255);
                margin-bottom: 20px;
            }

        
            .form-container {
                background: white;
                padding: 20px 30px;
                border-radius: 8px;
                box-shadow: 0 2px 6px rgba(0,0,0,0.1);
                margin-bottom: 30px;
            }

            .form-title {
                margin-top: 0;
                color: #0078D7;
            }

            .form-row {
                display: flex;
                gap: 15px;
                margin-bottom: 10px;
            }

            input, select {
                padding: 8px;
                border-radius: 5px;
                border: 1px solid #ccc;
                flex: 1;
            }

            .btn {
                padding: 8px 15px;
                border: none;
                border-radius: 6px;
                cursor: pointer;
            }

            .btn-add {
                background-color: #0078D7;
                color: white;
                font-weight: 600;
            }

            .btn-add:hover {
                background-color: #005fa3;
            }

            .msg-success {
                color: green;
                font-weight: 600;
            }

            .msg-error {
                color: red;
                font-weight: 600;
            }

        
            .search-section {
                margin-bottom: 10px;
            }

            .search-bar {
                display: flex;
                align-items: center;
                gap: 10px;
                justify-content: flex-start;
            }

            .search-bar input {
                width: 200px;
            }

            .btn-search {
                background-color: #0078D7;
                color: white;
            }

            
            .table-container {
                background: white;
                border-radius: 8px;
                padding: 20px;
                box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            }

            table {
                width: 100%;
                border-collapse: collapse;
            }

            th {
                background-color: #0078D7;
                color: white;
                text-align: left;
                padding: 10px;
            }

            td {
                padding: 10px;
                border-bottom: 1px solid #ddd;
            }

            tr:hover {
                background-color: #f0f6ff;
            }

            .btn-edit {
                background-color: #ffb400;
                color: white;
                margin-right: 5px;
            }

            .btn-delete {
                background-color: #d9534f;
                color: white;
            }

            .btn-edit:hover { background-color: #e0a100; }
            .btn-delete:hover { background-color: #c9302c; }
        </style>
    </head>

    <body>
    <div class="navbar">
        <div class="nav-left">
            <a href="dashboard.php">Dashboard</a>
            <a href="manage_users.php">Manage Users</a>
            <a href="manage_appliance.php" class="active">Manage Appliances</a>
            <a href="print_report.php">Print Report</a>
        </div>
        <div class="nav-right">
            <span>Welcome, <strong><?php echo $_SESSION['username']; ?></strong></span>
            <a href="../logout.php" class="logout">Logout</a>
        </div>
    </div>

    <div class="content">
        <h1 class="page-title">Manage Appliances</h1>

        
        <div class="form-container">
            <form method="POST">
                <h3 class="form-title">Add New Appliance</h3>

                <div class="form-row">
                    <input type="text" name="appliance_name" placeholder="Appliance Name" required>
                    <input type="text" name="brand" placeholder="Brand" required>
                </div>

                <div class="form-row">
                    <input type="text" name="model" placeholder="Model" required>
                    <p><strong>Purchased Date:</strong></p>
                    <input type="date" name="purchase_date" required>
                    <p><strong>Due Date:</strong></p>
                    <input type="date" name="warranty_expiry" required>
                </div>

                <div class="form-row">
                    <p><strong>Status:</strong></p>
                    <select name="status" required>
                        <option value="Working">Working</option>
                        <option value="Broken">Broken</option>
                        <option value="Under Repair">Under Repair</option>
                    </select>

                    <select name="user_id" required>
                        <option value="">-- Assign to User --</option>
                        <?php while ($u = $users->fetch_assoc()): ?>
                            <option value="<?php echo $u['user_id']; ?>">
                                <?php echo htmlspecialchars($u['username']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <button type="submit" name="add_appliance" class="btn btn-add">Add Appliance</button>
            </form>

            <?php if (!empty($add_success)) echo "<p class='msg-success'>$add_success</p>"; ?>
            <?php if (!empty($add_error)) echo "<p class='msg-error'>$add_error</p>"; ?>
        </div>

        
        <div class="search-section">
            <form method="GET" class="search-bar">
                <input type="text" name="search" placeholder="Search appliance..."
                    value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                <select name="status">
                    <option value="">Filter by Status</option>
                    <option value="Working" <?php if ($status_filter === "Working") echo "selected"; ?>>Working</option>
                    <option value="Broken" <?php if ($status_filter === "Broken") echo "selected"; ?>>Broken</option>
                    <option value="Under Repair" <?php if ($status_filter === "Under Repair") echo "selected"; ?>>Under Repair</option>
                    <option value="Expired" <?php if ($status_filter === "Expired") echo "selected"; ?>>Expired</option>
                </select>
                <button type="submit" class="btn btn-search">Search</button>
            </form>
        </div>

        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Appliance Name</th>
                        <th>Brand</th>
                        <th>Model</th>
                        <th>Purchase Date</th>
                        <th>Warranty Expiry</th>
                        <th>Status</th>
                        <th>Assigned User</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>
                                <td>{$row['appliance_id']}</td>
                                <td>{$row['appliance_name']}</td>
                                <td>{$row['brand']}</td>
                                <td>{$row['model']}</td>
                                <td>{$row['purchase_date']}</td>
                                <td>{$row['warranty_expiry']}</td>
                                <td>{$row['status']}</td>
                                <td>" . ($row['username'] ?? 'Unassigned') . "</td>
                                <td>
                                    <a href='edit_appliance.php?id={$row['appliance_id']}' class='btn btn-edit'>Edit</a>
                                    <a href='delete_appliance.php?id={$row['appliance_id']}' class='btn btn-delete' onclick='return confirm(\"Are you sure you want to delete this appliance?\");'>Delete</a>
                                </td>
                            </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='9'>No appliances found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    </body>
    </html>
