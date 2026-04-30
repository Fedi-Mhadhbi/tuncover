<?php
// php/admin/reservations.php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/payments.php';
require_once __DIR__ . '/../config/engagement.php';
require_once __DIR__ . '/../config/activity_capacity.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403); echo json_encode(['success'=>false,'error'=>'Admin only']); exit;
}

$db     = getDB();
ensurePaymentsTable($db);
ensureNotificationsTable($db);
ensureActivityCapacityColumns($db);
$statut = $_GET['statut'] ?? '';
$payment = $_GET['payment'] ?? '';
$q      = trim($_GET['q'] ?? '');
$dateFrom = trim((string)($_GET['date_from'] ?? ''));
$dateTo   = trim((string)($_GET['date_to'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $sql = "SELECT r.id_reservation, r.date_reservation, r.date_activite, r.nb_participants,
               r.montant_total, r.statut,
               COALESCE(p.payment_status,'pending') AS payment_status,
               p.transaction_ref,
               p.paid_at,
               p.artisan_amount,
               p.platform_fee,
               a.titre AS activite_titre, a.ville,
               CONCAT(ut.prenom,' ',ut.nom) AS tourist_nom, ut.email AS tourist_email,
               CONCAT(ua.prenom,' ',ua.nom) AS artisan_nom
        FROM reservations r
        JOIN activites a ON r.id_activite=a.id_activite
        JOIN utilisateurs ut ON r.id_touriste=ut.id_utilisateur
        JOIN artisans ar ON a.id_artisan=ar.id_artisan
        JOIN utilisateurs ua ON ar.id_utilisateur=ua.id_utilisateur
        LEFT JOIN payments p ON p.id_reservation=r.id_reservation
        WHERE 1=1";
    $params = [];

    if ($statut) { $sql .= " AND r.statut=?"; $params[] = $statut; }
    if ($payment === 'paid')   { $sql .= " AND COALESCE(p.payment_status,'pending')='completed'"; }
    if ($payment === 'unpaid') { $sql .= " AND COALESCE(p.payment_status,'pending')!='completed'"; }
    if ($q)      { $sql .= " AND (ut.nom LIKE ? OR ut.prenom LIKE ? OR a.titre LIKE ?)"; $p="%$q%"; $params[]=$p;$params[]=$p;$params[]=$p; }
    if ($dateFrom !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
        $sql .= " AND DATE(COALESCE(p.paid_at, r.date_reservation)) >= ?";
        $params[] = $dateFrom;
    }
    if ($dateTo !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
        $sql .= " AND DATE(COALESCE(p.paid_at, r.date_reservation)) <= ?";
        $params[] = $dateTo;
    }

    $sql .= " ORDER BY r.date_reservation DESC LIMIT 100";
    $s = $db->prepare($sql); $s->execute($params);
    echo json_encode(['success'=>true,'reservations'=>$s->fetchAll()]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = (int)($data['id'] ?? 0);
    $action = trim((string)($data['action'] ?? ''));

    if ($id <= 0 || $action === '') {
        echo json_encode(['success'=>false,'error'=>'Invalid parameters']); exit;
    }

    $stmt = $db->prepare("
        SELECT
            r.id_reservation,
            r.statut,
            r.id_touriste,
            r.id_activite,
            r.nb_participants,
            a.titre AS activite_titre,
            ar.id_utilisateur AS artisan_user_id,
            COALESCE(p.payment_status,'pending') AS payment_status
        FROM reservations r
        JOIN activites a ON a.id_activite = r.id_activite
        JOIN artisans ar ON ar.id_artisan = a.id_artisan
        LEFT JOIN payments p ON p.id_reservation=r.id_reservation
        WHERE r.id_reservation=?
        LIMIT 1
    ");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        echo json_encode(['success'=>false,'error'=>'Reservation not found']); exit;
    }

    try {
        $db->beginTransaction();

        if ($action === 'confirm') {
            if ($row['statut'] !== 'en_attente') {
                throw new RuntimeException('Only pending reservations can be confirmed');
            }
            $activity = loadActivityForAvailability($db, (int)$row['id_activite'], true);
            if (!$activity || ($activity['statut'] ?? '') !== 'publie') {
                throw new RuntimeException('Activity is no longer available');
            }
            $needed = max(1, (int)$row['nb_participants']);
            if (remainingPlacesFromRow($activity) < $needed) {
                throw new RuntimeException('Not enough places remain to confirm this reservation');
            }
            $db->prepare("UPDATE reservations SET statut='confirmee' WHERE id_reservation=?")->execute([$id]);
            adjustReservedPlaces($db, (int)$row['id_activite'], max(1, (int)$row['nb_participants']));
            createNotification($db, (int)$row['id_touriste'], 'booking_confirmed', 'Reservation Confirmed', 'Admin confirmed your reservation for "' . ($row['activite_titre'] ?? 'activity') . '".', 'tuncover-tourist.html', $id);
        } elseif ($action === 'complete') {
            if ($row['statut'] !== 'confirmee') {
                throw new RuntimeException('Only confirmed reservations can be completed');
            }
            $db->prepare("UPDATE reservations SET statut='terminee' WHERE id_reservation=?")->execute([$id]);
            adjustReservedPlaces($db, (int)$row['id_activite'], -max(1, (int)$row['nb_participants']));
            createNotification($db, (int)$row['id_touriste'], 'booking_completed', 'Reservation Completed', 'Admin marked your reservation for "' . ($row['activite_titre'] ?? 'activity') . '" as completed.', 'tuncover-tourist.html', $id);
        } elseif ($action === 'cancel') {
            if (in_array($row['statut'], ['annulee','terminee'], true)) {
                throw new RuntimeException('This reservation cannot be cancelled');
            }
            $db->prepare("UPDATE reservations SET statut='annulee' WHERE id_reservation=?")->execute([$id]);
            if ($row['statut'] === 'confirmee') {
                adjustReservedPlaces($db, (int)$row['id_activite'], -max(1, (int)$row['nb_participants']));
            }
            createNotification($db, (int)$row['id_touriste'], 'booking_cancelled', 'Reservation Cancelled', 'Admin cancelled your reservation for "' . ($row['activite_titre'] ?? 'activity') . '".', 'tuncover-tourist.html', $id);
            createNotification($db, (int)$row['artisan_user_id'], 'booking_cancelled', 'Reservation Cancelled', 'Admin cancelled reservation #' . $id . ' for "' . ($row['activite_titre'] ?? 'activity') . '".', 'tuncover-artisan-dashboard.html', $id);
            if (($row['payment_status'] ?? 'pending') === 'completed') {
                $db->prepare("UPDATE payments SET payment_status='refunded', updated_at=NOW() WHERE id_reservation=?")->execute([$id]);
                createNotification($db, (int)$row['id_touriste'], 'payment_refunded', 'Payment Refunded', 'Admin refunded your payment for "' . ($row['activite_titre'] ?? 'activity') . '".', 'tuncover-tourist.html', $id);
            }
        } elseif ($action === 'refund') {
            if (($row['payment_status'] ?? 'pending') !== 'completed') {
                throw new RuntimeException('Only paid reservations can be refunded');
            }
            $db->prepare("UPDATE payments SET payment_status='refunded', updated_at=NOW() WHERE id_reservation=?")->execute([$id]);
            createNotification($db, (int)$row['id_touriste'], 'payment_refunded', 'Payment Refunded', 'Admin refunded your payment for "' . ($row['activite_titre'] ?? 'activity') . '".', 'tuncover-tourist.html', $id);
        } else {
            throw new RuntimeException('Unknown action');
        }

        $db->commit();
        echo json_encode(['success'=>true]);
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
    }
    exit;
}

echo json_encode(['success'=>false,'error'=>'Method not allowed']);
