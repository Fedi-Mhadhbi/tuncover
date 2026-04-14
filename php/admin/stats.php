<?php
// php/admin/stats.php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/payments.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success'=>false,'error'=>'Admin only']); exit;
}

$db = getDB();
ensurePaymentsTable($db);

// Users by role
$u = $db->query("SELECT role, COUNT(*) as cnt FROM utilisateurs GROUP BY role")->fetchAll();
$userStats = ['touriste'=>0,'artisan'=>0,'admin'=>0];
foreach ($u as $row) $userStats[$row['role']] = (int)$row['cnt'];

// Pending artisans
$pending = (int)$db->query("SELECT COUNT(*) FROM artisans WHERE statut_validation='en_attente'")->fetchColumn();

// Reservations
$totalRes  = (int)$db->query("SELECT COUNT(*) FROM reservations")->fetchColumn();
$monthRes  = (int)$db->query("SELECT COUNT(*) FROM reservations WHERE MONTH(date_reservation)=MONTH(NOW()) AND YEAR(date_reservation)=YEAR(NOW())")->fetchColumn();
$cancelled = (int)$db->query("SELECT COUNT(*) FROM reservations WHERE statut='annulee'")->fetchColumn();
$cancRate  = $totalRes > 0 ? round($cancelled / $totalRes * 100) : 0;

// Activities
$totalAct  = (int)$db->query("SELECT COUNT(*) FROM activites WHERE statut='publie'")->fetchColumn();
$pendingAct= (int)$db->query("SELECT COUNT(*) FROM activites WHERE statut='brouillon'")->fetchColumn();

// Revenue — use all non-cancelled reservations (not just 'terminee' since that status may not be used yet)
$revenue      = (float)$db->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE payment_status='completed'")->fetchColumn();
$monthRevenue = (float)$db->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE payment_status='completed' AND MONTH(paid_at)=MONTH(NOW()) AND YEAR(paid_at)=YEAR(NOW())")->fetchColumn();
$totalPaid    = (int)$db->query("SELECT COUNT(*) FROM payments WHERE payment_status='completed'")->fetchColumn();
$avgBooking   = $totalPaid > 0 ? round($revenue / $totalPaid, 0) : 0;

// Platform rating (average of all reviews)
$ratingRow   = $db->query("SELECT COALESCE(AVG(note),0) AS avg_note, COUNT(*) AS total FROM avis")->fetch();
$avgRating   = round((float)$ratingRow['avg_note'], 1);
$totalReviews= (int)$ratingRow['total'];

// Bookings by region (top 6)
$regions = $db->query("
    SELECT a.ville, COUNT(*) as cnt
    FROM reservations r
    JOIN activites a ON r.id_activite=a.id_activite
    WHERE r.statut != 'annulee'
    GROUP BY a.ville
    ORDER BY cnt DESC LIMIT 6
")->fetchAll();

// Bookings by category (top 6)
$categories = $db->query("
    SELECT COALESCE(c.nom,'Other') AS nom, COUNT(*) as cnt
    FROM reservations r
    JOIN activites a ON r.id_activite=a.id_activite
    LEFT JOIN categories c ON a.id_categorie=c.id_categorie
    WHERE r.statut != 'annulee'
    GROUP BY c.nom
    ORDER BY cnt DESC LIMIT 6
")->fetchAll();

// Activities by category (when no reservations yet, show activity counts)
$activitiesByCategory = $db->query("
    SELECT COALESCE(c.nom,'Other') AS nom, COUNT(*) as cnt
    FROM activites a
    LEFT JOIN categories c ON a.id_categorie=c.id_categorie
    WHERE a.statut='publie'
    GROUP BY c.nom
    ORDER BY cnt DESC LIMIT 6
")->fetchAll();

// Top artisans by bookings
$topArtisans = $db->query("
    SELECT CONCAT(u.prenom,' ',u.nom) AS nom,
           COUNT(p.id_payment) AS bookings,
           COALESCE(SUM(p.amount),0) AS revenue
    FROM payments p
    JOIN reservations r ON p.id_reservation=r.id_reservation
    JOIN activites a ON r.id_activite=a.id_activite
    JOIN artisans ar ON a.id_artisan=ar.id_artisan
    JOIN utilisateurs u ON ar.id_utilisateur=u.id_utilisateur
    WHERE p.payment_status='completed'
    GROUP BY ar.id_artisan
    ORDER BY bookings DESC LIMIT 5
")->fetchAll();

// Top artisans by published activities (fallback when no bookings)
$topArtisansByActivities = $db->query("
    SELECT CONCAT(u.prenom,' ',u.nom) AS nom,
           COUNT(a.id_activite) AS bookings,
           0 AS revenue
    FROM activites a
    JOIN artisans ar ON a.id_artisan=ar.id_artisan
    JOIN utilisateurs u ON ar.id_utilisateur=u.id_utilisateur
    WHERE a.statut='publie' AND ar.statut_validation='valide'
    GROUP BY ar.id_artisan
    ORDER BY bookings DESC LIMIT 5
")->fetchAll();

// Recent registrations
$recent = $db->query("
    SELECT id_utilisateur, nom, prenom, email, role, statut, date_inscription
    FROM utilisateurs ORDER BY date_inscription DESC LIMIT 5
")->fetchAll();

// Pending artisans list
$pendingList = $db->query("
    SELECT ar.id_artisan, CONCAT(u.prenom,' ',u.nom) AS nom,
           u.email, ar.specialite, ar.ville
    FROM artisans ar
    JOIN utilisateurs u ON ar.id_utilisateur=u.id_utilisateur
    WHERE ar.statut_validation='en_attente'
    ORDER BY ar.id_artisan DESC LIMIT 5
")->fetchAll();

echo json_encode([
    'success'               => true,
    'users'                 => array_sum($userStats),
    'tourists'              => $userStats['touriste'],
    'artisans'              => $userStats['artisan'],
    'pending_artisans'      => $pending,
    'pending_activities'    => $pendingAct,
    'reservations'          => $totalRes,
    'month_reservations'    => $monthRes,
    'activities'            => $totalAct,
    'revenue'               => $revenue,
    'month_revenue'         => $monthRevenue,
    'avg_booking'           => $avgBooking,
    'avg_rating'            => $avgRating,
    'total_reviews'         => $totalReviews,
    'cancellation_rate'     => $cancRate,
    'regions'               => $regions,
    'categories'            => $categories,
    'activities_by_category'=> $activitiesByCategory,
    'top_artisans'          => count($topArtisans) ? $topArtisans : $topArtisansByActivities,
    'recent_users'          => $recent,
    'pending_list'          => $pendingList,
]);
