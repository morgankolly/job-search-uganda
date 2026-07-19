<?php

class CategoryModel {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function insertCategory(string $categoryName): bool {
        $stmt = $this->pdo->prepare(
            "INSERT INTO job_categories (category_name) VALUES (:category_name)"
        );
        $stmt->bindParam(':category_name', $categoryName, PDO::PARAM_STR);
        return $stmt->execute();
    }

    public function deleteCategory(int $categoryId): bool {
        $stmt = $this->pdo->prepare(
            "DELETE FROM job_categories WHERE category_id = :category_id"
        );
        $stmt->bindParam(':category_id', $categoryId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function updateCategory(int $categoryId, string $newCategoryName): bool {
        $stmt = $this->pdo->prepare(
            "UPDATE job_categories
             SET category_name = :new_category_name
             WHERE category_id = :category_id"
        );
        $stmt->bindParam(':category_id', $categoryId, PDO::PARAM_INT);
        $stmt->bindParam(':new_category_name', $newCategoryName, PDO::PARAM_STR);
        return $stmt->execute();
    }

    public function getAllCategories(): array {
        $stmt = $this->pdo->query(
            "SELECT * FROM job_categories ORDER BY category_name ASC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function fetchAllCategories(): array {
        return $this->getAllCategories();
    }

    public function fetchCategory(int $categoryId): ?array {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM job_categories WHERE category_id = :category_id"
        );
        $stmt->bindParam(':category_id', $categoryId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
