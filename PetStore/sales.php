<?php
include 'includes/db.php';
include 'templates/header.php';

// Handle form submissions for add sale
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add'])) {
    $customer_id = $_POST['customer_id'];
    $pet_id = $_POST['pet_id'];
    $sale_date = $_POST['sale_date'];
    $total_amount = $_POST['total_amount'];

    // Start a transaction
    $conn->begin_transaction();

    try {
        // Insert the sale record
        $stmt = $conn->prepare("INSERT INTO sales (customer_id, pet_id, sale_date, total_amount) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $customer_id, $pet_id, $sale_date, $total_amount);
        $stmt->execute();

        // Update the pet's owner
        $stmt = $conn->prepare("UPDATE pets SET customer_id = ? WHERE id = ?");
        $stmt->bind_param("ii", $customer_id, $pet_id);
        $stmt->execute();

        // Commit the transaction
        $conn->commit();

    } catch (mysqli_sql_exception $exception) {
        $conn->rollback();
        throw $exception;
    }

    // Redirect to avoid form resubmission
    header("Location: sales.php");
    exit();
}

// Fetch all sales with customer and pet names
$sales_result = $conn->query("SELECT sales.*, customers.name as customer_name, pets.name as pet_name FROM sales JOIN customers ON sales.customer_id = customers.id JOIN pets ON sales.pet_id = pets.id ORDER BY sale_date DESC");

// Fetch customers and available pets for dropdowns
$customers_result = $conn->query("SELECT * FROM customers");
$pets_result = $conn->query("SELECT * FROM pets WHERE customer_id IS NULL"); // Only show unowned pets

?>

<h2>Manage Sales</h2>

<!-- Add Sale Form -->
<div class="form-container">
    <h3>Record a New Sale</h3>
    <form action="sales.php" method="post">
        <div class="form-group">
            <label for="customer_id">Customer</label>
            <select id="customer_id" name="customer_id" required>
                <option value="">Select Customer</option>
                <?php while ($row = $customers_result->fetch_assoc()): ?>
                    <option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['name']); ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="pet_id">Pet</label>
            <select id="pet_id" name="pet_id" required>
                <option value="">Select Pet</option>
                <?php while ($row = $pets_result->fetch_assoc()): ?>
                    <option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['name']); ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="sale_date">Sale Date</label>
            <input type="date" id="sale_date" name="sale_date" required>
        </div>
        <div class="form-group">
            <label for="total_amount">Total Amount</label>
            <input type="text" id="total_amount" name="total_amount" placeholder="e.g., 500.00" required>
        </div>
        <button type="submit" name="add">Record Sale</button>
    </form>
</div>

<!-- Sales List -->
<h3>Sales List</h3>
<table>
    <thead>
        <tr>
            <th>Date</th>
            <th>Customer</th>
            <th>Pet</th>
            <th>Total Amount</th>
        </tr>
    </thead>
    <tbody>
    <?php while ($row = $sales_result->fetch_assoc()): ?>
    <tr>
        <td><?php echo $row['sale_date']; ?></td>
        <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
        <td><?php echo htmlspecialchars($row['pet_name']); ?></td>
        <td>$<?php echo number_format($row['total_amount'], 2); ?></td>
    </tr>
    <?php endwhile; ?>
    </tbody>
</table>

<?php include 'templates/footer.php'; ?>