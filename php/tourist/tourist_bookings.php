<?php
// ================================================================
// /var/www/tuncover/php/tourist/tourist_bookings.php
// GET    → list tourist's own reservations
// DELETE → cancel a reservation (only if en_attente)
// ================================================================

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success'=>false,'error'=>'Not authenticated']); exit;
}

$userId = (int)$_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];
$db     = getDB();

// ── GET: list my bookings ─────────────────────────────
if ($method === 'GET') {
    $statut = $_GET['statut'] ?? '';

    $sql = "
        SELECT
            r.id_reservation,
            r.date_reservation,
            r.date_activite,
            r.heure_activite,
            r.nb_participants,
            r.montant_total,
            r.statut,
            a.titre          AS activite_titre,
            a.ville          AS activite_ville,
            a.prix           AS prix_unitaire,
            u2.nom           AS artisan_nom,
            u2.prenom        AS artisan_prenom
        FROM reservations r
        JOIN activites a   ON r.id_activite    = a.id_activite
        JOIN artisans  ar  ON a.id_artisan      = ar.id_artisan
        JOIN utilisateurs u2 ON ar.id_utilisateur = u2.id_utilisateur
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
    echo json_encode(['success'=>true, 'bookings'=>$stmt->fetchAll()]);
    exit;
}

// ── DELETE: cancel booking ────────────────────────────
if ($method === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id   = (int)($data['id'] ?? 0);

    if (!$id) { echo json_encode(['success'=>false,'error'=>'ID required']); exit; }

    // Verify this booking belongs to this tourist and is still cancellable
    $check = $db->prepare("
        SELECT id_reservation, statut FROM reservations
        WHERE id_reservation = ? AND id_touriste = ?
    ");
    $check->execute([$id, $userId]);
    $booking = $check->fetch();

    if (!$booking) {
        http_response_code(403);
        echo json_encode(['success'=>false,'error'=>'Booking not found']); exit;
    }
    if ($booking['statut'] === 'annulee') {
        echo json_encode(['success'=>false,'error'=>'Already cancelled']); exit;
    }
    if ($booking['statut'] === 'confirmee') {
        echo json_encode(['success'=>false,'error'=>'Cannot cancel a confirmed booking — contact support']); exit;
    }

    $stmt = $db->prepare("UPDATE reservations SET statut = 'annulee' WHERE id_reservation = ?");
    $stmt->execute([$id]);
    echo json_encode(['success'=>true]);
    exit;
}

echo json_encode(['success'=>false,'error'=>'Method not allowed']);