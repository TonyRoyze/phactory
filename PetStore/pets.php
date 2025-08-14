<?php
include 'includes/db.php';
include 'templates/header.php';

// Handle form submissions for add/delete
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add'])) {
        $name = $_POST['name'];
        $breed = $_POST['breed'];
        $age = $_POST['age'];
        $price = $_POST['price'];
        $customer_id = !empty($_POST['customer_id']) ? $_POST['customer_id'] : null;

        $stmt = $conn->prepare("INSERT INTO pets (name, breed, age, price, customer_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssidi", $name, $breed, $age, $price, $customer_id);
        $stmt->execute();

    } elseif (isset($_POST['delete'])) {
        $id = $_POST['id'];
        $stmt = $conn->prepare("DELETE FROM pets WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }
    // Redirect to avoid form resubmission
    header("Location: pets.php");
    exit();
}

// Fetch all pets with owner information
$result = $conn->query("SELECT pets.*, customers.name as owner_name FROM pets LEFT JOIN customers ON pets.customer_id = customers.id");

// Fetch all customers for the dropdown
$customers_result = $conn->query("SELECT * FROM customers");

?>

<h2>Manage Pets</h2>

<!-- Add Pet Form -->
<div class="form-container">
    <h3>Add New Pet</h3>
    <form action="pets.php" method="post">
        <div class="form-group">
            <label for="name">Name</label>
            <input type="text" id="name" name="name" placeholder="e.g., Buddy" required>
        </div>
        <div class="form-group">
            <label for="breed">Breed</label>
            <input type="text" id="breed" name="breed" placeholder="e.g., Golden Retriever">
        </div>
        <div class="form-group">
            <label for="age">Age</label>
            <input type="number" id="age" name="age" placeholder="e.g., 2">
        </div>
        <div class="form-group">
            <label for="price">Price</label>
            <input type="text" id="price" name="price" placeholder="e.g., 500.00" required>
        </div>
        <div class="form-group">
            <label for="customer_id">Owner (Optional)</label>
            <select id="customer_id" name="customer_id">
                <option value="">No Owner</option>
                <?php while ($row = $customers_result->fetch_assoc()): ?>
                    <option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['name']); ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <button type="submit" name="add">Add Pet</button>
    </form>
</div>

<!-- Pet List -->
<h3>Pet List</h3>
<table>
    <thead>
        <tr>
            <th>Name</th>
            <th>Breed</th>
            <th>Age</th>
            <th>Price</th>
            <th>Owner</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php while ($row = $result->fetch_assoc()): ?>
    <tr>
        <td><?php echo htmlspecialchars($row['name']); ?></td>
        <td><?php echo htmlspecialchars($row['breed']); ?></td>
        <td><?php echo htmlspecialchars($row['age']); ?></td>
        <td>$<?php echo number_format($row['price'], 2); ?></td>
        <td><?php echo htmlspecialchars($row['owner_name'] ?? 'N/A'); ?></td>
        <td class="actions">
            <a href="edit_pet.php?id=<?php echo $row['id']; ?>" class="button-edit">Edit</a>
            <form action="pets.php" method="post" style="display:inline;">
                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                <button type="submit" name="delete" class="button-delete" onclick="return confirm('Are you sure you want to delete this pet?');">Delete</button>
            </form>
        </td>
    </tr>
    <?php endwhile; ?>
    </tbody>
</table>

<?php include 'templates/footer.php'; ?>