<?php
/**
 * Rate Limiter Middleware
 */

class RateLimiter {
    private $max_requests;
    private $window;
    private $storage;
    
    public function __construct($max_requests = RATE_LIMIT_REQUESTS, $window = RATE_LIMIT_WINDOW) {
        $this->max_requests = $max_requests;
        $this->window = $window;
        $this->storage = new RateLimitStorage();
    }
    
    public function check($key) {
        $current = time();
        $window_start = $current - $this->window;
        
        // Clean old requests
        $this->storage->clean($key, $window_start);
        
        // Count requests in current window
        $count = $this->storage->count($key);
        
        if ($count >= $this->max_requests) {
            $this->sendError(429, 'Rate limit exceeded. Maximum ' . $this->max_requests . ' requests per ' . ($this->window / 60) . ' minutes');
            return false;
        }
        
        // Add current request
        $this->storage->add($key, $current);
        
        // Set headers
        header('X-RateLimit-Limit: ' . $this->max_requests);
        header('X-RateLimit-Remaining: ' . ($this->max_requests - $count - 1));
        header('X-RateLimit-Reset: ' . ($current + $this->window));
        
        return true;
    }
    
    private function sendError($code, $message) {
        http_response_code($code);
        echo json_encode([
            'success' => false,
            'message' => $message
        ]);
        exit;
    }
}

class RateLimitStorage {
    private $storage_file;
    
    public function __construct() {
        $this->storage_file = sys_get_temp_dir() . '/rate_limit_storage.json';
        if (!file_exists($this->storage_file)) {
            file_put_contents($this->storage_file, json_encode([]));
        }
    }
    
    private function readStorage() {
        $content = file_get_contents($this->storage_file);
        return json_decode($content, true) ?: [];
    }
    
    private function writeStorage($data) {
        file_put_contents($this->storage_file, json_encode($data));
    }
    
    public function clean($key, $window_start) {
        $data = $this->readStorage();
        if (isset($data[$key])) {
            $data[$key] = array_filter($data[$key], function($timestamp) use ($window_start) {
                return $timestamp >= $window_start;
            });
            if (empty($data[$key])) {
                unset($data[$key]);
            }
            $this->writeStorage($data);
        }
    }
    
    public function count($key) {
        $data = $this->readStorage();
        return isset($data[$key]) ? count($data[$key]) : 0;
    }
    
    public function add($key, $timestamp) {
        $data = $this->readStorage();
        if (!isset($data[$key])) {
            $data[$key] = [];
        }
        $data[$key][] = $timestamp;
        $this->writeStorage($data);
    }
}
?>
