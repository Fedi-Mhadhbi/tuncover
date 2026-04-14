<?php
// php/public/activities.php — no auth required
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

$db      = getDB();
$q       = trim($_GET['q']         ?? '');
$ville   = trim($_GET['ville']     ?? '');
$cat     = trim($_GET['categorie'] ?? '');
$max_prix = (int)($_GET['max_prix'] ?? 0);

$sql = "
    SELECT
        a.id_activite, a.titre, a.description, a.prix,
        a.ville, a.duree_heures, a.max_participants, a.image_url,
        c.nom AS categorie,
        u.prenom AS artisan_prenom, u.nom AS artisan_nom,
        ar.specialite AS artisan_specialite, ar.id_artisan, ar.avatar_url,
        COALESCE(AVG(av.note),0) AS avg_rating,
        COUNT(DISTINCT av.id_avis) AS nb_reviews,
        COUNT(DISTINCT r.id_reservation) AS nb_bookings
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
$sql .= " GROUP BY a.id_activite ORDER BY nb_bookings DESC, a.id_activite DESC";

try {
    $s=$db->prepare($sql); $s->execute($params);
    echo json_encode(['success'=>true,'activities'=>$s->fetchAll()]);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}
