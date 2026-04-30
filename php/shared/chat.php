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
ensureDirectMessagesTable($db);
ensureNotificationsTable($db);

$userId = (int)$_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

function loadReservationParticipant(PDO $db, int $reservationId): ?array
{
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

function loadUserBasic(PDO $db, int $id): ?array
{
    $stmt = $db->prepare("
        SELECT id_utilisateur, nom, prenom, role, statut
        FROM utilisateurs
        WHERE id_utilisateur = ?
        LIMIT 1
    ");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function isAdmin(PDO $db, int $id): bool
{
    $u = loadUserBasic($db, $id);
    return ($u['role'] ?? '') === 'admin';
}

function safeDirectLinkForUser(string $role, int $counterpartUserId, string $counterpartName): string
{
    $base = $role === 'artisan' ? 'tuncover-artisan-dashboard.html' : 'tuncover-tourist.html';
    return $base . '?direct_user=' . $counterpartUserId . '&direct_name=' . rawurlencode($counterpartName);
}

if ($method === 'GET') {
    $reservationId = (int)($_GET['reservation_id'] ?? 0);
    $directUserId = (int)($_GET['direct_user_id'] ?? 0);
    $mode = trim((string)($_GET['mode'] ?? ''));

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
            'mode' => 'reservation',
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

    if ($directUserId > 0) {
        if ($directUserId === $userId) {
            echo json_encode(['success' => false, 'error' => 'Invalid direct user']);
            exit;
        }
        $partner = loadUserBasic($db, $directUserId);
        if (!$partner || ($partner['statut'] ?? '') !== 'actif' || ($partner['role'] ?? '') === 'admin') {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'User not available for direct chat']);
            exit;
        }
        if (isAdmin($db, $userId)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Admins cannot use direct chat']);
            exit;
        }

        $read = $db->prepare("
            UPDATE direct_messages
            SET is_read = 1
            WHERE id_sender = ? AND id_receiver = ? AND is_read = 0
        ");
        $read->execute([$directUserId, $userId]);

        $msg = $db->prepare("
            SELECT
                dm.id_direct_message AS id_chat,
                dm.id_sender,
                dm.id_receiver,
                dm.message,
                dm.is_read,
                dm.created_at,
                CONCAT(u.prenom, ' ', u.nom) AS sender_name
            FROM direct_messages dm
            JOIN utilisateurs u ON u.id_utilisateur = dm.id_sender
            WHERE
                (dm.id_sender = ? AND dm.id_receiver = ?)
                OR
                (dm.id_sender = ? AND dm.id_receiver = ?)
            ORDER BY dm.created_at ASC, dm.id_direct_message ASC
        ");
        $msg->execute([$userId, $directUserId, $directUserId, $userId]);

        echo json_encode([
            'success' => true,
            'mode' => 'direct',
            'direct' => [
                'counterpart_user_id' => $directUserId,
                'counterpart_name' => trim(($partner['prenom'] ?? '') . ' ' . ($partner['nom'] ?? '')),
            ],
            'messages' => $msg->fetchAll(PDO::FETCH_ASSOC),
        ]);
        exit;
    }

    if ($mode === 'direct_threads') {
        if (isAdmin($db, $userId)) {
            echo json_encode(['success' => true, 'threads' => []]);
            exit;
        }

        $threads = $db->prepare("
            SELECT
                t.counterpart_user_id,
                CONCAT(u.prenom, ' ', u.nom) AS counterpart_name,
                u.role AS counterpart_role,
                t.last_message,
                t.last_message_at,
                (
                    SELECT COUNT(*)
                    FROM direct_messages dm2
                    WHERE dm2.id_sender = t.counterpart_user_id
                      AND dm2.id_receiver = ?
                      AND dm2.is_read = 0
                ) AS unread_count
            FROM (
                SELECT
                    IF(dm.id_sender = ?, dm.id_receiver, dm.id_sender) AS counterpart_user_id,
                    SUBSTRING_INDEX(
                      GROUP_CONCAT(dm.message ORDER BY dm.created_at DESC, dm.id_direct_message DESC SEPARATOR '\n'),
                      '\n',
                      1
                    ) AS last_message,
                    MAX(dm.created_at) AS last_message_at
                FROM direct_messages dm
                WHERE dm.id_sender = ? OR dm.id_receiver = ?
                GROUP BY IF(dm.id_sender = ?, dm.id_receiver, dm.id_sender)
            ) t
            JOIN utilisateurs u ON u.id_utilisateur = t.counterpart_user_id
            ORDER BY t.last_message_at DESC
            LIMIT 200
        ");
        $threads->execute([$userId, $userId, $userId, $userId, $userId]);
        echo json_encode(['success' => true, 'threads' => $threads->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    }

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
    echo json_encode(['success' => true, 'mode' => 'reservation_threads', 'threads' => $threads->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) {
        echo json_encode(['success' => false, 'error' => 'Invalid body']);
        exit;
    }

    $message = trim((string)($data['message'] ?? ''));
    if ($message === '') {
        echo json_encode(['success' => false, 'error' => 'Message is required']);
        exit;
    }
    if (mb_strlen($message) > 2000) {
        echo json_encode(['success' => false, 'error' => 'Message too long']);
        exit;
    }

    $reservationId = (int)($data['reservation_id'] ?? 0);
    $directUserId = (int)($data['direct_user_id'] ?? 0);

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

        echo json_encode(['success' => true, 'mode' => 'reservation', 'id_chat' => (int)$db->lastInsertId()]);
        exit;
    }

    if ($directUserId > 0) {
        if ($directUserId === $userId) {
            echo json_encode(['success' => false, 'error' => 'Invalid direct user']);
            exit;
        }
        $sender = loadUserBasic($db, $userId);
        $receiver = loadUserBasic($db, $directUserId);
        if (!$sender || !$receiver || ($receiver['statut'] ?? '') !== 'actif') {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'User not found']);
            exit;
        }
        if (($sender['role'] ?? '') === 'admin' || ($receiver['role'] ?? '') === 'admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Direct chat is not available for admin accounts']);
            exit;
        }

        $ins = $db->prepare("
            INSERT INTO direct_messages (id_sender, id_receiver, message, is_read, created_at)
            VALUES (?, ?, ?, 0, NOW())
        ");
        $ins->execute([$userId, $directUserId, $message]);

        $senderName = trim(($sender['prenom'] ?? '') . ' ' . ($sender['nom'] ?? ''));
        $link = safeDirectLinkForUser((string)($receiver['role'] ?? ''), $userId, $senderName);
        createNotification(
            $db,
            $directUserId,
            'new_message',
            'New Direct Message',
            'You received a new message from ' . ($senderName !== '' ? $senderName : 'a user') . '.',
            $link,
            null
        );

        echo json_encode(['success' => true, 'mode' => 'direct', 'id_chat' => (int)$db->lastInsertId()]);
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Reservation or direct user is required']);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method not allowed']);
