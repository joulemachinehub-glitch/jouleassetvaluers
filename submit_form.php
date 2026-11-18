<?php
/**
 * JOULE ASSET VALUERS - OFFICIAL CONTACT FORM PROCESSOR
 * Saves inquiry directly to MySQL database and sends notifications to official email addresses
 */

declare(strict_types=1);

// Set response headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');

// Load database configuration
if (file_exists(__DIR__ . '/db_config.php')) {
    require_once __DIR__ . '/db_config.php';
}

if (!defined('DB_HOST'))    define('DB_HOST', 'localhost');
if (!defined('DB_USER'))    define('DB_USER', 'u948237985_JSValuersOffl');
if (!defined('DB_PASS'))    define('DB_PASS', 'Jouleassetvaluers$#2001');
if (!defined('DB_NAME'))    define('DB_NAME', 'u948237985_JSValuersOffl');
if (!defined('DB_PORT'))    define('DB_PORT', 3306);
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

// Helper: Check if request is AJAX
function isAjax(): bool {
    return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
        || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
        || (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false);
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
$name       = trim((string)($data['name'] ?? ''));
$email      = trim((string)($data['email'] ?? ''));
$phone      = trim((string)($data['phone'] ?? ''));
$location   = trim((string)($data['location'] ?? $data['address'] ?? ''));
$service    = trim((string)($data['service'] ?? 'Chartered Engineer & Asset Valuation Consultation'));
$message    = trim((string)($data['message'] ?? ''));
$pageSource = trim((string)($data['page_source'] ?? $_SERVER['HTTP_REFERER'] ?? 'Website Direct'));
$ipAddress  = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
$userAgent  = substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 255);

// Validation
$errors = [];

if (empty($name) || strlen($name) < 2) {
    $errors[] = 'Please enter your full name.';
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'A valid email address is required.';
}

if (empty($phone) || strlen($phone) < 7) {
    $errors[] = 'A valid phone number is required.';
}

if (empty($service)) {
    $service = 'Chartered Engineer & Asset Valuation Consultation';
}

if (empty($message)) {
    $message = 'Inquiry request submitted via website contact form.';
}

// Anti-Spam Security: Math Captcha Verification
$captchaAnswer = trim((string)($data['captcha_answer'] ?? ''));
$captchaHash   = trim((string)($data['captcha_hash'] ?? ''));

if (!empty($captchaHash) && $captchaAnswer !== '') {
    $expectedHash = base64_encode(intval($captchaAnswer) . '_salt_joule_2026');
    if ($captchaHash !== $expectedHash) {
        $errors[] = 'Security math question answered incorrectly. Please enter the correct sum.';
    }
}

// Return validation errors if any
if (!empty($errors)) {
    if (isAjax()) {
        jsonResponse(false, implode(' ', $errors));
    } else {
        echo "<script>alert('" . addslashes(implode('\n', $errors)) . "'); window.history.back();</script>";
        exit;
    }
}

// Honeypot spam check
if (!empty($data['website_url_hp']) || !empty($data['fax_number'])) {
    if (isAjax()) {
        jsonResponse(true, 'Your inquiry has been submitted successfully.', ['redirect' => 'thank-you.html']);
    } else {
        header('Location: thank-you.html');
        exit;
    }
}

// Spam keyword filter
$spamKeywords = [
    'viagra', 'cialis', 'casino', 'poker', 'crypto investment', 'whatsapp me on',
    'buy followers', 'seo audit report for free', 'earn money fast', 'porn', 'xxx',
    'ranking guarantee', 'guaranteed ranking', 'backlink service'
];
$combinedText = strtolower($name . ' ' . $email . ' ' . $phone . ' ' . $location . ' ' . $service . ' ' . $message . ' ' . $userAgent);
foreach ($spamKeywords as $spamWord) {
    if (strpos($combinedText, $spamWord) !== false) {
        if (isAjax()) {
            jsonResponse(true, 'Thank you! Your inquiry has been submitted.', ['redirect' => 'thank-you.html']);
        } else {
            header('Location: thank-you.html');
            exit;
        }
    }
}

// Generate Reference Code
$refCode     = 'JAV-' . date('ymd') . '-' . rand(1000, 9999);
$displayDate = date('d M Y, h:i A') . ' IST';
$safeName    = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
$safeEmail   = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
$safePhone   = htmlspecialchars($phone, ENT_QUOTES, 'UTF-8');
$safeLocation = htmlspecialchars(!empty($location) ? $location : 'Not specified', ENT_QUOTES, 'UTF-8');
$safeService = htmlspecialchars($service, ENT_QUOTES, 'UTF-8');
$safeSource  = htmlspecialchars($pageSource, ENT_QUOTES, 'UTF-8');
$safeMessage = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));

// =========================================================================
// 1. Send Notification Emails to ALL THREE official recipients IMMEDIATELY
// =========================================================================
$recipients = [
    'info@jouleassetvaluers.com',
    'jouleassetvaluers@gmail.com',
    'balamuruganp0405@gmail.com'
];

$subject = "New Inquiry: {$service} - {$name} [{$refCode}]";

// Gorgeously Aligned, Mobile-Responsive HTML Email Template
$htmlEmail = "
<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>
<html xmlns='http://www.w3.org/1999/xhtml'>
<head>
    <meta http-equiv='Content-Type' content='text/html; charset=UTF-8' />
    <meta name='viewport' content='width=device-width, initial-scale=1.0' />
    <title>New Website Inquiry {$refCode}</title>
</head>
<body style='margin: 0; padding: 0; background-color: #0b1329; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif;'>
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
                                        <div style='margin-top: 14px; display: inline-block; background-color: rgba(250, 204, 21, 0.15); border: 1px solid #facc15; padding: 5px 14px; border-radius: 20px; font-size: 12px; font-weight: 700; color: #facc15; letter-spacing: 0.5px;'>
                                            OFFICIAL WEBSITE INQUIRY RECEIVED
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
                                        Inquiry Reference: <strong style='color: #071a3d; font-size: 14px;'>{$refCode}</strong>
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
                            Reference: <code>{$refCode}</code> &bull; Sender IP: {$ipAddress} &bull; Timestamp: {$displayDate}
                        </td>
                    </tr>
                </table>

            </td>
        </tr>
    </table>
</body>
</html>
";

// High-Deliverability Plain-Text Version (Anti-Spam Requirement for SpamAssassin & Gmail)
$plainTextEmail = "=====================================================\n"
    . "JOULE ASSET VALUERS - NEW WEBSITE INQUIRY\n"
    . "=====================================================\n"
    . "Reference Code : {$refCode}\n"
    . "Received On    : {$displayDate}\n\n"
    . "CLIENT DETAILS:\n"
    . "-----------------------------------------------------\n"
    . "Client Name    : {$name}\n"
    . "Phone Number   : {$phone}\n"
    . "Email Address  : {$email}\n"
    . "Requested Service: {$service}\n"
    . "Location / City: " . (!empty($location) ? $location : 'Not specified') . "\n"
    . "Submitted From : {$pageSource}\n\n"
    . "REQUIREMENT / MESSAGE DETAILS:\n"
    . "-----------------------------------------------------\n"
    . "{$message}\n\n"
    . "=====================================================\n"
    . "Joule Asset Valuers • Registered Chartered Engineers & Valuers\n"
    . "Address: Phase 3, 2nd Floor, Spencer Plaza Mall, S101, Anna Salai, Chennai - 600002\n"
    . "Direct Line: +91 6374191927 | Email: info@jouleassetvaluers.com\n"
    . "Website: https://jouleassetvaluers.com\n"
    . "=====================================================\n";

// Unique MIME Boundary
$mimeBoundary = "==Multipart_Boundary_JAV_" . md5(uniqid((string)time(), true));

// RFC 5322 & RFC 2822 Headers
$rfcDate        = date('r');
$cleanDomain    = 'jouleassetvaluers.com';
$officialSender = 'info@jouleassetvaluers.com';
$msgId          = '<JAV-' . date('YmdHis') . '-' . bin2hex(random_bytes(6)) . '@' . $cleanDomain . '>';
$encodedSubject = "=?UTF-8?B?" . base64_encode($subject) . "?=";

$mailHeaders  = "MIME-Version: 1.0\r\n";
$mailHeaders .= "Date: {$rfcDate}\r\n";
$mailHeaders .= "Message-ID: {$msgId}\r\n";
$mailHeaders .= "From: Joule Asset Valuers <{$officialSender}>\r\n";
$mailHeaders .= "Reply-To: {$safeName} <{$email}>\r\n";
$mailHeaders .= "Return-Path: <{$officialSender}>\r\n";
$mailHeaders .= "Organization: Joule Asset Valuers\r\n";
$mailHeaders .= "X-Sender: {$officialSender}\r\n";
$mailHeaders .= "X-Mailer: JouleAssetValuers Portal\r\n";
$mailHeaders .= "X-Priority: 3 (Normal)\r\n";
$mailHeaders .= "Importance: Normal\r\n";
$mailHeaders .= "Auto-Submitted: auto-generated\r\n";
$mailHeaders .= "X-Auto-Response-Suppress: All\r\n";
$mailHeaders .= "Content-Type: multipart/alternative; boundary=\"{$mimeBoundary}\"\r\n";

// Multipart/Alternative Body Assembly (Plain text first, HTML second)
$fullEmailBody  = "--{$mimeBoundary}\r\n";
$fullEmailBody .= "Content-Type: text/plain; charset=UTF-8\r\n";
$fullEmailBody .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
$fullEmailBody .= $plainTextEmail . "\r\n\r\n";

$fullEmailBody .= "--{$mimeBoundary}\r\n";
$fullEmailBody .= "Content-Type: text/html; charset=UTF-8\r\n";
$fullEmailBody .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
$fullEmailBody .= $htmlEmail . "\r\n\r\n";

$fullEmailBody .= "--{$mimeBoundary}--\r\n";

// Explicit Envelope Sender parameter (-f) guarantees SPF / DMARC domain alignment on Hostinger
$envelopeSender = "-f" . $officialSender;

// Send email to all three recipients with strict SPF alignment
foreach ($recipients as $recipient) {
    @mail($recipient, $encodedSubject, $fullEmailBody, $mailHeaders, $envelopeSender);
}

// =========================================================================
// 2. Database Storage (Safe & Non-blocking)
// =========================================================================
$insertId = 0;
$dbSaved  = false;

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET . ";port=" . DB_PORT;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_TIMEOUT            => 5,
    ];
    if (defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
        $options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES utf8mb4";
    }
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

    // Ensure inquiries table exists
    $createTableSql = "
        CREATE TABLE IF NOT EXISTS inquiries (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(150) NOT NULL,
            email VARCHAR(191) NOT NULL,
            phone VARCHAR(50) NOT NULL,
            location VARCHAR(150) DEFAULT NULL,
            service VARCHAR(191) NOT NULL,
            message TEXT NOT NULL,
            page_source VARCHAR(255) DEFAULT NULL,
            ip_address VARCHAR(45) DEFAULT NULL,
            user_agent VARCHAR(255) DEFAULT NULL,
            status VARCHAR(50) DEFAULT 'New',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $pdo->exec($createTableSql);

    // Insert inquiry into database
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

    $insertId = (int)$pdo->lastInsertId();
    $dbSaved  = true;
} catch (\Throwable $e) {
    // Database hiccups will NEVER block lead delivery or return errors to users
    error_log("Database notice in submit_form.php: " . $e->getMessage());
    $logLine = date('Y-m-d H:i:s') . " | Ref: {$refCode} | Name: {$name} | Phone: {$phone} | Email: {$email} | Service: {$service} | DB Notice: " . $e->getMessage() . "\n";
    @file_put_contents(__DIR__ . '/inquiries_backup.log', $logLine, FILE_APPEND | LOCK_EX);
}

// =========================================================================
// 3. Return Success Response & Redirect to thank-you.html
// =========================================================================
if (isAjax()) {
    jsonResponse(true, 'Thank you! Your inquiry has been submitted successfully. Our engineering team will contact you shortly.', [
        'inquiry_id' => $insertId ?: $refCode,
        'redirect'   => 'thank-you.html'
    ]);
} else {
    header('Location: thank-you.html?status=success&ref=' . urlencode((string)($insertId ?: $refCode)));
    exit;
}
