<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php';
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
ensureChatMessagesTable($db);

$userId = (int)$_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

function loadReservationParticipant(PDO $db, int $reservationId): ?array {
    $stmt = $db->prepare("
        SELECT
            r.id_reservation,
            r.id_touriste,
            a.id_artisan,
            ar.id_utilisateur AS artisan_user_id,
            a.titre AS activite_titre
        FROM reservations r
        JOIN activites a ON r.id_activite = a.id_activite
        JOIN artisans ar ON a.id_artisan = ar.id_artisan
        WHERE r.id_reservation = ?
        LIMIT 1
    ");
    $stmt->execute([$reservationId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

if ($method === 'GET') {
    $reservationId = (int)($_GET['reservation_id'] ?? 0);

    if ($reservationId > 0) {
        $res = loadReservationParticipant($db, $reservationId);
        if (!$res) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Reservation not found']);
            exit;
        }
        $isTourist = ((int)$res['id_touriste'] === $userId);
        $isArtisan = ((int)$res['artisan_user_id'] === $userId);
        if (!$isTourist && !$isArtisan) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Access denied']);
            exit;
        }

        // Mark received messages as read for this user.
        $read = $db->prepare("
            UPDATE chat_messages
            SET is_read = 1
            WHERE id_reservation = ? AND id_receiver = ? AND is_read = 0
        ");
        $read->execute([$reservationId, $userId]);

        $msg = $db->prepare("
            SELECT
                m.id_chat,
                m.id_reservation,
                m.id_sender,
                m.id_receiver,
                m.message,
                m.is_read,
                m.created_at,
                CONCAT(u.prenom, ' ', u.nom) AS sender_name
            FROM chat_messages m
            JOIN utilisateurs u ON u.id_utilisateur = m.id_sender
            WHERE m.id_reservation = ?
            ORDER BY m.created_at ASC, m.id_chat ASC
        ");
        $msg->execute([$reservationId]);

        echo json_encode([
            'success' => true,
            'reservation' => [
                'id_reservation' => (int)$res['id_reservation'],
                'activite_titre' => $res['activite_titre'],
                'tourist_user_id' => (int)$res['id_touriste'],
                'artisan_user_id' => (int)$res['artisan_user_id'],
            ],
            'messages' => $msg->fetchAll(PDO::FETCH_ASSOC),
        ]);
        exit;
    }

    // Conversation list for current user
    $threads = $db->prepare("
        SELECT
            r.id_reservation,
            a.titre AS activite_titre,
            r.date_activite,
            r.statut,
            CONCAT(ut.prenom, ' ', ut.nom) AS tourist_name,
            CONCAT(ua.prenom, ' ', ua.nom) AS artisan_name,
            (
              SELECT cm.message
              FROM chat_messages cm
              WHERE cm.id_reservation = r.id_reservation
              ORDER BY cm.created_at DESC, cm.id_chat DESC
              LIMIT 1
            ) AS last_message,
            (
              SELECT cm.created_at
              FROM chat_messages cm
              WHERE cm.id_reservation = r.id_reservation
              ORDER BY cm.created_at DESC, cm.id_chat DESC
              LIMIT 1
            ) AS last_message_at,
            (
              SELECT COUNT(*)
              FROM chat_messages cm
              WHERE cm.id_reservation = r.id_reservation
                AND cm.id_receiver = ?
                AND cm.is_read = 0
            ) AS unread_count
        FROM reservations r
        JOIN activites a ON r.id_activite = a.id_activite
        JOIN utilisateurs ut ON ut.id_utilisateur = r.id_touriste
        JOIN artisans ar ON ar.id_artisan = a.id_artisan
        JOIN utilisateurs ua ON ua.id_utilisateur = ar.id_utilisateur
        WHERE r.id_touriste = ? OR ar.id_utilisateur = ?
        ORDER BY COALESCE(last_message_at, r.date_reservation) DESC
        LIMIT 100
    ");
    $threads->execute([$userId, $userId, $userId]);
    echo json_encode(['success' => true, 'threads' => $threads->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) {
        echo json_encode(['success' => false, 'error' => 'Invalid body']);
        exit;
    }

    $reservationId = (int)($data['reservation_id'] ?? 0);
    $message = trim((string)($data['message'] ?? ''));
    if ($reservationId <= 0 || $message === '') {
        echo json_encode(['success' => false, 'error' => 'Reservation and message are required']);
        exit;
    }
    if (mb_strlen($message) > 2000) {
        echo json_encode(['success' => false, 'error' => 'Message too long']);
        exit;
    }

    $res = loadReservationParticipant($db, $reservationId);
    if (!$res) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Reservation not found']);
        exit;
    }

    $isTourist = ((int)$res['id_touriste'] === $userId);
    $isArtisan = ((int)$res['artisan_user_id'] === $userId);
    if (!$isTourist && !$isArtisan) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Access denied']);
        exit;
    }

    $receiver = $isTourist ? (int)$res['artisan_user_id'] : (int)$res['id_touriste'];

    $ins = $db->prepare("
        INSERT INTO chat_messages (id_reservation, id_sender, id_receiver, message, is_read, created_at)
        VALUES (?, ?, ?, ?, 0, NOW())
    ");
    $ins->execute([$reservationId, $userId, $receiver, $message]);

    createNotification(
        $db,
        $receiver,
        'new_message',
        'New Chat Message',
        'You received a new message in reservation #' . $reservationId . '.',
        null,
        $reservationId
    );

    echo json_encode(['success' => true, 'id_chat' => (int)$db->lastInsertId()]);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method not allowed']);
