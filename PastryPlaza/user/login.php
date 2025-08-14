<?php 
session_start();
include './header.php'; 
include '../connector.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $login_username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE username = '$login_username' LIMIT 1";
    $result = $conn->query($sql);

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password'])||$password == $row['password']) {
            $_SESSION['user_id'] = $row['user_id'];
            if($row['user_type'] == 0){
                header("Location: ./homepage.php");
                exit();
            }
            else if($row['user_type'] == 1){
                header("Location: ../admin/users_page.php");
                exit();
            }
        } else {
            $error = "Invalid username or password";
            echo "<script>alert('Invalid username or password. Please try again.');</script>";
        }
    } else {
        $error = "Invalid username or password";
        echo "<script>alert('Invalid username or password. Please try again.');</script>";
    }

    $conn->close();
}

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
                    <label class="title">Login to your account</label>
                </div>
                <input placeholder="Enter your username" title="Enter your username" name="username" type="text" class="input_field">
                <input placeholder="Enter your password" title="Enter your password" name="password" type="password" class="input_field">
                <button type="submit" class="btn">Submit</button>
                <p class="p">Don't have an account? <a href="./signup.php"><span class="span">Sign Up</span></a>
            </form>
        </div>

</section>
<?php include '../footer.php'; ?>
</body>
</html>
