<?php
// php/auth/me.php — returns the currently logged-in user from session
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

require_once __DIR__ . '/../config/db.php';

try {
    $db   = getDB();
    $stmt = $db->prepare("SELECT id_utilisateur, nom, prenom, email, role, statut FROM utilisateurs WHERE id_utilisateur = ? LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user || $user['statut'] !== 'actif') {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Account not active']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'user'    => [
            'id'     => (int)$user['id_utilisateur'],
            'nom'    => $user['nom'],
            'prenom' => $user['prenom'],
            'email'  => $user['email'],
            'role'   => $user['role'],
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}