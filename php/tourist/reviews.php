<?php
// php/tourist/reviews.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';
if(session_status()===PHP_SESSION_NONE) session_start();

if(!isset($_SESSION['user_id'])){
    http_response_code(401);
    echo json_encode(['success'=>false,'error'=>'Not authenticated']); exit;
}

$userId = (int)$_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];
$db     = getDB();

// ── GET: my reviews ────────────────────────────────
if($method === 'GET'){
    $stmt = $db->prepare("
        SELECT av.id_avis, av.note, av.commentaire, av.date_avis,
               a.titre AS activite_titre, a.ville,
               CONCAT(u.prenom,' ',u.nom) AS artisan_nom
        FROM avis av
        JOIN activites a    ON av.id_activite    = a.id_activite
        JOIN artisans ar    ON a.id_artisan       = ar.id_artisan
        JOIN utilisateurs u ON ar.id_utilisateur  = u.id_utilisateur
        WHERE av.id_touriste = ?
        ORDER BY av.date_avis DESC
    ");
    $stmt->execute([$userId]);
    echo json_encode(['success'=>true,'reviews'=>$stmt->fetchAll()]); exit;
}

// ── POST: submit a review ──────────────────────────
if($method === 'POST'){
    $data           = json_decode(file_get_contents('php://input'), true) ?? [];
    $id_reservation = (int)($data['id_reservation'] ?? 0);
    $note           = (int)($data['note']           ?? 0);
    $commentaire    = trim($data['commentaire']      ?? '');

    if(!$id_reservation){
        echo json_encode(['success'=>false,'error'=>'Booking ID required']); exit;
    }
    if($note < 1 || $note > 5){
        echo json_encode(['success'=>false,'error'=>'Rating must be between 1 and 5']); exit;
    }
    if(strlen($commentaire) < 10){
        echo json_encode(['success'=>false,'error'=>'Please write at least 10 characters']); exit;
    }

    // Verify booking belongs to this tourist
    $check = $db->prepare("
        SELECT r.id_reservation, r.id_activite, r.statut
        FROM reservations r
        WHERE r.id_reservation = ? AND r.id_touriste = ?
        LIMIT 1
    ");
    $check->execute([$id_reservation, $userId]);
    $booking = $check->fetch();

    if(!$booking){
        http_response_code(403);
        echo json_encode(['success'=>false,'error'=>'Booking not found']); exit;
    }

    // Accept confirmee OR terminee — tourist can review after artisan confirms
    // (DB uses 'terminee' not 'completee')
    if(!in_array($booking['statut'], ['confirmee','terminee'])){
        echo json_encode(['success'=>false,'error'=>'You can only review confirmed or completed bookings']); exit;
    }

    // Check not already reviewed for this activity
    $already = $db->prepare("
        SELECT id_avis FROM avis
        WHERE id_touriste = ? AND id_activite = ?
        LIMIT 1
    ");
    $already->execute([$userId, $booking['id_activite']]);
    if($already->fetch()){
        echo json_encode(['success'=>false,'error'=>'You already reviewed this activity']); exit;
    }

    // Insert review
    $db->prepare("
        INSERT INTO avis (id_touriste, id_activite, note, commentaire, date_avis)
        VALUES (?, ?, ?, ?, NOW())
    ")->execute([$userId, $booking['id_activite'], $note, $commentaire]);

    // Update artisan's average rating
    $db->prepare("
        UPDATE artisans SET note_moyenne = (
            SELECT ROUND(AVG(av.note),1)
            FROM avis av
            JOIN activites a ON av.id_activite = a.id_activite
            WHERE a.id_artisan = (SELECT id_artisan FROM activites WHERE id_activite = ?)
        )
        WHERE id_artisan = (SELECT id_artisan FROM activites WHERE id_activite = ?)
    ")->execute([$booking['id_activite'], $booking['id_activite']]);

    echo json_encode(['success'=>true,'message'=>'Review submitted — thank you!']); exit;
}

echo json_encode(['success'=>false,'error'=>'Method not allowed']);