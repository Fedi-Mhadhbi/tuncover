<?php
// ================================================================
// /var/www/tuncover/php/artisan/bookings.php
// GET  → list artisan's bookings
// POST → accept or reject a booking
// ================================================================

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success'=>false,'error'=>'Not authenticated']);
    exit;
}

$userId = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];
$db     = getDB();

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
            r.montant_total,
            r.statut,
            a.titre AS activite_titre,
            u.nom AS tourist_nom,
            u.prenom AS tourist_prenom,
            u.email AS tourist_email
        FROM reservations r
        JOIN activites a ON r.id_activite = a.id_activite
        JOIN utilisateurs u ON r.id_touriste = u.id_utilisateur
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

    if (!$id || !in_array($statut, ['confirmee','annulee'])) {
        echo json_encode(['success'=>false,'error'=>'Invalid parameters']); exit;
    }

    // Verify this booking belongs to this artisan
    $check = $db->prepare("
        SELECT r.id_reservation
        FROM reservations r
        JOIN activites a ON r.id_activite = a.id_activite
        WHERE r.id_reservation = ? AND a.id_artisan = ?
    ");
    $check->execute([$id, $artisanId]);
    if (!$check->fetch()) {
        http_response_code(403);
        echo json_encode(['success'=>false,'error'=>'Booking not found or access denied']);
        exit;
    }

    $stmt = $db->prepare("UPDATE reservations SET statut = ? WHERE id_reservation = ?");
    $stmt->execute([$statut, $id]);
    echo json_encode(['success'=>true, 'statut'=>$statut]);
    exit;
}

echo json_encode(['success'=>false,'error'=>'Method not allowed']);