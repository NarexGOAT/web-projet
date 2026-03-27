<?php
class Database {
    private $host = "localhost";
    private $db_name = "stage"; // <--- METS LE NOM DE TA BDD ICI
    private $username = "root";
    private $password = ""; // Sur WSL, c'est souvent vide ou "root"

    public function getConnection() {
        try {
            $conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $conn;
        } catch (PDOException $e) {
            echo "Erreur de connexion : " . $e->getMessage();
            return null;
        }
    }
}
?>