<?php 

include './header.php'; 
include '../connector.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

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

            $insert_sql = "INSERT INTO users (username, password, user_type) VALUES ('$username', '$hashed_password', 0)";
            $result = $conn->query($insert_sql);
            if ($result) {
                header("Location: ./login.php");
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
<style>

</style>
<section id="col-2-layout">
    <div class="master-container">
    <div class="tagline">
        <h2>FRESH & DELICIOUS BAKED GOODS JUST FOR YOU</h2>
    </div>
<form class="form" method="post">
                <div class="note">
                    <label class="title">Create your account</label>
                </div>
                <input placeholder="Enter your username" title="Enter your username" name="username" type="text" class="input_field">
                <input placeholder="Enter your password" title="Enter your password" name="password" type="password" class="input_field">
                <input placeholder="Confirm password" title="Confirm password" name="confirm_password" type="password" class="input_field">
                <button type="submit" class="btn">REGISTER</button>
                <p class="p">Already have an account? <a href="./login.php"><span class="span">Login</span></a>
            </form>
        </div>

</section>
<?php include '../footer.php'; ?>
</body>
</html>
