<?php
include 'includes/db.php';
include 'templates/header.php';

$id = $_GET['id'];

// Handle form submission for editing
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit'])) {
    $name = $_POST['name'];
    $breed = $_POST['breed'];
    $age = $_POST['age'];
    $price = $_POST['price'];
    $customer_id = !empty($_POST['customer_id']) ? $_POST['customer_id'] : null;

    $stmt = $conn->prepare("UPDATE pets SET name = ?, breed = ?, age = ?, price = ?, customer_id = ? WHERE id = ?");
    $stmt->bind_param("ssidii", $name, $breed, $age, $price, $customer_id, $id);
    $stmt->execute();

    // Redirect to the pet list
    header("Location: pets.php");
    exit();
}

// Fetch the pet's data
$stmt = $conn->prepare("SELECT * FROM pets WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$pet = $result->fetch_assoc();

// Fetch all customers for the dropdown
$customers_result = $conn->query("SELECT * FROM customers");

?>

<h2>Edit Pet</h2>

<div class="form-container">
    <form action="edit_pet.php?id=<?php echo $id; ?>" method="post">
        <div class="form-group">
            <label for="name">Name</label>
            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($pet['name']); ?>" required>
        </div>
        <div class="form-group">
            <label for="breed">Breed</label>
            <input type="text" id="breed" name="breed" value="<?php echo htmlspecialchars($pet['breed']); ?>">
        </div>
        <div class="form-group">
            <label for="age">Age</label>
            <input type="number" id="age" name="age" value="<?php echo htmlspecialchars($pet['age']); ?>">
        </div>
        <div class="form-group">
            <label for="price">Price</label>
            <input type="text" id="price" name="price" value="<?php echo htmlspecialchars($pet['price']); ?>" required>
        </div>
        <div class="form-group">
            <label for="customer_id">Owner</label>
            <select id="customer_id" name="customer_id">
                <option value="">No Owner</option>
                <?php while ($row = $customers_result->fetch_assoc()): ?>
                    <option value="<?php echo $row['id']; ?>" <?php echo ($pet['customer_id'] == $row['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($row['name']); ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <button type="submit" name="edit">Save Changes</button>
        <a href="pets.php" class="button-cancel">Cancel</a>
    </form>
</div>

<?php include 'templates/footer.php'; ?>
