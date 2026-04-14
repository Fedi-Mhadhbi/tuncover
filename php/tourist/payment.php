<?php

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

$db = getDB();
ensurePaymentsTable($db);
ensureTestCardsTable($db);
ensureNotificationsTable($db);

$userId = (int) $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

function loadReservationForPayment(PDO $db, int $reservationId, int $userId): ?array {
    $stmt = $db->prepare("
        SELECT
            r.id_reservation,
            r.id_touriste,
            r.id_activite,
            r.date_activite,
            r.heure_activite,
            r.nb_participants,
            r.montant_total,
            r.statut,
            a.titre AS activite_titre,
            a.ville AS activite_ville,
            a.id_artisan,
            CONCAT(u.prenom, ' ', u.nom) AS artisan_nom,
            p.id_payment,
            p.payment_status,
            p.amount,
            p.platform_fee,
            p.artisan_amount,
            p.transaction_ref,
            p.card_holder,
            p.card_last4,
            p.paid_at
        FROM reservations r
        JOIN activites a ON r.id_activite = a.id_activite
        JOIN artisans ar ON a.id_artisan = ar.id_artisan
        JOIN utilisateurs u ON ar.id_utilisateur = u.id_utilisateur
        LEFT JOIN payments p ON p.id_reservation = r.id_reservation
        WHERE r.id_reservation = ? AND r.id_touriste = ?
        LIMIT 1
    ");
    $stmt->execute([$reservationId, $userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

if ($method === 'GET') {
    $reservationId = (int) ($_GET['reservation_id'] ?? 0);
    if ($reservationId <= 0) {
        echo json_encode(['success' => false, 'error' => 'Reservation ID is required']);
        exit;
    }

    $reservation = loadReservationForPayment($db, $reservationId, $userId);
    if (!$reservation) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Reservation not found']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'payment' => [
            'reservation_id' => (int) $reservation['id_reservation'],
            'activity_title' => $reservation['activite_titre'],
            'city' => $reservation['activite_ville'],
            'artisan_name' => $reservation['artisan_nom'],
            'date_activite' => $reservation['date_activite'],
            'heure_activite' => $reservation['heure_activite'],
            'participants' => (int) $reservation['nb_participants'],
            'amount' => (float) $reservation['montant_total'],
            'currency' => 'TND',
            'reservation_status' => $reservation['statut'],
            'payment_status' => $reservation['payment_status'] ?: 'pending',
            'transaction_ref' => $reservation['transaction_ref'],
            'card_holder' => $reservation['card_holder'],
            'card_last4' => $reservation['card_last4'],
            'paid_at' => $reservation['paid_at'],
        ],
        'test_cards' => $db->query("
            SELECT card_holder, card_number, exp_month, exp_year, cvv, card_brand
            FROM test_cards
            WHERE is_active = 1
            ORDER BY id_card ASC
            LIMIT 6
        ")->fetchAll(PDO::FETCH_ASSOC),
    ]);
    exit;
}

if ($method === 'POST') {
    $payload = json_decode(file_get_contents('php://input'), true);
    if (!is_array($payload)) {
        echo json_encode(['success' => false, 'error' => 'Invalid payment data']);
        exit;
    }

    $reservationId = (int) ($payload['reservation_id'] ?? 0);
    $cardHolder = trim((string) ($payload['card_holder'] ?? ''));
    $cardNumber = preg_replace('/\D+/', '', (string) ($payload['card_number'] ?? ''));
    $expMonth = trim((string) ($payload['exp_month'] ?? ''));
    $expYear = trim((string) ($payload['exp_year'] ?? ''));
    $cvv = preg_replace('/\D+/', '', (string) ($payload['cvv'] ?? ''));
    $amount = (float) ($payload['amount'] ?? 0);

    if ($reservationId <= 0) {
        echo json_encode(['success' => false, 'error' => 'Reservation ID is required']);
        exit;
    }
    if ($cardHolder === '') {
        echo json_encode(['success' => false, 'error' => 'Card holder name is required']);
        exit;
    }
    if (strlen($cardNumber) < 12 || strlen($cardNumber) > 19) {
        echo json_encode(['success' => false, 'error' => 'Card number looks invalid']);
        exit;
    }
    if (!preg_match('/^\d{2}$/', $expMonth) || (int) $expMonth < 1 || (int) $expMonth > 12) {
        echo json_encode(['success' => false, 'error' => 'Expiry month is invalid']);
        exit;
    }
    if (!preg_match('/^\d{2,4}$/', $expYear)) {
        echo json_encode(['success' => false, 'error' => 'Expiry year is invalid']);
        exit;
    }
    if (strlen($cvv) < 3 || strlen($cvv) > 4) {
        echo json_encode(['success' => false, 'error' => 'CVV is invalid']);
        exit;
    }

    $cardCheck = $db->prepare("
        SELECT id_card
        FROM test_cards
        WHERE is_active = 1
          AND card_number = ?
          AND exp_month = ?
          AND exp_year = ?
          AND cvv = ?
        LIMIT 1
    ");
    $cardCheck->execute([$cardNumber, $expMonth, strlen($expYear) === 2 ? ('20' . $expYear) : $expYear, $cvv]);
    if (!$cardCheck->fetch(PDO::FETCH_ASSOC)) {
        echo json_encode([
            'success' => false,
            'error' => 'Card not found in demo cards table. Use one of the virtual cards.',
        ]);
        exit;
    }

    $reservation = loadReservationForPayment($db, $reservationId, $userId);
    if (!$reservation) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Reservation not found']);
        exit;
    }
    if ($reservation['statut'] === 'en_attente') {
        echo json_encode(['success' => false, 'error' => 'Payment is allowed only after artisan confirmation']);
        exit;
    }
    if ($reservation['statut'] === 'annulee') {
        echo json_encode(['success' => false, 'error' => 'Cancelled reservations cannot be paid']);
        exit;
    }
    if ($reservation['statut'] === 'terminee') {
        echo json_encode(['success' => false, 'error' => 'Completed reservations cannot be paid']);
        exit;
    }
    if (($reservation['payment_status'] ?? '') === 'completed') {
        echo json_encode(['success' => false, 'error' => 'This reservation is already paid']);
        exit;
    }

    $expectedAmount = (float) $reservation['montant_total'];
    if (abs($amount - $expectedAmount) > 0.01) {
        echo json_encode(['success' => false, 'error' => 'Payment amount does not match the reservation total']);
        exit;
    }

    $platformFee = round($expectedAmount * 0.10, 2);
    $artisanAmount = round($expectedAmount - $platformFee, 2);
    $transactionRef = 'TC-' . date('YmdHis') . '-' . str_pad((string) $reservationId, 6, '0', STR_PAD_LEFT);
    $cardLast4 = substr($cardNumber, -4);

    try {
        $db->beginTransaction();

        $save = $db->prepare("
            INSERT INTO payments (
                id_reservation,
                id_artisan,
                amount,
                platform_fee,
                artisan_amount,
                currency,
                payment_method,
                card_holder,
                card_last4,
                transaction_ref,
                payment_status,
                paid_at
            ) VALUES (?, ?, ?, ?, ?, 'TND', 'fake_card', ?, ?, ?, 'completed', NOW())
            ON DUPLICATE KEY UPDATE
                amount = VALUES(amount),
                platform_fee = VALUES(platform_fee),
                artisan_amount = VALUES(artisan_amount),
                card_holder = VALUES(card_holder),
                card_last4 = VALUES(card_last4),
                transaction_ref = VALUES(transaction_ref),
                payment_status = 'completed',
                paid_at = NOW()
        ");
        $save->execute([
            $reservationId,
            $reservation['id_artisan'],
            $expectedAmount,
            $platformFee,
            $artisanAmount,
            $cardHolder,
            $cardLast4,
            $transactionRef,
        ]);

        $artisanUserStmt = $db->prepare("SELECT id_utilisateur FROM artisans WHERE id_artisan = ? LIMIT 1");
        $artisanUserStmt->execute([(int)$reservation['id_artisan']]);
        $artisanUserId = (int)$artisanUserStmt->fetchColumn();

        createNotification(
            $db,
            $userId,
            'payment_completed',
            'Payment Successful',
            'Your payment for "' . $reservation['activite_titre'] . '" was completed successfully.',
            'tuncover-tourist.html',
            $reservationId
        );
        if ($artisanUserId > 0) {
            createNotification(
                $db,
                $artisanUserId,
                'payment_received',
                'Booking Paid',
                'A tourist completed payment for "' . $reservation['activite_titre'] . '".',
                'tuncover-artisan-dashboard.html',
                $reservationId
            );
        }

        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Fake payment completed successfully',
            'payment' => [
                'reservation_id' => $reservationId,
                'transaction_ref' => $transactionRef,
                'amount' => $expectedAmount,
                'platform_fee' => $platformFee,
                'artisan_amount' => $artisanAmount,
                'card_last4' => $cardLast4,
                'payment_status' => 'completed',
            ],
        ]);
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Could not save payment: ' . $e->getMessage(),
        ]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method not allowed']);
