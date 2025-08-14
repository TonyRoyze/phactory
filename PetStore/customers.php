<?php
include 'includes/db.php';
include 'templates/header.php';

// Handle form submissions for add/delete
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add'])) {
        $name = $_POST['name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $stmt = $conn->prepare("INSERT INTO customers (name, email, phone) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $email, $phone);
        $stmt->execute();
    } elseif (isset($_POST['delete'])) {
        $id = $_POST['id'];
        $stmt = $conn->prepare("DELETE FROM customers WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }
    // Redirect to avoid form resubmission
    header("Location: customers.php");
    exit();
}

// Fetch all customers
$result = $conn->query("SELECT * FROM customers");

?>

<h2>Manage Customers</h2>

<!-- Add Customer Form -->
<div class="form-container">
    <h3>Add New Customer</h3>
    <form action="customers.php" method="post">
        <div class="form-group">
            <label for="name">Name</label>
            <input type="text" id="name" name="name" placeholder="e.g., John Doe" required>
        </div>
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" placeholder="e.g., john.doe@example.com" required>
        </div>
        <div class="form-group">
            <label for="phone">Phone</label>
            <input type="text" id="phone" name="phone" placeholder="e.g., 123-456-7890">
        </div>
        <button type="submit" name="add">Add Customer</button>
    </form>
</div>


<!-- Customer List -->
<h3>Customer List</h3>
<table>
    <thead>
        <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php while ($row = $result->fetch_assoc()): ?>
    <tr>
        <td><?php echo htmlspecialchars($row['name']); ?></td>
        <td><?php echo htmlspecialchars($row['email']); ?></td>
        <td><?php echo htmlspecialchars($row['phone']); ?></td>
        <td class="actions">
            <a href="edit_customer.php?id=<?php echo $row['id']; ?>" class="button-edit">Edit</a>
            <form action="customers.php" method="post" style="display:inline;">
                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                <button type="submit" name="delete" class="button-delete" onclick="return confirm('Are you sure you want to delete this customer?');">Delete</button>
            </form>
        </td>
    </tr>
    <?php endwhile; ?>
    </tbody>
</table>

<?php include 'templates/footer.php'; ?>