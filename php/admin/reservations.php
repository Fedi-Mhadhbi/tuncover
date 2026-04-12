<?php
// php/admin/reservations.php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403); echo json_encode(['success'=>false,'error'=>'Admin only']); exit;
}

$db     = getDB();
$statut = $_GET['statut'] ?? '';
$q      = trim($_GET['q'] ?? '');

$sql = "SELECT r.id_reservation, r.date_reservation, r.date_activite, r.nb_participants,
               r.montant_total, r.statut,
               a.titre AS activite_titre, a.ville,
               CONCAT(ut.prenom,' ',ut.nom) AS tourist_nom, ut.email AS tourist_email,
               CONCAT(ua.prenom,' ',ua.nom) AS artisan_nom
        FROM reservations r
        JOIN activites a ON r.id_activite=a.id_activite
        JOIN utilisateurs ut ON r.id_touriste=ut.id_utilisateur
        JOIN artisans ar ON a.id_artisan=ar.id_artisan
        JOIN utilisateurs ua ON ar.id_utilisateur=ua.id_utilisateur
        WHERE 1=1";
$params = [];

if ($statut) { $sql .= " AND r.statut=?"; $params[] = $statut; }
if ($q)      { $sql .= " AND (ut.nom LIKE ? OR ut.prenom LIKE ? OR a.titre LIKE ?)"; $p="%$q%"; $params[]=$p;$params[]=$p;$params[]=$p; }

$sql .= " ORDER BY r.date_reservation DESC LIMIT 100";
$s = $db->prepare($sql); $s->execute($params);
echo json_encode(['success'=>true,'reservations'=>$s->fetchAll()]);