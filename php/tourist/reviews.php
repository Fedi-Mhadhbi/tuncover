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

$userId = (int)$_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];
$db = getDB();
ensureNotificationsTable($db);

try {
    if ($method === 'GET') {
        $stmt = $db->prepare("
            SELECT
                av.id_avis,
                av.note,
                av.commentaire,
                av.date_avis,
                a.titre AS activite_titre,
                a.ville,
                CONCAT(u.prenom, ' ', u.nom) AS artisan_nom
            FROM avis av
            JOIN activites a ON av.id_activite = a.id_activite
            JOIN artisans ar ON a.id_artisan = ar.id_artisan
            JOIN utilisateurs u ON ar.id_utilisateur = u.id_utilisateur
            WHERE av.id_touriste = ?
            ORDER BY av.date_avis DESC
        ");
        $stmt->execute([$userId]);

        echo json_encode(['success' => true, 'reviews' => $stmt->fetchAll()]);
        exit;
    }

    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) {
            echo json_encode(['success' => false, 'error' => 'Invalid request body']);
            exit;
        }

        $id_reservation = (int)($data['id_reservation'] ?? 0);
        $note = (int)($data['note'] ?? 0);
        $commentaire = trim($data['commentaire'] ?? '');

        if (!$id_reservation) {
            echo json_encode(['success' => false, 'error' => 'Booking ID required']);
            exit;
        }
        if ($note < 1 || $note > 5) {
            echo json_encode(['success' => false, 'error' => 'Rating must be between 1 and 5']);
            exit;
        }
        if (mb_strlen($commentaire) < 10) {
            echo json_encode(['success' => false, 'error' => 'Please write at least 10 characters']);
            exit;
        }

        $check = $db->prepare("
            SELECT r.id_reservation, r.id_activite, r.statut, ar.id_utilisateur AS artisan_user_id
            FROM reservations r
            JOIN activites a ON a.id_activite = r.id_activite
            JOIN artisans ar ON ar.id_artisan = a.id_artisan
            WHERE r.id_reservation = ? AND r.id_touriste = ?
            LIMIT 1
        ");
        $check->execute([$id_reservation, $userId]);
        $booking = $check->fetch();

        if (!$booking) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Booking not found']);
            exit;
        }

        if (!in_array($booking['statut'], ['confirmee', 'terminee'], true)) {
            echo json_encode(['success' => false, 'error' => 'You can only review confirmed or completed bookings']);
            exit;
        }

        $already = $db->prepare("
            SELECT id_avis
            FROM avis
            WHERE id_touriste = ? AND id_reservation = ?
            LIMIT 1
        ");
        $already->execute([$userId, $id_reservation]);
        if ($already->fetch()) {
            echo json_encode(['success' => false, 'error' => 'You already reviewed this reservation']);
            exit;
        }

        $db->beginTransaction();

        $insert = $db->prepare("
            INSERT INTO avis (id_reservation, id_touriste, id_activite, note, commentaire, date_avis)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $insert->execute([$id_reservation, $userId, $booking['id_activite'], $note, $commentaire]);

        $update = $db->prepare("
            UPDATE artisans
            SET note_moyenne = (
                SELECT ROUND(AVG(av.note), 1)
                FROM avis av
                JOIN activites a ON av.id_activite = a.id_activite
                WHERE a.id_artisan = (
                    SELECT id_artisan
                    FROM activites
                    WHERE id_activite = ?
                )
            )
            WHERE id_artisan = (
                SELECT id_artisan
                FROM activites
                WHERE id_activite = ?
            )
        ");
        $update->execute([$booking['id_activite'], $booking['id_activite']]);

        $artisanUserId = (int)($booking['artisan_user_id'] ?? 0);
        if ($artisanUserId > 0) {
            createNotification(
                $db,
                $artisanUserId,
                'new_review',
                'New Review Received',
                'A tourist left a new ' . $note . '-star review on your activity.',
                'tuncover-artisan-dashboard.html',
                (int)$id_reservation
            );
        }

        $db->commit();

        echo json_encode(['success' => true, 'message' => 'Review submitted successfully']);
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error while saving review']);
}
