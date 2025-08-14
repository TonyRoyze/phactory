<?php
include 'includes/db.php';
include 'templates/header.php';

$id = $_GET['id'];

// Handle form submission for editing
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];

    $stmt = $conn->prepare("UPDATE customers SET name = ?, email = ?, phone = ? WHERE id = ?");
    $stmt->bind_param("sssi", $name, $email, $phone, $id);
    $stmt->execute();

    // Redirect to the customer list
    header("Location: customers.php");
    exit();
}

// Fetch the customer's data
$stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$customer = $result->fetch_assoc();

?>

<h2>Edit Customer</h2>

<div class="form-container">
    <form action="edit_customer.php?id=<?php echo $id; ?>" method="post">
        <div class="form-group">
            <label for="name">Name</label>
            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($customer['name']); ?>" required>
        </div>
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($customer['email']); ?>" required>
        </div>
        <div class="form-group">
            <label for="phone">Phone</label>
            <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($customer['phone']); ?>">
        </div>
        <button type="submit" name="edit">Save Changes</button>
        <a href="customers.php" class="button-cancel">Cancel</a>
    </form>
</div>

<?php include 'templates/footer.php'; ?>
