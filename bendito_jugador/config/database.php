<?php
declare(strict_types=1);

class Database
{
    private string $host = 'localhost';
    private string $dbName = 'bendito_jugador';
    private string $username = 'root';
    private string $password = '';
    private ?PDO $connection = null;

    public function getConnection(): PDO
    {
        if ($this->connection instanceof PDO) {
            return $this->connection;
        }

        try {
            $this->connection = new PDO(
                sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $this->host, $this->dbName),
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $exception) {
            throw new RuntimeException('No se pudo establecer conexión con la base de datos.', 0, $exception);
        }

        return $this->connection;
    }
}
