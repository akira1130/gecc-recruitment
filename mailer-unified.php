<?php
/**
 * GECC - Unified Email Notification System
 * Uses PHPMailer with environment configuration
 */

require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailNotifier {
    private $mail;
    private $fromEmail;
    private $fromName;

    public function __construct() {
        // Load environment variables
        $this->loadEnv();
        
        $this->mail = new PHPMailer(true);
        
        try {
            // Get email config from environment
            $driver = getenv('MAIL_DRIVER') ?: 'gmail';
            $host = getenv('MAIL_HOST') ?: 'smtp.gmail.com';
            $port = (int)(getenv('MAIL_PORT') ?: 465);
            $username = getenv('MAIL_USERNAME') ?: '';
            $password = getenv('MAIL_PASSWORD') ?: '';
            $encryption = getenv('MAIL_ENCRYPTION') ?: 'smtps';
            
            $this->fromEmail = getenv('MAIL_FROM_EMAIL') ?: 'globalenglish.recruits@gmail.com';
            $this->fromName = getenv('MAIL_FROM_NAME') ?: 'GECC - Global English Call Center';
            
            // Validate configuration
            if (!$username || !$password) {
                throw new Exception('Email credentials not configured. Update MAIL_USERNAME and MAIL_PASSWORD in .env');
            }
            
            // Configure SMTP
            $this->mail->isSMTP();
            $this->mail->Host = $host;
            $this->mail->SMTPAuth = true;
            $this->mail->Username = $username;
            $this->mail->Password = $password;
            $this->mail->Port = $port;
            $this->mail->CharSet = 'UTF-8';
            
            // Set encryption
            if ($encryption === 'smtps' || $encryption === 'SMTPS') {
                $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($encryption === 'tls' || $encryption === 'TLS') {
                $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }
            
            // Enable debug mode (set to 2 for full SMTP debug)
            $this->mail->SMTPDebug = 2;  // Debug enabled to see errors
            
            // SSL options (for development/testing)
            $this->mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
            
            // Set from address
            $this->mail->setFrom($this->fromEmail, $this->fromName);
            
            error_log("Email notifier initialized: {$driver} via {$host}:{$port}");
            
        } catch (Exception $e) {
            error_log('Mailer Configuration Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Load environment variables from .env file
     */
    private function loadEnv() {
        $envFile = __DIR__ . '/.env';
        
        if (!file_exists($envFile)) {
            error_log("Warning: .env file not found at {$envFile}");
            return;
        }
        
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // Parse key=value
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Remove quotes if present
                if ((strpos($value, '"') === 0 && strrpos($value, '"') === strlen($value) - 1) ||
                    (strpos($value, "'") === 0 && strrpos($value, "'") === strlen($value) - 1)) {
                    $value = substr($value, 1, -1);
                }
                
                if (!empty($key)) {
                    putenv("{$key}={$value}");
                }
            }
        }
    }

    /**
     * Send Approval Email
     */
    public function sendApprovalEmail($applicantName, $applicantEmail) {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($applicantEmail);
            $this->mail->Subject = 'Your GECC Application Has Been Approved! ✓';
            
            $body = $this->getApprovalEmailBody($applicantName);
            $this->mail->Body = $body;
            $this->mail->isHTML(true);
            $this->mail->AltBody = strip_tags($body);
            
            $result = $this->mail->send();
            error_log("Approval email sent successfully to: {$applicantEmail}");
            return true;
            
        } catch (Exception $e) {
            error_log('Failed to send approval email: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send Rejection Email
     */
    public function sendRejectionEmail($applicantName, $applicantEmail, $reason = '') {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($applicantEmail);
            $this->mail->Subject = 'Your GECC Application Status';
            
            $body = $this->getRejectionEmailBody($applicantName, $reason);
            $this->mail->Body = $body;
            $this->mail->isHTML(true);
            $this->mail->AltBody = strip_tags($body);
            
            $result = $this->mail->send();
            error_log("Rejection email sent successfully to: {$applicantEmail}");
            return true;
            
        } catch (Exception $e) {
            error_log('Failed to send rejection email: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Approval Email Template
     */
    private function getApprovalEmailBody($applicantName) {
        return "
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: linear-gradient(135deg, #6366f1 0%, #06b6d4 100%); color: white; padding: 20px; border-radius: 8px; text-align: center; margin-bottom: 20px; }
                    .header h1 { margin: 0; font-size: 28px; }
                    .content { background: #f9fafb; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
                    .content h2 { color: #10b981; margin-top: 0; }
                    .btn { display: inline-block; background: #6366f1; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin: 10px 0; }
                    .footer { text-align: center; color: #666; font-size: 12px; margin-top: 20px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>🎉 Congratulations!</h1>
                    </div>
                    
                    <div class='content'>
                        <h2>Your Application Has Been Approved!</h2>
                        <p>Hi <strong>{$applicantName}</strong>,</p>
                        
                        <p>Great news! We're excited to inform you that your application to join GECC has been <strong>approved</strong>!</p>
                        
                        <p>Our team was impressed with your qualifications and experience. We believe you'll be a great addition to our community of inspiring English teachers.</p>
                        
                        <h3>Next Steps:</h3>
                        <ul>
                            <li>Check your email for onboarding instructions</li>
                            <li>Complete the orientation training</li>
                            <li>Start teaching with GECC</li>
                        </ul>
                        
                        <p>If you have any questions, feel free to reach out to us at <strong>{$this->fromEmail}</strong> or call <strong>+63 919 830 9467</strong>.</p>
                        
                        <p>Welcome to GECC! We look forward to working with you.</p>
                        
                        <p>Best regards,<br><strong>GECC Recruitment Team</strong></p>
                    </div>
                    
                    <div class='footer'>
                        <p>© 2024 Global English Call Center Inc. All rights reserved.</p>
                        <p>This is an automated message. Please do not reply to this email.</p>
                    </div>
                </div>
            </body>
            </html>
        ";
    }

    /**
     * Rejection Email Template
     */
    private function getRejectionEmailBody($applicantName, $reason = '') {
        $reasonText = !empty($reason) ? "<p><strong>Reason:</strong> {$reason}</p>" : '';
        
        return "
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: #f3f4f6; color: #333; padding: 20px; border-radius: 8px; text-align: center; margin-bottom: 20px; border-left: 4px solid #ef4444; }
                    .header h1 { margin: 0; font-size: 24px; }
                    .content { background: #f9fafb; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
                    .btn { display: inline-block; background: #6366f1; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin: 10px 0; }
                    .footer { text-align: center; color: #666; font-size: 12px; margin-top: 20px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>Your GECC Application Status</h1>
                    </div>
                    
                    <div class='content'>
                        <p>Hi <strong>{$applicantName}</strong>,</p>
                        
                        <p>Thank you for taking the time to apply to GECC. We appreciate your interest in joining our community of English teachers.</p>
                        
                        <p>After careful review of your application, we regret to inform you that we are unable to move forward with your candidacy at this time.</p>
                        
                        {$reasonText}
                        
                        <p>This decision doesn't reflect on your abilities as an educator. We encourage you to apply again in the future, and we wish you the very best in your teaching career.</p>
                        
                        <p>If you have any questions, please feel free to reach out to us at <strong>{$this->fromEmail}</strong>.</p>
                        
                        <p>Best regards,<br><strong>GECC Recruitment Team</strong></p>
                    </div>
                    
                    <div class='footer'>
                        <p>© 2024 Global English Call Center Inc. All rights reserved.</p>
                        <p>This is an automated message. Please do not reply to this email.</p>
                    </div>
                </div>
            </body>
            </html>
        ";
    }
}

?>
