<?php
session_start();
require_once '../../config/db.php';
require_once '../../src/User.php';
require_once '../../src/Product.php';

// Auth check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php?error=Access denied.');
    exit;
}

$pdo = getDBConnection();
$user = new User($pdo);
$product = new Product($pdo);

$users = $user->getAll();
$products = $product->getAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Invoice</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include '_nav.php'; ?>
    <div class="container mt-5">
        <h2>Create New Invoice</h2>
        <form action="handle_create_invoice.php" method="POST">
            <div class="card p-3">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="user_id" class="form-label">Select User</label>
                        <select class="form-select" id="user_id" name="user_id" required>
                            <option value="">Choose a user...</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['username']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="due_date" class="form-label">Due Date</label>
                        <input type="date" class="form-control" id="due_date" name="due_date" required>
                    </div>
                </div>

                <hr>

                <h4>Invoice Items</h4>
                <div id="invoice-items">
                    <!-- Item row will be added here -->
                </div>
                <button type="button" id="add-item" class="btn btn-secondary mt-2 align-self-start">Add Item</button>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">Create Invoice</button>
                <a href="invoices.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>

    <script>
    const products = <?php echo json_encode($products); ?>;
    let itemIndex = 0;

    document.getElementById('add-item').addEventListener('click', () => {
        const itemsContainer = document.getElementById('invoice-items');
        const itemRow = document.createElement('div');
        itemRow.classList.add('row', 'mb-2', 'align-items-center');
        itemRow.innerHTML = `
            <div class="col-md-5">
                <select class="form-select product-select" name="items[${itemIndex}][product_id]" required>
                    <option value="">Select a product...</option>
                    ${products.map(p => `<option value="${p.id}" data-price="${p.price}">${p.name}</option>`).join('')}
                </select>
            </div>
            <div class="col-md-2">
                <input type="number" class="form-control quantity" name="items[${itemIndex}][quantity]" placeholder="Qty" value="1" min="1" required>
            </div>
            <div class="col-md-3">
                <input type="number" step="0.01" class="form-control price" name="items[${itemIndex}][price]" placeholder="Price" required>
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-danger remove-item">Remove</button>
            </div>
        `;
        itemsContainer.appendChild(itemRow);
        itemIndex++;
    });

    document.getElementById('invoice-items').addEventListener('change', (e) => {
        if (e.target.classList.contains('product-select')) {
            const selectedOption = e.target.options[e.target.selectedIndex];
            const price = selectedOption.dataset.price;
            const priceInput = e.target.closest('.row').querySelector('.price');
            priceInput.value = price;
        }
    });

    document.getElementById('invoice-items').addEventListener('click', (e) => {
        if (e.target.classList.contains('remove-item')) {
            e.target.closest('.row').remove();
        }
    });

    // Add one item row by default
    document.getElementById('add-item').click();
    </script>
</body>
</html>