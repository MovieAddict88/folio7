<?php

class Notification {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function create($userId, $message) {
        $sql = "INSERT INTO notifications (user_id, message) VALUES (?, ?)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$userId, $message]);
    }

    public function getUnreadByUserId($userId) {
        $sql = "SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function markAsRead($notificationId) {
        $sql = "UPDATE notifications SET is_read = 1 WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$notificationId]);
    }

    public function delete($notificationId) {
        $sql = "DELETE FROM notifications WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$notificationId]);
    }

    public function getUnreadByUserIdWithPagination($userId, $limit, $offset) {
        $sql = "SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId, $limit, $offset]);
        return $stmt->fetchAll();
    }

    public function countUnreadByUserId($userId) {
        $sql = "SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    }

    public function getById($notificationId) {
        $sql = "SELECT * FROM notifications WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$notificationId]);
        return $stmt->fetch();
    }
}