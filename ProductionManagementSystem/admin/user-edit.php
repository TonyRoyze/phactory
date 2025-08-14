<?php global $conn;
session_start();
include "../connector.php";
include "../functions.php";

$user_data = checkLogin($conn);

$errorMessage = "";
$details = "";
$successMessage = "";

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    if (!isset($_GET["user_id"])) {
        header("location: ./user-dashboard.php");
        exit();
    }

    $user_id = $_GET["user_id"];

    $sql = "SELECT * FROM user WHERE user_id='$user_id'";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();

    if (!$row) {
        header("location: ./user-dashboard.php");
        exit();
    }

    $name = $row["user_name"];
    $user_type = $row["user_type"];
    $pass = "";
    $repass = "";
} else {
    $user_id = $_POST["user_id"];
    $name = $_POST["name"];
    $user_type = $_POST["user_type"];
    $pass = $_POST["pass"];
    $repass = $_POST["repass"];

    if ($pass == $repass) {
        $pwd = password_hash($pass, PASSWORD_DEFAULT);

        $sql =
            /** @lang text */
            "UPDATE user " .
            "SET user_name = '$name', user_type = '$user_type', password = '$pwd' " .
            "WHERE user_id = '$user_id'";

        try {
            $result = $conn->query($sql);
            $successMessage = "User Updated Successfully";
            // Reset form values
            $username = "";
            $user_type = "";
            $pass = "";
            $repass = "";

            header("location: ./user-dashboard.php");
            exit();
        } catch (Exception $e) {
            $errorMessage = "Invalid query";
            $details = $conn->error;
        }
    } else {
        $errorMessage = "Passwords don't match";
    }
}
?>

<?php
include "./admin-header.php";
echo "
    <div class='wrapper medium'>
        <div class='form-box'>
            <h2>Edit User</h2>
            <form method='post'>
                <input type='hidden' name='user_id' value='$user_id'>
                <div class='form-container medium'>
                    <div class='input-box'>
                        <input type='text' name='name' value='$name' required>
                        <label>Username</label>
                    </div>
                    <div class='input-box'>
                        <select name='user_type' required>
                            <option value=''>Select User Type</option>
                            <option value='ADMIN'" .
    ($user_type == "ADMIN" ? " selected" : "") .
    ">Admin</option>
                            <option value='MANAGER' " .
    ($user_type == "MANAGER" ? " selected" : "") .
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
                    <button type='submit' class='btn'>Update</button>
                    <a class='btn' href='user-dashboard.php'>Cancel</a>
                </div>
            </form>" .
    (empty($errorMessage)
        ? ""
        : "<p title='$details' class='err-msg'>$errorMessage</p>") .
    "</div>
    </div>
";


?>
