<?php
require_once "../class/db_connect.php";
require_once "../class/PHPMailer.php";
require_once "../class/SMTP.php";
require_once "../class/Exception.php";

if (isset($_POST['id'], $_POST['status'])) {
    $id = $_POST['id'];
    $status = $_POST['status'];

    // Update status
    $stmt = $conn->prepare("UPDATE appliances SET status = ? WHERE appliance_id = ?");
    $stmt->bind_param("si", $status, $id);
    $stmt->execute();

    // Fetch user + appliance info
    $query = $conn->prepare("
        SELECT a.appliance_name, u.username, u.email 
        FROM appliances a
        INNER JOIN user_accounts u ON a.user_id = u.user_id
        WHERE a.appliance_id = ?
    ");
    $query->bind_param("i", $id);
    $query->execute();
    $result = $query->get_result();
    $data = $result->fetch_assoc();

    if ($data) {
        $userEmail = $data['email'];
        $username = $data['username'];
        $applianceName = $data['appliance_name'];

        $mail = new PHPMailer\PHPMailer\PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = "smtp.gmail.com";
            $mail->SMTPAuth = true;
            $mail->Username = "YOUR_EMAIL@gmail.com"; 
            $mail->Password = "YOUR_APP_PASSWORD"; 
            $mail->SMTPSecure = "ssl";
            $mail->Port = 465;

            $mail->setFrom("YOUR_EMAIL@gmail.com", "Warranty Tracker");
            $mail->addAddress($userEmail);

            $mail->isHTML(true);
            $mail->Subject = "Your Appliance Status Has Been Updated";
            $mail->Body = "
                Hello $username,<br><br>
                The status of your appliance <b>$applianceName</b> has been updated to:<br><br>
                <b style='color:blue;'>$status</b><br><br>
                Thank you,<br>
                Appliance Service Warranty Tracker
            ";

            $mail->send();

        } catch (Exception $e) {
            error_log("Email Error: " . $mail->ErrorInfo);
        }
    }

    header("Location: manage_appliance.php?success=1");
    exit;
}

header("Location: manage_appliance.php");
exit;
?>
