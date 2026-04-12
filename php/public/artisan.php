<?php
// php/public/artisan.php — public artisan profile + their activities
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { echo json_encode(['success'=>false,'error'=>'Artisan ID required']); exit; }

$db = getDB();
try {
    // Artisan profile
    $stmt = $db->prepare("
        SELECT ar.id_artisan, ar.specialite, ar.ville, ar.bio,
               ar.statut_validation, ar.note_moyenne, ar.avatar_url,
               u.prenom, u.nom, u.email,
               COUNT(DISTINCT a.id_activite) AS nb_activites,
               COUNT(DISTINCT r.id_reservation) AS nb_reservations,
               COALESCE(AVG(av.note),0) AS avg_rating,
               COUNT(DISTINCT av.id_avis) AS nb_reviews
        FROM artisans ar
        JOIN utilisateurs u ON ar.id_utilisateur = u.id_utilisateur
        LEFT JOIN activites a ON a.id_artisan = ar.id_artisan AND a.statut = 'publie'
        LEFT JOIN reservations r ON r.id_activite = a.id_activite AND r.statut != 'annulee'
        LEFT JOIN avis av ON av.id_activite = a.id_activite
        WHERE ar.id_artisan = ? AND ar.statut_validation = 'valide'
        GROUP BY ar.id_artisan
    ");
    $stmt->execute([$id]);
    $artisan = $stmt->fetch();
    if (!$artisan) { echo json_encode(['success'=>false,'error'=>'Artisan not found']); exit; }

    // Their published activities
    $actStmt = $db->prepare("
        SELECT a.id_activite, a.titre, a.description, a.prix,
               a.ville, a.duree_heures, a.max_participants, a.image_url,
               c.nom AS categorie,
               COALESCE(AVG(av.note),0) AS avg_rating,
               COUNT(DISTINCT av.id_avis) AS nb_reviews
        FROM activites a
        LEFT JOIN categories c ON a.id_categorie = c.id_categorie
        LEFT JOIN avis av ON av.id_activite = a.id_activite
        WHERE a.id_artisan = ? AND a.statut = 'publie'
        GROUP BY a.id_activite
        ORDER BY a.date_creation DESC
    ");
    $actStmt->execute([$id]);
    $activities = $actStmt->fetchAll();

    // Recent reviews across all their activities
    $revStmt = $db->prepare("
        SELECT av.note, av.commentaire, av.date_avis,
               u.prenom AS tourist_prenom, u.nom AS tourist_nom,
               a.titre AS activite_titre
        FROM avis av
        JOIN utilisateurs u ON av.id_touriste = u.id_utilisateur
        JOIN activites a ON av.id_activite = a.id_activite
        WHERE a.id_artisan = ?
        ORDER BY av.date_avis DESC LIMIT 6
    ");
    $revStmt->execute([$id]);
    $reviews = $revStmt->fetchAll();

    echo json_encode([
        'success'    => true,
        'artisan'    => $artisan,
        'activities' => $activities,
        'reviews'    => $reviews
    ]);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}