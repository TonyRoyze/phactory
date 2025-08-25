<?php
include "../connector.php";

$username = "";
$user_type = "";
$user_id = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_POST["user_id"];
    $username = $_POST["username"];
    $user_type = $_POST["user_type"];
    $password = $_POST["password"];
    $repass = $_POST["repassword"];

    if (!empty($password) && $password == $repass) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "UPDATE user SET user_name = ?, user_type = ?, pass = ? WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $username, $user_type, $hashed_password, $user_id);
    } else {
        $sql = "UPDATE user SET user_name = ?, user_type = ? WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $username, $user_type, $user_id);
    }
    $stmt->execute();

    $admin_id = $_GET["admin_id"];
    header("location: ./manage-users.php?admin_id=$admin_id");
}

if (isset($_GET["user_id"])) {
    $user_id = $_GET["user_id"];
    $sql = "SELECT * FROM user WHERE user_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    $username = $row["user_name"];
    $user_type = $row["user_type"];
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Edit Member - Community Bulletin Admin</title>
        <link rel="stylesheet" href="admin.css">
        <link rel="stylesheet" href="create.css">
    </head>
<body>
    <?php include "./nav.php"; ?>
    <div class="content flex-center">
        <div class="popup">
          <form class="form" method="post">
            <div class="icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#115DFC" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user-pen">
                    <path d="M11.5 15H7a4 4 0 0 0-4 4v2"/><path d="M21.378 16.626a1 1 0 0 0-3.004-3.004l-4.01 4.012a2 2 0 0 0-.506.854l-.837 2.87a.5.5 0 0 0 .62.62l2.87-.837a2 2 0 0 0 .854-.506z"/>
                    <circle cx="10" cy="7" r="4"/>
                </svg>
            </div>
            <div class="note">
              <label class="title">Edit Community Member</label>
            </div>
            <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
            <input placeholder="Enter username" title="Enter username" name="username" type="text" class="input_field" value="<?php echo $username; ?>" required >
            <select name="user_type" class="input_field" required>
                <option value="ADMIN" <?php echo ($user_type == 'ADMIN') ? 'selected' : ''; ?>>Admin</option>
                <option value="MEMBER" <?php echo ($user_type == 'MEMBER') ? 'selected' : ''; ?>>Community Member</option>
            </select>
            <input placeholder="Enter new password" title="Enter new password"  name="password" type="password" class="input_field">
            <input placeholder="Enter new password again" title="Enter new password again"  name="repassword" type="password" class="input_field">
            <button class="submit" style="width: 100%">Update Member</button>
          </form>
        </div>
    </div>
</body>
</html>