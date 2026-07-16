<?php
/**
 * Database Configuration Class
 * Handles database connection using Singleton pattern
 */

class Database {
    private static $instance = null;
    private $conn;
    
    private $host = "localhost";
    private $db_name = "smart_water_db";
    private $username = "root";
    private $password = "";
    private $port = 3306;
    
    private function __construct() {
        try {
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->db_name};charset=utf8mb4";
            $this->conn = new PDO($dsn, $this->username, $this->password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => false
            ]);
        } catch(PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    public function beginTransaction() {
        $this->conn->beginTransaction();
    }
    
    public function commit() {
        $this->conn->commit();
    }
    
    public function rollback() {
        $this->conn->rollback();
    }
    
    public function lastInsertId() {
        return $this->conn->lastInsertId();
    }
}
?>
