<?php 
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}
include './header.php'; 
include '../connector.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $product_name = $_POST['product_name'];
    $price = $_POST['price'];
    $category_id = $_POST['category_id'];
    $is_featured = $_POST['is_featured'];

    $file_name = $_FILES["image"]["name"];
    $temp_name = $_FILES["image"]["tmp_name"];
    $folder = "../images/".$file_name;

    move_uploaded_file($temp_name, $folder);


    $sql = "SELECT product_id FROM products WHERE product_name = '$product_name' LIMIT 1";
    $result = $conn->query($sql);

    if ($row = $result->fetch_assoc()) {
        $error = "Product already exists";
        echo "<script>alert('Product already exists. Please choose a different product name.');</script>";
    } else {
        $insert_sql = "INSERT INTO products (product_name, price, category_id, is_featured, image_name) VALUES ('$product_name', $price, $category_id, $is_featured, '$file_name')";
        $result = $conn->query($insert_sql);
        if ($result) {
            header("Location: ./products_page.php");
            exit();
        } else {
            $error = "Product addition failed. Please try again.";
            header("Location: ./products_page.php");
            exit();
            echo "<script>alert('Product addition failed. Please try again.');</script>";
            
        }
    }
}

$conn->close();
?>

<section id="col-2-layout">
    <div class="master-container">
    <div class="tagline">
        <h2>FRESH & DELICIOUS BAKED GOODS JUST FOR YOU</h2>
    </div>
        <form class="form" method="post">
            <div class="note">
                <label class="title">Add Product</label>
            </div>
            <input placeholder="Enter product name" name="product_name" type="text" class="input_field">
            <input placeholder="Enter product price" name="price" type="number" class="input_field">
            <select name="category_id" class="select_field">
                <option value="1">Cake</option>
                <option value="2">Pastry</option>
                <option value="3">Cookies</option>
                <option value="4">Cupcakes</option>
            </select>
            <select name="is_featured" class="select_field">
                <option value="1">Yes</option>
                <option value="0">No</option>
            </select>
            <div class="custom-file-upload-container">
            <label class="custom-file-upload" for="file">
                <div class="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#115DFC" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-image-up">
                        <path d="M10.3 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v10l-3.1-3.1a2 2 0 0 0-2.814.014L6 21"/><path d="m14 19.5 3-3 3 3"/><path d="M17 22v-5.5"/><circle cx="9" cy="9" r="2"/>
                    </svg>
                </div>
                <div class="text">
                    <span>Click to upload image</span>
                </div>
                <input name="image" type="file" id="file" accept="image/*">
            </label>
            <img id="image-preview" src="" alt="Image preview">
        </div>
            <button type="submit" class="btn">ADD</button>
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
