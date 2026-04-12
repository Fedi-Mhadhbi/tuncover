<?php
// /var/www/tuncover/php/auth/register.php

header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success'=>false,'error'=>'Method not allowed']); exit;
}

require_once __DIR__ . '/../config/db.php';
session_start();

$data       = json_decode(file_get_contents('php://input'), true);
$nom        = trim($data['nom']        ?? '');
$prenom     = trim($data['prenom']     ?? '');
$email      = trim($data['email']      ?? '');
$password   = trim($data['password']   ?? '');
$role       = in_array($data['role'] ?? '', ['touriste','artisan']) ? $data['role'] : 'touriste';
$specialite = trim($data['specialite'] ?? '');
$ville      = trim($data['ville']      ?? '');

// Validation
if (!$nom)    { echo json_encode(['success'=>false,'field'=>'regNom',   'error'=>'Last name required']);   exit; }
if (!$prenom) { echo json_encode(['success'=>false,'field'=>'regPrenom','error'=>'First name required']); exit; }
if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success'=>false,'field'=>'regEmail','error'=>'Valid email required']); exit;
}
if (!$password || strlen($password) < 8) {
    echo json_encode(['success'=>false,'field'=>'regPwd','error'=>'Password min 8 characters']); exit;
}

try {
    $db = getDB();

    // Check email not already used
    $check = $db->prepare("SELECT id_utilisateur FROM utilisateurs WHERE email = ? LIMIT 1");
    $check->execute([$email]);
    if ($check->fetch()) {
        echo json_encode(['success'=>false,'field'=>'email','error'=>'Email already registered']); exit;
    }

    // Insert user
    $hashed = password_hash($password, PASSWORD_BCRYPT);
    $stmt   = $db->prepare("INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role, statut) VALUES (?,?,?,?,?,'actif')");
    $stmt->execute([$nom, $prenom, $email, $hashed, $role]);
    $userId = (int)$db->lastInsertId();

    // If artisan → create artisan row
    if ($role === 'artisan') {
        $db->prepare("INSERT INTO artisans (id_utilisateur, specialite, ville, statut_validation) VALUES (?,?,?,'en_attente')")
           ->execute([$userId, $specialite, $ville]);
    }

    // Save session
    $_SESSION['user_id'] = $userId;
    $_SESSION['role']    = $role;
    $_SESSION['prenom']  = $prenom;
    $_SESSION['nom']     = $nom;
    $_SESSION['email']   = $email;

    echo json_encode([
        'success' => true,
        'user' => [
            'id'     => $userId,
            'nom'    => $nom,
            'prenom' => $prenom,
            'email'  => $email,
            'role'   => $role,
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>'Could not create account: ' . $e->getMessage()]);
}