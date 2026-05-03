<?php
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/engagement.php';

function portfolio_error(string $msg, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $msg]);
    exit;
}

function is_local_portfolio_path(string $path): bool {
    if ($path === '' || str_contains($path, '..')) return false;
    return (bool)preg_match('#^uploads/portfolio/[A-Za-z0-9_\-./]+$#', $path);
}

function delete_local_portfolio_file(string $path): void {
    if (!is_local_portfolio_path($path)) return;
    $full = dirname(__DIR__, 2) . '/' . str_replace('\\', '/', ltrim($path, '/'));
    if (is_file($full)) {
        @unlink($full);
    }
}

try {
    $db = getDB();
    ensureArtisanPortfolioTable($db);

    $artisanStmt = $db->prepare("
        SELECT ar.id_artisan, u.role, u.statut
        FROM utilisateurs u
        LEFT JOIN artisans ar ON ar.id_utilisateur = u.id_utilisateur
        WHERE u.id_utilisateur = ?
        LIMIT 1
    ");
    $artisanStmt->execute([(int)$_SESSION['user_id']]);
    $me = $artisanStmt->fetch();

    if (!$me || ($me['statut'] ?? '') !== 'actif') {
        portfolio_error('Account not active', 403);
    }
    if (($me['role'] ?? '') !== 'artisan' || empty($me['id_artisan'])) {
        portfolio_error('Only artisans can manage portfolio', 403);
    }
    $artisanId = (int)$me['id_artisan'];

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $db->prepare("
            SELECT id_portfolio, id_artisan, album_key, album_title, title, media_type, media_url, thumbnail_url, display_order, is_public, created_at
            FROM artisan_portfolio
            WHERE id_artisan = ?
            ORDER BY display_order ASC, id_portfolio DESC
        ");
        $stmt->execute([$artisanId]);
        echo json_encode(['success' => true, 'items' => $stmt->fetchAll()]);
        exit;
    }

    $raw = file_get_contents('php://input') ?: '';
    $in = json_decode($raw, true);
    if (!is_array($in)) $in = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $isMultipart = stripos((string)($_SERVER['CONTENT_TYPE'] ?? ''), 'multipart/form-data') !== false;
        $src = $isMultipart ? $_POST : $in;

        $title = trim((string)($src['title'] ?? ''));
        $albumKey = trim((string)($src['album_key'] ?? ''));
        $albumTitle = trim((string)($src['album_title'] ?? ''));
        $mediaType = strtolower(trim((string)($src['media_type'] ?? 'image')));
        $mediaUrl = trim((string)($src['media_url'] ?? ''));
        $thumb = trim((string)($src['thumbnail_url'] ?? ''));
        $isPublic = isset($src['is_public']) ? ((int)$src['is_public'] === 1 ? 1 : 0) : 1;

        if ($albumTitle === '' && $title === '') {
            portfolio_error('Please provide an album name or item title');
        }
        if ($title !== '' && mb_strlen($title) > 180) {
            portfolio_error('Please provide an item title up to 180 chars');
        }
        if ($albumTitle !== '' && mb_strlen($albumTitle) > 180) {
            portfolio_error('Please provide an album title up to 180 chars');
        }
        if (!in_array($mediaType, ['image', 'video'], true)) {
            portfolio_error('media_type must be image or video');
        }
        if ($albumKey !== '' && !preg_match('/^[A-Za-z0-9_-]{3,120}$/', $albumKey)) {
            portfolio_error('album_key contains invalid characters');
        }
        if ($albumKey === '') {
            $seed = $albumTitle !== '' ? $albumTitle : $title;
            $seed = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $seed), '-'));
            $albumKey = $seed !== '' ? $seed . '-' . substr(bin2hex(random_bytes(3)), 0, 6) : 'album-' . substr(bin2hex(random_bytes(4)), 0, 8);
        }
        if ($albumTitle === '') {
            $albumTitle = $title !== '' ? $title : 'Portfolio Album';
        }
        if ($title === '') {
            $title = $albumTitle;
        }

        // Optional desktop upload (preferred when provided)
        if ($isMultipart && isset($_FILES['media_file']) && is_array($_FILES['media_file'])) {
            $f = $_FILES['media_file'];
            $err = (int)($f['error'] ?? UPLOAD_ERR_NO_FILE);
            if ($err !== UPLOAD_ERR_NO_FILE) {
                if ($err !== UPLOAD_ERR_OK) {
                    portfolio_error('Upload failed. Please try another file');
                }
                $size = (int)($f['size'] ?? 0);
                if ($size <= 0 || $size > 50 * 1024 * 1024) {
                    portfolio_error('File must be between 1 byte and 50MB');
                }

                $tmp = (string)($f['tmp_name'] ?? '');
                if (!is_uploaded_file($tmp)) {
                    portfolio_error('Invalid uploaded file');
                }

                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = $finfo ? (string)finfo_file($finfo, $tmp) : '';
                if ($finfo) finfo_close($finfo);

                $extMap = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'webp',
                    'image/gif' => 'gif',
                    'video/mp4' => 'mp4',
                    'video/webm' => 'webm',
                    'video/ogg' => 'ogv',
                    'video/quicktime' => 'mov',
                ];
                if (!isset($extMap[$mime])) {
                    portfolio_error('Unsupported file type. Use JPG, PNG, WEBP, GIF, MP4, WEBM, OGG or MOV');
                }

                if (str_starts_with($mime, 'video/')) $mediaType = 'video';
                if (str_starts_with($mime, 'image/')) $mediaType = 'image';

                $baseDir = dirname(__DIR__, 2) . '/uploads/portfolio/artisan_' . $artisanId;
                if (!is_dir($baseDir) && !mkdir($baseDir, 0777, true) && !is_dir($baseDir)) {
                    portfolio_error('Could not create upload folder', 500);
                }

                $fileName = date('YmdHis') . '_' . bin2hex(random_bytes(6)) . '.' . $extMap[$mime];
                $target = $baseDir . '/' . $fileName;
                if (!move_uploaded_file($tmp, $target)) {
                    portfolio_error('Failed to save uploaded file', 500);
                }
                $mediaUrl = 'uploads/portfolio/artisan_' . $artisanId . '/' . $fileName;
            }
        }

        if ($mediaUrl === '' || mb_strlen($mediaUrl) > 600) {
            portfolio_error('Please upload a file or provide a valid media URL');
        }
        $isRemoteUrl = (bool)filter_var($mediaUrl, FILTER_VALIDATE_URL);
        $isLocalPath = is_local_portfolio_path($mediaUrl);
        if (!$isRemoteUrl && !$isLocalPath) {
            portfolio_error('media_url must be a valid URL or uploaded file path');
        }
        if ($thumb !== '' && (!filter_var($thumb, FILTER_VALIDATE_URL) || mb_strlen($thumb) > 600)) {
            portfolio_error('thumbnail_url must be a valid URL');
        }

        $nextOrderStmt = $db->prepare("SELECT COALESCE(MAX(display_order), 0) + 1 FROM artisan_portfolio WHERE id_artisan = ?");
        $nextOrderStmt->execute([$artisanId]);
        $order = (int)$nextOrderStmt->fetchColumn();

        $ins = $db->prepare("
            INSERT INTO artisan_portfolio (id_artisan, album_key, album_title, title, media_type, media_url, thumbnail_url, display_order, is_public, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $ins->execute([$artisanId, $albumKey, $albumTitle, $title, $mediaType, $mediaUrl, ($thumb !== '' ? $thumb : null), $order, $isPublic]);

        echo json_encode([
            'success' => true,
            'id_portfolio' => (int)$db->lastInsertId(),
            'album_key' => $albumKey,
            'album_title' => $albumTitle,
        ]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        $id = (int)($in['id_portfolio'] ?? 0);
        if ($id <= 0) portfolio_error('id_portfolio is required');

        $get = $db->prepare("SELECT media_url, thumbnail_url FROM artisan_portfolio WHERE id_portfolio = ? AND id_artisan = ? LIMIT 1");
        $get->execute([$id, $artisanId]);
        $row = $get->fetch();
        if (!$row) portfolio_error('Portfolio item not found', 404);

        $del = $db->prepare("DELETE FROM artisan_portfolio WHERE id_portfolio = ? AND id_artisan = ? LIMIT 1");
        $del->execute([$id, $artisanId]);
        if ($del->rowCount() < 1) portfolio_error('Portfolio item not found', 404);

        delete_local_portfolio_file((string)($row['media_url'] ?? ''));
        delete_local_portfolio_file((string)($row['thumbnail_url'] ?? ''));

        echo json_encode(['success' => true]);
        exit;
    }

    portfolio_error('Method not allowed', 405);
} catch (Throwable $e) {
    portfolio_error('Server error', 500);
}
