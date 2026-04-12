<?php
// php/public/activity.php — single activity by id
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { echo json_encode(['success'=>false,'error'=>'ID required']); exit; }

$db = getDB();
try {
    $stmt = $db->prepare("
        SELECT a.id_activite, a.titre, a.description, a.prix,
               a.ville, a.duree_heures, a.max_participants, a.image_url,
               c.nom AS categorie,
               u.prenom AS artisan_prenom, u.nom AS artisan_nom,
               u.email AS artisan_email, u.id_utilisateur AS artisan_user_id,
               ar.id_artisan, ar.specialite, ar.bio, ar.note_moyenne, ar.avatar_url,
               COALESCE(AVG(av.note),0) AS avg_rating,
               COUNT(DISTINCT av.id_avis) AS nb_reviews
        FROM activites a
        LEFT JOIN categories c ON a.id_categorie  = c.id_categorie
        JOIN artisans ar        ON a.id_artisan    = ar.id_artisan
        JOIN utilisateurs u     ON ar.id_utilisateur = u.id_utilisateur
        LEFT JOIN avis av       ON av.id_activite  = a.id_activite
        WHERE a.id_activite = ? AND a.statut = 'publie'
        GROUP BY a.id_activite
    ");
    $stmt->execute([$id]);
    $activity = $stmt->fetch();
    if (!$activity) { echo json_encode(['success'=>false,'error'=>'Activity not found']); exit; }

    $revStmt = $db->prepare("
        SELECT av.note, av.commentaire, av.date_avis,
               u.prenom AS tourist_prenom, u.nom AS tourist_nom
        FROM avis av
        JOIN utilisateurs u ON av.id_touriste = u.id_utilisateur
        WHERE av.id_activite = ?
        ORDER BY av.date_avis DESC LIMIT 5
    ");
    $revStmt->execute([$id]);
    $reviews = $revStmt->fetchAll();

    echo json_encode(['success'=>true,'activity'=>$activity,'reviews'=>$reviews]);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}