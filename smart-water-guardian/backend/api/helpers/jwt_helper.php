<?php
/**
 * JWT (JSON Web Token) Helper Class
 */

class JWT {
    private static $secret = JWT_SECRET;
    private static $algorithm = 'HS256';
    
    /**
     * Generate JWT Token
     */
    public static function generate($payload) {
        $header = json_encode([
            'typ' => 'JWT',
            'alg' => self::$algorithm
        ]);
        
        $payload['iat'] = time();
        $payload['exp'] = time() + JWT_EXPIRY;
        
        $base64_header = self::base64UrlEncode($header);
        $base64_payload = self::base64UrlEncode(json_encode($payload));
        
        $signature = hash_hmac(
            'sha256',
            $base64_header . '.' . $base64_payload,
            self::$secret,
            true
        );
        $base64_signature = self::base64UrlEncode($signature);
        
        return $base64_header . '.' . $base64_payload . '.' . $base64_signature;
    }
    
    /**
     * Verify JWT Token
     */
    public static function verify($token) {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }
        
        list($header, $payload, $signature) = $parts;
        
        $expected_signature = hash_hmac(
            'sha256',
            $header . '.' . $payload,
            self::$secret,
            true
        );
        $expected_signature = self::base64UrlEncode($expected_signature);
        
        if ($signature !== $expected_signature) {
            return false;
        }
        
        $payload_data = json_decode(self::base64UrlDecode($payload), true);
        if (!$payload_data) {
            return false;
        }
        
        if (isset($payload_data['exp']) && $payload_data['exp'] < time()) {
            return false;
        }
        
        return $payload_data;
    }
    
    /**
     * Get payload from token without verification
     */
    public static function getPayload($token) {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }
        
        return json_decode(self::base64UrlDecode($parts[1]), true);
    }
    
    private static function base64UrlEncode($data) {
        return str_replace(
            ['+', '/', '='],
            ['-', '_', ''],
            base64_encode($data)
        );
    }
    
    private static function base64UrlDecode($data) {
        return base64_decode(str_replace(
            ['-', '_'],
            ['+', '/'],
            $data
        ));
    }
}
?>
