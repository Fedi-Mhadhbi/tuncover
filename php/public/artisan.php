<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/engagement.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Artisan ID required']);
    exit;
}

$db = getDB();
ensureArtisanPortfolioTable($db);

try {
    $artisanStmt = $db->prepare("
        SELECT
            ar.id_artisan,
            ar.id_utilisateur,
            ar.specialite,
            ar.ville,
            ar.bio,
            ar.note_moyenne,
            ar.avatar_url,
            ar.statut_validation,
            u.prenom,
            u.nom,
            u.email,
            COALESCE((
                SELECT AVG(av.note)
                FROM avis av
                JOIN activites a2 ON a2.id_activite = av.id_activite
                WHERE a2.id_artisan = ar.id_artisan
            ), 0) AS avg_rating,
            (
                SELECT COUNT(*)
                FROM activites a2
                WHERE a2.id_artisan = ar.id_artisan
                  AND a2.statut = 'publie'
            ) AS nb_activites,
            (
                SELECT COUNT(*)
                FROM reservations r
                JOIN activites a2 ON a2.id_activite = r.id_activite
                WHERE a2.id_artisan = ar.id_artisan
                  AND a2.statut = 'publie'
                  AND r.statut != 'annulee'
            ) AS nb_reservations,
            (
                SELECT COUNT(*)
                FROM avis av
                JOIN activites a2 ON a2.id_activite = av.id_activite
                WHERE a2.id_artisan = ar.id_artisan
            ) AS nb_reviews
        FROM artisans ar
        JOIN utilisateurs u ON u.id_utilisateur = ar.id_utilisateur
        WHERE ar.id_artisan = ?
          AND ar.statut_validation = 'valide'
          AND u.statut = 'actif'
        LIMIT 1
    ");
    $artisanStmt->execute([$id]);
    $artisan = $artisanStmt->fetch();

    if (!$artisan) {
        echo json_encode(['success' => false, 'error' => 'Artisan not found']);
        exit;
    }

    $activitiesStmt = $db->prepare("
        SELECT
            a.id_activite,
            a.titre,
            a.description,
            a.prix,
            a.ville,
            a.duree_heures,
            a.max_participants,
            a.image_url,
            c.nom AS categorie,
            COALESCE(AVG(av.note), 0) AS avg_rating,
            COUNT(DISTINCT av.id_avis) AS nb_reviews
        FROM activites a
        LEFT JOIN categories c ON c.id_categorie = a.id_categorie
        LEFT JOIN avis av ON av.id_activite = a.id_activite
        WHERE a.id_artisan = ?
          AND a.statut = 'publie'
        GROUP BY
            a.id_activite, a.titre, a.description, a.prix,
            a.ville, a.duree_heures, a.max_participants, a.image_url, c.nom
        ORDER BY a.date_creation DESC, a.id_activite DESC
    ");
    $activitiesStmt->execute([$id]);
    $activities = $activitiesStmt->fetchAll();

    $reviewsStmt = $db->prepare("
        SELECT
            av.note,
            av.commentaire,
            av.date_avis,
            a.titre AS activite_titre,
            u.prenom AS tourist_prenom,
            u.nom AS tourist_nom
        FROM avis av
        JOIN activites a ON a.id_activite = av.id_activite
        JOIN utilisateurs u ON u.id_utilisateur = av.id_touriste
        WHERE a.id_artisan = ?
          AND a.statut = 'publie'
        ORDER BY av.date_avis DESC, av.id_avis DESC
        LIMIT 20
    ");
    $reviewsStmt->execute([$id]);
    $reviews = $reviewsStmt->fetchAll();

    $portfolioStmt = $db->prepare("
        SELECT
            id_portfolio,
            album_key,
            album_title,
            title,
            media_type,
            media_url,
            thumbnail_url,
            display_order,
            created_at
        FROM artisan_portfolio
        WHERE id_artisan = ?
          AND is_public = 1
        ORDER BY display_order ASC, id_portfolio DESC
    ");
    $portfolioStmt->execute([$id]);
    $portfolio = $portfolioStmt->fetchAll();

    echo json_encode([
        'success' => true,
        'artisan' => $artisan,
        'activities' => $activities,
        'reviews' => $reviews,
        'portfolio' => $portfolio,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
