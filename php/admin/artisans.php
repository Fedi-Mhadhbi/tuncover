<?php
// php/admin/artisans.php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403); echo json_encode(['success'=>false,'error'=>'Admin only']); exit;
}

$db = getDB();
$m  = $_SERVER['REQUEST_METHOD'];

// GET — list artisans
if ($m === 'GET') {
    $statut = $_GET['statut'] ?? '';
    $q      = trim($_GET['q'] ?? '');

    $sql = "SELECT ar.id_artisan, ar.specialite, ar.ville, ar.statut_validation, ar.note_moyenne,
                   u.id_utilisateur, u.nom, u.prenom, u.email, u.statut AS user_statut,
                   (SELECT COUNT(*) FROM activites a WHERE a.id_artisan=ar.id_artisan AND a.statut='publie') AS nb_activites,
                   (SELECT COUNT(*) FROM reservations r JOIN activites a ON r.id_activite=a.id_activite WHERE a.id_artisan=ar.id_artisan) AS nb_reservations
            FROM artisans ar
            JOIN utilisateurs u ON ar.id_utilisateur=u.id_utilisateur
            WHERE 1=1";
    $params = [];

    if ($statut) { $sql .= " AND ar.statut_validation=?"; $params[] = $statut; }
    if ($q)      { $sql .= " AND (u.nom LIKE ? OR u.prenom LIKE ? OR ar.specialite LIKE ? OR ar.ville LIKE ?)"; $p="%$q%"; $params[]=$p;$params[]=$p;$params[]=$p;$params[]=$p; }

    $sql .= " ORDER BY ar.id_artisan DESC";
    $s = $db->prepare($sql); $s->execute($params);
    echo json_encode(['success'=>true,'artisans'=>$s->fetchAll()]); exit;
}

// POST — approve or reject
if ($m === 'POST') {
    $d      = json_decode(file_get_contents('php://input'), true) ?? [];
    $id     = (int)($d['id']     ?? 0);
    $statut = $d['statut'] ?? '';

    if (!$id || !in_array($statut, ['valide','rejete'])) {
        echo json_encode(['success'=>false,'error'=>'Invalid params']); exit;
    }
    $db->prepare("UPDATE artisans SET statut_validation=? WHERE id_artisan=?")->execute([$statut,$id]);
    echo json_encode(['success'=>true,'statut'=>$statut]); exit;
}

echo json_encode(['success'=>false,'error'=>'Method not allowed']);