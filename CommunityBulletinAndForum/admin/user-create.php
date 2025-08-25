<?php
include "../connector.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $user_type = $_POST["user_type"];
    $password = $_POST["password"];
    $repass = $_POST["repassword"];

    if ($password == $repass) {
        $sql = "INSERT INTO user (user_name, pass, user_type) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $username, $password, $user_type);
        $stmt->execute();
    }

    $admin_id = $_GET["admin_id"];
    header("location: ./manage-users.php?admin_id=$admin_id");
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Add Member - Community Bulletin Admin</title>
        <link rel="stylesheet" href="admin.css">
        <link rel="stylesheet" href="create.css">
    </head>
<body>
    <?php include "./nav.php"; ?>
    <div class="content flex-center">
        <div class="popup">
          <form class="form" method="post">
            <div class="icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#115DFC" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user-round-plus">
                  <path d="M2 21a8 8 0 0 1 13.292-6"/>
                  <circle cx="10" cy="8" r="5"/><path d="M19 16v6"/><path d="M22 19h-6"/>
                </svg>
            </div>
            <div class="note">
              <label class="title">Add Community Member</label>
            </div>
            <input placeholder="Enter username" title="Enter username" name="username" type="text" class="input_field" required>
            <select name="user_type" class="input_field" required>
                <option value="">Select user type</option>
                <option value="ADMIN">Admin</option>
                <option value="MEMBER">Community Member</option>
            </select>
            <input placeholder="Enter password" title="Enter password"  name="password" type="password" class="input_field" required>
            <input placeholder="Enter password again" title="Enter password again"  name="repassword" type="password" class="input_field" required>
            <button class="submit" style="width: 100%">Add Member</button>

          </form>
        </div>
    </div>
</body>
</html>