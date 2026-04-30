<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/activity_capacity.php';

$db = getDB();
ensureActivityCapacityColumns($db);
$q  = trim($_GET['q'] ?? '');

try {
    $sql = "
        SELECT
            ar.id_artisan,
            ar.id_utilisateur AS artisan_user_id,
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
                FROM reservations r
                JOIN activites a ON a.id_activite = r.id_activite
                WHERE a.id_artisan = ar.id_artisan
                  AND r.statut != 'annulee'
            ) AS nb_reservations,
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
            ) AS cover_url,
            (
                (
                    (
                        SELECT COUNT(*)
                        FROM reservations r
                        JOIN activites a ON a.id_activite = r.id_activite
                        WHERE a.id_artisan = ar.id_artisan
                          AND r.statut != 'annulee'
                    ) * 0.5
                ) +
                (
                    COALESCE(ar.note_moyenne, 0) * 0.3
                ) +
                (
                    CASE
                        WHEN (
                            SELECT COALESCE(SUM(COALESCE(a.capacite, a.max_participants, 1)), 0)
                            FROM activites a
                            WHERE a.id_artisan = ar.id_artisan
                              AND a.statut = 'publie'
                        ) > 0
                        THEN (
                            (
                                SELECT COALESCE(SUM(COALESCE(a.places_reservees, 0)), 0)
                                FROM activites a
                                WHERE a.id_artisan = ar.id_artisan
                                  AND a.statut = 'publie'
                            ) /
                            (
                                SELECT COALESCE(SUM(COALESCE(a.capacite, a.max_participants, 1)), 0)
                                FROM activites a
                                WHERE a.id_artisan = ar.id_artisan
                                  AND a.statut = 'publie'
                            )
                        ) * 100 * 0.2
                        ELSE 0
                    END
                )
            ) AS popularity_score
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

    $sql .= " ORDER BY popularity_score DESC, nb_reviews DESC, ar.note_moyenne DESC, ar.id_artisan DESC";

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
