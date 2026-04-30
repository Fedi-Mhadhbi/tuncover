<?php
// php/public/activities.php — no auth required
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/activity_capacity.php';

$db      = getDB();
ensureActivityCapacityColumns($db);
$q       = trim($_GET['q']         ?? '');
$ville   = trim($_GET['ville']     ?? '');
$cat     = trim($_GET['categorie'] ?? '');
$max_prix = (int)($_GET['max_prix'] ?? 0);
$sort    = trim($_GET['sort'] ?? 'popular');

$sql = "
    SELECT
        a.id_activite, a.titre, a.description, a.prix,
        a.ville, a.duree_heures, a.max_participants, a.image_url,
        COALESCE(a.capacite, a.max_participants, 1) AS capacite,
        COALESCE(a.places_reservees, 0) AS places_reservees,
        GREATEST(COALESCE(a.capacite, a.max_participants, 1) - COALESCE(a.places_reservees, 0), 0) AS places_restantes,
        COALESCE(a.availability_status, 'disponible') AS availability_status,
        c.nom AS categorie,
        u.prenom AS artisan_prenom, u.nom AS artisan_nom,
        u.id_utilisateur AS artisan_user_id,
        ar.specialite AS artisan_specialite, ar.id_artisan, ar.avatar_url,
        COALESCE(AVG(av.note),0) AS avg_rating,
        COUNT(DISTINCT av.id_avis) AS nb_reviews,
        COUNT(DISTINCT r.id_reservation) AS nb_bookings,
        (
            (COUNT(DISTINCT r.id_reservation) * 0.5) +
            (COALESCE(AVG(av.note),0) * 0.3) +
            (
                CASE
                    WHEN COALESCE(a.capacite, a.max_participants, 1) > 0
                    THEN (COALESCE(a.places_reservees, 0) / COALESCE(a.capacite, a.max_participants, 1)) * 100 * 0.2
                    ELSE 0
                END
            )
        ) AS popularity_score
    FROM activites a
    LEFT JOIN categories c  ON a.id_categorie    = c.id_categorie
    JOIN  artisans ar        ON a.id_artisan      = ar.id_artisan
    JOIN  utilisateurs u     ON ar.id_utilisateur = u.id_utilisateur
    LEFT JOIN avis av        ON av.id_activite    = a.id_activite
    LEFT JOIN reservations r ON r.id_activite     = a.id_activite AND r.statut != 'annulee'
    WHERE a.statut IN ('publie', 'published')
      AND (ar.statut_validation = 'valide' OR ar.statut_validation = 'en_attente' OR ar.statut_validation IS NULL)
";
$params = [];
if ($q)       { $p="%$q%"; $sql.=" AND (a.titre LIKE ? OR a.description LIKE ? OR a.ville LIKE ? OR u.nom LIKE ? OR u.prenom LIKE ? OR c.nom LIKE ?)"; $params=array_merge($params,[$p,$p,$p,$p,$p,$p]); }
if ($ville)   { $sql.=" AND a.ville=?"; $params[]=$ville; }
if ($cat)     { $sql.=" AND c.nom LIKE ?"; $params[]="%$cat%"; }
if ($max_prix){ $sql.=" AND a.prix<=?"; $params[]=$max_prix; }
$sql .= " GROUP BY a.id_activite";

if ($sort === 'price_asc') {
    $sql .= " ORDER BY a.prix ASC, a.id_activite DESC";
} elseif ($sort === 'price_desc') {
    $sql .= " ORDER BY a.prix DESC, a.id_activite DESC";
} elseif ($sort === 'rating') {
    $sql .= " ORDER BY avg_rating DESC, nb_reviews DESC, a.id_activite DESC";
} else {
    $sql .= " ORDER BY popularity_score DESC, nb_bookings DESC, a.id_activite DESC";
}

try {
    $s=$db->prepare($sql); $s->execute($params);
    echo json_encode(['success'=>true,'activities'=>$s->fetchAll()]);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}
