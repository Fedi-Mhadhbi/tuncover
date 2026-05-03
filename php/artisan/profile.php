<?php

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/../config/db.php';

$contentType = (string)($_SERVER['CONTENT_TYPE'] ?? '');
$isMultipart = stripos($contentType, 'multipart/form-data') !== false;
$payload = $isMultipart ? $_POST : json_decode(file_get_contents('php://input'), true);

if (!is_array($payload)) {
    echo json_encode(['success' => false, 'error' => 'Invalid body']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$nom = trim((string)($payload['nom'] ?? ''));
$prenom = trim((string)($payload['prenom'] ?? ''));
$specialite = trim((string)($payload['specialite'] ?? ''));
$ville = trim((string)($payload['ville'] ?? ''));
$bio = trim((string)($payload['bio'] ?? ''));
$avatarUrl = trim((string)($payload['avatar_url'] ?? ''));

if ($nom === '' || $prenom === '') {
    echo json_encode(['success' => false, 'error' => 'First and last name are required']);
    exit;
}

try {
    $db = getDB();
    $db->beginTransaction();

    $artisanStmt = $db->prepare("SELECT id_artisan, avatar_url FROM artisans WHERE id_utilisateur = ? LIMIT 1");
    $artisanStmt->execute([$userId]);
    $artisan = $artisanStmt->fetch(PDO::FETCH_ASSOC);

    if (!$artisan) {
        $db->rollBack();
        echo json_encode(['success' => false, 'error' => 'Artisan profile not found']);
        exit;
    }

    $artisanId = (int)$artisan['id_artisan'];
    $existingAvatar = (string)($artisan['avatar_url'] ?? '');

    if ($isMultipart && isset($_FILES['avatar_file']) && is_array($_FILES['avatar_file'])) {
        $file = $_FILES['avatar_file'];
        $err = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($err !== UPLOAD_ERR_NO_FILE) {
            if ($err !== UPLOAD_ERR_OK) {
                throw new RuntimeException('Avatar upload failed');
            }

            $tmp = (string)($file['tmp_name'] ?? '');
            if (!is_uploaded_file($tmp)) {
                throw new RuntimeException('Invalid uploaded avatar');
            }

            $size = (int)($file['size'] ?? 0);
            if ($size <= 0 || $size > 10 * 1024 * 1024) {
                throw new RuntimeException('Avatar must be under 10MB');
            }

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = $finfo ? (string)finfo_file($finfo, $tmp) : '';
            if ($finfo) {
                finfo_close($finfo);
            }

            $extMap = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
                'image/gif' => 'gif',
            ];

            if (!isset($extMap[$mime])) {
                throw new RuntimeException('Unsupported avatar format');
            }

            $baseDir = dirname(__DIR__, 2) . '/uploads/avatars/artisan_' . $artisanId;
            if (!is_dir($baseDir) && !mkdir($baseDir, 0777, true) && !is_dir($baseDir)) {
                throw new RuntimeException('Could not create avatar folder');
            }

            $fileName = date('YmdHis') . '_' . bin2hex(random_bytes(6)) . '.' . $extMap[$mime];
            $target = $baseDir . '/' . $fileName;
            if (!move_uploaded_file($tmp, $target)) {
                throw new RuntimeException('Could not save avatar');
            }

            $avatarUrl = 'uploads/avatars/artisan_' . $artisanId . '/' . $fileName;

            if ($existingAvatar !== '' && str_starts_with($existingAvatar, 'uploads/avatars/')) {
                $oldPath = dirname(__DIR__, 2) . '/' . ltrim(str_replace('\\', '/', $existingAvatar), '/');
                if (is_file($oldPath)) {
                    @unlink($oldPath);
                }
            }
        }
    }

    $userUpdate = $db->prepare("
        UPDATE utilisateurs
        SET nom = ?, prenom = ?
        WHERE id_utilisateur = ?
    ");
    $userUpdate->execute([$nom, $prenom, $userId]);

    $artisanUpdate = $db->prepare("
        UPDATE artisans
        SET specialite = ?, ville = ?, bio = ?, avatar_url = ?
        WHERE id_utilisateur = ?
    ");
    $artisanUpdate->execute([$specialite, $ville, $bio, ($avatarUrl !== '' ? $avatarUrl : null), $userId]);

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully',
        'user' => [
            'nom' => $nom,
            'prenom' => $prenom,
            'specialite' => $specialite,
            'ville' => $ville,
            'bio' => $bio,
            'avatar_url' => $avatarUrl,
            'id_artisan' => $artisanId,
        ],
    ]);
} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
