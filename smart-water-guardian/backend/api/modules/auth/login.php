<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once '../../config/database.php';
require_once '../../helpers/jwt_helper.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['email']) || !isset($data['password'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email and password required']);
    exit;
}

$db = Database::getInstance()->getConnection();

$query = "SELECT * FROM users WHERE email = :email AND is_active = 1";
$stmt = $db->prepare($query);
$stmt->bindParam(':email', $data['email']);
$stmt->execute();

$user = $stmt->fetch();

if (!$user || !password_verify($data['password'], $user['password'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
    exit;
}

// Generate JWT
$token = JWT::generate([
    'user_id' => $user['id'],
    'email' => $user['email'],
    'role' => $user['role']
]);

// Log login
$log_query = "INSERT INTO system_logs (user_id, action, ip_address) 
              VALUES (:user_id, 'login', :ip)";
$stmt = $db->prepare($log_query);
$stmt->bindParam(':user_id', $user['id']);
$stmt->bindParam(':ip', $_SERVER['REMOTE_ADDR']);
$stmt->execute();

echo json_encode([
    'success' => true,
    'token' => $token,
    'user' => [
        'id' => $user['id'],
        'name' => $user['name'],
        'surname' => $user['surname'],
        'email' => $user['email'],
        'role' => $user['role']
    ]
]);
?>
