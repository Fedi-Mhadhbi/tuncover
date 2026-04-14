<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/engagement.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$db = getDB();
ensureNotificationsTable($db);

$userId = (int)$_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $limit = max(1, min(100, (int)($_GET['limit'] ?? 30)));
    $stmt = $db->prepare("
        SELECT id_notification, type, title, message, link_url, reference_id, is_read, created_at
        FROM notifications
        WHERE id_utilisateur = ?
        ORDER BY created_at DESC, id_notification DESC
        LIMIT ?
    ");
    $stmt->bindValue(1, $userId, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();

    $unread = $db->prepare("SELECT COUNT(*) FROM notifications WHERE id_utilisateur = ? AND is_read = 0");
    $unread->execute([$userId]);

    echo json_encode([
        'success' => true,
        'unread_count' => (int)$unread->fetchColumn(),
        'notifications' => $stmt->fetchAll(PDO::FETCH_ASSOC),
    ]);
    exit;
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) {
        echo json_encode(['success' => false, 'error' => 'Invalid body']);
        exit;
    }

    $mode = trim((string)($data['mode'] ?? 'single'));
    if ($mode === 'all') {
        $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE id_utilisateur = ?");
        $stmt->execute([$userId]);
        echo json_encode(['success' => true]);
        exit;
    }

    $id = (int)($data['id_notification'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Notification ID required']);
        exit;
    }
    $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE id_notification = ? AND id_utilisateur = ?");
    $stmt->execute([$id, $userId]);
    echo json_encode(['success' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method not allowed']);

