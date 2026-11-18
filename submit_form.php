<?php
/**
 * JOULE ASSET VALUERS - OFFICIAL CONTACT FORM PROCESSOR
 * Saves inquiry directly to MySQL database and sends notifications to official email addresses
 */

declare(strict_types=1);

// Set response header to JSON for AJAX requests
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');

// Load database configuration
require_once __DIR__ . '/db_config.php';

// Helper: Check if request is AJAX
function isAjax(): bool {
    return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
        || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
}

// Helper: JSON response
function jsonResponse(bool $success, string $message, array $extra = []): void {
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if (isAjax()) {
        jsonResponse(false, 'Invalid request method. Only POST is accepted.');
    } else {
        header('Location: contact.html');
        exit;
    }
}

// Collect raw POST data or JSON payload
$data = $_POST;
if (empty($data)) {
    $rawInput = file_get_contents('php://input');
    if (!empty($rawInput)) {
        $jsonData = json_decode($rawInput, true);
        if (is_array($jsonData)) {
            $data = $jsonData;
        }
    }
}

// Sanitize and extract form inputs
$name       = trim($data['name'] ?? '');
$email      = trim($data['email'] ?? '');
$phone      = trim($data['phone'] ?? '');
$location   = trim($data['location'] ?? '');
$service    = trim($data['service'] ?? '');
$message    = trim($data['message'] ?? '');
$pageSource = trim($data['page_source'] ?? $_SERVER['HTTP_REFERER'] ?? 'Website Direct');
$ipAddress  = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
$userAgent  = substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 255);

// Validation
$errors = [];

if (empty($name) || mb_strlen($name) < 2) {
    $errors[] = 'Please enter your full name.';
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'A valid email address is required.';
}

if (empty($phone) || mb_strlen($phone) < 7) {
    $errors[] = 'A valid phone number is required.';
}

if (empty($service)) {
    $errors[] = 'Please select a service from the dropdown.';
}

if (empty($message) || mb_strlen($message) < 5) {
    $errors[] = 'Please enter your requirement or message details.';
}

// Anti-Spam Security: Math Captcha Verification
$captchaAnswer = trim($data['captcha_answer'] ?? '');
$captchaHash   = trim($data['captcha_hash'] ?? '');

if (!empty($captchaHash)) {
    $expectedHash = base64_encode(intval($captchaAnswer) . '_salt_joule_2026');
    if ($captchaHash !== $expectedHash) {
        $errors[] = 'Security math question answered incorrectly. Please try again.';
    }
}

// Return validation errors if any
if (!empty($errors)) {
    if (isAjax()) {
        jsonResponse(false, implode(' ', $errors));
    } else {
        $errorMsg = urlencode(implode(' ', $errors));
        echo "<script>alert('" . addslashes(implode('\n', $errors)) . "'); window.history.back();</script>";
        exit;
    }
}

// Honeypot spam check
if (!empty($data['website_url_hp']) || !empty($data['fax_number'])) {
    // Bot detected silently
    if (isAjax()) {
        jsonResponse(true, 'Your inquiry has been submitted successfully.');
    } else {
        header('Location: thank-you.html');
        exit;
    }
}

// Spam keyword filter
$spamKeywords = [
    'viagra', 'cialis', 'casino', 'poker', 'crypto investment', 'whatsapp me on',
    'buy followers', 'seo audit report for free', 'earn money fast', 'porn', 'xxx'
];
$combinedText = strtolower($name . ' ' . $email . ' ' . $phone . ' ' . $location . ' ' . $service . ' ' . $message . ' ' . $userAgent);
foreach ($spamKeywords as $spamWord) {
    if (strpos($combinedText, $spamWord) !== false) {
        if (isAjax()) {
            jsonResponse(true, 'Thank you! Your inquiry has been submitted.');
        } else {
            header('Location: thank-you.html');
            exit;
        }
    }
}

try {
    // 1. Establish PDO Connection
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

    // 2. Ensure inquiries table exists
    $createTableSql = "
        CREATE TABLE IF NOT EXISTS inquiries (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(150) NOT NULL,
            email VARCHAR(150) NOT NULL,
            phone VARCHAR(50) NOT NULL,
            location VARCHAR(150) NULL,
            service VARCHAR(150) NOT NULL,
            message TEXT NOT NULL,
            page_source VARCHAR(255) NULL,
            ip_address VARCHAR(50) NULL,
            user_agent VARCHAR(255) NULL,
            status VARCHAR(50) DEFAULT 'New',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $pdo->exec($createTableSql);

    // 3. Insert inquiry into database
    $insertSql = "
        INSERT INTO inquiries 
        (name, email, phone, location, service, message, page_source, ip_address, user_agent)
        VALUES 
        (:name, :email, :phone, :location, :service, :message, :page_source, :ip_address, :user_agent)
    ";
    $stmt = $pdo->prepare($insertSql);
    $stmt->execute([
        ':name'        => $name,
        ':email'       => $email,
        ':phone'       => $phone,
        ':location'    => $location,
        ':service'     => $service,
        ':message'     => $message,
        ':page_source' => $pageSource,
        ':ip_address'  => $ipAddress,
        ':user_agent'  => $userAgent
    ]);

    $insertId = $pdo->lastInsertId();

    // 4. Send Notification Email to ALL three official recipients
    $recipients = [
        'info@jouleassetvaluers.com',
        'jouleassetvaluers@gmail.com',
        'balamuruganp0405@gmail.com'
    ];

    $subject = "New Inquiry #{$insertId}: {$service} - {$name}";
    $displayDate = date('d M Y, h:i A') . ' IST';
    $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $safeEmail = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
    $safePhone = htmlspecialchars($phone, ENT_QUOTES, 'UTF-8');
    $safeLocation = htmlspecialchars(!empty($location) ? $location : 'Not specified', ENT_QUOTES, 'UTF-8');
    $safeService = htmlspecialchars($service, ENT_QUOTES, 'UTF-8');
    $safeSource = htmlspecialchars($pageSource, ENT_QUOTES, 'UTF-8');
    $safeMessage = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));

    // Gorgeously Aligned, Mobile-Responsive HTML Email Template
    $htmlEmail = "
<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>
<html xmlns='http://www.w3.org/1999/xhtml'>
<head>
    <meta http-equiv='Content-Type' content='text/html; charset=UTF-8' />
    <meta name='viewport' content='width=device-width, initial-scale=1.0' />
    <title>New Website Inquiry #{$insertId}</title>
</head>
<body style='margin: 0; padding: 0; background-color: #0b1329; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;'>
    <table border='0' cellpadding='0' cellspacing='0' width='100%' style='table-layout: fixed; background-color: #0b1329; padding: 25px 10px;'>
        <tr>
            <td align='center'>
                
                <!-- Main Container Box (620px) -->
                <table border='0' cellpadding='0' cellspacing='0' width='100%' style='max-width: 620px; background-color: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.35); border: 1px solid #1e293b;'>
                    
                    <!-- Header Banner -->
                    <tr>
                        <td style='background: linear-gradient(135deg, #071a3d 0%, #0d2857 100%); padding: 28px 24px; text-align: center; border-bottom: 4px solid #facc15;'>
                            <table border='0' cellpadding='0' cellspacing='0' width='100%'>
                                <tr>
                                    <td align='center'>
                                        <h1 style='margin: 0; font-size: 24px; font-weight: 800; letter-spacing: 1px; color: #ffffff;'>
                                            JOULE <span style='color: #facc15;'>ASSET VALUERS</span>
                                        </h1>
                                        <p style='margin: 6px 0 0; font-size: 13px; color: #cbd5e1; font-weight: 600; letter-spacing: 0.5px; text-transform: uppercase;'>
                                            Chartered Engineers &bull; Plant &bull; Machinery Valuers
                                        </p>
                                        <div style='margin-top: 14px; display: inline-block; background-color: rgba(250, 204, 21, 0.15); border: 1px solid #facc15; padding: 5px 14px; border-radius: 20px; font-size: 12px; font-weight: 700; color: #facc15;'>
                                            &star; NEW WEBSITE INQUIRY RECEIVED &star;
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Status Info Pill -->
                    <tr>
                        <td style='background-color: #f8fafc; padding: 14px 24px; border-bottom: 1px solid #e2e8f0;'>
                            <table border='0' cellpadding='0' cellspacing='0' width='100%'>
                                <tr>
                                    <td style='font-size: 13px; color: #475569;'>
                                        Inquiry Reference: <strong style='color: #071a3d; font-size: 14px;'>#{$insertId}</strong>
                                    </td>
                                    <td align='right' style='font-size: 13px; color: #64748b;'>
                                        Received: <strong style='color: #0f172a;'>{$displayDate}</strong>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Client Details Table -->
                    <tr>
                        <td style='padding: 24px;'>
                            <table border='0' cellpadding='0' cellspacing='0' width='100%' style='border-collapse: separate; border-spacing: 0; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;'>
                                
                                <!-- Client Name -->
                                <tr>
                                    <td width='34%' style='padding: 12px 16px; background-color: #f8fafc; border-bottom: 1px solid #e2e8f0; font-size: 13px; font-weight: 700; color: #475569; text-transform: uppercase;'>
                                        Client Name
                                    </td>
                                    <td width='66%' style='padding: 12px 16px; background-color: #ffffff; border-bottom: 1px solid #e2e8f0; font-size: 15px; font-weight: 700; color: #071a3d;'>
                                        {$safeName}
                                    </td>
                                </tr>

                                <!-- Phone Number -->
                                <tr>
                                    <td style='padding: 12px 16px; background-color: #f8fafc; border-bottom: 1px solid #e2e8f0; font-size: 13px; font-weight: 700; color: #475569; text-transform: uppercase;'>
                                        Phone Number
                                    </td>
                                    <td style='padding: 12px 16px; background-color: #ffffff; border-bottom: 1px solid #e2e8f0; font-size: 15px; font-weight: 800; color: #071a3d;'>
                                        <a href='tel:{$phone}' style='color: #0284c7; text-decoration: none;'>&#128222; {$safePhone}</a>
                                    </td>
                                </tr>

                                <!-- Email Address -->
                                <tr>
                                    <td style='padding: 12px 16px; background-color: #f8fafc; border-bottom: 1px solid #e2e8f0; font-size: 13px; font-weight: 700; color: #475569; text-transform: uppercase;'>
                                        Email Address
                                    </td>
                                    <td style='padding: 12px 16px; background-color: #ffffff; border-bottom: 1px solid #e2e8f0; font-size: 14px; color: #071a3d;'>
                                        <a href='mailto:{$email}' style='color: #0284c7; text-decoration: underline;'>&#9993; {$safeEmail}</a>
                                    </td>
                                </tr>

                                <!-- Requested Service -->
                                <tr>
                                    <td style='padding: 12px 16px; background-color: #f8fafc; border-bottom: 1px solid #e2e8f0; font-size: 13px; font-weight: 700; color: #475569; text-transform: uppercase;'>
                                        Requested Service
                                    </td>
                                    <td style='padding: 12px 16px; background-color: #ffffff; border-bottom: 1px solid #e2e8f0; font-size: 14px;'>
                                        <span style='display: inline-block; background-color: #fef3c7; color: #92400e; padding: 4px 10px; border-radius: 4px; font-weight: 700; font-size: 13px; border: 1px solid #fde68a;'>
                                            {$safeService}
                                        </span>
                                    </td>
                                </tr>

                                <!-- Location -->
                                <tr>
                                    <td style='padding: 12px 16px; background-color: #f8fafc; border-bottom: 1px solid #e2e8f0; font-size: 13px; font-weight: 700; color: #475569; text-transform: uppercase;'>
                                        Location / City
                                    </td>
                                    <td style='padding: 12px 16px; background-color: #ffffff; border-bottom: 1px solid #e2e8f0; font-size: 14px; font-weight: 600; color: #0f172a;'>
                                        &#128205; {$safeLocation}
                                    </td>
                                </tr>

                                <!-- Submitted From -->
                                <tr>
                                    <td style='padding: 12px 16px; background-color: #f8fafc; font-size: 13px; font-weight: 700; color: #475569; text-transform: uppercase;'>
                                        Submitted From
                                    </td>
                                    <td style='padding: 12px 16px; background-color: #ffffff; font-size: 13px; color: #64748b;'>
                                        {$safeSource}
                                    </td>
                                </tr>
                            </table>

                            <!-- Message Section -->
                            <div style='margin-top: 22px;'>
                                <table border='0' cellpadding='0' cellspacing='0' width='100%'>
                                    <tr>
                                        <td>
                                            <p style='margin: 0 0 8px; font-size: 13px; font-weight: 700; color: #071a3d; text-transform: uppercase;'>
                                                Client Requirement / Message Details:
                                            </p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style='background-color: #f8fafc; border: 1px solid #cbd5e1; border-left: 4px solid #facc15; border-radius: 6px; padding: 16px; font-size: 14px; line-height: 1.65; color: #1e293b;'>
                                            {$safeMessage}
                                        </td>
                                    </tr>
                                </table>
                            </div>

                            <!-- Fast Action Buttons (Call / Email) -->
                            <div style='margin-top: 26px; text-align: center;'>
                                <table border='0' cellpadding='0' cellspacing='0' width='100%'>
                                    <tr>
                                        <td align='center'>
                                            <table border='0' cellpadding='0' cellspacing='0'>
                                                <tr>
                                                    <td style='padding: 0 8px;'>
                                                        <a href='tel:{$phone}' style='display: inline-block; background-color: #facc15; color: #000000; font-size: 14px; font-weight: 800; padding: 12px 24px; border-radius: 6px; text-decoration: none; box-shadow: 0 4px 12px rgba(250, 204, 21, 0.4);'>
                                                            &#128222; Call Client Now
                                                        </a>
                                                    </td>
                                                    <td style='padding: 0 8px;'>
                                                        <a href='mailto:{$email}?subject=Regarding%20Your%20Inquiry%20-%20Joule%20Asset%20Valuers' style='display: inline-block; background-color: #071a3d; color: #ffffff; font-size: 14px; font-weight: 700; padding: 12px 24px; border-radius: 6px; text-decoration: none; border: 1px solid #facc15; box-shadow: 0 4px 12px rgba(7, 26, 61, 0.3);'>
                                                            &#9993; Reply via Email
                                                        </a>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                            </div>

                        </td>
                    </tr>

                    <!-- Footer Details -->
                    <tr>
                        <td style='background-color: #f1f5f9; padding: 18px 24px; text-align: center; border-top: 1px solid #e2e8f0; font-size: 12px; color: #64748b; line-height: 1.6;'>
                            <strong style='color: #071a3d;'>Joule Asset Valuers &bull; Official Inquiries Portal</strong><br />
                            Recorded automatically into MySQL Database Table: <code>inquiries</code> (#{$insertId})<br />
                            Sender IP: {$ipAddress} &bull; Timestamp: {$displayDate}
                        </td>
                    </tr>
                </table>

            </td>
        </tr>
    </table>
</body>
</html>
    ";

    $mailHeaders  = "MIME-Version: 1.0
";
    $mailHeaders .= "Content-Type: text/html; charset=UTF-8
";
    $mailHeaders .= "From: Joule Asset Valuers <no-reply@jouleassetvaluers.com>
";
    $mailHeaders .= "Reply-To: {$safeName} <{$email}>
";
    $mailHeaders .= "X-Mailer: PHP/" . phpversion();

    // Send email to all three recipients
    foreach ($recipients as $recipient) {
        @mail($recipient, $subject, $htmlEmail, $mailHeaders);
    }

    // 5. Respond with Success
    if (isAjax()) {
        jsonResponse(true, 'Thank you! Your inquiry has been submitted successfully. Our engineering team will contact you shortly.', [
            'inquiry_id' => $insertId
        ]);
    } else {
        header('Location: thank-you.html?status=success&id=' . urlencode((string)$insertId));
        exit;
    }

} catch (PDOException $e) {
    error_log("Database Error in submit_form.php: " . $e->getMessage());

    if (isAjax()) {
        jsonResponse(false, 'Unable to save inquiry. Please contact us directly at +91 6374191927 or info@jouleassetvaluers.com.');
    } else {
        echo "<script>alert('Unable to save inquiry. Please contact us directly at +91 6374191927 or info@jouleassetvaluers.com'); window.history.back();</script>";
        exit;
    }
} catch (Exception $e) {
    error_log("General Error in submit_form.php: " . $e->getMessage());

    if (isAjax()) {
        jsonResponse(false, 'An unexpected error occurred. Please try again or call us at +91 6374191927.');
    } else {
        echo "<script>alert('An unexpected error occurred. Please contact us directly at +91 6374191927.'); window.history.back();</script>";
        exit;
    }
}
