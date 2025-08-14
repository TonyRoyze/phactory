<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

?>

<?php include './header.php'; ?>
<section id="orders">
<a href="./add_order.php" class="btn btn-add">Add Order</a>
    <h2 class="orders-title">Orders</h2>
    <div class="orders-container">
        <?php
        include '../connector.php';

        $sql = "SELECT orders.order_id, users.username, orders.order_date, orders.total_price, orders.address FROM orders JOIN users ON orders.user_id = users.user_id WHERE orders.is_shipped = 0 ORDER BY order_id DESC";
        $result = $conn->query($sql);


        if ($result->num_rows > 0) {
            echo '<table class="order-table">';
            echo '<tr><th>Order ID</th><th>Username</th><th>Order Date</th><th>Address</th><th>Total Price</th><th>Actions</th></tr>';
            while($row = $result->fetch_assoc()) {
                echo '<tr>';
                echo '<td style="width: 10%;">' . $row['order_id'] . '</td>';
                echo '<td style="width: 10%;">' . $row['username'] . '</td>';
                echo '<td style="width: 20%;">' . $row['order_date'] . '</td>';
                echo '<td style="width: 30%;">' . $row['address'] . '</td>';
                echo '<td style="width: 10%;">' . $row['total_price'] . '</td>';
                echo '<td style="width: 20%;" class="table-actions"><a href="./edit_order.php?order_id=' . $row['order_id'] . '" class="btn">Edit</a>
</td>';
                echo '</tr>';
            }
            echo '</table>';
        } else {
            echo '<p>No orders found.</p>';
        }
        ?>
    </div>
</section>
<section id="completed-orders">
    <h2 class="orders-title">Completed Orders</h2>
    <div class="orders-container">
        <?php
        include '../connector.php';

        $sql = "SELECT orders.order_id, users.username, orders.order_date, orders.total_price, orders.address FROM orders JOIN users ON orders.user_id = users.user_id WHERE orders.is_shipped = 1 ORDER BY order_id DESC";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            echo '<table class="user-table">';
            echo '<tr><th>Order ID</th><th>Username</th><th>Order Date</th><th>Address</th><th>Total Price</th><th>Actions</th></tr>';
            while($row = $result->fetch_assoc()) {
                echo '<tr>';
                echo '<td style="width: 10%;">' . $row['order_id'] . '</td>';
                echo '<td style="width: 10%;">' . $row['username'] . '</td>';
                echo '<td style="width: 20%;">' . $row['order_date'] . '</td>';
                echo '<td style="width: 30%;">' . $row['address'] . '</td>';
                echo '<td style="width: 10%;">' . $row['total_price'] . '</td>';
                echo '<td style="width: 20%;" class="table-actions"><a href="./edit_order.php?order_id=' . $row['order_id'] . '" class="btn">Edit</a><a href="./delete_order.php?order_id=' . $row['order_id'] . '" class="btn">Delete</a></td>';
                echo '</tr>';
            }
            echo '</table>';
        } else {
            echo '<p>No orders found.</p>';
        }
        ?>
    </div>
</section>
<?php include '../footer.php'; ?>
</body>
</html>
