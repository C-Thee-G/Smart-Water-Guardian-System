<?php
/**
 * User Registration API
 */

header('Content-Type: application/json');

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../helpers/jwt_helper.php';
require_once '../../helpers/validation_helper.php';
require_once '../../middleware/cors.php';
require_once '../../middleware/rate_limiter.php';

// Handle CORS
$cors = new CORS();
$cors->handle();

// Rate limiting
$limiter = new RateLimiter();
$limiter->check($_SERVER['REMOTE_ADDR']);

// Get input data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid JSON data'
    ]);
    exit;
}

// Validate input
$validator = new Validator();
$rules = [
    'name' => 'required|min:2|max:100',
    'surname' => 'required|min:2|max:100',
    'email' => 'required|email',
    'password' => 'required|password',
    'phone' => 'required|phone',
    'address' => 'required|min:5'
];
$errors = $validator->validate($data, $rules);

if ($validator->hasErrors()) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'errors' => $errors
    ]);
    exit;
}

// Get database connection
$db = Database::getInstance()->getConnection();

try {
    // Check if email already exists
    $check_query = "SELECT id FROM users WHERE email = :email";
    $stmt = $db->prepare($check_query);
    $stmt->bindParam(':email', $data['email']);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'message' => 'Email already registered'
        ]);
        exit;
    }
    
    // Generate user ID
    $user_id = 'USR_' . uniqid();
    
    // Hash password
    $hashed_password = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
    
    // Insert user
    $query = "INSERT INTO users (id, name, surname, email, password, phone, address, role) 
              VALUES (:id, :name, :surname, :email, :password, :phone, :address, 'consumer')";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $user_id);
    $stmt->bindParam(':name', $data['name']);
    $stmt->bindParam(':surname', $data['surname']);
    $stmt->bindParam(':email', $data['email']);
    $stmt->bindParam(':password', $hashed_password);
    $stmt->bindParam(':phone', $data['phone']);
    $stmt->bindParam(':address', $data['address']);
    $stmt->execute();
    
    // Generate JWT token
    $token_data = [
        'user_id' => $user_id,
        'email' => $data['email'],
        'role' => 'consumer'
    ];
    $token = JWT::generate($token_data);
    
    // Log registration
    $log_query = "INSERT INTO system_logs (user_id, action, details, ip_address, user_agent) 
                  VALUES (:user_id, 'register', :details, :ip, :user_agent)";
    $stmt = $db->prepare($log_query);
    $details = json_encode(['email' => $data['email']]);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':details', $details);
    $stmt->bindParam(':ip', $_SERVER['REMOTE_ADDR']);
    $stmt->bindParam(':user_agent', $_SERVER['HTTP_USER_AGENT']);
    $stmt->execute();
    
    // Send welcome email (async)
    // sendWelcomeEmail($data['email'], $data['name']);
    
    echo json_encode([
        'success' => true,
        'message' => 'Registration successful',
        'token' => $token,
        'user' => [
            'id' => $user_id,
            'name' => $data['name'],
            'surname' => $data['surname'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'role' => 'consumer'
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Registration error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Registration failed. Please try again later.'
    ]);
}
?>
