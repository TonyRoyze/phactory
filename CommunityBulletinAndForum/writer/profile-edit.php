<?php global $conn;
include "../connector.php";

$username = "";
$user_type = "";
$user_id = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_POST["user_id"];
    $username = $_POST["username"];
    $password = $_POST["password"];
    $repass = $_POST["repassword"];

    if ($password == $repass) {
        $sql =
            /** @lang text */
            "UPDATE user " .
            "SET user_name = '$username', pass = '$password' " .
            "WHERE user_id = '$user_id'";
        $result = $conn->query($sql);
    }
    $writer_id = $_GET["writer_id"];
    header("location: ./writer.php?writer_id=$writer_id");
}

if (isset($_GET["user_id"])) {
    $user_id = $_GET["user_id"];
    $sql = "SELECT * FROM user WHERE user_id='$user_id '";
    $result = $conn->query($sql);
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
        <title>Edit Profile - Community Bulletin</title>
        <link rel="stylesheet" href="writer.css">
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
              <label class="title">Edit Profile</label>
            </div>
            <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
            <input placeholder="Enter username" title="Enter username" name="username" type="text" class="input_field" value="<?php echo $username; ?>" required >
            <input placeholder="Enter password" title="Enter password"  name="password" type="password" class="input_field" required>
            <input placeholder="Enter password again" title="Enter password again"  name="repassword" type="password" class="input_field" required>
            <button class="submit">Update Profile</button>
          </form>
        </div>
    </div>
</body>
</html>
