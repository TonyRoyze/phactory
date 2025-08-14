<?php global $conn;
session_start();
include "../connector.php";
include "../functions.php";

$user_data = checkLogin($conn);

$errorMessage = "";
$details = "";
$successMessage = "";

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    if (!isset($_GET["workstation_id"])) {
        header("location: ./workstation-dashboard.php");
        exit();
    }

    $workstation_id = $_GET["workstation_id"];

    $sql =
        /** @lang text **/
        "SELECT user.user_name," .
        "workstation.workstation_id, workstation_capacity, part.part_id " .
        "FROM user JOIN workstation ON user.workstation_id = workstation.workstation_id " .
        "JOIN part ON part.part_id = workstation.part_id WHERE workstation.workstation_id =$workstation_id";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();

    if (!$row) {
        header("location: ./workstation-dashboard.php");
        exit();
    }

    $username = $row["user_name"];
    $workstation_capacity = $row["workstation_capacity"];
    $part_id = $row["part_id"];
    $pass = "";
    $repass = "";
} else {
    $workstation_id = $_POST["workstation_id"];
    $username = $_POST["username"];
    $pass = $_POST["pass"];
    $repass = $_POST["repass"];

    $workstation_capacity = $_POST["workstation_capacity"];
    $part_id = $_POST["part_id"];

    if ($pass == $repass) {
        do {
            $pwd = password_hash($pass, PASSWORD_DEFAULT);

            $sql =
                /** @lang text */
                "UPDATE user " .
                "SET user_name = '$username', password = '$pwd' " .
                "WHERE workstation_id = '$workstation_id'";

            try {
                $result = $conn->query($sql);
            } catch (Exception $e) {
                $errorMessage = "Invalid query";
                $details = $conn->error;
                break;
            }

            $sql2 =
                /** @lang text */
                "UPDATE workstation " .
                "SET workstation_capacity = '$workstation_capacity', part_id = '$part_id' " .
                "WHERE workstation_id = '$workstation_id'";

            try {
                $result = $conn->query($sql2);
            } catch (Exception $e) {
                $errorMessage = "Invalid query";
                $details = $conn->error;
                break;
            }

            $successMessage = "Workstation Updated Successfully";

            header("location: ./workstation-dashboard.php");
            exit();
        } while (false);
    } else {
        $errorMessage = "Passwords don't match";
    }
}
?>

<?php
include "./admin-header.php";
echo "
    <div class='wrapper large'>
        <div class='form-box'>
            <h2>Edit Workstation</h2>
            <form method='post'>
                <input type='hidden' name='workstation_id' value='$workstation_id'>
                <div class='form-container medium'>
                <div class='col-2'>
                <div class='col'>
                    <div class='input-box'>
                        <input type='text' name='username' value='$username' required>
                        <label>Workstation Name</label>
                    </div>

                    <div class='input-box'>
                        <select name='part_id' required>
                            <option value=''>Select Part</option>";

$sql = "SELECT * FROM part";
$result = $conn->query($sql);

if (!$result) {
    die("Invalid query: " . $conn->connect_error);
}

while ($row = $result->fetch_assoc()) {
    $selected = $row["part_id"] == $part_id ? "selected" : "";
    echo "<option value='" .
        $row["part_id"] .
        "' $selected>" .
        $row["part_name"] .
        "</option>";
}

echo "              </select>
                    </div>
                    <div class='input-box'>
                        <input type='number' name='workstation_capacity' value='$workstation_capacity' required>
                        <label>Workstation Capacity</label>
                    </div>

                </div>
                <div class='col'>
                <div class='input-box'>
                    <input type='password' name='pass' value='$pass' required>
                    <label>Password</label>
                </div>
                <div class='input-box'>
                    <input type='password' name='repass' value='$repass' required>
                    <label>Re-enter Password</label>
                </div>
                </div>
                </div>
                <div class='footer'>
                    <button type='submit' class='btn'>Update</button>
                    <a class='btn' href='workstation-dashboard.php'>Cancel</a>
                </div>
            </form>" .
    (empty($errorMessage)
        ? ""
        : "<p title='$details' class='err-msg'>$errorMessage</p>") .
    "</div>
    </div>
";


?>
