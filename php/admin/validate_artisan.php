<?php
// ================================================================
// /var/www/tuncover/php/admin/validate_artisan.php
// Admin: approve or reject an artisan
// ================================================================
// USAGE: POST { "id": 2, "statut": "valide" }  or "rejete"

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success'=>false,'error'=>'Admin access required']);
    exit;
}

$data   = json_decode(file_get_contents('php://input'), true);
$id     = (int)($data['id']     ?? 0);
$statut = $data['statut'] ?? '';

if (!$id || !in_array($statut, ['valide','rejete'])) {
    echo json_encode(['success'=>false,'error'=>'Invalid parameters']);
    exit;
}

try {
    $db   = getDB();
    $stmt = $db->prepare("UPDATE artisans SET statut_validation = ? WHERE id_artisan = ?");
    $stmt->execute([$statut, $id]);
    echo json_encode(['success'=>true, 'statut'=>$statut]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>'Database error']);
}