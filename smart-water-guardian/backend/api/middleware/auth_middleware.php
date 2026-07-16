<?php
/**
 * Authentication Middleware
 */

require_once dirname(__DIR__) . '/helpers/jwt_helper.php';

class AuthMiddleware {
    private $allowed_roles = [];
    
    public function __construct($allowed_roles = []) {
        $this->allowed_roles = $allowed_roles;
    }
    
    /**
     * Validate JWT token and check roles
     */
    public function validate() {
        $headers = getallheaders();
        $auth_header = $headers['Authorization'] ?? '';
        
        if (empty($auth_header)) {
            $this->sendError(401, 'Authorization header required');
            return false;
        }
        
        if (!preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) {
            $this->sendError(401, 'Invalid authorization format. Use: Bearer <token>');
            return false;
        }
        
        $token = $matches[1];
        $payload = JWT::verify($token);
        
        if (!$payload) {
            $this->sendError(401, 'Invalid or expired token');
            return false;
        }
        
        // Check roles
        if (!empty($this->allowed_roles)) {
            if (!in_array($payload['role'], $this->allowed_roles)) {
                $this->sendError(403, 'Access denied. Required role: ' . implode(' or ', $this->allowed_roles));
                return false;
            }
        }
        
        return $payload;
    }
    
    /**
     * Send error response
     */
    private function sendError($code, $message) {
        http_response_code($code);
        echo json_encode([
            'success' => false,
            'message' => $message
        ]);
        exit;
    }
}
?>
