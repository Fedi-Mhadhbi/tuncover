<?php
// ================================================================
// /var/www/tuncover/php/artisan/listings.php
// GET    → list artisan's activities
// POST   → create new listing
// PUT    → update listing (edit or pause/publish)
// DELETE → delete listing
// ================================================================

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/activity_capacity.php';
require_once __DIR__ . '/../config/payments.php';
require_once __DIR__ . '/../config/engagement.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success'=>false,'error'=>'Not authenticated']);
    exit;
}

$userId = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];
$db     = getDB();
ensureActivityCapacityColumns($db);
ensurePaymentsTable($db);
ensureNotificationsTable($db);

// Get artisan id
$artStmt = $db->prepare("SELECT id_artisan FROM artisans WHERE id_utilisateur = ?");
$artStmt->execute([$userId]);
$artisan = $artStmt->fetch();

if (!$artisan) {
    http_response_code(403);
    echo json_encode(['success'=>false,'error'=>'Artisan profile not found']);
    exit;
}
$artisanId = $artisan['id_artisan'];

// ── GET: list my activities ──────────────────────
if ($method === 'GET') {
    $stmt = $db->prepare("
        SELECT
            a.*,
            c.nom AS categorie_nom,
            (SELECT COUNT(*) FROM reservations r WHERE r.id_activite = a.id_activite) AS total_bookings,
            (SELECT AVG(av.note) FROM avis av WHERE av.id_activite = a.id_activite) AS avg_rating,
            COALESCE(a.capacite, a.max_participants, 1) AS capacity_total,
            COALESCE(a.places_reservees, 0) AS places_reservees_live,
            GREATEST(COALESCE(a.capacite, a.max_participants, 1) - COALESCE(a.places_reservees, 0), 0) AS places_restantes
        FROM activites a
        LEFT JOIN categories c ON a.id_categorie = c.id_categorie
        WHERE a.id_artisan = ?
        ORDER BY a.date_creation DESC
    ");
    $stmt->execute([$artisanId]);
    echo json_encode(['success'=>true, 'listings'=>$stmt->fetchAll()]);
    exit;
}

// ── POST: create new listing ──────────────────────
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $titre       = trim($data['titre']       ?? '');
    $description = trim($data['description'] ?? '');
    $prix        = (float)($data['prix']     ?? 0);
    $ville       = trim($data['ville']       ?? '');
    $duree       = (int)($data['duree']      ?? 2);
    $max_p       = (int)($data['max_participants'] ?? 8);
    $statut      = in_array($data['statut'] ?? '', ['publie','brouillon']) ? $data['statut'] : 'brouillon';
    $cat_id      = (int)($data['id_categorie'] ?? 0) ?: null;

    if (!$titre) {
        echo json_encode(['success'=>false,'error'=>'Title is required']); exit;
    }
    if ($prix <= 0) {
        echo json_encode(['success'=>false,'error'=>'Price must be greater than 0']); exit;
    }

    $image_url = trim($data['image_url'] ?? '');

    $stmt = $db->prepare("
        INSERT INTO activites
            (id_artisan, id_categorie, titre, description, prix, ville, duree_heures, max_participants, capacite, places_reservees, availability_status, statut, image_url)
        VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 'disponible', ?, ?)
    ");
    $stmt->execute([$artisanId, $cat_id, $titre, $description, $prix, $ville, $duree, $max_p, $max_p, $statut, $image_url ?: null]);
    $newId = $db->lastInsertId();

    echo json_encode(['success'=>true, 'id_activite'=>$newId, 'statut'=>$statut]);
    exit;
}

// ── PUT: update listing ───────────────────────────
if ($method === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id   = (int)($data['id'] ?? 0);
    if (!$id) { echo json_encode(['success'=>false,'error'=>'ID required']); exit; }

    // Verify ownership
    $check = $db->prepare("SELECT id_activite FROM activites WHERE id_activite = ? AND id_artisan = ?");
    $check->execute([$id, $artisanId]);
    if (!$check->fetch()) {
        http_response_code(403);
        echo json_encode(['success'=>false,'error'=>'Access denied']); exit;
    }

    // Build dynamic update query
    $fields  = [];
    $params  = [];
    $allowed = ['titre','description','prix','ville','duree_heures','max_participants','statut','id_categorie','image_url'];

    $capacityUpdated = false;
    $newCapacity = null;
    foreach ($allowed as $field) {
        if (isset($data[$field])) {
            $fields[]  = "$field = ?";
            $value = $data[$field];
            if ($field === 'max_participants') {
                $capacityUpdated = true;
                $newCapacity = max(1, (int)$value);
                $value = $newCapacity;
            }
            $params[]  = $value;
        }
    }

    if ($capacityUpdated) {
        $confirmedSeats = confirmedParticipantsCount($db, $id);
        if ($newCapacity < $confirmedSeats) {
            echo json_encode(['success'=>false,'error'=>'Capacity cannot be lower than already confirmed places (' . $confirmedSeats . ')']); exit;
        }
        $fields[] = "capacite = ?";
        $params[] = $newCapacity;
        $fields[] = "places_reservees = ?";
        $params[] = $confirmedSeats;
        $fields[] = "availability_status = ?";
        $params[] = ($newCapacity - $confirmedSeats) <= 0 ? 'complet' : 'disponible';
    }

    if (empty($fields)) {
        echo json_encode(['success'=>false,'error'=>'Nothing to update']); exit;
    }

    $params[] = $id;
    $stmt = $db->prepare("UPDATE activites SET " . implode(', ', $fields) . " WHERE id_activite = ?");
    $stmt->execute($params);

    if ($capacityUpdated) {
        revalidatePendingReservations($db, $id, 'capacity_updated');
    }

    echo json_encode(['success'=>true]);
    exit;
}

// ── DELETE: remove listing ────────────────────────
if ($method === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id   = (int)($data['id'] ?? 0);
    if (!$id) { echo json_encode(['success'=>false,'error'=>'ID required']); exit; }

    // Verify ownership
    $check = $db->prepare("SELECT id_activite FROM activites WHERE id_activite = ? AND id_artisan = ?");
    $check->execute([$id, $artisanId]);
    if (!$check->fetch()) {
        http_response_code(403);
        echo json_encode(['success'=>false,'error'=>'Access denied']); exit;
    }

    try {
        $db->beginTransaction();
        cancelReservationsForActivity($db, $id, 'This activity was removed by the artisan.');
        $stmt = $db->prepare("UPDATE activites SET statut = 'supprime', availability_status = 'complet' WHERE id_activite = ?");
        $stmt->execute([$id]);
        $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        http_response_code(500);
        echo json_encode(['success'=>false,'error'=>'Could not delete activity']); exit;
    }

    echo json_encode(['success'=>true]);
    exit;
}

echo json_encode(['success'=>false,'error'=>'Method not allowed']);
