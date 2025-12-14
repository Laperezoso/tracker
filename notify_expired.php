<?php
// notify_expired.php
session_start();
require_once __DIR__ . '/class/db_connect.php'; // Database connection
require_once __DIR__ . '/class/Mailer.php';     // Reusable Mailer class

// Fetch appliances whose warranty has already expired
$query = "
SELECT a.appliance_name, u.username, u.email, w.warranty_expiry
FROM appliances a
JOIN user_accounts u ON a.user_id = u.user_id
JOIN warranty w ON a.appliance_id = w.appliance_id
WHERE w.warranty_expiry < CURDATE()
";

$result = $conn->query($query);

if ($result->num_rows === 0) {
    echo "No appliances with expired warranty found.";
    exit;
}

while ($row = $result->fetch_assoc()) {
    $username = $row['username'];
    $email = $row['email'];
    $appliance_name = $row['appliance_name'];
    $expiry_date = $row['warranty_expiry'];

    // Email subject and body
    $subject = "Warranty Expired: $appliance_name";
    $body = "
        <p>Hi $username,</p>
        <p>This is to inform you that your appliance <b>$appliance_name</b> warranty expired on <b>$expiry_date</b>.</p>
        <p>Please check your appliance and take necessary action.</p>
        <p>Thank you,<br>Appliance Service Warranty Tracker</p>
    ";

    // Send email using Mailer class
    $send_status = Mailer::send($email, $subject, $body);

    if ($send_status === true) {
        echo "✔ Email sent to $email for appliance '$appliance_name'<br>";
    } else {
        echo "❌ Failed to send email to $email: $send_status<br>";
    }
}
