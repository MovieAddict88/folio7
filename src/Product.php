<?php
class Product {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Creates a new product.
     * @param string $name
     * @param string $description
     * @param float $price
     * @return bool
     */
    public function create($name, $description, $price) {
        $stmt = $this->pdo->prepare('INSERT INTO products (name, description, price) VALUES (?, ?, ?)');
        return $stmt->execute([$name, $description, $price]);
    }

    /**
     * Fetches all products.
     * @return array
     */
    public function getAll() {
        $stmt = $this->pdo->query('SELECT * FROM products ORDER BY created_at DESC');
        return $stmt->fetchAll();
    }

    /**
     * Fetches a single product by its ID.
     * @param int $id
     * @return mixed
     */
    public function getById($id) {
        $stmt = $this->pdo->prepare('SELECT * FROM products WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Updates a product.
     * @param int $id
     * @param string $name
     * @param string $description
     * @param float $price
     * @return bool
     */
    public function update($id, $name, $description, $price) {
        $stmt = $this->pdo->prepare('UPDATE products SET name = ?, description = ?, price = ? WHERE id = ?');
        return $stmt->execute([$name, $description, $price, $id]);
    }

    /**
     * Deletes a product by its ID.
     * @param int $id
     * @return bool
     */
    public function delete($id) {
        $stmt = $this->pdo->prepare('DELETE FROM products WHERE id = ?');
        return $stmt->execute([$id]);
    }
}
?>