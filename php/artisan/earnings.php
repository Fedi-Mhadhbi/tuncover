<?php
// php/artisan/earnings.php
header('Content-Type: application/json; charset=utf-8');
if(session_status()===PHP_SESSION_NONE) session_start();
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/payments.php';

if(!isset($_SESSION['user_id'])){echo json_encode(['success'=>false,'error'=>'Not authenticated']);exit;}

$db=$db=getDB();
ensurePaymentsTable($db);
$as=$db->prepare("SELECT id_artisan FROM artisans WHERE id_utilisateur=? LIMIT 1");
$as->execute([$_SESSION['user_id']]);
$art=$as->fetch();
if(!$art){echo json_encode(['success'=>false,'error'=>'Artisan not found']);exit;}
$artId=$art['id_artisan'];

// This month revenue
$mon=$db->prepare("SELECT COALESCE(SUM(p.artisan_amount),0) FROM payments p WHERE p.id_artisan=? AND p.payment_status='completed' AND MONTH(p.paid_at)=MONTH(NOW()) AND YEAR(p.paid_at)=YEAR(NOW())");
$mon->execute([$artId]); $thisMonth=(float)$mon->fetchColumn();

// Last month
$lm=$db->prepare("SELECT COALESCE(SUM(p.artisan_amount),0) FROM payments p WHERE p.id_artisan=? AND p.payment_status='completed' AND MONTH(p.paid_at)=MONTH(DATE_SUB(NOW(),INTERVAL 1 MONTH)) AND YEAR(p.paid_at)=YEAR(DATE_SUB(NOW(),INTERVAL 1 MONTH))");
$lm->execute([$artId]); $lastMonth=(float)$lm->fetchColumn();

// Total all time
$tot=$db->prepare("SELECT COALESCE(SUM(p.artisan_amount),0) FROM payments p WHERE p.id_artisan=? AND p.payment_status='completed'");
$tot->execute([$artId]); $total=(float)$tot->fetchColumn();

// Pending payout (unpaid reservations)
$pend=$db->prepare("
    SELECT COALESCE(SUM(r.montant_total * 0.9),0)
    FROM reservations r
    JOIN activites a ON r.id_activite=a.id_activite
    LEFT JOIN payments p ON p.id_reservation=r.id_reservation AND p.payment_status='completed'
    WHERE a.id_artisan=? AND r.statut != 'annulee' AND p.id_payment IS NULL
");
$pend->execute([$artId]); $pending=(float)$pend->fetchColumn();

// Rating
$rat=$db->prepare("SELECT COALESCE(ROUND(AVG(av.note),1),0) AS avg_note, COUNT(*) AS total FROM avis av JOIN activites a ON av.id_activite=a.id_activite WHERE a.id_artisan=?");
$rat->execute([$artId]); $rating=$rat->fetch();

// Recent transactions (last 20)
$tr=$db->prepare("
    SELECT
        r.id_reservation,
        p.paid_at AS date_reservation,
        p.amount AS montant_total,
        p.platform_fee,
        p.artisan_amount,
        p.payment_status AS statut,
        p.transaction_ref,
        a.titre AS activite,
        CONCAT(u.prenom,' ',u.nom) AS tourist
    FROM payments p
    JOIN reservations r ON p.id_reservation=r.id_reservation
    JOIN activites a ON r.id_activite=a.id_activite
    JOIN utilisateurs u ON r.id_touriste=u.id_utilisateur
    WHERE p.id_artisan=? AND p.payment_status='completed'
    ORDER BY p.paid_at DESC
    LIMIT 20
");
$tr->execute([$artId]);

echo json_encode([
    'success'      => true,
    'stats'        => [
        'this_month'   => $thisMonth,
        'last_month'   => $lastMonth,
        'total'        => $total,
        'pending'      => $pending,
        'avg_rating'   => (float)$rating['avg_note'],
        'total_reviews'=> (int)$rating['total'],
    ],
    'transactions' => $tr->fetchAll(),
]);
