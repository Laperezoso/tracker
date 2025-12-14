<?php
// notify_status_update.php
session_start();
require_once __DIR__ . '/class/db_connect.php';
require_once __DIR__ . '/class/Mailer.php';

// Check required parameter
if (!isset($_GET['appliance_id'])) {
    echo "No appliance ID provided.";
    exit;
}

$appliance_id = intval($_GET['appliance_id']);

// Secure prepared query
$stmt = $conn->prepare("
    SELECT a.appliance_name, a.status, u.username, u.email
    FROM appliances a
    JOIN user_accounts u ON a.user_id = u.user_id
    WHERE a.appliance_id = ?
");
$stmt->bind_param("i", $appliance_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Appliance not found.";
    exit;
}

$row = $result->fetch_assoc();
$username = $row['username'];
$email = $row['email'];
$appliance_name = $row['appliance_name'];
$status = $row['status'];

if (empty($email)) {
    echo "User email not found. Cannot send notification.";
    exit;
}

// Friendly status message
switch ($status) {
    case "Working":
        $status_message = "Your appliance is now marked as <b>Working</b>. Everything appears normal.";
        break;
    case "Broken":
        $status_message = "Your appliance has been marked as <b>Broken</b>. It may require repair.";
        break;
    case "Under Repair":
        $status_message = "Your appliance is currently <b>Under Repair</b>. We will notify you once fixed.";
        break;
    default:
        $status_message = "The status of your appliance has been updated.";
}

// Subject
$subject = "Status Update: $appliance_name";

// 🎨 NEW: Styled Email Template
$body = '
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f0f6ff; padding:20px;">
    <tr>
        <td align="center">
            <table width="600" cellpadding="0" cellspacing="0" 
                   style="background:white; border-radius:10px; padding:20px; 
                          box-shadow:0 4px 10px rgba(0,0,0,0.15); font-family:Arial, sans-serif;">

                <!-- Header -->
                <tr>
                    <td align="center" 
                        style="background:#0d47a1; padding:15px; border-radius:8px 8px 0 0;">
                        <h2 style="color:white; margin:0; font-size:24px;">
                            Appliance Status Update
                        </h2>
                    </td>
                </tr>

                <!-- Body -->
                <tr>
                    <td style="padding:20px; color:#111; font-size:16px; line-height:1.6;">
                        <p>Hi <b>'.$username.'</b>,</p>

                        <p>
                            The status of your appliance <b style="color:#0d47a1;">'.$appliance_name.'</b> 
                            has been updated.
                        </p>

                        <div style="padding:15px; 
                                    background:#e3f2fd; 
                                    border-left:5px solid #0d47a1; 
                                    border-radius:6px; 
                                    margin:15px 0;
                                    font-size:16px;">
                            '.$status_message.'
                        </div>

                        <p>If you did not request this change, please contact support immediately.</p>

                        <p style="margin-top:30px;">
                            Thank you,<br>
                            <b>Appliance Service Warranty Tracker</b>
                        </p>
                    </td>
                </tr>

                <!-- Footer -->
                <tr>
                    <td align="center" style="padding:15px; color:#555; font-size:14px;">
                        This is an automated message — please do not reply.
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>
';

// Send email
$send_status = Mailer::send($email, $subject, $body);

if ($send_status === true) {
    echo '
    <div style="
        font-family: Arial, sans-serif;
        background:#f0f6ff;
        height:100vh;
        display:flex;
        justify-content:center;
        align-items:center;
        padding:20px;
    ">
        <div style="
            background:white;
            width:450px;
            padding:25px;
            border-radius:10px;
            box-shadow:0 4px 15px rgba(0,0,0,0.15);
            text-align:center;
            border-top:6px solid #0d47a1;
        ">
            <h2 style="color:#0d47a1; margin:0 0 10px 0;">
                ✔ Status Update Email Sent Successfully!
            </h2>

            <p style="color:#111; font-size:16px; margin:10px 0;">
                Sent to: <b>'.$email.'</b>
            </p>

            <a href="admin/manage_appliance.php" style="
                display:inline-block;
                margin-top:20px;
                padding:10px 20px;
                background:#0d47a1;
                color:white;
                text-decoration:none;
                border-radius:6px;
            ">Back to Dashboard</a>
        </div>
    </div>
    ';
} else {
    echo '
    <div style="
        font-family: Arial, sans-serif;
        background:#fff3f3;
        height:100vh;
        display:flex;
        justify-content:center;
        align-items:center;
        padding:20px;
    ">
        <div style="
            background:white;
            width:450px;
            padding:25px;
            border-radius:10px;
            box-shadow:0 4px 15px rgba(0,0,0,0.15);
            text-align:center;
            border-top:6px solid #d32f2f;
        ">
            <h2 style="color:#d32f2f; margin:0 0 10px 0;">
                ❌ Failed to Send Email
            </h2>

            <p style="color:#111; font-size:16px; margin:10px 0;">
                Error: <b>'.$send_status.'</b>
            </p>

            <a href="admin/manage_appliance.php" style="
                display:inline-block;
                margin-top:20px;
                padding:10px 20px;
                background:#d32f2f;
                color:white;
                text-decoration:none;
                border-radius:6px;
            ">Back to Dashboard</a>
        </div>
    </div>
    ';
}

?>
