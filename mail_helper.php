<?php
/**
 * Reusable Mail Helper using PHPMailer
 * Supports both Composer autoloader and manual cPanel file uploads.
 * All functions are try-caught internally so mail issues do not crash the site.
 */

require_once __DIR__ . '/mail_config.php';

// Resolve PHPMailer namespaces
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

class MailSender {

    /**
     * Initializes PHPMailer based on configuration.
     * Returns PHPMailer instance or null if PHPMailer is missing.
     */
    private static function getMailerInstance() {
        // 1. Attempt to load PHPMailer
        if (file_exists(__DIR__ . '/vendor/autoload.php')) {
            require_once __DIR__ . '/vendor/autoload.php';
        } else {
            // Fallback for manual cPanel upload: check in root phpmailer folder
            $manualPaths = [
                __DIR__ . '/phpmailer/src/Exception.php',
                __DIR__ . '/phpmailer/src/PHPMailer.php',
                __DIR__ . '/phpmailer/src/SMTP.php'
            ];
            
            $loaded = true;
            foreach ($manualPaths as $path) {
                if (file_exists($path)) {
                    require_once $path;
                } else {
                    $loaded = false;
                }
            }

            if (!$loaded) {
                error_log("PHPMailer Error: Autoloader or manual directory 'phpmailer/src/' not found. Please install composer packages or upload PHPMailer files.");
                return null;
            }
        }

        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = MAIL_SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = MAIL_SMTP_USER;
            $mail->Password   = MAIL_SMTP_PASS;
            $mail->Port       = MAIL_SMTP_PORT;
            
            // SMTP Secure (TLS/SSL)
            $secure = strtolower(MAIL_SMTP_SECURE);
            if ($secure === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($secure === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } else {
                $mail->SMTPAutoTLS = false;
                $mail->SMTPSecure = '';
            }

            // SMTP Debug Mode (Useful for development)
            $mail->SMTPDebug = MAIL_SMTP_DEBUG;
            $mail->Debugoutput = function($str, $level) {
                error_log("SMTP DEBUG (Level $level): $str");
            };

            // Sender details
            $mail->setFrom(MAIL_SENDER_EMAIL, MAIL_SENDER_NAME);
            
            return $mail;
        } catch (Exception $e) {
            error_log("PHPMailer Initialization Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Prevent header injection by cleaning newlines/carriage returns and whitespaces from email headers.
     */
    private static function sanitizeHeader($value) {
        return preg_replace('/[\r\n\t]+/', ' ', trim($value));
    }

    /**
     * Validate email format and check for potential injection patterns.
     */
    public static function isValidEmail($email) {
        $email = trim($email);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        // Strict protection against header injections in mail addresses
        if (preg_match('/[\r\n]/', $email)) {
            return false;
        }
        return true;
    }

    /**
     * Generic send function
     * Returns true on success, false on failure (fails silently without breaking caller page)
     */
    public static function send($toEmail, $toName, $subject, $htmlBody, $altBody = '') {
        if (!self::isValidEmail($toEmail)) {
            error_log("MailSender Error: Invalid email recipient address: '$toEmail'");
            return false;
        }

        $mail = self::getMailerInstance();
        if (!$mail) {
            return false;
        }

        try {
            $mail->addAddress(trim($toEmail), self::sanitizeHeader($toName));
            $mail->Subject = self::sanitizeHeader($subject);
            
            $mail->isHTML(true);
            $mail->Body = $htmlBody;
            
            if (!empty($altBody)) {
                $mail->AltBody = $altBody;
            } else {
                $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<p>'], "\n", $htmlBody));
            }

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("MailSender Error: Failed to send email to $toEmail. Error: " . $mail->ErrorInfo);
            return false;
        }
    }

    /**
     * Email wrapper template for consistent luxury styling.
     */
    private static function getEmailBaseTemplate($title, $contentHtml) {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>' . htmlspecialchars($title) . '</title>
            <style>
                body {
                    margin: 0;
                    padding: 0;
                    background-color: #f3f4f6;
                    font-family: "Plus Jakarta Sans", "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
                    -webkit-font-smoothing: antialiased;
                }
                .container {
                    max-width: 600px;
                    margin: 40px auto;
                    background-color: #ffffff;
                    border-radius: 24px;
                    overflow: hidden;
                    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
                    border: 1px solid #e5e7eb;
                }
                .header {
                    background: linear-gradient(135deg, #003580 0%, #001f4d 100%);
                    padding: 40px 30px;
                    text-align: center;
                }
                .header img {
                    height: 50px;
                    margin-bottom: 15px;
                }
                .header h1 {
                    color: #ffffff;
                    margin: 0;
                    font-size: 24px;
                    font-weight: 800;
                    letter-spacing: -0.5px;
                }
                .content {
                    padding: 40px 35px;
                    color: #374151;
                    line-height: 1.6;
                    font-size: 15px;
                }
                .content h2 {
                    color: #003580;
                    margin-top: 0;
                    font-size: 20px;
                    font-weight: 700;
                }
                .button-container {
                    text-align: center;
                    margin: 35px 0 15px 0;
                }
                .btn {
                    display: inline-block;
                    padding: 14px 30px;
                    background-color: #003580;
                    color: #ffffff !important;
                    text-decoration: none;
                    font-weight: bold;
                    border-radius: 12px;
                    font-size: 14px;
                    text-transform: uppercase;
                    letter-spacing: 1px;
                    box-shadow: 0 4px 15px rgba(0, 53, 128, 0.2);
                }
                .footer {
                    background-color: #f9fafb;
                    padding: 30px;
                    text-align: center;
                    border-top: 1px solid #f3f4f6;
                    font-size: 11px;
                    color: #9ca3af;
                    font-weight: 500;
                    letter-spacing: 0.5px;
                }
                .footer a {
                    color: #003580;
                    text-decoration: none;
                }
                .highlight-box {
                    background-color: #f0f7ff;
                    border-left: 4px solid #006ce4;
                    padding: 20px;
                    border-radius: 0 16px 16px 0;
                    margin: 25px 0;
                }
                .detail-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 25px 0;
                }
                .detail-table th, .detail-table td {
                    padding: 12px 15px;
                    text-align: left;
                    border-bottom: 1px solid #f3f4f6;
                }
                .detail-table th {
                    font-size: 12px;
                    text-transform: uppercase;
                    color: #9ca3af;
                    font-weight: 700;
                    width: 35%;
                }
                .detail-table td {
                    font-weight: 600;
                    color: #1f2937;
                }
                .badge {
                    display: inline-block;
                    padding: 4px 12px;
                    border-radius: 50px;
                    font-size: 11px;
                    font-weight: 700;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                }
                .badge-pending { background-color: #fef3c7; color: #d97706; }
                .badge-confirmed { background-color: #d1fae5; color: #059669; }
                .badge-checked_in { background-color: #dbeafe; color: #2563eb; }
                .badge-checked_out { background-color: #e5e7eb; color: #4b5563; }
                .badge-cancelled { background-color: #fee2e2; color: #dc2626; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Bookingjaunt</h1>
                </div>
                <div class="content">
                    ' . $contentHtml . '
                </div>
                <div class="footer">
                    <p>© 2026 Bookingjaunt. Experience Sri Lanka, effortlessly.</p>
                    <p>If you have any questions, contact us at <a href="mailto:support@bookingjaunt.com">support@bookingjaunt.com</a></p>
                </div>
            </div>
        </body>
        </html>';
    }

    /**
     * 1. Send Welcome Email
     */
    public static function sendWelcomeEmail($email, $name) {
        $subject = "Welcome to Bookingjaunt - Sri Lanka awaits you!";
        $content = '
        <h2>Welcome, ' . htmlspecialchars($name) . '!</h2>
        <p>Thank you for registering an account with Bookingjaunt. We are absolutely thrilled to welcome you to our community where travel meets luxury and convenience.</p>
        <p>With your new Bookingjaunt account, you can seamlessly explore and book hotels, safari packages, day-outs, and vehicle rentals across Sri Lanka.</p>
        <div class="highlight-box">
            <strong>Where to start?</strong><br>
            Log in to your account to update your profile, check active bookings, and customize your travel preferences. If you are a property owner, you can list your properties and manage bookings directly from your manager dashboard.
        </div>
        <div class="button-container">
            <a href="http://bookingjaunt.com/login.php" class="btn">Explore Bookingjaunt</a>
        </div>
        <p>Best regards,<br>The Bookingjaunt Team</p>';

        $html = self::getEmailBaseTemplate("Welcome to Bookingjaunt", $content);
        return self::send($email, $name, $subject, $html);
    }

    /**
     * 2. Send Property Registration Received
     */
    public static function sendPropertyRegisteredEmail($email, $ownerName, $propertyName, $businessType) {
        $subject = "Property Submission Received - " . $propertyName;
        $content = '
        <h2>Property Registration Success!</h2>
        <p>Dear ' . htmlspecialchars($ownerName) . ',</p>
        <p>Thank you for listing your business on Bookingjaunt. We have successfully received your registration details for <strong>' . htmlspecialchars($propertyName) . '</strong>.</p>
        
        <table class="detail-table">
            <tr>
                <th>Business Name</th>
                <td>' . htmlspecialchars($propertyName) . '</td>
            </tr>
            <tr>
                <th>Type</th>
                <td style="text-transform: capitalize;">' . htmlspecialchars($businessType) . '</td>
            </tr>
            <tr>
                <th>Submission Status</th>
                <td><span class="badge badge-pending">Pending Review</span></td>
            </tr>
        </table>

        <div class="highlight-box">
            <strong>What happens next?</strong><br>
            Shortly, our staff will review your business registration details and verification documents. Once approved, you will get notified with an email, your dashboard credentials will activate, and your property will be published automatically.
        </div>
        <p>If you have any questions or need to make urgent changes, please reply to this email.</p>
        <p>Warm regards,<br>Bookingjaunt Staff Team</p>';

        $html = self::getEmailBaseTemplate("Property Registration Submitted", $content);
        return self::send($email, $ownerName, $subject, $html);
    }

    /**
     * 3. Send Property Approval / Rejection Status
     */
    public static function sendPropertyApprovalStatusEmail($email, $ownerName, $propertyName, $status, $adminNotes = '') {
        $isApproved = ($status === 'approved');
        $subject = "Business Review Update - " . ($isApproved ? "Approved" : "Revision Required") . " - " . $propertyName;
        
        $statusBadge = $isApproved ? 
            '<span class="badge badge-confirmed">Approved & Published</span>' : 
            '<span class="badge badge-cancelled">Revision Required</span>';

        $notesSection = !empty($adminNotes) ? '
        <div class="highlight-box" style="background-color: #fffbeb; border-left-color: #f59e0b;">
            <strong>Reviewer Feedback:</strong><br>
            ' . nl2br(htmlspecialchars($adminNotes)) . '
        </div>' : '';

        $content = '
        <h2>Business Status Update</h2>
        <p>Dear ' . htmlspecialchars($ownerName) . ',</p>
        <p>Our review board has evaluated your property registration request for <strong>' . htmlspecialchars($propertyName) . '</strong>.</p>
        
        <table class="detail-table">
            <tr>
                <th>Business Name</th>
                <td>' . htmlspecialchars($propertyName) . '</td>
            </tr>
            <tr>
                <th>Status</th>
                <td>' . $statusBadge . '</td>
            </tr>
        </table>

        ' . $notesSection . '

        ' . ($isApproved ? '
        <p>Congratulations! Your business is now live on our platform. Visitors from all around the world can view and book your rooms or services.</p>
        <div class="button-container">
            <a href="http://bookingjaunt.com/login.php" class="btn">Manage Your Property</a>
        </div>
        ' : '
        <p>Please log in and update the fields according to the reviewer notes above to resubmit your property for review.</p>
        <div class="button-container">
            <a href="http://bookingjaunt.com/login.php" class="btn">Update Details</a>
        </div>
        ') . '
        
        <p>Best regards,<br>The Bookingjaunt Administration</p>';

        $html = self::getEmailBaseTemplate("Property Approval Status", $content);
        return self::send($email, $ownerName, $subject, $html);
    }

    /**
     * 4. Send Booking Confirmation
     */
    public static function sendBookingConfirmationEmail($email, $guestName, $bookingId, $propertyName, $roomName, $checkIn, $checkOut, $totalPrice, $status, $paymentStatus) {
        $subject = "Booking Confirmed! - ID: #" . $bookingId . " - " . $propertyName;
        
        $content = '
        <h2>Thank you for booking with us!</h2>
        <p>Dear ' . htmlspecialchars($guestName) . ',</p>
        <p>We are pleased to confirm your booking at <strong>' . htmlspecialchars($propertyName) . '</strong>. Below are your booking confirmation details:</p>
        
        <table class="detail-table">
            <tr>
                <th>Booking ID</th>
                <td>#' . htmlspecialchars($bookingId) . '</td>
            </tr>
            <tr>
                <th>Property</th>
                <td>' . htmlspecialchars($propertyName) . '</td>
            </tr>
            <tr>
                <th>Accommodation</th>
                <td>' . htmlspecialchars($roomName) . '</td>
            </tr>
            <tr>
                <th>Check-in Date</th>
                <td>' . date('l, F j, Y', strtotime($checkIn)) . '</td>
            </tr>
            <tr>
                <th>Check-out Date</th>
                <td>' . date('l, F j, Y', strtotime($checkOut)) . '</td>
            </tr>
            <tr>
                <th>Total Price</th>
                <td>LKR ' . number_format($totalPrice) . '</td>
            </tr>
            <tr>
                <th>Booking Status</th>
                <td><span class="badge badge-' . htmlspecialchars($status) . '">' . str_replace('_', ' ', $status) . '</span></td>
            </tr>
            <tr>
                <th>Payment Status</th>
                <td style="text-transform: uppercase; font-size: 13px;">' . htmlspecialchars($paymentStatus) . '</td>
            </tr>
        </table>

        <div class="highlight-box">
            <strong>Need to manage your booking?</strong><br>
            You can view details, download vouchers, or review cancel conditions anytime by logging into the Bookingjaunt portal.
        </div>

        <div class="button-container">
            <a href="http://bookingjaunt.com/login.php" class="btn">View Booking Details</a>
        </div>
        <p>Enjoy your stay!<br>The Bookingjaunt Travel Team</p>';

        $html = self::getEmailBaseTemplate("Booking Confirmed - #" . $bookingId, $content);
        return self::send($email, $guestName, $subject, $html);
    }

    /**
     * 5. Send Booking Status Update
     */
    public static function sendBookingStatusUpdateEmail($email, $guestName, $bookingId, $propertyName, $roomName, $checkIn, $checkOut, $status) {
        $subject = "Booking Status Update - ID: #" . $bookingId;
        
        $content = '
        <h2>Your Booking Status Has Been Updated</h2>
        <p>Dear ' . htmlspecialchars($guestName) . ',</p>
        <p>We want to inform you that the status of your reservation at <strong>' . htmlspecialchars($propertyName) . '</strong> has been changed.</p>
        
        <table class="detail-table">
            <tr>
                <th>Booking ID</th>
                <td>#' . htmlspecialchars($bookingId) . '</td>
            </tr>
            <tr>
                <th>Property</th>
                <td>' . htmlspecialchars($propertyName) . '</td>
            </tr>
            <tr>
                <th>Room Type</th>
                <td>' . htmlspecialchars($roomName) . '</td>
            </tr>
            <tr>
                <th>New Status</th>
                <td><span class="badge badge-' . htmlspecialchars($status) . '">' . str_replace('_', ' ', $status) . '</span></td>
            </tr>
            <tr>
                <th>Stay Dates</th>
                <td>' . date('M j, Y', strtotime($checkIn)) . ' to ' . date('M j, Y', strtotime($checkOut)) . '</td>
            </tr>
        </table>

        <div class="highlight-box">
            <strong>Current status meaning:</strong><br>
            ' . self::getStatusExplanation($status) . '
        </div>

        <div class="button-container">
            <a href="http://bookingjaunt.com/login.php" class="btn">View Dashboard</a>
        </div>
        <p>Warm regards,<br>Bookingjaunt & ' . htmlspecialchars($propertyName) . ' Management</p>';

        $html = self::getEmailBaseTemplate("Booking Status Updated - #" . $bookingId, $content);
        return self::send($email, $guestName, $subject, $html);
    }

    /**
     * 6. Send Forgot Password OTP
     */
    public static function sendForgotPassOTPEmail($email, $name, $otp) {
        $subject = "Password Reset Request - OTP Verification Code";
        $content = '
        <h2>Reset Your Password</h2>
        <p>Hello ' . htmlspecialchars($name) . ',</p>
        <p>We received a request to reset the password for your Bookingjaunt account. Use the verification code below to proceed with resetting your password:</p>
        
        <div style="text-align: center; margin: 30px 0;">
            <div style="display: inline-block; font-size: 32px; font-weight: 800; letter-spacing: 6px; color: #003580; background-color: #f0f7ff; padding: 15px 40px; border-radius: 16px; border: 2px dashed #006ce4;">
                ' . htmlspecialchars($otp) . '
            </div>
        </div>

        <div class="highlight-box" style="background-color: #fee2e2; border-left-color: #dc2626; color: #991b1b;">
            <strong>Warning:</strong> This code is highly confidential and will expire in <strong>15 minutes</strong>. If you did not make this request, please ignore this email or contact support immediately.
        </div>
        <p>Best regards,<br>The Bookingjaunt Security Team</p>';

        $html = self::getEmailBaseTemplate("Password Reset Verification", $content);
        return self::send($email, $name, $subject, $html);
    }

    /**
     * Helper to render descriptive status explanations.
     */
    private static function getStatusExplanation($status) {
        switch ($status) {
            case 'pending':
                return 'Your booking is pending validation and property approval.';
            case 'confirmed':
                return 'Your reservation is confirmed! The property is preparing for your arrival.';
            case 'checked_in':
                return 'You have successfully checked in. Have a wonderful stay!';
            case 'checked_out':
                return 'You have successfully checked out. Thank you for booking with us!';
            case 'cancelled':
                return 'Your reservation has been cancelled. If this is a mistake, please contact customer care.';
            default:
                return 'Your booking status has been updated. Please log in to see the details.';
        }
    }

    /**
     * Helper to automatically fetch booking details from database and send status update email.
     */
    public static function sendBookingStatusUpdateEmailById($pdo, $booking_id, $new_status) {
        try {
            $stmt = $pdo->prepare("
                SELECT b.*, p.property_name, COALESCE(pr.room_name, p.property_name) as item_name
                FROM bookings b
                JOIN properties p ON b.property_id = p.id
                LEFT JOIN property_rooms pr ON b.room_id = pr.id
                WHERE b.id = ?
                LIMIT 1
            ");
            $stmt->execute([$booking_id]);
            $booking = $stmt->fetch();
            
            if ($booking && !empty($booking['guest_email'])) {
                return self::sendBookingStatusUpdateEmail(
                    $booking['guest_email'],
                    $booking['guest_name'],
                    $booking['id'],
                    $booking['property_name'],
                    $booking['item_name'],
                    $booking['check_in_date'],
                    $booking['check_out_date'],
                    $new_status
                );
            }
        } catch (Exception $e) {
            error_log("MailSender Error: Failed to fetch booking info for status change email: " . $e->getMessage());
        }
        return false;
    }

    /**
     * 7. Send Property Request Status Notification (for edits/deletions)
     */
    public static function sendPropertyRequestStatusEmail($email, $ownerName, $propertyName, $requestType, $status, $adminNotes = '') {
        $isApproved = ($status === 'approved');
        $subject = "Property " . ucfirst($requestType) . " Request " . ($isApproved ? "Approved" : "Rejected") . " - " . $propertyName;
        
        $statusBadge = $isApproved ? 
            '<span class="badge badge-confirmed">Approved</span>' : 
            '<span class="badge badge-cancelled">Rejected</span>';

        $notesSection = !empty($adminNotes) ? '
        <div class="highlight-box" style="background-color: #fffbeb; border-left-color: #f59e0b;">
            <strong>Reviewer Feedback:</strong><br>
            ' . nl2br(htmlspecialchars($adminNotes)) . '
        </div>' : '';

        $content = '
        <h2>Property Request Update</h2>
        <p>Dear ' . htmlspecialchars($ownerName) . ',</p>
        <p>Our review board has processed your <strong>' . htmlspecialchars($requestType) . '</strong> request for the property <strong>' . htmlspecialchars($propertyName) . '</strong>.</p>
        
        <table class="detail-table">
            <tr>
                <th>Property Name</th>
                <td>' . htmlspecialchars($propertyName) . '</td>
            </tr>
            <tr>
                <th>Request Type</th>
                <td style="text-transform: capitalize;">' . htmlspecialchars($requestType) . '</td>
            </tr>
            <tr>
                <th>Status</th>
                <td>' . $statusBadge . '</td>
            </tr>
        </table>

        ' . $notesSection . '

        ' . ($isApproved ? '
        <p>Your request has been successfully approved and the updates have been published to the site.</p>
        ' : '
        <p>Unfortunately, your request could not be approved at this time. Please check the feedback notes above.</p>
        ') . '
        
        <p>Best regards,<br>The Bookingjaunt Team</p>';

        $html = self::getEmailBaseTemplate("Property Request Update", $content);
        return self::send($email, $ownerName, $subject, $html);
    }
}
