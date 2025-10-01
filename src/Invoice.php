<?php
class Invoice {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Creates a new invoice.
     * @param int $userId
     * @param array $items - Array of ['product_id' =>, 'quantity' =>, 'price' =>]
     * @param string $dueDate
     * @return int|false The new invoice ID on success, false on failure.
     */
    public function create($userId, $items, $dueDate) {
        $totalAmount = 0;
        foreach ($items as $item) {
            $totalAmount += $item['quantity'] * $item['price'];
        }

        try {
            $this->pdo->beginTransaction();

            // Insert into invoices table
            $stmt = $this->pdo->prepare(
                'INSERT INTO invoices (user_id, total_amount, balance, due_date, status) VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([$userId, $totalAmount, $totalAmount, $dueDate, 'pending']);
            $invoiceId = $this->pdo->lastInsertId();

            // Insert into invoice_items table
            $stmt = $this->pdo->prepare(
                'INSERT INTO invoice_items (invoice_id, product_id, quantity, price) VALUES (?, ?, ?, ?)'
            );
            foreach ($items as $item) {
                $stmt->execute([$invoiceId, $item['product_id'], $item['quantity'], $item['price']]);
            }

            $this->pdo->commit();
            return $invoiceId;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            // In a real app, you would log this error
            return false;
        }
    }

    /**
     * Fetches all invoices with user information.
     * @return array
     */
    public function getAllWithUsers() {
        $sql = "SELECT i.id, i.total_amount, i.amount_paid, i.balance, i.status, i.created_at, i.due_date, u.username
                FROM invoices i
                JOIN users u ON i.user_id = u.id
                ORDER BY i.created_at DESC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Fetches a single invoice with its details, items, and user info.
     * @param int $id
     * @return mixed
     */
    public function getDetailsById($id) {
        $details = [];
        // Get invoice and user info
        $stmt = $this->pdo->prepare(
            "SELECT i.*, u.username, u.email
             FROM invoices i
             JOIN users u ON i.user_id = u.id
             WHERE i.id = ?"
        );
        $stmt->execute([$id]);
        $details['invoice'] = $stmt->fetch();

        if (!$details['invoice']) {
            return false;
        }

        // Get invoice items
        $stmt = $this->pdo->prepare(
            "SELECT ii.*, p.name as product_name
             FROM invoice_items ii
             JOIN products p ON ii.product_id = p.id
             WHERE ii.invoice_id = ?"
        );
        $stmt->execute([$id]);
        $details['items'] = $stmt->fetchAll();

        return $details;
    }

     /**
     * Fetches all invoices for a specific user.
     * @param int $userId
     * @return array
     */
    public function getByUserId($userId) {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM invoices WHERE user_id = ? ORDER BY created_at DESC'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Fetches a single invoice by its ID.
     * @param int $id
     * @return mixed
     */
    public function getById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM invoices WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Updates the status of an invoice.
     * @param int $id
     * @param string $status
     * @return bool
     */
    public function updateStatus($id, $status) {
        $stmt = $this->pdo->prepare("UPDATE invoices SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }

    /**
     * Updates the payment details of an invoice.
     * @param int $id
     * @param float $amountPaid
     * @return bool
     */
    public function updatePaymentDetails($id, $amountPaid) {
        $invoice = $this->getById($id);
        if (!$invoice) {
            return false;
        }

        $newAmountPaid = $invoice['amount_paid'] + $amountPaid;
        $newBalance = $invoice['total_amount'] - $newAmountPaid;

        $status = $newBalance <= 0 ? 'paid' : 'pending';

        $stmt = $this->pdo->prepare(
            "UPDATE invoices SET amount_paid = ?, balance = ?, status = ? WHERE id = ?"
        );
        return $stmt->execute([$newAmountPaid, $newBalance, $status, $id]);
    }

    /**
     * Reverts a payment on an invoice by adjusting the balance and amount paid.
     * This is used when a payment is rejected.
     * @param int $id The invoice ID.
     * @param float $amountToRevert The payment amount to revert.
     * @return bool
     */
    public function revertPayment($id, $amountToRevert) {
        $invoice = $this->getById($id);
        if (!$invoice) {
            return false;
        }

        $newAmountPaid = $invoice['amount_paid'] - $amountToRevert;
        $newBalance = $invoice['balance'] + $amountToRevert;

        // Clamp values to prevent invalid states
        $newAmountPaid = max(0, $newAmountPaid);
        $newBalance = min($invoice['total_amount'], $newBalance);

        $stmt = $this->pdo->prepare(
            "UPDATE invoices SET amount_paid = ?, balance = ? WHERE id = ?"
        );
        return $stmt->execute([$newAmountPaid, $newBalance, $id]);
    }

    /**
     * Records a payment against an invoice without changing its status.
     * This is used for payments that are pending verification.
     * @param int $id The invoice ID.
     * @param float $amountPaid The amount that was paid.
     * @return bool
     */
    public function recordPendingPayment($id, $amountPaid) {
        $invoice = $this->getById($id);
        if (!$invoice) {
            return false;
        }

        $newAmountPaid = $invoice['amount_paid'] + $amountPaid;
        $newBalance = $invoice['total_amount'] - $newAmountPaid;
        
        // Clamp values to prevent invalid states
        $newAmountPaid = min($invoice['total_amount'], $newAmountPaid);
        $newBalance = max(0, $newBalance);

        $stmt = $this->pdo->prepare(
            "UPDATE invoices SET amount_paid = ?, balance = ? WHERE id = ?"
        );
        return $stmt->execute([$newAmountPaid, $newBalance, $id]);
    }
}
?>