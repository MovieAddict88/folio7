<?php

class Message {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function create($conversationId, $senderId, $message, $filePath = null) {
        $sql = "INSERT INTO messages (conversation_id, sender_id, message, file_path) VALUES (?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$conversationId, $senderId, $message, $filePath]);
    }

    public function getByConversationId($conversationId) {
        $sql = "SELECT m.*, u.username AS sender_name FROM messages m JOIN users u ON m.sender_id = u.id WHERE m.conversation_id = ? ORDER BY m.created_at ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$conversationId]);
        return $stmt->fetchAll();
    }

    public function markAsRead($conversationId, $userId) {
        $sql = "UPDATE messages SET is_read = 1 WHERE conversation_id = ? AND sender_id != ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$conversationId, $userId]);
    }
}