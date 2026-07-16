<?php
/**
 * Firebase Realtime Database Configuration
 */

require_once 'constants.php';

class Firebase {
    private $base_url;
    private $secret;
    private $timeout = 30;
    
    public function __construct() {
        $this->base_url = FIREBASE_URL;
        $this->secret = FIREBASE_SECRET;
    }
    
    /**
     * Set data to Firebase
     */
    public function set($path, $data) {
        $url = $this->base_url . $path . '.json?auth=' . $this->secret;
        $json_data = json_encode($data);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            error_log("Firebase set error: " . $error);
            return false;
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Get data from Firebase
     */
    public function get($path) {
        $url = $this->base_url . $path . '.json?auth=' . $this->secret;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            error_log("Firebase get error: " . $error);
            return null;
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Update data in Firebase
     */
    public function update($path, $data) {
        $url = $this->base_url . $path . '.json?auth=' . $this->secret;
        $json_data = json_encode($data);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
    
    /**
     * Delete data from Firebase
     */
    public function delete($path) {
        $url = $this->base_url . $path . '.json?auth=' . $this->secret;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
    
    /**
     * Push real-time reading to Firebase
     */
    public function pushRealtimeReading($meter_id, $reading) {
        $path = "meters/{$meter_id}/lastReading";
        return $this->set($path, $reading);
    }
    
    /**
     * Push alert to Firebase
     */
    public function pushAlert($user_id, $alert) {
        $path = "alerts/{$user_id}/" . uniqid();
        return $this->set($path, $alert);
    }
    
    /**
     * Update consumer dashboard data
     */
    public function updateConsumerDashboard($user_id, $data) {
        $path = "dashboard/consumer/{$user_id}";
        return $this->set($path, $data);
    }
    
    /**
     * Update municipal dashboard data
     */
    public function updateMunicipalDashboard($data) {
        $path = "dashboard/municipal";
        return $this->set($path, $data);
    }
}
?>
