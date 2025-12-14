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
    <title>VIEW APPLIANCES</title>
    <style>
    body {
        font-family: Arial, sans-serif;
        margin: 30px;
        background-color: #f5f7fa;
        color: #111;
    }

    h2 {
        color: rgb(0, 123, 255);
        margin-bottom: 15px;
        text-align: center;
    }

    a {
        color: rgb(0, 123, 255);
        text-decoration: none;
        font-weight: bold;
    }

    a:hover {
        text-decoration: underline;
    }

    form {
        max-width: 500px;
        margin: 0 auto 20px auto;
        display: flex;
        gap: 10px;
        justify-content: center;
    }

    form input[type="text"] {
        flex: 1;
        padding: 8px 10px;
        border: 1px solid rgb(0, 123, 255);
        border-radius: 5px;
    }

    form button {
        padding: 8px 15px;
        background-color: rgb(0, 123, 255);
        color: #fff;
        border: none;
        border-radius: 5px;
        cursor: pointer;
    }

    form button:hover {
        background-color: rgb(0, 90, 190);
    }

    table {
        border-collapse: collapse;
        width: 100%;
        background-color: #fff;
        border-radius: 6px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    th, td {
        padding: 12px 10px;
        text-align: center;
        border-bottom: 1px solid #ddd;
    }

    th {
        background-color: rgb(0, 123, 255);
        color: #fff;
        font-weight: bold;
    }

    tr:nth-child(even) {
        background-color: #e9f2ff; 
    }

    tr:hover {
        background-color: #cce4ff;
    }

    td a {
        margin: 0 5px;
        color: rgb(0, 123, 255);
    }

    td a:hover {
        text-decoration: underline;
    }

    span.status-working {
        color: green;
        font-weight: bold;
    }

    span.status-broken {
        color: red;
        font-weight: bold;
    }

   
    @media screen and (max-width: 768px) {
        table, thead, tbody, th, td, tr {
            display: block;
        }
        th {
            display: none;
        }
        tr {
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 6px;
            padding: 10px;
        }
        td {
            text-align: right;
            padding-left: 50%;
            position: relative;
        }
        td::before {
            content: attr(data-label);
            position: absolute;
            left: 10px;
            width: 45%;
            padding-left: 10px;
            font-weight: bold;
            text-align: left;
        }
    }
</style>


</head>
<body>
    <h2>My Appliances</h2>
    <a href="dashboard.php">← Back to Dashboard</a><br><br>


    <form method="GET" action="">
        <input type="text" name="search" placeholder="Search by name, brand, or model" 
               value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit">Search</button>
    </form>
    <br>

 
    <table>
        <tr>
            <th>ID</th>
            <th>Appliance Name</th>
            <th>Brand</th>
            <th>Model</th>
            <th>Purchase Date</th>
            <th>Warranty Expiry</th>
            <th>Status</th> 
            <th>Actions</th>
        </tr>

        <?php if ($result->num_rows > 0) { ?>
            <?php while ($row = $result->fetch_assoc()) { ?>
                <tr>
                    <td><?php echo $row['appliance_id']; ?></td>
                    <td><?php echo htmlspecialchars($row['appliance_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['brand']); ?></td>
                    <td><?php echo htmlspecialchars($row['model']); ?></td>
                    <td><?php echo htmlspecialchars($row['purchase_date'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($row['warranty_expiry'] ?? 'N/A'); ?></td>
                    <td>
                        <?php 
                          
                            $status = strtolower($row['status']);
                            if ($status === "broken") {
                                echo "<span style='color:grey; font-weight:bold;'>Broken</span>";
                            } elseif ($status === "working") {
                                echo "<span style='color:green; font-weight:bold;'>Working</span>";
                            }elseif ($status === "under repair") {
                                echo "<span style='color:blue; font-weight:bold;'>Under Repair</span>";
                            }elseif ($status === "expired") {
                                echo "<span style='color:red; font-weight:bold;'>Expired</span>";
                            }else {
                                echo htmlspecialchars($row['status']);
                            }
                        ?>
                    </td>
                    <td>
                        <a href="edit_appliance.php?id=<?php echo $row['appliance_id']; ?>">Edit</a> |
                        <a href="delete_appliance.php?id=<?php echo $row['appliance_id']; ?>" 
                           onclick="return confirm('Are you sure you want to delete this appliance?');">
                           Delete
                        </a>
                    </td>
                </tr>
            <?php } ?>
        <?php } else { ?>
            <tr>
                <td colspan="8" style="text-align:center;">No appliances found.</td>
            </tr>
        <?php } ?>
    </table>
</body>
</html>
