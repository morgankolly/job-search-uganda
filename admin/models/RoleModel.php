<?php
class RoleModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getRoles() {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM Roles");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "Error fetching roles: " . $e->getMessage();
            return [];
        }
    }

    public function getRoleById($role_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM Roles WHERE role_id = :role_id LIMIT 1");
            $stmt->bindParam(':role_id', $role_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "Error fetching role: " . $e->getMessage();
            return false;
        }
    }
}
