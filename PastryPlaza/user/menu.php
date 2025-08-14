<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id]++;
    } else {
        $_SESSION['cart'][$product_id] = 1;
    }
}
?>

<?php include './header.php'; ?>
<section id="featured-products">
    <!-- <select id="category-filter">
        <option value="all">All</option>
        <option value="featured">Featured</option>
        <option value="products">Products</option>
    </select> -->
    <h2 class="products-title">Featured Products</h2>
    <div class="products-container">
        <?php
        include '../connector.php';

        $sql = "SELECT * FROM products WHERE is_featured = 1";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                echo '<div class="product">';
                echo '<img src="../images/' . $row['image_name'] . '" alt="' . $row['product_name'] . '">';
                echo '<h3>' . $row['product_name'] . '</h3>';
                echo '<p>$' . $row['price'] . '</p>';
                echo '<form method="POST">';
                echo '<input type="hidden" name="product_id" value="' . $row['product_id'] . '">';
                echo '<button type="submit" name="add_to_cart" class="btn">ADD TO CART</button>';
                echo '</form>';
                echo '</div>';
            }
        }
        $conn->close();
        ?>
    </div>
</section>
<section id="products">
    <h2 class="products-title">Our Products</h2>
    <div class="products-container">
    <?php
        include '../connector.php';

        $sql = "SELECT * FROM products WHERE is_featured = 0";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                echo '<div class="product">';
                echo '<img src="../images/' . $row['image_name'] . '" alt="' . $row['product_name'] . '">';
                echo '<h3>' . $row['product_name'] . '</h3>';
                echo '<p>$' . $row['price'] . '</p>';
                echo '<form method="POST">';
                echo '<input type="hidden" name="product_id" value="' . $row['product_id'] . '">';
                echo '<button type="submit" name="add_to_cart" class="btn">ADD TO CART</button>';
                echo '</form>';
                echo '</div>';
            }
        }
        $conn->close();
        ?>
    </div>
</section>
<?php include '../footer.php'; ?>
</body>
</html>
