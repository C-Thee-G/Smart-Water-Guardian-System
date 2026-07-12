<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once '../../config/database.php';
require_once '../../helpers/jwt_helper.php';

$data = json_decode(file_get_contents('php://input'), true);

// Validation
$required_fields = ['name', 'surname', 'email', 'password', 'phone'];
foreach ($required_fields as $field) {
    if (empty($data[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => ucfirst($field) . ' is required']);
        exit;
    }
}

// Validate email
if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

// Validate password strength
if (strlen($data['password']) < 8) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters']);
    exit;
}

$db = Database::getInstance()->getConnection();

// Check if email exists
$check_query = "SELECT id FROM users WHERE email = :email";
$stmt = $db->prepare($check_query);
$stmt->bindParam(':email', $data['email']);
$stmt->execute();

if ($stmt->rowCount() > 0) {
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'Email already registered']);
    exit;
}

// Create user
$user_id = 'USR_' . uniqid();
$hashed_password = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);

$query = "INSERT INTO users (id, name, surname, email, password, phone, address, role) 
          VALUES (:id, :name, :surname, :email, :password, :phone, :address, 'consumer')";

$stmt = $db->prepare($query);
$stmt->bindParam(':id', $user_id);
$stmt->bindParam(':name', $data['name']);
$stmt->bindParam(':surname', $data['surname']);
$stmt->bindParam(':email', $data['email']);
$stmt->bindParam(':password', $hashed_password);
$stmt->bindParam(':phone', $data['phone']);
$stmt->bindParam(':address', $data['address'] ?? '');

if ($stmt->execute()) {
    // Generate JWT
    $token = JWT::generate([
        'user_id' => $user_id,
        'email' => $data['email'],
        'role' => 'consumer'
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Registration successful',
        'token' => $token,
        'user' => [
            'id' => $user_id,
            'name' => $data['name'],
            'surname' => $data['surname'],
            'email' => $data['email'],
            'role' => 'consumer'
        ]
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Registration failed']);
}
?>
