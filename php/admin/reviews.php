<?php
// php/admin/reviews.php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403); echo json_encode(['success'=>false,'error'=>'Admin only']); exit;
}

$db = getDB();
$m  = $_SERVER['REQUEST_METHOD'];

if ($m === 'GET') {
    $s = $db->query("SELECT av.id_avis, av.note, av.commentaire, av.date_avis,
                            a.titre AS activite_titre,
                            CONCAT(u.prenom,' ',u.nom) AS tourist_nom
                     FROM avis av
                     JOIN activites a ON av.id_activite=a.id_activite
                     JOIN utilisateurs u ON av.id_touriste=u.id_utilisateur
                     ORDER BY av.date_avis DESC LIMIT 100");
    echo json_encode(['success'=>true,'reviews'=>$s->fetchAll()]); exit;
}

if ($m === 'DELETE') {
    $d  = json_decode(file_get_contents('php://input'), true) ?? [];
    $id = (int)($d['id'] ?? 0);
    if (!$id) { echo json_encode(['success'=>false,'error'=>'ID required']); exit; }
    $db->prepare("DELETE FROM avis WHERE id_avis=?")->execute([$id]);
    echo json_encode(['success'=>true]); exit;
}

echo json_encode(['success'=>false,'error'=>'Method not allowed']);