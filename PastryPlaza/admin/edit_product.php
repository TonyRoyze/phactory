<?php 
session_start();if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}
include './header.php'; 
include '../connector.php';

if (!isset($_GET['product_id'])) {
    header("Location: ./products_page.php");
    exit();
}

$product_id = $_GET['product_id'];

$sql = "SELECT * FROM products WHERE product_id = $product_id";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    header("Location: ./products_page.php");
    exit();
}

$product = $result->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $product_name = $_POST['product_name'];
    $price = $_POST['price'];
    $category_id = $_POST['category_id'];
    $is_featured = $_POST['is_featured'];

    $update_sql = "UPDATE products SET product_name = '$product_name', price = $price, category_id = $category_id, is_featured = $is_featured WHERE product_id = $product_id";
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $file_name = $_FILES["image"]["name"];
        $temp_name = $_FILES["image"]["tmp_name"];
        $folder = "../images/".$file_name;

        if (move_uploaded_file($temp_name, $folder)) {
            $update_sql = "UPDATE products SET product_name = '$product_name', price = $price, category_id = $category_id, is_featured = $is_featured, image_name = '$file_name' WHERE product_id = $product_id";
        }
    }

    $result = $conn->query($update_sql);
    if ($result) {
        header("Location: ./products_page.php");
        exit();
    } else {
        $error = "Product update failed. Please try again.";
        echo "<script>alert('Product update failed. Please try again.');</script>";
    }
}

$conn->close();
?>

<section id="col-2-layout">
    <div class="master-container">
    <div class="tagline">
        <h2>FRESH & DELICIOUS BAKED GOODS JUST FOR YOU</h2>
    </div>
            <form class="form" method="post" enctype="multipart/form-data">
                <div class="note">
                    <label class="title">Edit Product</label>
                </div>
                <input placeholder="Enter product name" name="product_name" type="text" class="input_field" value="<?php echo $product['product_name']; ?>">
                <input placeholder="Enter product price" name="price" type="number" step="0.01" class="input_field" value="<?php echo $product['price']; ?>">
                <select name="category_id" class="select_field">
                    <option value="1" <?php echo ($product['category_id'] == 1) ? 'selected' : ''; ?>>Cake</option>
                    <option value="2" <?php echo ($product['category_id'] == 2) ? 'selected' : ''; ?>>Pastry</option>
                    <option value="3" <?php echo ($product['category_id'] == 3) ? 'selected' : ''; ?>>Cookies</option>
                    <option value="4" <?php echo ($product['category_id'] == 4) ? 'selected' : ''; ?>>Cupcakes</option>
                </select>
                <select name="is_featured" class="select_field">
                    <option value="1" <?php echo ($product['is_featured'] == 1) ? 'selected' : ''; ?>>Yes</option>
                    <option value="0" <?php echo ($product['is_featured'] == 0) ? 'selected' : ''; ?>>No</option>
                </select>
                <div class="custom-file-upload-container">
                <label class="custom-file-upload" for="file">
                    <div class="icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#115DFC" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-image-up">
                            <path d="M10.3 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v10l-3.1-3.1a2 2 0 0 0-2.814.014L6 21"/><path d="m14 19.5 3-3 3 3"/><path d="M17 22v-5.5"/><circle cx="9" cy="9" r="2"/>
                        </svg>
                    </div>
                    <div class="text">
                        <span>Click to upload new image</span>
                    </div>
                    <input name="image" type="file" id="file" accept="image/*">
                </label>
                <img id="image-preview" src="../images/<?php echo $product['image_name']; ?>" alt="Image preview" style="display: <?php echo $product['image_name'] ? 'block' : 'none'; ?>;">

            </div>
                <button type="submit" class="btn">UPDATE</button>
            </form>
        </div>

</section>
<?php include '../footer.php'; ?>
</body>
<script>
    document.getElementById('file').addEventListener('change', function(event) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = document.getElementById('image-preview');
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
            reader.readAsDataURL(file);
        }
    });
</script>
</html>
