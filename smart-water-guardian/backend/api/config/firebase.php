<?php
class Firebase {
    private $base_url;
    private $secret;
    
    public function __construct() {
        $this->base_url = FIREBASE_URL;
        $this->secret = FIREBASE_SECRET;
    }
    
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
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
    
    public function get($path) {
        $url = $this->base_url . $path . '.json?auth=' . $this->secret;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
    
    public function pushRealtimeReading($meter_id, $reading) {
        $path = "meters/{$meter_id}/lastReading";
        return $this->set($path, $reading);
    }
    
    public function pushAlert($user_id, $alert) {
        $path = "alerts/{$user_id}/" . uniqid();
        return $this->set($path, $alert);
    }
}
?>
