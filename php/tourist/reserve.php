<?php
// ================================================================
// TunCover - Create Reservation
// File: /php/tourist/reserve.php
// ================================================================

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/payments.php';
require_once __DIR__ . '/../config/engagement.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ================================================================
// 1) Authentication check
// ================================================================
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error'   => 'Not authenticated'
    ]);
    exit;
}

// ================================================================
// 2) Method check
// ================================================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error'   => 'Method not allowed'
    ]);
    exit;
}

// ================================================================
// 3) Read JSON body
// ================================================================
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data) {
    echo json_encode([
        'success' => false,
        'error'   => 'Invalid request data'
    ]);
    exit;
}

$userId = (int) $_SESSION['user_id'];

$id_activite     = isset($data['id_activite']) ? (int)$data['id_activite'] : 0;
$date_activite   = trim($data['date_activite'] ?? '');
$heure_activite  = trim($data['heure_activite'] ?? '10:00');
$nb_participants = isset($data['nb_participants']) ? (int)$data['nb_participants'] : 1;

// ================================================================
// 4) Validation
// ================================================================
if ($id_activite <= 0) {
    echo json_encode([
        'success' => false,
        'error'   => 'Invalid activity'
    ]);
    exit;
}

if (empty($date_activite)) {
    echo json_encode([
        'success' => false,
        'error'   => 'Please select a date'
    ]);
    exit;
}

if ($nb_participants < 1) {
    echo json_encode([
        'success' => false,
        'error'   => 'Minimum 1 participant'
    ]);
    exit;
}

// Check future date
if (strtotime($date_activite) < strtotime(date('Y-m-d'))) {
    echo json_encode([
        'success' => false,
        'error'   => 'Date must be today or future'
    ]);
    exit;
}

try {
    $db = getDB();
    ensurePaymentsTable($db);
    ensureNotificationsTable($db);

    // ============================================================
    // 5) Load activity
    // ============================================================
    $stmt = $db->prepare("
        SELECT id_activite, titre, prix, max_participants, statut, ville, id_artisan
        FROM activites
        WHERE id_activite = ?
    ");
    $stmt->execute([$id_activite]);

    $activity = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$activity) {
        echo json_encode([
            'success' => false,
            'error'   => 'Activity not found'
        ]);
        exit;
    }

    // Must be published
    if ($activity['statut'] !== 'publie') {
        echo json_encode([
            'success' => false,
            'error'   => 'Activity not available'
        ]);
        exit;
    }

    // ============================================================
    // 6) Max participants
    // ============================================================
    if ($nb_participants > (int)$activity['max_participants']) {
        echo json_encode([
            'success' => false,
            'error'   => 'Maximum ' . $activity['max_participants'] . ' guests allowed'
        ]);
        exit;
    }

    // ============================================================
    // 7) Prevent artisan booking own activity
    // ============================================================
    $artStmt = $db->prepare("
        SELECT id_artisan
        FROM artisans
        WHERE id_utilisateur = ?
    ");
    $artStmt->execute([$userId]);

    $artisan = $artStmt->fetch(PDO::FETCH_ASSOC);

    if ($artisan && (int)$artisan['id_artisan'] === (int)$activity['id_artisan']) {
        echo json_encode([
            'success' => false,
            'error'   => 'You cannot reserve your own activity'
        ]);
        exit;
    }

    // ============================================================
    // 8) Price calculation
    // ============================================================
    $prixBase = (float)$activity['prix'];

    $subtotal   = $prixBase * $nb_participants;
    $serviceFee = $subtotal * 0.10;
    $total      = $subtotal + $serviceFee;

    // ============================================================
    // 9) Insert reservation
    // ============================================================
    $insert = $db->prepare("
        INSERT INTO reservations (
            id_touriste,
            id_activite,
            date_reservation,
            date_activite,
            heure_activite,
            nb_participants,
            montant_total,
            statut
        ) VALUES (
            ?, ?, NOW(), ?, ?, ?, ?, 'en_attente'
        )
    ");

    $insert->execute([
        $userId,
        $id_activite,
        $date_activite,
        $heure_activite,
        $nb_participants,
        $total
    ]);

    $reservationId = $db->lastInsertId();

    $artisanUserStmt = $db->prepare("SELECT id_utilisateur FROM artisans WHERE id_artisan = ? LIMIT 1");
    $artisanUserStmt->execute([(int)$activity['id_artisan']]);
    $artisanUserId = (int)$artisanUserStmt->fetchColumn();
    if ($artisanUserId > 0) {
        createNotification(
            $db,
            $artisanUserId,
            'booking_created',
            'New Reservation Request',
            'You received a new reservation for "' . $activity['titre'] . '" on ' . $date_activite . '.',
            'tuncover-artisan-dashboard.html',
            (int)$reservationId
        );
    }

    // ============================================================
    // 10) Success response
    // ============================================================
    echo json_encode([
        'success'         => true,
        'message'         => 'Reservation created successfully',
        'id_reservation'  => $reservationId,
        'payment_url'     => '../../tuncover-payment.html?reservation_id=' . $reservationId,
        'titre'           => $activity['titre'],
        'id_artisan'      => $activity['id_artisan'],
        'ville'           => $activity['ville'],
        'date_activite'   => $date_activite,
        'heure_activite'  => $heure_activite,
        'participants'    => $nb_participants,
        'subtotal'        => $subtotal,
        'service_fee'     => $serviceFee,
        'total'           => $total,
        'statut'          => 'en_attente'
    ]);

} catch (PDOException $e) {
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'error'   => 'Database error: ' . $e->getMessage()
    ]);
}
