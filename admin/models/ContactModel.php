<?php

class ContactModel
{
    private PDO $pdo;
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }



    public function fetchAllMessages(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM contact ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    public function deleteMessage(int $contact_id): bool
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM contact WHERE contact_id = :contact_id"
        );
        return $stmt->execute(['contact_id' => $contact_id]);
    }

      public function saveAndSendMessage(string $name, string $email, string $message): bool
    {
        $sql = "INSERT INTO contact (contact_name, email, message, created_at)
                VALUES (:contact_name, :email, :message, NOW())";
        $stmt = $this->pdo->prepare($sql);
        try {
            $stmt->execute([
                ':contact_name' => $name,
                ':email' => $email,
                ':message' => $message
            ]);
            return true;
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return false;
        }
    }
}




