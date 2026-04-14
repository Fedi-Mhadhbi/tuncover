<?php
// ================================================================
// /var/www/tuncover/php/tourist/tourist_bookings.php
// GET    -> list tourist's own reservations
// DELETE -> cancel reservation with lifecycle and refund rules
// ================================================================

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/payments.php';
require_once __DIR__ . '/../config/engagement.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];
$db     = getDB();
ensurePaymentsTable($db);
ensureNotificationsTable($db);

if ($method === 'GET') {
    $statut = $_GET['statut'] ?? '';

    $sql = "
        SELECT
            r.id_reservation,
            r.id_activite,
            r.date_reservation,
            r.date_activite,
            r.heure_activite,
            r.nb_participants,
            r.montant_total,
            r.statut,
            COALESCE(p.payment_status, 'pending') AS payment_status,
            p.transaction_ref,
            p.paid_at,
            a.titre AS activite_titre,
            a.ville AS activite_ville,
            a.prix AS prix_unitaire,
            u2.nom AS artisan_nom,
            u2.prenom AS artisan_prenom,
            (
                SELECT COUNT(*)
                FROM avis av
                WHERE av.id_touriste = r.id_touriste
                  AND av.id_reservation = r.id_reservation
            ) AS has_review
        FROM reservations r
        JOIN activites a ON r.id_activite = a.id_activite
        JOIN artisans ar ON a.id_artisan = ar.id_artisan
        JOIN utilisateurs u2 ON ar.id_utilisateur = u2.id_utilisateur
        LEFT JOIN payments p ON p.id_reservation = r.id_reservation
        WHERE r.id_touriste = ?
    ";
    $params = [$userId];

    if ($statut) {
        $sql .= " AND r.statut = ?";
        $params[] = $statut;
    }

    $sql .= " ORDER BY r.date_reservation DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    echo json_encode(['success' => true, 'bookings' => $stmt->fetchAll()]);
    exit;
}

if ($method === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id   = (int)($data['id'] ?? 0);

    if (!$id) {
        echo json_encode(['success' => false, 'error' => 'ID required']);
        exit;
    }

    $check = $db->prepare("
        SELECT
            r.id_reservation,
            r.statut,
            r.date_activite,
            r.heure_activite,
            COALESCE(p.payment_status, 'pending') AS payment_status,
            a.titre AS activite_titre,
            ar.id_utilisateur AS artisan_user_id
        FROM reservations r
        JOIN activites a ON a.id_activite = r.id_activite
        JOIN artisans ar ON ar.id_artisan = a.id_artisan
        LEFT JOIN payments p ON p.id_reservation = r.id_reservation
        WHERE r.id_reservation = ? AND r.id_touriste = ?
    ");
    $check->execute([$id, $userId]);
    $booking = $check->fetch();

    if (!$booking) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Booking not found']);
        exit;
    }
    if ($booking['statut'] === 'annulee') {
        echo json_encode(['success' => false, 'error' => 'Already cancelled']);
        exit;
    }
    if (!in_array($booking['statut'], ['en_attente', 'confirmee'], true)) {
        echo json_encode(['success' => false, 'error' => 'This booking cannot be cancelled']);
        exit;
    }

    $activityDT = trim(($booking['date_activite'] ?? '') . ' ' . ($booking['heure_activite'] ?? '00:00:00'));
    $activityTs = strtotime($activityDT);
    if ($activityTs !== false && $activityTs < (time() + 48 * 3600)) {
        echo json_encode(['success' => false, 'error' => 'Cancellation is allowed only 48h before activity time']);
        exit;
    }

    try {
        $db->beginTransaction();

        $stmt = $db->prepare("UPDATE reservations SET statut = 'annulee' WHERE id_reservation = ?");
        $stmt->execute([$id]);

        if (($booking['payment_status'] ?? 'pending') === 'completed') {
            $refund = $db->prepare("
                UPDATE payments
                SET payment_status = 'refunded', updated_at = NOW()
                WHERE id_reservation = ?
            ");
            $refund->execute([$id]);

            createNotification(
                $db,
                $userId,
                'payment_refunded',
                'Payment Refunded',
                'Your payment for "' . ($booking['activite_titre'] ?? 'activity') . '" was refunded after cancellation.',
                'tuncover-tourist.html',
                $id
            );
        }

        $artisanUserId = (int)($booking['artisan_user_id'] ?? 0);
        if ($artisanUserId > 0) {
            createNotification(
                $db,
                $artisanUserId,
                'booking_cancelled',
                'Reservation Cancelled',
                'A tourist cancelled reservation #' . $id . ' for "' . ($booking['activite_titre'] ?? 'activity') . '".',
                'tuncover-artisan-dashboard.html',
                $id
            );
        }

        $db->commit();
        echo json_encode([
            'success' => true,
            'payment_status' => (($booking['payment_status'] ?? 'pending') === 'completed') ? 'refunded' : ($booking['payment_status'] ?? 'pending')
        ]);
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Could not cancel booking']);
    }
    exit;
}

echo json_encode(['success' => false, 'error' => 'Method not allowed']);
