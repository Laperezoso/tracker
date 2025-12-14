<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "appliance_warranty_system";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

require_once __DIR__ . '/CSRF.php';

