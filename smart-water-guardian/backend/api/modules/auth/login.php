<?php
/**
 * User Login API
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
    'email' => 'required|email',
    'password' => 'required|min:8'
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
    // Find user by email
    $query = "SELECT * FROM users WHERE email = :email AND is_active = 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $data['email']);
    $stmt->execute();
    
    $user = $stmt->fetch();
    
    if (!$user) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid email or password'
        ]);
        exit;
    }
    
    // Verify password
    if (!password_verify($data['password'], $user['password'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid email or password'
        ]);
        exit;
    }
    
    // Check if account is locked
    $lock_query = "SELECT * FROM system_logs 
                   WHERE user_id = :user_id 
                   AND action = 'login_failed' 
                   AND created_at > DATE_SUB(NOW(), INTERVAL 30 MINUTE)
                   ORDER BY created_at DESC";
    $stmt = $db->prepare($lock_query);
    $stmt->bindParam(':user_id', $user['id']);
    $stmt->execute();
    
    $failed_attempts = $stmt->rowCount();
    if ($failed_attempts >= 5) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Account temporarily locked due to multiple failed attempts. Please try again later.'
        ]);
        exit;
    }
    
    // Generate JWT token
    $token_data = [
        'user_id' => $user['id'],
        'email' => $user['email'],
        'role' => $user['role']
    ];
    $token = JWT::generate($token_data);
    
    // Log successful login
    $log_query = "INSERT INTO system_logs (user_id, action, details, ip_address, user_agent) 
                  VALUES (:user_id, 'login_success', :details, :ip, :user_agent)";
    $stmt = $db->prepare($log_query);
    $details = json_encode(['email' => $user['email']]);
    $stmt->bindParam(':user_id', $user['id']);
    $stmt->bindParam(':details', $details);
    $stmt->bindParam(':ip', $_SERVER['REMOTE_ADDR']);
    $stmt->bindParam(':user_agent', $_SERVER['HTTP_USER_AGENT']);
    $stmt->execute();
    
    // Return response
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'token' => $token,
        'user' => [
            'id' => $user['id'],
            'name' => $user['name'],
            'surname' => $user['surname'],
            'email' => $user['email'],
            'phone' => $user['phone'],
            'role' => $user['role']
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Login error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred during login'
    ]);
}
?>
