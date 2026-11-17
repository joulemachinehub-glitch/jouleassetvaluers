<?php
/**
 * view_inquiries.php - Secure Admin Viewer for Website Inquiries
 * Accessible only by passing the security secret token: ?key=JouleAsset2026Secure
 */

$secretKey = 'JouleAsset2026Secure';
$providedKey = $_GET['key'] ?? ($_POST['key'] ?? '');

if ($providedKey !== $secretKey) {
    http_response_code(403);
    echo "<!DOCTYPE html><html><head><title>Access Denied</title><style>body{font-family:sans-serif;text-align:center;padding:50px;background:#0a192f;color:#ffffff;}</style></head><body><h2>🔒 Access Restricted</h2><p>Please provide the authorized administrative access key.</p><form method='GET'><input type='password' name='key' placeholder='Enter access key' style='padding:10px;border-radius:6px;border:none;'><button type='submit' style='padding:10px 18px;margin-left:8px;background:#facc15;border:none;border-radius:6px;font-weight:bold;cursor:pointer;'>Login</button></form></body></html>";
    exit;
}

require_once __DIR__ . '/db_config.php';

try {
    $pdo = getDbConnection();
    
    // Check if CSV export requested
    if (isset($_GET['export']) && $_GET['export'] === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=website_inquiries_' . date('Y-m-d') . '.csv');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Date', 'Name', 'Phone', 'Email', 'Location', 'Service', 'Requirement Details', 'Page Source', 'IP Address']);
        $exportStmt = $pdo->query("SELECT id, created_at, name, phone, email, location, service, message, page_source, ip_address FROM website_inquiries ORDER BY id DESC");
        while ($row = $exportStmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, $row);
        }
        fclose($output);
        exit;
    }

    $stmt = $pdo->query("SELECT * FROM website_inquiries ORDER BY id DESC");
    $inquiries = $stmt->fetchAll();
    $totalCount = count($inquiries);
} catch (Exception $e) {
    die("Database Error: " . htmlspecialchars($e->getMessage()));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Website Inquiries Database | Joule Asset Valuers</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #071529; color: #f1f5f9; margin: 0; padding: 24px; }
        .header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .title h1 { margin: 0 0 6px 0; font-size: 1.6rem; color: #facc15; }
        .title p { margin: 0; color: #94a3b8; font-size: 0.9rem; }
        .actions a { background: #facc15; color: #0a192f; text-decoration: none; padding: 10px 18px; border-radius: 8px; font-weight: 700; font-size: 0.9rem; display: inline-flex; align-items: center; gap: 8px; }
        .actions a:hover { background: #ffe066; }
        .card { background: #0d1e36; border: 1px solid #1e293b; border-radius: 12px; overflow: hidden; box-shadow: 0 8px 30px rgba(0,0,0,0.4); }
        .table-responsive { width: 100%; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; text-align: left; font-size: 0.92rem; }
        th { background: #0a192f; color: #facc15; font-weight: 700; padding: 14px 16px; border-bottom: 2px solid #1e293b; white-space: nowrap; }
        td { padding: 14px 16px; border-bottom: 1px solid #1e293b; vertical-align: top; }
        tr:hover { background: rgba(255,255,255,0.02); }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 50px; font-size: 0.78rem; font-weight: 700; background: rgba(250,204,21,0.15); color: #facc15; border: 1px solid rgba(250,204,21,0.3); }
        .contact-info a { color: #f1f5f9; text-decoration: none; }
        .contact-info a:hover { color: #facc15; text-decoration: underline; }
        .msg-box { max-width: 380px; word-break: break-word; line-height: 1.5; color: #cbd5e1; }
        .empty-state { text-align: center; padding: 60px 20px; color: #94a3b8; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">
            <h1><i class="fa-solid fa-database"></i> Website Inquiries Database</h1>
            <p>Joule Asset Valuers &bull; Total Submissions: <strong><?= $totalCount ?></strong> &bull; Database: <code><?= htmlspecialchars(DB_NAME) ?></code></p>
        </div>
        <div class="actions">
            <a href="?key=<?= urlencode($secretKey) ?>&export=csv"><i class="fa-solid fa-file-excel"></i> Export All to CSV</a>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <?php if ($totalCount === 0): ?>
                <div class="empty-state">
                    <i class="fa-solid fa-inbox" style="font-size: 3rem; margin-bottom: 16px; opacity: 0.5;"></i>
                    <h3>No inquiries in database yet</h3>
                    <p>New inquiries submitted from any service page or the contact form will appear here in real time.</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>#ID</th>
                            <th>Date & Time</th>
                            <th>Client Name</th>
                            <th>Phone & Email</th>
                            <th>Location</th>
                            <th>Requested Service</th>
                            <th>Requirement Details</th>
                            <th>Page Source</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inquiries as $row): ?>
                            <tr>
                                <td><strong>#<?= htmlspecialchars($row['id']) ?></strong></td>
                                <td style="white-space: nowrap; color: #94a3b8; font-size: 0.85rem;">
                                    <?= htmlspecialchars(date('d M Y, h:i A', strtotime($row['created_at']))) ?>
                                </td>
                                <td><strong><?= htmlspecialchars($row['name']) ?></strong></td>
                                <td class="contact-info">
                                    <div><a href="tel:<?= htmlspecialchars($row['phone']) ?>"><i class="fa-solid fa-phone" style="font-size: 0.8rem; color: #facc15;"></i> <?= htmlspecialchars($row['phone']) ?></a></div>
                                    <div style="margin-top: 4px;"><a href="mailto:<?= htmlspecialchars($row['email']) ?>"><i class="fa-solid fa-envelope" style="font-size: 0.8rem; color: #94a3b8;"></i> <?= htmlspecialchars($row['email']) ?></a></div>
                                </td>
                                <td><?= htmlspecialchars($row['location'] ?: '—') ?></td>
                                <td><span class="badge"><?= htmlspecialchars($row['service']) ?></span></td>
                                <td><div class="msg-box"><?= nl2br(htmlspecialchars($row['message'])) ?></div></td>
                                <td style="font-size: 0.82rem; color: #94a3b8;"><?= htmlspecialchars($row['page_source'] ?: 'Direct') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
