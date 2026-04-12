<?php
// php/admin/users.php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403); echo json_encode(['success'=>false,'error'=>'Admin only']); exit;
}

$db = getDB();
$m  = $_SERVER['REQUEST_METHOD'];

// GET — list users with filters
if ($m === 'GET') {
    $role   = $_GET['role']   ?? '';
    $statut = $_GET['statut'] ?? '';
    $q      = trim($_GET['q'] ?? '');

    $sql    = "SELECT id_utilisateur, nom, prenom, email, role, statut, telephone, date_inscription FROM utilisateurs WHERE 1=1";
    $params = [];

    if ($role)   { $sql .= " AND role=?";   $params[] = $role; }
    if ($statut) { $sql .= " AND statut=?"; $params[] = $statut; }
    if ($q)      { $sql .= " AND (nom LIKE ? OR prenom LIKE ? OR email LIKE ?)"; $p="%$q%"; $params[]=$p;$params[]=$p;$params[]=$p; }

    $sql .= " ORDER BY date_inscription DESC";
    $s = $db->prepare($sql); $s->execute($params);
    echo json_encode(['success'=>true,'users'=>$s->fetchAll()]); exit;
}

// POST — ban / unban
if ($m === 'POST') {
    $d      = json_decode(file_get_contents('php://input'), true) ?? [];
    $id     = (int)($d['id']     ?? 0);
    $statut = $d['statut'] ?? '';

    if (!$id || !in_array($statut, ['actif','banni'])) {
        echo json_encode(['success'=>false,'error'=>'Invalid params']); exit;
    }
    // Prevent banning yourself
    if ($id === (int)$_SESSION['user_id']) {
        echo json_encode(['success'=>false,'error'=>'Cannot ban yourself']); exit;
    }
    $db->prepare("UPDATE utilisateurs SET statut=? WHERE id_utilisateur=?")->execute([$statut,$id]);
    echo json_encode(['success'=>true,'statut'=>$statut]); exit;
}

// DELETE — remove user
if ($m === 'DELETE') {
    $d  = json_decode(file_get_contents('php://input'), true) ?? [];
    $id = (int)($d['id'] ?? 0);
    if (!$id) { echo json_encode(['success'=>false,'error'=>'ID required']); exit; }
    if ($id === (int)$_SESSION['user_id']) {
        echo json_encode(['success'=>false,'error'=>'Cannot delete yourself']); exit;
    }
    $db->prepare("DELETE FROM utilisateurs WHERE id_utilisateur=?")->execute([$id]);
    echo json_encode(['success'=>true]); exit;
}

echo json_encode(['success'=>false,'error'=>'Method not allowed']);