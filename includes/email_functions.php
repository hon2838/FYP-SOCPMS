<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

function configureMailer() {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; // Replace with your SMTP host
        $mail->SMTPAuth   = true;
        $mail->Username   = 'your-email@gmail.com'; // Replace with your email
        $mail->Password   = 'your-app-password'; // Replace with your app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->setFrom('socpms@example.com', 'SOC Paperwork Management System');
        
        return $mail;
    } catch (Exception $e) {
        error_log("Mailer Error: {$mail->ErrorInfo}");
        return null;
    }
}

function sendSubmissionEmail($userEmail, $userName, $paperworkDetails) {
    try {
        $mail = configureMailer();
        if (!$mail) return false;

        $mail->addAddress($userEmail, $userName);
        $mail->Subject = 'Paperwork Submission Confirmation';
        
        // Create HTML message
        $message = "
        <html>
        <body style='font-family: Arial, sans-serif;'>
            <h2>Dear {$userName},</h2>
            <p>Your paperwork has been successfully submitted:</p>
            <ul>
                <li><strong>Reference Number:</strong> {$paperworkDetails['ref_number']}</li>
                <li><strong>Paperwork Name:</strong> {$paperworkDetails['project_name']}</li>
                <li><strong>Submission Date:</strong> " . date('d M Y, h:i A', strtotime($paperworkDetails['submission_time'])) . "</li>
            </ul>
            <p>You will be notified of any updates to your submission.</p>
            <br>
            <p>Regards,<br>SOC Paperwork Management System</p>
        </body>
        </html>";
        
        $mail->isHTML(true);
        $mail->Body = $message;
        $mail->AltBody = strip_tags(str_replace(['<br>', '</p>'], "\n", $message));
        
        return $mail->send();
    } catch (Exception $e) {
        error_log("Mailer Error: {$e->getMessage()}");
        return false;
    }
}

function sendHODNotificationEmail($hodEmail, $userName, $paperworkDetails) {
    try {
        $mail = configureMailer();
        if (!$mail) return false;

        $mail->addAddress($hodEmail);
        $mail->Subject = 'New Paperwork Pending Review';
        
        $message = "
        <html>
        <body style='font-family: Arial, sans-serif;'>
            <h2>Dear Head of Department,</h2>
            <p>A new paperwork has been submitted and requires your review:</p>
            <ul>
                <li><strong>Staff Name:</strong> {$userName}</li>
                <li><strong>Reference Number:</strong> {$paperworkDetails['ref_number']}</li>
                <li><strong>Paperwork Name:</strong> {$paperworkDetails['project_name']}</li>
                <li><strong>Submission Date:</strong> " . date('d M Y, h:i A', strtotime($paperworkDetails['submission_time'])) . "</li>
            </ul>
            <p>Please login to the system to review this submission.</p>
            <br>
            <p>Regards,<br>SOC Paperwork Management System</p>
        </body>
        </html>";
        
        $mail->isHTML(true);
        $mail->Body = $message;
        $mail->AltBody = strip_tags(str_replace(['<br>', '</p>'], "\n", $message));
        
        return $mail->send();
    } catch (Exception $e) {
        error_log("Mailer Error: {$e->getMessage()}");
        return false;
    }
}

function sendDeanNotificationEmail($deanEmail, $paperworkDetails, $hodName) {
    try {
        $mail = configureMailer();
        if (!$mail) return false;

        $mail->addAddress($deanEmail);
        $mail->Subject = 'Paperwork Endorsed by HOD - Pending Review';
        
        $message = "
        <html>
        <body style='font-family: Arial, sans-serif;'>
            <h2>Dear Dean,</h2>
            <p>A paperwork has been endorsed by the HOD and requires your review:</p>
            <ul>
                <li><strong>Reference Number:</strong> {$paperworkDetails['ref_number']}</li>
                <li><strong>Paperwork Name:</strong> {$paperworkDetails['project_name']}</li>
                <li><strong>Endorsed by:</strong> {$hodName}</li>
                <li><strong>Endorsement Date:</strong> " . date('d M Y, h:i A', strtotime($paperworkDetails['hod_approval_date'])) . "</li>
            </ul>
            <p>Please login to the system to review this submission.</p>
            <br>
            <p>Regards,<br>SOC Paperwork Management System</p>
        </body>
        </html>";
        
        $mail->isHTML(true);
        $mail->Body = $message;
        $mail->AltBody = strip_tags(str_replace(['<br>', '</p>'], "\n", $message));
        
        return $mail->send();
    } catch (Exception $e) {
        error_log("Mailer Error: {$e->getMessage()}");
        return false;
    }
}

function sendPasswordResetEmail($userEmail, $resetLink) {
    $mail = configureMailer();
    if (!$mail) return false;

    $mail->addAddress($userEmail);
    $mail->Subject = 'Password Reset Request';
    
    $message = "
    <html>
    <body style='font-family: Arial, sans-serif;'>
        <h2>Password Reset Request</h2>
        <p>You have requested to reset your password. Click the link below to proceed:</p>
        <p><a href='{$resetLink}'>Reset Password</a></p>
        <p>This link will expire in 1 hour.</p>
        <p>If you did not request this, please ignore this email.</p>
        <br>
        <p>Regards,<br>SOC Paperwork Management System</p>
    </body>
    </html>";
    
    $mail->isHTML(true);
    $mail->Body = $message;
    
    return $mail->send();
}