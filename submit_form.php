<?php
// submit_form.php - Handles All Inquiries Across the Website
// Saves inquiry directly to MySQL database and sends notifications to official email addresses

// Enable CORS for same-origin and preflight requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo "Method Not Allowed. Please use POST.";
    exit;
}

require_once __DIR__ . '/db_config.php';

// Accept both JSON payload and regular application/x-www-form-urlencoded / multipart POST
$rawInput = file_get_contents('php://input');
$jsonData = json_decode($rawInput, true);
$data = is_array($jsonData) ? $jsonData : $_POST;

// Determine if the request originated from fetch/AJAX or native form POST
$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
       || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
       || (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false)
       || is_array($jsonData);

$name        = trim($data['name'] ?? '');
$email       = trim($data['email'] ?? '');
$phone       = trim($data['phone'] ?? '');
$location    = trim($data['location'] ?? ($data['address'] ?? ''));
$service     = trim($data['service'] ?? 'General Consultation');
$message     = trim($data['message'] ?? '');
$pageSource  = trim($data['page_source'] ?? ($_SERVER['HTTP_REFERER'] ?? 'Website Form'));
$ipAddress   = $_SERVER['REMOTE_ADDR'] ?? '';
$userAgent   = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);

// Validation
$errors = [];

if (empty($name)) {
    $errors[] = 'Full name is required.';
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'A valid email address is required.';
}

if (empty($phone)) {
    $errors[] = 'Phone number is required.';
}

if (empty($message)) {
    $errors[] = 'Requirement details / message cannot be empty.';
}

if (!empty($errors)) {
    if ($isAjax) {
        header('Content-Type: application/json; charset=UTF-8');
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => implode(' ', $errors),
            'errors'  => $errors
        ]);
    } else {
        echo "<script>alert('" . htmlspecialchars(implode('\n', $errors), ENT_QUOTES) . "'); window.history.back();</script>";
    }
    exit;
}

// ==========================================
// 1. Math Captcha Verification
// ==========================================
$captchaAnswer = trim($data['captcha_answer'] ?? ($data['captcha_input'] ?? ''));
$captchaHash   = trim($data['captcha_hash'] ?? ($data['captcha_expected'] ?? ''));

if (!empty($captchaHash)) {
    $decodedExpected = base64_decode($captchaHash);
    $expectedAnswer = explode('_salt_joule_', $decodedExpected)[0] ?? null;

    if ($expectedAnswer === null || (string)$captchaAnswer !== (string)$expectedAnswer) {
        if ($isAjax) {
            header('Content-Type: application/json; charset=UTF-8');
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Security verification failed: Incorrect math answer. Please solve the calculation to submit.',
                'errors'  => ['Security verification failed.']
            ]);
        } else {
            echo "<script>alert('Security verification failed: Incorrect math answer.'); window.history.back();</script>";
        }
        exit;
    }
}

// ==========================================
// 2. Comprehensive Anti-Spam & Marketing Filter
// ==========================================
// Block when used in unsolicited promotional messages.
// DO NOT save to database and DO NOT send enquiry to email.
$spamKeywords = [
    'seo packages',
    'seo package',
    'seo services',
    'seo service',
    'seo solutions',
    'improve your website\'s online visibility',
    'improve your websites online visibility',
    'improve your website online visibility',
    'online visibility',
    'digital marketing services',
    'digital marketing service',
    'digital marketing',
    'marketing packages',
    'marketing package',
    'website promotion',
    'link building',
    'backlink services',
    'backlink service',
    'backlinks',
    'backlink',
    'guest posting',
    'guest post',
    'guest posts',
    'ranking guarantee',
    'guaranteed ranking',
    'increase website traffic',
    'website traffic',
    'google indexing spam',
    'search index',
    'google search results',
    'feature your website',
    'searchregister.live',
    'searchregister',
    'submit your website today',
    'submit your website',
    'indexing service',
    'indexing services',
    'get indexed today',
    'get indexed',
    'seo and marketing spam',
    'unsolicited promotional',
    'rank on google',
    'first page of google',
    'boost your ranking',
    'boost google ranking'
];

$combinedText = strtolower($name . ' ' . $email . ' ' . $phone . ' ' . $location . ' ' . $service . ' ' . $message . ' ' . $userAgent);

$detectedSpam = null;
foreach ($spamKeywords as $spamWord) {
    if (stripos($combinedText, $spamWord) !== false) {
        $detectedSpam = $spamWord;
        break;
    }
}

if ($detectedSpam !== null) {
    // STRICTLY DO NOT SAVE TO DATABASE
    // STRICTLY DO NOT SEND EMAIL
    if ($isAjax) {
        header('Content-Type: application/json; charset=UTF-8');
        http_response_code(400);
        echo json_encode([
            'success'  => false,
            'blocked'  => true,
            'detected' => $detectedSpam,
            'message'  => "Submission blocked: Promotional or SEO marketing keywords detected ('{$detectedSpam}'). Contact forms are strictly reserved for genuine engineering and valuation inquiries.",
            'errors'   => ['Promotional spam content is prohibited.']
        ]);
    } else {
        echo "<script>alert('Submission blocked: Promotional or SEO keywords detected. Contact forms are strictly reserved for engineering and valuation inquiries.'); window.history.back();</script>";
    }
    exit;
}

// Save to MySQL Database
try {
    $pdo = getDbConnection();

    $stmt = $pdo->prepare("
        INSERT INTO website_inquiries 
        (name, email, phone, location, service, message, page_source, ip_address, user_agent)
        VALUES 
        (:name, :email, :phone, :location, :service, :message, :page_source, :ip_address, :user_agent)
    ");

    $stmt->execute([
        ':name'        => $name,
        ':email'       => $email,
        ':phone'       => $phone,
        ':location'    => $location,
        ':service'     => $service,
        ':message'     => $message,
        ':page_source' => $pageSource,
        ':ip_address'  => $ipAddress,
        ':user_agent'  => $userAgent,
    ]);

    $insertId = $pdo->lastInsertId();

    // Send Notification Email to BOTH recipients
    $recipients = [
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

    $htmlEmail = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>New Inquiry Notification</title>
    </head>
    <body style='font-family: Arial, sans-serif; background-color: #f1f5f9; margin: 0; padding: 20px; color: #334155;'>
        <div style='max-width: 620px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); border: 1px solid #e2e8f0;'>
            
            <!-- Header -->
            <div style='background-color: #0a192f; padding: 24px; text-align: center; border-bottom: 3px solid #0ea5e9;'>
                <h1 style='color: #ffffff; margin: 0; font-size: 22px; letter-spacing: 0.5px;'>JOULE ASSET VALUERS</h1>
                <p style='color: #38bdf8; margin: 6px 0 0; font-size: 13px; font-weight: bold; text-transform: uppercase;'>New Website Service Inquiry</p>
            </div>

            <!-- Body -->
            <div style='padding: 24px;'>
                <div style='background-color: #f8fafc; border-left: 4px solid #0ea5e9; padding: 12px 16px; margin-bottom: 20px;'>
                    <p style='margin: 0; font-size: 14px; color: #64748b;'>
                        Inquiry ID: <strong style='color: #0f172a;'>#{$insertId}</strong> | Received: <strong style='color: #0f172a;'>{$displayDate}</strong>
                    </p>
                </div>
                
                <table style='width: 100%; border-collapse: collapse; font-size: 14px; margin-bottom: 20px;'>
                    <tr>
                        <td style='padding: 10px 12px; border-bottom: 1px solid #e2e8f0; font-weight: bold; color: #475569; width: 35%;'>Client Name:</td>
                        <td style='padding: 10px 12px; border-bottom: 1px solid #e2e8f0; color: #0f172a; font-weight: 600;'>{$safeName}</td>
                    </tr>
                    <tr>
                        <td style='padding: 10px 12px; border-bottom: 1px solid #e2e8f0; font-weight: bold; color: #475569;'>Phone Number:</td>
                        <td style='padding: 10px 12px; border-bottom: 1px solid #e2e8f0; color: #0284c7; font-weight: bold;'>
                            <a href='tel:{$phone}' style='color: #0284c7; text-decoration: none;'>{$safePhone}</a>
                        </td>
                    </tr>
                    <tr>
                        <td style='padding: 10px 12px; border-bottom: 1px solid #e2e8f0; font-weight: bold; color: #475569;'>Email Address:</td>
                        <td style='padding: 10px 12px; border-bottom: 1px solid #e2e8f0;'>
                            <a href='mailto:{$email}' style='color: #0284c7; text-decoration: none;'>{$safeEmail}</a>
                        </td>
                    </tr>
                    <tr>
                        <td style='padding: 10px 12px; border-bottom: 1px solid #e2e8f0; font-weight: bold; color: #475569;'>Requested Service:</td>
                        <td style='padding: 10px 12px; border-bottom: 1px solid #e2e8f0; color: #0f172a; font-weight: 600;'>{$safeService}</td>
                    </tr>
                    <tr>
                        <td style='padding: 10px 12px; border-bottom: 1px solid #e2e8f0; font-weight: bold; color: #475569;'>Location / City:</td>
                        <td style='padding: 10px 12px; border-bottom: 1px solid #e2e8f0; color: #0f172a;'>{$safeLocation}</td>
                    </tr>
                    <tr>
                        <td style='padding: 10px 12px; border-bottom: 1px solid #e2e8f0; font-weight: bold; color: #475569;'>Submitted From:</td>
                        <td style='padding: 10px 12px; border-bottom: 1px solid #e2e8f0; color: #64748b; font-size: 13px;'>{$safeSource}</td>
                    </tr>
                </table>

                <div style='margin-bottom: 24px;'>
                    <h4 style='margin: 0 0 8px; color: #0f172a; font-size: 14px;'>Requirement / Message Details:</h4>
                    <div style='background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 6px; padding: 14px; font-size: 14px; line-height: 1.6; color: #1e293b;'>
                        {$safeMessage}
                    </div>
                </div>

                <div style='text-align: center; margin-top: 24px;'>
                    <a href='tel:{$phone}' style='display: inline-block; background-color: #0ea5e9; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-weight: bold; font-size: 14px; margin-right: 10px;'>Call Client Now</a>
                    <a href='mailto:{$email}' style='display: inline-block; background-color: #0a192f; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-weight: bold; font-size: 14px;'>Reply via Email</a>
                </div>
            </div>

            <!-- Footer -->
            <div style='background-color: #f1f5f9; padding: 16px; text-align: center; font-size: 12px; color: #64748b; border-top: 1px solid #e2e8f0;'>
                Saved automatically to MySQL database (Table: <code>website_inquiries</code>).<br>
                IP Address: {$ipAddress} | Joule Asset Valuers Portal
            </div>
        </div>
    </body>
    </html>
    ";

    $mailHeaders  = "MIME-Version: 1.0\r\n";
    $mailHeaders .= "Content-Type: text/html; charset=UTF-8\r\n";
    $mailHeaders .= "From: Joule Asset Valuers <no-reply@jouleassetvaluers.com>\r\n";
    $mailHeaders .= "Reply-To: {$safeName} <{$email}>\r\n";
    $mailHeaders .= "X-Mailer: PHP/" . phpversion();

    // Send email to both jouleassetvaluers@gmail.com and balamuruganp0405@gmail.com
    foreach ($recipients as $recipient) {
        @mail($recipient, $subject, $htmlEmail, $mailHeaders);
    }

    // Success response
    if ($isAjax) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success'    => true,
            'message'    => 'Thank you! Your inquiry has been submitted and saved successfully.',
            'redirect'   => 'thank-you.html',
            'inquiry_id' => $insertId
        ]);
    } else {
        // Native Form POST redirect
        header('Location: thank-you.html?status=success&id=' . $insertId);
    }
    exit;

} catch (Exception $e) {
    error_log("Database Insert Error: " . $e->getMessage());

    if ($isAjax) {
        header('Content-Type: application/json; charset=UTF-8');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Unable to save inquiry to database. Please check your network or contact us directly at +91 6374191927.'
        ]);
    } else {
        echo "<script>alert('Unable to save inquiry. Please contact us directly at +91 6374191927 or jouleassetvaluers@gmail.com'); window.history.back();</script>";
    }
    exit;
}
