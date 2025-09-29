<?php
// backend/config/db.php
// Conexión PDO a MySQL (WAMP): usuario root sin contraseña
// Asegúrate de haber importado antes db/schema.sql para crear la base de datos "clinica_moya".

class Database {
    private string $host = '127.0.0.1';
    private int $port = 3306; // Puerto por defecto de MySQL en WAMP
    private string $dbName = 'clinica_moya';
    private string $username = 'root';
    private string $password = '';

    private ?\PDO $conn = null;

    public function getConnection(): \PDO {
        if ($this->conn instanceof \PDO) {
            return $this->conn;
        }

        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $this->host, $this->port, $this->dbName);

        $options = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
            // timeouts razonables
            \PDO::ATTR_TIMEOUT => 5,
        ];

        $this->conn = new \PDO($dsn, $this->username, $this->password, $options);
        // Forzar zona horaria si deseas
        $this->conn->exec("SET time_zone = '+00:00'");
        return $this->conn;
    }
}

// Helper global opcional
function db(): \PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = (new Database())->getConnection();
    }
    return $pdo;
}
