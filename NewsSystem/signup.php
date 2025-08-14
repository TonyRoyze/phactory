<?php global $conn;
include "./connector.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $password = $_POST["password"];
    $repass = $_POST["repassword"];

    if ($password == $repass) {
        $sql = "INSERT INTO user (user_name, pass, user_type) VALUES ('$username', '$password','WRITER') ";
        $result = $conn->query($sql);
    }

    header("location: ./login.php");
}
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Online News</title>
        <link rel="stylesheet" href="login.css">
    </head>
<body>
    <div class="flex">
        <div class="hero">
        </div>
        <div class="flex section">
            <form class="form" method="post">
                <a href='./index.php'>
                <div class="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#115DFC" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-key-round">
                        <path d="M2.586 17.414A2 2 0 0 0 2 18.828V21a1 1 0 0 0 1 1h3a1 1 0 0 0 1-1v-1a1 1 0 0 1 1-1h1a1 1 0 0 0 1-1v-1a1 1 0 0 1 1-1h.172a2 2 0 0 0 1.414-.586l.814-.814a6.5 6.5 0 1 0-4-4z"/>
                        <circle cx="16.5" cy="7.5" r=".5" fill="#115DFC"/>
                    </svg>
                </div>
                </a>
                <div class="note">
                    <label class="title">Sign up</label>
                </div>
                <input placeholder="Enter your username" title="Enter your username" name="username" type="text" class="input_field" required>
                <input placeholder="Enter your password" title="Enter your password" name="password" type="password" class="input_field" required>
                <input placeholder="Enter your password again" title="Enter your password again" name="repassword" type="password" class="input_field" required>
                <button class="submit">Submit</button>
                <p class="p">Have an account? <a href="./login.php"><span class="span">Login</span></a>
            </form>
        </div>
    </div>
</body>
</html>
