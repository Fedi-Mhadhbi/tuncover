<?php
// ================================================================
// /var/www/tuncover/php/artisan/profile.php
// GET  → get artisan profile
// POST → update artisan profile
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

// ── GET: load profile ────────────────────────────
if ($method === 'GET') {
    $stmt = $db->prepare("
        SELECT
            u.id_utilisateur, u.nom, u.prenom, u.email, u.telephone,
            a.id_artisan, a.specialite, a.ville, a.bio,
            a.statut_validation, a.note_moyenne
        FROM utilisateurs u
        JOIN artisans a ON a.id_utilisateur = u.id_utilisateur
        WHERE u.id_utilisateur = ?
    ");
    $stmt->execute([$userId]);
    $profile = $stmt->fetch();

    if (!$profile) {
        echo json_encode(['success'=>false,'error'=>'Profile not found']); exit;
    }

    echo json_encode(['success'=>true, 'profile'=>$profile]);
    exit;
}

// ── POST: update profile ──────────────────────────
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $nom         = trim($data['nom']         ?? '');
    $prenom      = trim($data['prenom']      ?? '');
    $telephone   = trim($data['telephone']   ?? '');
    $specialite  = trim($data['specialite']  ?? '');
    $ville       = trim($data['ville']       ?? '');
    $bio         = trim($data['bio']         ?? '');
    $newPassword = $data['password'] ?? '';

    $db->beginTransaction();

    try {
        // Update utilisateurs table
        $fields  = ['nom = ?', 'prenom = ?', 'telephone = ?'];
        $params  = [$nom, $prenom, $telephone];

        if (!empty($newPassword)) {
            if (strlen($newPassword) < 8) {
                echo json_encode(['success'=>false,'error'=>'Password must be at least 8 characters']); exit;
            }
            $fields[] = 'mot_de_passe = ?';
            $params[] = password_hash($newPassword, PASSWORD_BCRYPT);
        }

        $params[] = $userId;
        $db->prepare("UPDATE utilisateurs SET " . implode(', ', $fields) . " WHERE id_utilisateur = ?")
           ->execute($params);

        // Update artisans table
        $db->prepare("UPDATE artisans SET specialite = ?, ville = ?, bio = ? WHERE id_utilisateur = ?")
           ->execute([$specialite, $ville, $bio, $userId]);

        $db->commit();
        echo json_encode(['success'=>true]);

    } catch (PDOException $e) {
        $db->rollBack();
        http_response_code(500);
        echo json_encode(['success'=>false,'error'=>'Update failed']);
    }

    exit;
}

echo json_encode(['success'=>false,'error'=>'Method not allowed']);