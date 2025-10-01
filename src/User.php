<?php
class User {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Finds a user by their username.
     * @param string $username
     * @return mixed User data if found, false otherwise.
     */
    public function findByUsername($username) {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE username = ?');
        $stmt->execute([$username]);
        return $stmt->fetch();
    }

    /**
     * Creates a new user.
     * @param string $username
     * @param string $email
     * @param string $password
     * @return bool True on success, false on failure.
     */
    public function create($username, $email, $password) {
        // Check if username or email already exists
        $stmt = $this->pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            return false; // User already exists
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare('INSERT INTO users (username, email, password) VALUES (?, ?, ?)');
        return $stmt->execute([$username, $email, $hashedPassword]);
    }

    /**
     * Verifies user credentials.
     * @param string $username
     * @param string $password
     * @return mixed User data if valid, false otherwise.
     */
    public function login($username, $password) {
        $user = $this->findByUsername($username);
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return false;
    }

    /**
     * Fetches all users (for admin).
     * @return array
     */
    public function getAll() {
        $stmt = $this->pdo->query('SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC');
        return $stmt->fetchAll();
    }

    /**
     * Fetches a single user by their ID.
     * @param int $id
     * @return mixed User data if found, false otherwise.
     */
    public function getById($id) {
        $stmt = $this->pdo->prepare('SELECT id, username, email, role, created_at FROM users WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
}
?>