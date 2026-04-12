<?php
// php/admin/activities.php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403); echo json_encode(['success'=>false,'error'=>'Admin only']); exit;
}

$db = getDB();
$m  = $_SERVER['REQUEST_METHOD'];

// GET — list activities
if ($m === 'GET') {
    $statut = $_GET['statut'] ?? '';
    $q      = trim($_GET['q'] ?? '');

    $sql = "SELECT a.id_activite, a.titre, a.prix, a.ville, a.statut, a.date_creation,
                   CONCAT(u.prenom,' ',u.nom) AS artisan_nom,
                   c.nom AS categorie,
                   (SELECT COUNT(*) FROM reservations r WHERE r.id_activite=a.id_activite AND r.statut NOT IN('annulee')) AS nb_reservations
            FROM activites a
            JOIN artisans ar ON a.id_artisan=ar.id_artisan
            JOIN utilisateurs u ON ar.id_utilisateur=u.id_utilisateur
            LEFT JOIN categories c ON a.id_categorie=c.id_categorie
            WHERE a.statut != 'supprime'";
    $params = [];

    if ($statut) { $sql .= " AND a.statut=?"; $params[] = $statut; }
    if ($q)      { $sql .= " AND (a.titre LIKE ? OR a.ville LIKE ? OR u.nom LIKE ?)"; $p="%$q%"; $params[]=$p;$params[]=$p;$params[]=$p; }

    $sql .= " ORDER BY a.date_creation DESC";
    $s = $db->prepare($sql); $s->execute($params);
    echo json_encode(['success'=>true,'activities'=>$s->fetchAll()]); exit;
}

// PUT — toggle status (pause/publish)
if ($m === 'PUT') {
    $d      = json_decode(file_get_contents('php://input'), true) ?? [];
    $id     = (int)($d['id']     ?? 0);
    $statut = $d['statut'] ?? '';

    if (!$id || !in_array($statut, ['publie','brouillon','pause'])) {
        echo json_encode(['success'=>false,'error'=>'Invalid params']); exit;
    }
    $db->prepare("UPDATE activites SET statut=? WHERE id_activite=?")->execute([$statut,$id]);
    echo json_encode(['success'=>true]); exit;
}

// DELETE — soft delete activity
if ($m === 'DELETE') {
    $d  = json_decode(file_get_contents('php://input'), true) ?? [];
    $id = (int)($d['id'] ?? 0);
    if (!$id) { echo json_encode(['success'=>false,'error'=>'ID required']); exit; }
    $db->prepare("UPDATE activites SET statut='supprime' WHERE id_activite=?")->execute([$id]);
    echo json_encode(['success'=>true]); exit;
}

echo json_encode(['success'=>false,'error'=>'Method not allowed']);