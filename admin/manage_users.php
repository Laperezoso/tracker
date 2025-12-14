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
<title>Manage Users</title>
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
    max-width: 1100px;
    margin: auto;
}

h1 {
    text-transform: uppercase;
    color: var(--primary-blue);
    letter-spacing: 1px;
}


form {
    background-color: var(--white);
    padding: 20px;
    border-radius: 6px;
    box-shadow: 0 0 6px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

form h3 {
    margin-top: 0;
    color: var(--dark);
}

input[type="text"], input[type="password"], input[type="email"] {
    padding: 8px;
    width: calc(50% - 10px);
    margin: 6px 5px;
    border: 1px solid #ccc;
    border-radius: 4px;
}

button {
    padding: 8px 16px;
    background-color: var(--primary-blue);
    color: var(--white);
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 500;
}

button:hover {
    background-color: #0056b3;
}


.search-bar {
    display: flex;
    justify-content: flex-start;
    margin-bottom: 10px;
}

.search-bar input {
    padding: 6px;
    border: 1px solid #ccc;
    border-radius: 4px;
    width: 200px;
}

.search-bar button {
    margin-left: 6px;
}


table {
    width: 100%;
    border-collapse: collapse;
    background-color: var(--white);
    border-radius: 6px;
    overflow: hidden;
    box-shadow: 0 0 6px rgba(0,0,0,0.1);
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


.btn {
    padding: 6px 10px;
    border-radius: 4px;
    font-size: 14px;
    text-decoration: none;
    color: var(--white);
    margin: 0 4px;
}

.btn-toggle {
    background-color: #17a2b8;
}

.btn-toggle:hover {
    background-color: #138496;
}

.btn-delete {
    background-color: #dc3545;
}

.btn-delete:hover {
    background-color: #b02a37;
}


.msg-success {
    color: green;
    font-weight: 500;
}

.msg-error {
    color: red;
    font-weight: 500;
}
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
    <h1>Manage Users</h1>

   
        <form method="POST">
            <h3>Add New User</h3>
            <input type="text" name="new_username" placeholder="Username" required>
            <input type="password" name="new_password" placeholder="Password" required>
            <input type="email" name="new_email" placeholder="Email" required>
            <button type="submit" name="add_user">Add User</button>
        </form>


    <?php if (!empty($add_success)) echo "<p class='msg-success'>$add_success</p>"; ?>
    <?php if (!empty($add_error)) echo "<p class='msg-error'>$add_error</p>"; ?>

    
    <form method="GET" class="search-bar">
        <input type="text" name="search" placeholder="Search user..." 
               value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
        <button type="submit">Search</button>
    </form>

    
    <table>
        <tr>
            <th>User ID</th>
            <th>Username</th>
            <th>Email</th>
            <th>Role</th>
            <th>Status</th>
            <th>Created At</th>
            <th>Actions</th>
        </tr>
        <?php
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "<tr>
                    <td>{$row['user_id']}</td>
                    <td>{$row['username']}</td>
                    <td>{$row['email']}</td>
                    <td>{$row['role']}</td>
                    <td>{$row['status']}</td>
                    <td>{$row['created_at']}</td>
                    <td>";
                if ($row['user_id'] == $_SESSION['user_id']) {
                    echo "<strong>SELF</strong>";
                } else {
                    echo "<a class='btn btn-toggle' href='toggle_status.php?id={$row['user_id']}'>Toggle</a>
                          <a class='btn btn-delete' href='delete_user.php?id={$row['user_id']}' onclick=\"return confirm('Delete this user?');\">Delete</a>";
                }
                echo "</td></tr>";
            }
        } else {
            echo "<tr><td colspan='6'>No users found.</td></tr>";
        }
        ?>
    </table>
</div>

</body>
</html>
