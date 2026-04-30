<?php
// ================================================================
// /var/www/tuncover/php/artisan/bookings.php
// GET  → list artisan's bookings
// POST → accept or reject a booking
// ================================================================

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/payments.php';
require_once __DIR__ . '/../config/engagement.php';
require_once __DIR__ . '/../config/activity_capacity.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success'=>false,'error'=>'Not authenticated']);
    exit;
}

$userId = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];
$db     = getDB();
ensurePaymentsTable($db);
ensureNotificationsTable($db);
ensureActivityCapacityColumns($db);

// Get artisan id from user id
$artStmt = $db->prepare("SELECT id_artisan FROM artisans WHERE id_utilisateur = ?");
$artStmt->execute([$userId]);
$artisan = $artStmt->fetch();

if (!$artisan) {
    http_response_code(403);
    echo json_encode(['success'=>false,'error'=>'Artisan profile not found']);
    exit;
}
$artisanId = $artisan['id_artisan'];

// ── GET: list bookings ───────────────────────────
if ($method === 'GET') {
    $statut = $_GET['statut'] ?? '';

    $sql = "
        SELECT
            r.id_reservation,
            r.date_reservation,
            r.date_activite,
            r.heure_activite,
            r.nb_participants,
            r.id_activite,
            r.montant_total,
            r.statut,
            COALESCE(p.payment_status, 'pending') AS payment_status,
            p.transaction_ref,
            p.paid_at,
            a.titre AS activite_titre,
            u.nom AS tourist_nom,
            u.prenom AS tourist_prenom,
            u.email AS tourist_email
        FROM reservations r
        JOIN activites a ON r.id_activite = a.id_activite
        JOIN utilisateurs u ON r.id_touriste = u.id_utilisateur
        LEFT JOIN payments p ON p.id_reservation = r.id_reservation
        WHERE a.id_artisan = ?
    ";
    $params = [$artisanId];

    if ($statut) {
        $sql .= " AND r.statut = ?";
        $params[] = $statut;
    }

    $sql .= " ORDER BY r.date_reservation DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    echo json_encode(['success'=>true, 'bookings'=>$stmt->fetchAll()]);
    exit;
}

// ── POST: update booking status ──────────────────
if ($method === 'POST') {
    $data   = json_decode(file_get_contents('php://input'), true);
    $id     = (int)($data['id']     ?? 0);
    $statut = $data['statut'] ?? '';

    if (!$id || !in_array($statut, ['confirmee','annulee','terminee'])) {
        echo json_encode(['success'=>false,'error'=>'Invalid parameters']); exit;
    }

    // Verify this booking belongs to this artisan
    $check = $db->prepare("
        SELECT
            r.id_reservation,
            r.statut,
            r.id_touriste,
            r.id_activite,
            r.nb_participants,
            a.titre AS activite_titre,
            COALESCE(p.payment_status,'pending') AS payment_status
        FROM reservations r
        JOIN activites a ON r.id_activite = a.id_activite
        LEFT JOIN payments p ON p.id_reservation = r.id_reservation
        WHERE r.id_reservation = ? AND a.id_artisan = ?
    ");
    $check->execute([$id, $artisanId]);
    $existing = $check->fetch();
    if (!$existing) {
        http_response_code(403);
        echo json_encode(['success'=>false,'error'=>'Booking not found or access denied']);
        exit;
    }

    // Lifecycle transitions:
    // en_attente -> confirmee / annulee
    // confirmee  -> terminee / annulee
    // terminee/annulee are final
    $current = $existing['statut'] ?? '';
    $allowed = [
        'en_attente' => ['confirmee','annulee'],
        'confirmee'  => ['terminee','annulee'],
        'terminee'   => [],
        'annulee'    => [],
    ];
    if (!isset($allowed[$current]) || !in_array($statut, $allowed[$current], true)) {
        echo json_encode(['success'=>false,'error'=>'Invalid status transition']); exit;
    }

    try {
        $db->beginTransaction();

        if ($statut === 'confirmee') {
            $activity = loadActivityForAvailability($db, (int)$existing['id_activite'], true);
            if (!$activity || ($activity['statut'] ?? '') !== 'publie') {
                throw new RuntimeException('Activity is no longer available');
            }
            $remaining = remainingPlacesFromRow($activity);
            $needed = max(1, (int)$existing['nb_participants']);
            if ($remaining < $needed) {
                throw new RuntimeException('Not enough places remain to confirm this reservation');
            }
        }

        $stmt = $db->prepare("UPDATE reservations SET statut = ? WHERE id_reservation = ?");
        $stmt->execute([$statut, $id]);

        if ($current === 'en_attente' && $statut === 'confirmee') {
            adjustReservedPlaces($db, (int)$existing['id_activite'], max(1, (int)$existing['nb_participants']));
        }
        if ($current === 'confirmee' && $statut === 'terminee') {
            adjustReservedPlaces($db, (int)$existing['id_activite'], -max(1, (int)$existing['nb_participants']));
        }
        if ($current === 'confirmee' && $statut === 'annulee') {
            adjustReservedPlaces($db, (int)$existing['id_activite'], -max(1, (int)$existing['nb_participants']));
        }

        if ($statut === 'annulee' && ($existing['payment_status'] ?? 'pending') === 'completed') {
            $db->prepare("UPDATE payments SET payment_status='refunded', updated_at=NOW() WHERE id_reservation=?")->execute([$id]);
            createNotification(
                $db,
                (int)$existing['id_touriste'],
                'payment_refunded',
                'Payment Refunded',
                'Your paid reservation for "' . ($existing['activite_titre'] ?? 'activity') . '" was refunded by the artisan.',
                'tuncover-tourist.html',
                $id
            );
        }

        if ($statut === 'confirmee') {
            createNotification(
                $db,
                (int)$existing['id_touriste'],
                'booking_confirmed',
                'Reservation Confirmed',
                'Your reservation for "' . ($existing['activite_titre'] ?? 'activity') . '" is now confirmed.',
                'tuncover-tourist.html',
                $id
            );
        } elseif ($statut === 'terminee') {
            createNotification(
                $db,
                (int)$existing['id_touriste'],
                'booking_completed',
                'Reservation Completed',
                'Your reservation for "' . ($existing['activite_titre'] ?? 'activity') . '" is marked as completed.',
                'tuncover-tourist.html',
                $id
            );
        } elseif ($statut === 'annulee') {
            createNotification(
                $db,
                (int)$existing['id_touriste'],
                'booking_cancelled',
                'Reservation Cancelled',
                'Your reservation for "' . ($existing['activite_titre'] ?? 'activity') . '" was cancelled by the artisan.',
                'tuncover-tourist.html',
                $id
            );
        }

        $db->commit();
        echo json_encode(['success'=>true, 'statut'=>$statut]);
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        http_response_code(500);
        echo json_encode(['success'=>false,'error'=>'Could not update booking: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success'=>false,'error'=>'Method not allowed']);
