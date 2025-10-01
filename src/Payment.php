<?php

class Payment {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function create($invoiceId, $amount, $paymentMethod, $transactionId = null, $referenceNumber = null) {
        $sql = "INSERT INTO payments (invoice_id, amount, payment_method, transaction_id, reference_number) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$invoiceId, $amount, $paymentMethod, $transactionId, $referenceNumber]);
    }

    public function findByInvoiceId($invoiceId) {
        $sql = "SELECT * FROM payments WHERE invoice_id = ? ORDER BY payment_date DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$invoiceId]);
        return $stmt->fetchAll();
    }

    public function deleteByInvoiceId($invoiceId) {
        $sql = "DELETE FROM payments WHERE invoice_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$invoiceId]);
    }

    public function deleteById($id) {
        $sql = "DELETE FROM payments WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Fetches the latest payment for a specific invoice.
     * @param int $invoiceId
     * @return mixed The latest payment record or false if not found.
     */
    public function getLatestPaymentForInvoice($invoiceId) {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM payments WHERE invoice_id = ? ORDER BY payment_date DESC LIMIT 1"
        );
        $stmt->execute([$invoiceId]);
        return $stmt->fetch();
    }
}