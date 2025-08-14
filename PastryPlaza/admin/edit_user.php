<?php 
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

include './header.php'; 
include '../connector.php';

if (isset($_GET['user_id'])) {
    $user_id = $_GET['user_id'];
    $sql = "SELECT * FROM users WHERE user_id = $user_id";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $username = $user['username'];
        $user_type = $user['user_type'];
    } else {
        echo "<script>alert('User not found.');</script>";
        header("Location: ./users_page.php");
        exit();
    }
} else {
    header("Location: ./users_page.php");
    exit();
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_POST['user_id'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $user_type = $_POST['user_type'];


    if (!empty($password)) {
        if ($password !== $confirm_password) {
            $error = "Passwords do not match";
            echo "<script>alert('Passwords do not match. Please try again.');</script>";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $update_sql = "UPDATE users SET username = '$username', user_type = $user_type, password = '$hashed_password' WHERE user_id = $user_id";
            $result = $conn->query($update_sql);
            if ($result) {
                echo "<script>alert('User updated successfully.');</script>";
                header("Location: ./users_page.php");
                exit();
            } else {
                $error = "Update failed. Please try again.";
                echo "<script>alert('Update failed. Please try again.');</script>";
            }
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
                    <label class="title">Edit User</label>
                </div>
                <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                <input placeholder="Enter username" name="username" type="text" class="input_field" value="<?php echo $username; ?>">
                <select placeholder="Enter user type" name="user_type" class="select_field">
                    <option value="0">User</option>
                    <option value="1">Admin</option>
                </select>
                <input placeholder="Enter password" name="password" type="password" class="input_field">
                <input placeholder="Confirm password" name="confirm_password" type="password" class="input_field">
                <button type="submit" class="btn">Submit</button>
            </form>
        </div>

</section>
<?php include '../footer.php'; ?>
</body>
</html>
