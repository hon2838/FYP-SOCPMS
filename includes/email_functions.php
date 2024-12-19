<?php
// Start session first
session_start();

// Include database connection before any usage
include 'dbconnect.php';

// Now include PermissionManager after database connection is established
require_once 'telegram/telegram_handlers.php';
require_once 'includes/PermissionManager.php';

// Strict session validation with notification
if (!isset($_SESSION['email']) || !isset($_SESSION['user_type'])) {
    notifySystemError(
        'Unauthorized Access',
        "Session validation failed in admin account management",
        __FILE__,
        __LINE__
    );
    header('Location: index.php');
    exit;
}

// Validate admin access with notification
if ($_SESSION['user_type'] !== 'admin') {
    notifySystemError(
        'Access Violation',
        "Non-admin user attempted to access admin account management: {$_SESSION['email']}",
        __FILE__,
        __LINE__
    );
    header('Location: index.php');
    exit; 
}

// Initialize PermissionManager after session and database connection are ready
$permManager = new PermissionManager($conn, $_SESSION['user_id']);

// Check if user has permission to manage users
try {
    $permManager->requirePermission('manage_users');
} catch (Exception $e) {
    error_log("Permission denied: " . $e->getMessage());
    header('Location: index.php');
    exit;
}

require_once 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Email configuration function
if (!function_exists('configureMailer')) {
    function configureMailer() {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'your-email@gmail.com';
            $mail->Password = 'your-app-password';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->setFrom('socpms@example.com', 'SOC Paperwork Management System');
            
            return $mail;
        } catch (Exception $e) {
            error_log("Mailer Configuration Error: " . $e->getMessage());
            return null;
        }
    }
}

// Common email templates
if (!function_exists('getEmailTemplate')) {
    function getEmailTemplate($type, $data) {
        $templates = [
            'submission' => "
                <h2>Dear {$data['userName']},</h2>
                <p>Your paperwork has been successfully submitted:</p>",
            'hod_notification' => "
                <h2>Dear HOD,</h2>
                <p>A new paperwork submission requires your review:</p>",
            'dean_notification' => "
                <h2>Dear Dean,</h2>
                <p>A paperwork has been endorsed by the HOD and requires your review:</p>",
            'password_reset' => "
                <h2>Password Reset Request</h2>
                <p>You have requested to reset your password. Click the link below to proceed:</p>"
        ];
        
        return $templates[$type] ?? '';
    }
}

// Email sending functions - wrapped in function_exists checks
if (!function_exists('sendSubmissionEmail')) {
    function sendSubmissionEmail($userEmail, $userName, $paperworkDetails) {
        try {
            $mail = configureMailer();
            if (!$mail) return false;
            
            $mail->addAddress($userEmail, $userName);
            $mail->Subject = 'Paperwork Submission Confirmation';
            
            $message = getEmailTemplate('submission', ['userName' => $userName]) . 
                      getCommonEmailBody($paperworkDetails);
            
            return sendEmail($mail, $message);
        } catch (Exception $e) {
            error_log("Submission Email Error: " . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('sendHODNotificationEmail')) {
    function sendHODNotificationEmail($hodEmail, $paperworkDetails) {
        try {
            $mail = configureMailer();
            if (!$mail) return false;
            
            $mail->addAddress($hodEmail);
            $mail->Subject = 'New Paperwork Pending Review';
            
            $message = getEmailTemplate('hod_notification', []) . 
                      getCommonEmailBody($paperworkDetails);
            
            return sendEmail($mail, $message);
        } catch (Exception $e) {
            error_log("HOD Notification Error: " . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('sendDeanNotificationEmail')) {
    function sendDeanNotificationEmail($deanEmail, $paperworkDetails, $hodName) {
        try {
            $mail = configureMailer();
            if (!$mail) return false;
            
            $mail->addAddress($deanEmail);
            $mail->Subject = 'Paperwork Endorsed by HOD - Pending Review';
            
            $message = getEmailTemplate('dean_notification', []) . 
                      getCommonEmailBody($paperworkDetails, $hodName);
            
            return sendEmail($mail, $message);
        } catch (Exception $e) {
            error_log("Dean Notification Error: " . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('sendPasswordResetEmail')) {
    function sendPasswordResetEmail($userEmail, $resetLink) {
        try {
            $mail = configureMailer();
            if (!$mail) return false;

            $mail->addAddress($userEmail);
            $mail->Subject = 'Password Reset Request';
            
            $message = getEmailTemplate('password_reset', []) . 
                      "<p><a href='{$resetLink}'>Reset Password</a></p>
                       <p>This link will expire in 1 hour.</p>
                       <p>If you did not request this, please ignore this email.</p>";
            
            return sendEmail($mail, $message);
        } catch (Exception $e) {
            error_log("Password Reset Email Error: " . $e->getMessage());
            return false;
        }
    }
}

// Helper function for sending emails
if (!function_exists('sendEmail')) {
    function sendEmail($mail, $message) {
        $mail->isHTML(true);
        $message .= "<br><p>Regards,<br>SOC Paperwork Management System</p>";
        $mail->Body = $message;
        $mail->AltBody = strip_tags(str_replace(['<br>', '</p>'], "\n", $message));
        
        return $mail->send();
    }
}

// Helper function for common email body
if (!function_exists('getCommonEmailBody')) {
    function getCommonEmailBody($paperworkDetails, $endorsedBy = null) {
        $body = "<ul>
            <li><strong>Reference Number:</strong> {$paperworkDetails['ref_number']}</li>
            <li><strong>Paperwork Name:</strong> {$paperworkDetails['project_name']}</li>";
        
        if ($endorsedBy) {
            $body .= "<li><strong>Endorsed by:</strong> {$endorsedBy}</li>";
        }
        
        $body .= "</ul>
            <p>Please login to the system to review this submission.</p>
            <br>
            <p>Regards,<br>SOC Paperwork Management System</p>";
            
        return $body;
    }
}