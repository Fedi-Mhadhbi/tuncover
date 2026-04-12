<?php
// /var/www/tuncover/php/auth/login.php

header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success'=>false,'error'=>'Method not allowed']); exit;
}

require_once __DIR__ . '/../config/db.php';
session_start();

$data     = json_decode(file_get_contents('php://input'), true);
$email    = trim($data['email']    ?? '');
$password = trim($data['password'] ?? '');

if (!$email)  { echo json_encode(['success'=>false,'field'=>'email',   'error'=>'Email is required']);    exit; }
if (!$password){ echo json_encode(['success'=>false,'field'=>'password','error'=>'Password is required']); exit; }

try {
    $db   = getDB();
    $stmt = $db->prepare("SELECT * FROM utilisateurs WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>'Database error']); exit;
}

if (!$user) {
    echo json_encode(['success'=>false,'field'=>'email','error'=>'No account found with this email']); exit;
}
if ($user['statut'] !== 'actif') {
    echo json_encode(['success'=>false,'error'=>'Account suspended. Contact support.']); exit;
}
if (!password_verify($password, $user['mot_de_passe'])) {
    echo json_encode(['success'=>false,'field'=>'password','error'=>'Incorrect password']); exit;
}

// Save to PHP session
$_SESSION['user_id'] = (int)$user['id_utilisateur'];
$_SESSION['role']    = $user['role'];
$_SESSION['prenom']  = $user['prenom'];
$_SESSION['nom']     = $user['nom'];
$_SESSION['email']   = $user['email'];

// Return user data — JS stores this in localStorage
echo json_encode([
    'success' => true,
    'user' => [
        'id'     => (int)$user['id_utilisateur'],
        'nom'    => $user['nom'],
        'prenom' => $user['prenom'],
        'email'  => $user['email'],
        'role'   => $user['role'],
    ]
]);