<?php
require_once '../helpers/jwt_helper.php';

class AuthMiddleware {
    public function validateToken() {
        $headers = getallheaders();
        $auth_header = $headers['Authorization'] ?? '';
        
        if (empty($auth_header)) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Authorization header required']);
            exit;
        }
        
        // Extract token from "Bearer <token>"
        if (!preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Invalid authorization format']);
            exit;
        }
        
        $token = $matches[1];
        $payload = JWT::verify($token);
        
        if (!$payload) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Invalid or expired token']);
            exit;
        }
        
        return $payload;
    }
}
?>
