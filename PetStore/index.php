<?php
include 'includes/db.php';
include 'templates/header.php';

// Fetch stats
$customer_count = $conn->query("SELECT COUNT(*) as count FROM customers")->fetch_assoc()['count'];
$pet_count = $conn->query("SELECT COUNT(*) as count FROM pets")->fetch_assoc()['count'];
$sales_total = $conn->query("SELECT SUM(total_amount) as total FROM sales")->fetch_assoc()['total'];

?>

<div class="dashboard-stats">
    <div class="stat">
        <h3>Total Customers</h3>
        <p><?php echo $customer_count; ?></p>
    </div>
    <div class="stat">
        <h3>Total Pets</h3>
        <p><?php echo $pet_count; ?></p>
    </div>
    <div class="stat">
        <h3>Total Sales</h3>
        <p>$<?php echo number_format($sales_total, 2); ?></p>
    </div>
</div>

<?php include 'templates/footer.php'; ?>