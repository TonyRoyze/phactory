<?php 
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}
include './header.php'; 
include '../connector.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $user_type = $_POST['user_type'];

    if ($password !== $confirm_password) {
        $error = "Passwords do not match";
        echo "<script>alert('Passwords do not match. Please try again.');</script>";
    } else {
        $sql = "SELECT user_id FROM users WHERE username = '$username' LIMIT 1";
        $result = $conn->query($sql);

        if ($row = $result->fetch_assoc()) {
            $error = "Username already exists";
            echo "<script>alert('Username already exists. Please choose a different username.');</script>";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $insert_sql = "INSERT INTO users (username, password, user_type) VALUES ('$username', '$hashed_password', $user_type)";
            $result = $conn->query($insert_sql);
            if ($result) {
                header("Location: ./users_page.php");
                exit();
            } else {
                $error = "Registration failed. Please try again.";
                echo "<script>alert('Registration failed. Please try again.');</script>";
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
                    <label class="title">Add User</label>
                </div>
                <input placeholder="Enter username" name="username" type="text" class="input_field">
                <select placeholder="Enter user type" name="user_type" class="select_field">
                    <option value="0">User</option>
                    <option value="1">Admin</option>
                </select>
                <input placeholder="Enter password" name="password" type="password" class="input_field">
                <input placeholder="Confirm password" name="confirm_password" type="password" class="input_field">
                <button type="submit" class="btn">ADD</button>
            </form>
        </div>

</section>
<?php include '../footer.php'; ?>
</body>
</html>
