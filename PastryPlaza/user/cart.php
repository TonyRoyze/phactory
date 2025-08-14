<?php 
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}
include './header.php'; 
include '../connector.php';

global $total;
$total = 0;
$qty = 0;
$delivery = 0.99;

if (isset($_POST['decrease_quantity'])) {
    $product_id = $_POST['decrease_quantity'];
    if (isset($_SESSION['cart'][$product_id])) {
       if ($_SESSION['cart'][$product_id] > 1) {
        $_SESSION['cart'][$product_id]--;
       } else {
        unset($_SESSION['cart'][$product_id]);
       }
    }
}

if (isset($_POST['increase_quantity'])) {
    $product_id = $_POST['increase_quantity'];
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id]++;
    } else {
        $_SESSION['cart'][$product_id] = 1;
    }
}

if (isset($_POST['order'])) {
    $address = $_POST['address'];
    $user_id = $_SESSION['user_id'];

    foreach ($_SESSION['cart'] as $product_id => $quantity) {
        $sql = "SELECT price FROM products WHERE product_id = $product_id";
        $result = $conn->query($sql);
        $product = $result->fetch_assoc();
        $total += $product['price'] * $quantity + $delivery;
    }

    $sql = "INSERT INTO orders (user_id, total_price, address, is_shipped) VALUES ('$user_id', '$total', '$address', '0')";
    $order_result = $conn->query($sql);

    $order_id = $conn->insert_id;

    foreach ($_SESSION['cart'] as $product_id => $quantity) {
        $sql = "INSERT INTO order_items (order_id, product_id, quantity) VALUES ('$order_id', '$product_id', '$quantity')";
        $result = $conn->query($sql);
    }

    if ($order_result) {
        foreach ($_SESSION['cart'] as $product_id => $quantity) {
            unset($_SESSION['cart'][$product_id]);
        }
        unset($_SESSION['cart']);
        echo "<script>alert('Order placed successfully!');</script>";
        header("Location: ./homepage.php");
        exit();
    } else {
        echo "<script>alert('Failed to place order. Please try again.');</script>";
    }

    $conn->close();
}

?>
<div class="master-container" >
    <div class="cart-container">
  <div class="card cart">
    <div class="card-header">
        <label class="title">Your cart</label>
    </div>
    <div class="cart-products">
    <?php
        include '../connector.php';
        if (isset($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $product_id => $quantity) {
            echo '<div class="cart-product" data-id="'.$product_id.'">
            <div class="cart-product-icon">';
            $sql = "SELECT * FROM products JOIN categories ON products.category_id = categories.category_id WHERE product_id = $product_id";
            $result = mysqli_query($conn, $sql);
            $product = mysqli_fetch_assoc($result);
            $total += $product['price'] * $quantity;
            if ($product['category_id'] == 1) {
                echo '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="#FFB672" stroke="#FF8413" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-croissant">
                <circle cx="9" cy="7" r="2"/>
                <path d="M7.2 7.9 3 11v9c0 .6.4 1 1 1h16c.6 0 1-.4 1-1v-9c0-2-3-6-7-8l-3.6 2.6"/>
                <path d="M16 13H3"/>
                <path d="M16 17H3"/>
            </svg>';
            } elseif ($product['category_id'] == 2) {
                echo '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="#FFB672" stroke="#FF8413" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-croissant">
                <path d="m4.6 13.11 5.79-3.21c1.89-1.05 4.79 1.78 3.71 3.71l-3.22 5.81C8.8 23.16.79 15.23 4.6 13.11Z"/>
                <path d="m10.5 9.5-1-2.29C9.2 6.48 8.8 6 8 6H4.5C2.79 6 2 6.5 2 8.5a7.71 7.71 0 0 0 2 4.83"/>
                <path d="M8 6c0-1.55.24-4-2-4-2 0-2.5 2.17-2.5 4"/>
                <path d="m14.5 13.5 2.29 1c.73.3 1.21.7 1.21 1.5v3.5c0 1.71-.5 2.5-2.5 2.5a7.71 7.71 0 0 1-4.83-2"/>
                <path d="M18 16c1.55 0 4-.24 4 2 0 2-2.17 2.5-4 2.5"/>
            </svg>';
            }
            elseif ($product['category_id'] == 3) {
                echo '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="#FFB672" stroke="#FF8413" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-croissant">
                <path d="M12 2a10 10 0 1 0 10 10 4 4 0 0 1-5-5 4 4 0 0 1-5-5"/>
                <path d="M8.5 8.5v.01"/>
                <path d="M16 15.5v.01"/>
                <path d="M12 12v.01"/>
                <path d="M11 17v.01"/>
                <path d="M7 14v.01"/>
            </svg>';
            }
            elseif ($product['category_id'] == 4) {
                echo '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="#FFB672" stroke="#FF8413" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-croissant">
                <circle cx="12" cy="4" r="2"/>
                <path d="M10.2 3.2C5.5 4 2 8.1 2 13a2 2 0 0 0 4 0v-1a2 2 0 0 1 4 0v4a2 2 0 0 0 4 0v-4a2 2 0 0 1 4 0v1a2 2 0 0 0 4 0c0-4.9-3.5-9-8.2-9.8"/>
                <path d="M3.2 14.8a9 9 0 0 0 17.6 0"/>
            </svg>';
            }
            echo '</div>
                    <div class="cart-product-title">
                    <span>'.$product['product_name'].'</span>
                    <p>'.$product['category_name'].'</p>
                    </div>
                    <div class="quantity">
                    <form method="POST">
                    <button type="submit" name="decrease_quantity" value="'.$product_id.'">
                        <svg fill="none" viewBox="0 0 24 24" height="14" width="14" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linejoin="round" stroke-linecap="round" stroke-width="2.5" stroke="#47484b" d="M20 12L4 12"></path>
                        </svg>
                    </button>
                    </form>
                    <label id="quantity-'.$product_id.'">'.$quantity.'</label>
                    <form method="POST">
                    <button type="submit" name="increase_quantity" value="'.$product_id.'">
                        <svg fill="none" viewBox="0 0 24 24" height="14" width="14" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linejoin="round" stroke-linecap="round" stroke-width="2.5" stroke="#47484b" d="M12 4V20M20 12H4"></path>
                        </svg>
                    </button>
                    </form>
                    </div>
                    <div class="price-container">
                    <label class="price">$'.$product['price']*$quantity.'</label>
                    </div>
                </div> ';
        }
    }
    ?>
    </div>
  </div>
</div>

<form method="POST" class="checkout-container">
  <div class="card address">
    <div class="card-header">
        <label class="title">Address</label>
    </div>
    <div class="address-input-container">
      <input type="text" placeholder="Enter address" name="address" class="input_field" required>
    </div>
  </div>
<div class="card checkout">
      <div class="card-header">
          <label class="title">Checkout</label>
      </div>
      <div class="details">
      <span>Your cart subtotal:</span>
      <span>$<?php echo $total; ?></span>
      <span>Delivery fees:</span>
      <span>$<?php echo $delivery; ?></span>
      </div>
      <div class="checkout--footer">
      <label class="price"><sup>$</sup><?php echo $total + $delivery; ?></label>
      <button class="btn" name="order">ORDER</button>
      </div>
  </div>
  </form>
</div>
<?php include '../footer.php'; ?>
</body>
</html>
