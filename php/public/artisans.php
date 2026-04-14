<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

$db = getDB();
$q  = trim($_GET['q'] ?? '');

try {
    $sql = "
        SELECT
            ar.id_artisan,
            ar.specialite,
            ar.ville,
            ar.bio,
            ar.note_moyenne,
            ar.avatar_url,
            u.prenom,
            u.nom,
            (
                SELECT COUNT(*)
                FROM activites a
                WHERE a.id_artisan = ar.id_artisan
                  AND a.statut = 'publie'
            ) AS nb_activites,
            (
                SELECT COUNT(*)
                FROM avis av
                JOIN activites a ON a.id_activite = av.id_activite
                WHERE a.id_artisan = ar.id_artisan
            ) AS nb_reviews,
            (
                SELECT a.image_url
                FROM activites a
                WHERE a.id_artisan = ar.id_artisan
                  AND a.statut = 'publie'
                  AND a.image_url IS NOT NULL
                ORDER BY a.date_creation DESC, a.id_activite DESC
                LIMIT 1
            ) AS cover_url
        FROM artisans ar
        JOIN utilisateurs u ON ar.id_utilisateur = u.id_utilisateur
        WHERE ar.statut_validation = 'valide'
          AND u.statut = 'actif'
    ";
    $params = [];

    if ($q !== '') {
        $like = "%$q%";
        $sql .= " AND (u.nom LIKE ? OR u.prenom LIKE ? OR ar.specialite LIKE ? OR ar.ville LIKE ?)";
        $params = [$like, $like, $like, $like];
    }

    $sql .= " ORDER BY nb_reviews DESC, ar.note_moyenne DESC, ar.id_artisan DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    echo json_encode([
        'success' => true,
        'artisans' => $stmt->fetchAll(),
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Could not load artisans',
    ]);
}
