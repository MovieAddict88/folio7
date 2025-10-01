<?php

class Conversation {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function create($userId, $adminId, $subject) {
        $sql = "INSERT INTO conversations (user_id, admin_id, subject) VALUES (?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId, $adminId, $subject]);
        return $this->pdo->lastInsertId();
    }

    public function getById($conversationId) {
        $sql = "SELECT * FROM conversations WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$conversationId]);
        return $stmt->fetch();
    }

    public function getByUserId($userId) {
        $sql = "SELECT * FROM conversations WHERE user_id = ? OR admin_id = ? ORDER BY created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId, $userId]);
        return $stmt->fetchAll();
    }

    public function findBySubjectAndUserId($subject, $userId) {
        $sql = "SELECT * FROM conversations WHERE subject = ? AND user_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$subject, $userId]);
        return $stmt->fetch();
    }
}