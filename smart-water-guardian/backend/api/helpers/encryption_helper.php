<?php
/**
 * Encryption Helper Class
 */

class Encryption {
    private static $method = 'AES-256-CBC';
    private static $key = 'SMART_WATER_ENCRYPTION_KEY_2026';
    
    /**
     * Encrypt data
     */
    public static function encrypt($data) {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(self::$method));
        $encrypted = openssl_encrypt(
            $data,
            self::$method,
            self::$key,
            0,
            $iv
        );
        return base64_encode($iv . $encrypted);
    }
    
    /**
     * Decrypt data
     */
    public static function decrypt($encrypted_data) {
        $data = base64_decode($encrypted_data);
        $iv_length = openssl_cipher_iv_length(self::$method);
        $iv = substr($data, 0, $iv_length);
        $encrypted = substr($data, $iv_length);
        return openssl_decrypt(
            $encrypted,
            self::$method,
            self::$key,
            0,
            $iv
        );
    }
    
    /**
     * Hash data (one-way)
     */
    public static function hash($data) {
        return password_hash($data, PASSWORD_BCRYPT, ['cost' => 12]);
    }
    
    /**
     * Verify hash
     */
    public static function verifyHash($data, $hash) {
        return password_verify($data, $hash);
    }
}
?>
