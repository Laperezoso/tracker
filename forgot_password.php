<?php
session_start();
require_once "class/db_connect.php";
require_once "class/Mailer.php";

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!CSRF::check($_POST['csrf_token'])) {
        die("CSRF validation failed.");
    }
    $email = trim($_POST["email"]);

    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Check if email exists
        $stmt = $conn->prepare("SELECT user_id, username FROM user_accounts WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Generate Token
            $token = bin2hex(random_bytes(16));
            $token_hash = hash("sha256", $token);
            $expiry = date("Y-m-d H:i:s", time() + 60 * 30); // 30 minutes

            // Save to DB
            $update = $conn->prepare("UPDATE user_accounts SET reset_token_hash = ?, reset_token_expires_at = ? WHERE user_id = ?");
            $update->bind_param("ssi", $token_hash, $expiry, $user['user_id']);
            
            if ($update->execute()) {
                // Send Email
                $resetLink = "http://localhost/tracker/reset_password.php?token=" . $token;
                
                $emailSubject = "Password Reset Request";
                $emailBody = Mailer::getTemplate(
                    $user['username'],
                    "Password Reset",
                    "<p>We received a request to reset your password.</p>
                     <p>Click the link below to reset it (valid for 30 minutes):</p>
                     <p style='text-align:center; margin: 30px 0;'>
                        <a href='$resetLink' style='background:#0d47a1; color:white; padding:12px 24px; text-decoration:none; border-radius:5px; font-weight:bold;'>Reset Password</a>
                     </p>
                     <p>If you did not request this, please ignore this email.</p>"
                );

                if (Mailer::send($email, $emailSubject, $emailBody)) {
                    $message = "✅ Reset link sent to your email.";
                    $message_type = "success";
                } else {
                    $message = "❌ Failed to send email. Server error.";
                    $message_type = "danger";
                }
            } else {
                $message = "❌ Database error.";
                $message_type = "danger";
            }
        } else {
            // "Silently" fail or ensure user privacy? 
            // For this project, explicit feedback is probably preferred by the user for clarity.
            $message = "❌ Email not found in our records.";
            $message_type = "danger";
        }
    } else {
        $message = "Please enter a valid email address.";
        $message_type = "danger";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | Warranty Tracker</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="login-page">
    <div class="login-card">
        <div class="login-header">
            <div class="login-logo" style="background: transparent;">
                <img src="image/Clearbglogo.png" alt="Logo" style="width: 100%; height: 100%; object-fit: contain;">
            </div>
            <h2>Forgot Password</h2>
            <p style="color: var(--text-secondary); margin-top: 0.5rem;">Enter your email to receive a reset link</p>
        </div>

        <?php if (!empty($message)): ?>
            <div style="background-color: var(--<?php echo $message_type; ?>); color: white; padding: 0.75rem; border-radius: var(--radius); margin-bottom: 1.5rem; font-size: 0.9rem; text-align: center;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <?php echo CSRF::input(); ?>
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
            </div>

            <button type="submit" class="btn btn-primary w-full">Send Reset Link</button>
            
            <div style="text-align: center; margin-top: 1.5rem;">
                <a href="login.php" style="color: var(--text-secondary); font-size: 0.9rem;"><i class="fas fa-arrow-left"></i> Back to Login</a>
            </div>
        </form>
    </div>
</body>
</html>
