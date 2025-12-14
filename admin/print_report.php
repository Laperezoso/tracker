<?php
session_start();
require_once "../class/db_connect.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Generate Report</title>
<style>
body {
    font-family: "Segoe UI", sans-serif;
    background-color: #f4f4f4;
    margin: 0;
    padding: 30px;
    color: #333;
}
.container {
    max-width: 600px;
    background: white;
    margin: auto;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    text-align: center;
}
h1 {
    color: #007bff;
    text-align: center;
    margin-top: 10px;
}
.logo {
    width: 120px;
    height: auto;
    display: block;
    margin: 0 auto 10px auto;
}
form {
    display: flex;
    flex-direction: column;
    gap: 16px;
    text-align: left;
}
fieldset {
    border: 1px solid #ccc;
    border-radius: 6px;
    padding: 10px;
}
legend {
    font-weight: bold;
    color: #007bff;
}
button {
    background-color: #007bff;
    color: white;
    border: none;
    padding: 10px;
    font-weight: bold;
    border-radius: 6px;
    cursor: pointer;
}
button:hover {
    background-color: #0056b3;
}
.back-btn {
    background-color: #6c757d;
    margin-top: 10px;
}
.back-btn:hover {
    background-color: #565e64;
}
</style>
</head>
<body>

<div class="container">
    <img src="../image/Clearbglogo.png" alt="Logo" class="logo">
    <h1>Generate Printable Report</h1>
    
    <form method="GET" action="print_preview.php" target="_blank">
        <fieldset>
            <legend>Select Report Sections</legend>
            <label><input type="checkbox" name="sections[]" value="users" checked> Users</label><br>
            <label><input type="checkbox" name="sections[]" value="appliances" checked> Appliances</label><br>
            <label><input type="checkbox" name="sections[]" value="charts"> Charts Summary</label>
        </fieldset>

        <fieldset>
            <legend>Filter by Date</legend>
            <label>Month:
                <select name="month">
                    <option value="">All</option>
                    <?php
                    for ($m = 1; $m <= 12; $m++) {
                        $monthName = date('F', mktime(0, 0, 0, $m, 1));
                        echo "<option value='$m'>$monthName</option>";
                    }
                    ?>
                </select>
            </label><br>
            <label>Year:
                <select name="year">
                    <option value="">All</option>
                    <?php
                    $currentYear = date("Y");
                    for ($y = $currentYear; $y >= 2020; $y--) {
                        echo "<option value='$y'>$y</option>";
                    }
                    ?>
                </select>
            </label>
        </fieldset>

        <button type="submit">Generate Report</button>
    </form>

    <form action="dashboard.php" method="get">
        <button type="submit" class="back-btn">← Back</button>
    </form>
</div
