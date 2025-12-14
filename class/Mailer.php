<?php
// class/Mailer.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/PHPMailer.php';
require_once __DIR__ . '/SMTP.php';
require_once __DIR__ . '/Exception.php';

class Mailer {
    public static function send($to, $subject, $body) {
        $mail = new PHPMailer(true);
        try {
            // SMTP settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->AuthType = 'LOGIN';
            $mail->Username = 'razelherodias014@gmail.com';   // Replace with your Gmail
            $mail->Password = 'uhhqwnfjtfuiexik';      // Replace with your Gmail App Password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('razelherodias014@gmail.com', 'Warranty Tracker');
            $mail->addAddress($to);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = strip_tags($body);

            $mail->send();
            return true;
        } catch (Exception $e) {
            return $mail->ErrorInfo;
        }
    }

    public static function getTemplate($username, $title, $content) {
        return '
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
                                    ' . $title . '
                                </h2>
                            </td>
                        </tr>

                        <!-- Body -->
                        <tr>
                            <td style="padding:20px; color:#111; font-size:16px; line-height:1.6;">
                                <p>Hi <b>' . htmlspecialchars($username) . '</b>,</p>
                                
                                ' . $content . '

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
        </table>';
    }
}
