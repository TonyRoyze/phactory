<?php global $conn;
session_start();
include "./connector.php";
include "./functions.php";

$login = true;
$login_username = "";
$login_pass = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["btn-login"])) {
        $login_username = $_POST["login_username"];
        $login_pass = $_POST["login_pass"];
    }

    if (!empty($login_username) && !empty($login_pass)) {
        do {
            $sql = "SELECT * FROM user WHERE user_name = '$login_username' LIMIT 1";

            try {
                $result = $conn->query($sql);
            } catch (Exception $e) {
                $errorMessage = "Invalid Query";
                break;
            }

            if ($result && mysqli_num_rows($result) > 0) {
                $user_data = $result->fetch_assoc();

                if (
                    password_verify($login_pass, $user_data["password"]) ||
                    $user_data["password"] == $login_pass
                ) {
                    $_SESSION["Username"] = $user_data["user_name"];
                    $successMessage = "Logged In Successfully";
                    if ($user_data["user_type"] == "ADMIN") {
                        header("location: /admin/user-dashboard.php");
                        exit();
                    } elseif ($user_data["user_type"] == "MANAGER") {
                        header("location: /manager/order-dashboard.php");
                        exit();
                    } else {
                        $sql = "SELECT is_active FROM workstation WHERE workstation_id = $user_data[workstation_id]";

                        try {
                            $result = $conn->query($sql);
                        } catch (Exception $e) {
                            $errorMessage = "Invalid Query";
                        }
                        $workstation_data = $result->fetch_assoc();
                        header(
                            "location: /workstation/workstation-dashboard.php?workstation_id=$user_data[workstation_id]&is_active=$workstation_data[is_active]"
                        );
                        exit();
                    }
                } else {
                    $errorMessage = "Username or Password is Incorrect";
                    break;
                }
            }
        } while (false);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prod Manage</title>
    <link rel="stylesheet" href="styles/common.css">

</head>

<body>
<?php echo "
<div class='wrapper login'>
    <div class='form-box login'>
        <h2>Login</h2>
        <form method='post' class='form'>
            <div class='input-box'>
                <span class='icon'><svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='lucide lucide-circle-user'>
                    <circle cx='12' cy='12' r='10'/><circle cx='12' cy='10' r='3'/>
                    <path d='M7 20.662V19a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v1.662'/>
                </svg></span>
                <input type='text' name='login_username' required>
                <label>User Name</label>
            </div>
            <div class='input-box'>
                <span class='icon'><svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='lucide lucide-key-round'>
                    <path d='M2.586 17.414A2 2 0 0 0 2 18.828V21a1 1 0 0 0 1 1h3a1 1 0 0 0 1-1v-1a1 1 0 0 1 1-1h1a1 1 0 0 0 1-1v-1a1 1 0 0 1 1-1h.172a2 2 0 0 0 1.414-.586l.814-.814a6.5 6.5 0 1 0-4-4z'/>
                    <circle cx='16.5' cy='7.5' r='.5' fill='currentColor'/>
                </svg></span>
                <input type='password' name='login_pass' required>
                <label>Password</label>
            </div>
            <input type='submit' class='btn' name='btn-login' value='Submit'>
        </form>" .
    (empty($errorMessage) ? "" : "<p class='err-msg'>$errorMessage</p>") .
    "</div>
</div>
"; ?>
</body>
</html>
