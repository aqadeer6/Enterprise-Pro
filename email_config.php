<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';

// Simple function to send emails using PHPMailer
function sendEmail($to, $subject, $body) {
    $mail = new PHPMailer(true);
    
    try {
        // Setting up the SMTP stuff for Gmail
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Gmail SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = 'mghufrann1@gmail.com'; // My Gmail address
        $mail->Password = 'uayqztfwydtsnrkm'; // Your 16-character App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Use TLS
        $mail->Port = 587; // Port for TLS

        // Who’s sending and receiving the email
        $mail->setFrom('mghufrann1@gmail.com', 'Bradford Council Asset Management'); // Updated to match Username
        $mail->addAddress($to);

        // The actual email content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->AltBody = strip_tags($body); // Plain text fallback for old-school email clients

        $mail->send();
        return true; // All good, email sent!
    } catch (Exception $e) {
        // If something goes wrong, log it and return false
        error_log("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
?>