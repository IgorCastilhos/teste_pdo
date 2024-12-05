<?php

class Database {
    private $hostname;
    private $dbname;
    private $username;
    private $password;
    private $conn;
    private $options;

    public function __construct(
        string $hostname = '127.0.0.1',
        string $dbname = 'pdo_practice',
        string $username = 'root',
        string $password = 'secret'
    ) {
        $this->hostname = $hostname;
        $this->dbname = $dbname;
        $this->username = $username;
        $this->password = $password;
        $this->options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];

        $this->connect();
    }

    private function connect(): void {
        try {
            $dsn = "mysql:host={$this->hostname};dbname={$this->dbname};charset=utf8mb4";
            $this->conn = new PDO($dsn, $this->username, $this->password, $this->options);
        } catch (PDOException $e) {
            throw new Exception("Connection failed: " . $e->getMessage());
        }
    }

    public function customSelect(string $sql, array $params = []): array {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("Query failed: " . $e->getMessage());
        }
    }

    public function select(string $table, string $condition = '', array $params = []): array {
        $sql = "SELECT * FROM " . $this->escapeIdentifier($table);
        if ($condition) {
            $sql .= " WHERE " . $condition;
        }

        return $this->customSelect($sql, $params);
    }

    public function insert(string $table, array $data): int {
        try {
            $columns = array_keys($data);
            $values = array_fill(0, count($columns), '?');

            $sql = sprintf(
                "INSERT INTO %s (%s) VALUES (%s)",
                $this->escapeIdentifier($table),
                implode(', ', array_map([$this, 'escapeIdentifier'], $columns)),
                implode(', ', $values)
            );

            $stmt = $this->conn->prepare($sql);
            $stmt->execute(array_values($data));

            return (int) $this->conn->lastInsertId();
        } catch (PDOException $e) {
            throw new Exception("Insert failed: " . $e->getMessage());
        }
    }

    public function update(string $table, array $data, string $condition, array $params = []): int {
        try {
            $setParts = [];
            $executeParams = [];

            foreach ($data as $column => $value) {
                $setParts[] = $this->escapeIdentifier($column) . ' = ?';
                $executeParams[] = $value;
            }

            $sql = sprintf(
                "UPDATE %s SET %s WHERE %s",
                $this->escapeIdentifier($table),
                implode(', ', $setParts),
                $condition
            );

            $stmt = $this->conn->prepare($sql);
            $stmt->execute(array_merge($executeParams, $params));

            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new Exception("Update failed: " . $e->getMessage());
        }
    }

    public function delete(string $table, string $condition = '', array $params = []): int {
        try {
            $sql = "DELETE FROM " . $this->escapeIdentifier($table);
            if ($condition) {
                $sql .= " WHERE " . $condition;
            }

            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);

            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new Exception("Delete failed: " . $e->getMessage());
        }
    }

    private function escapeIdentifier(string $identifier): string {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    public function getConnection(): PDO {
        return $this->conn;
    }
}