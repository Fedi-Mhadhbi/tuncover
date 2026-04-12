<?php
// php/artisan/me.php
header('Content-Type: application/json; charset=utf-8');

// Start session safely
if(session_status() === PHP_SESSION_NONE) session_start();

// Return consistent error string that JS checks for
if(!isset($_SESSION['user_id'])){
    http_response_code(401);
    echo json_encode(['success'=>false, 'error'=>'Not authenticated']); exit;
}

require_once __DIR__ . '/../config/db.php';

try {
    $db   = getDB();
    $stmt = $db->prepare("
        SELECT u.id_utilisateur, u.nom, u.prenom, u.email, u.role, u.statut,
               ar.id_artisan, ar.specialite, ar.ville, ar.bio,
               ar.statut_validation, ar.note_moyenne
        FROM utilisateurs u
        LEFT JOIN artisans ar ON ar.id_utilisateur = u.id_utilisateur
        WHERE u.id_utilisateur = ?
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $row = $stmt->fetch();

    if(!$row){
        http_response_code(404);
        echo json_encode(['success'=>false,'error'=>'User not found']); exit;
    }

    if($row['statut'] !== 'actif'){
        // Account banned — force logout
        session_destroy();
        echo json_encode(['success'=>false,'error'=>'Not authenticated']); exit;
    }

    echo json_encode([
        'success' => true,
        'user'    => [
            'id'                => (int)$row['id_utilisateur'],
            'nom'               => $row['nom'],
            'prenom'            => $row['prenom'],
            'email'             => $row['email'],
            'role'              => $row['role'],
            'id_artisan'        => $row['id_artisan'] ? (int)$row['id_artisan'] : null,
            'specialite'        => $row['specialite'] ?? '',
            'ville'             => $row['ville'] ?? '',
            'bio'               => $row['bio'] ?? '',
            'statut_validation' => $row['statut_validation'] ?? 'en_attente',
            'note_moyenne'      => (float)($row['note_moyenne'] ?? 0),
        ]
    ]);

} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>'Database error']); 
}