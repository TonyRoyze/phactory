<?php
global $conn;
session_start();
include "../connector.php";
include "../functions.php";

$user_data = checkLogin($conn);

$username = "";
$user_type = "";
$pass = "";
$repass = "";
$errorMessage = "";
$details = "";
$successMessage = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $user_type = $_POST["user_type"];
    $pass = $_POST["pass"];
    $repass = $_POST["repass"];

    $sql = "SELECT * FROM user WHERE user_name = '$username'";

    try {
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $errorMessage = "Username is already taken";
        } else {
            if ($pass == $repass) {
                $pwd = password_hash($pass, PASSWORD_DEFAULT);

                $sql = "INSERT INTO user (user_name, user_type, password) VALUES ('$username', '$user_type', '$pwd')";

                try {
                    $result = $conn->query($sql);
                    $successMessage = "User Added Successfully";
                    // Reset form values
                    $username = "";
                    $user_type = "";
                    $pass = "";
                    $repass = "";
                } catch (Exception $e) {
                    $errorMessage = "Invalid query";
                    $details = $conn->error;
                }
            } else {
                $errorMessage = "Passwords don't match";
            }
        }
    } catch (Exception $e) {
        $errorMessage = "Invalid query";
        $details = $conn->error;
    }
}
?>

<?php
include "./admin-header.php";
echo "
    <div class='wrapper medium'>
        <div class='form-box'>
            <h2>New User</h2>
            <form method='post'>
                <div class='form-container medium'>
                    <div class='input-box'>
                        <input type='text' name='username' value='$username' required>
                        <label>Username</label>
                    </div>
                    <div class='input-box'>
                        <select name='user_type' required>
                            <option value=''>Select User Type</option>
                            <option value='ADMIN' " .
    ($user_type == "ADMIN" ? "selected" : "") .
    ">Admin</option>
                            <option value='MANAGER' " .
    ($user_type == "MANAGER" ? "selected" : "") .
    ">Manager</option>
                        </select>
                    </div>
                    <div class='input-box'>
                        <input type='password' name='pass' value='$pass' required>
                        <label>Password</label>
                    </div>
                    <div class='input-box'>
                        <input type='password' name='repass' value='$repass' required>
                        <label>Re-enter Password</label>
                    </div>
                </div>
                <div class='footer'>
                    <button type='submit' class='btn'>Add</button>
                    <a class='btn' href='user-dashboard.php'>Cancel</a>
                </div>
            </form>" .
    (empty($errorMessage)
        ? ""
        : "<p title='$details' class='err-msg'>$errorMessage</p>") .
    (empty($successMessage) ? "" : "<p class='suc-msg'>$successMessage</p>") .
    "</div>
    </div>
";
?>
</body>
