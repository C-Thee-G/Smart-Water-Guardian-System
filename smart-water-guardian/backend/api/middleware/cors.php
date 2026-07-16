<?php
/**
 * CORS Middleware
 */

class CORS {
    private $allowed_origins = [
        'http://localhost',
        'http://localhost:3000',
        'http://localhost:8080',
        'https://smart-water-guardian.example.com'
    ];
    
    private $allowed_methods = ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'];
    private $allowed_headers = ['Content-Type', 'Authorization', 'X-API-Key'];
    private $exposed_headers = ['X-Total-Count'];
    private $max_age = 86400; // 24 hours
    
    public function handle() {
        // Handle preflight OPTIONS request
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            $this->sendHeaders();
            exit(0);
        }
        
        $this->sendHeaders();
    }
    
    private function sendHeaders() {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        
        if (in_array($origin, $this->allowed_origins)) {
            header('Access-Control-Allow-Origin: ' . $origin);
        } else {
            // Allow specific origins only
            header('Access-Control-Allow-Origin: ' . $this->allowed_origins[0]);
        }
        
        header('Access-Control-Allow-Methods: ' . implode(', ', $this->allowed_methods));
        header('Access-Control-Allow-Headers: ' . implode(', ', $this->allowed_headers));
        header('Access-Control-Expose-Headers: ' . implode(', ', $this->exposed_headers));
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: ' . $this->max_age);
    }
}
?>
