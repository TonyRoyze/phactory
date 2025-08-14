<?php global $conn;
session_start();
include "../connector.php";
include "../functions.php";

$user_data = checkLogin($conn);

$workstation_name = "";
$workstation_capacity = "";
$part_id = "";
$pass = "";
$repass = "";
$details = "";
$successMessage = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $workstation_name = $_POST["workstation_name"];
    $workstation_capacity = $_POST["workstation_capacity"];
    $part_id = $_POST["part_id"];
    $pass = $_POST["pass"];
    $repass = $_POST["repass"];

    if ($pass == $repass) {
        do {
            $sql =
                /** @lang text */
                "INSERT INTO workstation (workstation_capacity, is_active, part_id)" .
                "VALUES ('$workstation_capacity', 0, $part_id)";

            try {
                $result = $conn->query($sql);
            } catch (Exception $e) {
                $errorMessage = "Failed to create Workstation";
                $details = $conn->error;
                break;
            }

            $sql2 =
                "SELECT workstation_id FROM workstation ORDER BY workstation_id DESC LIMIT 1";

            try {
                $result = $conn->query($sql2);
            } catch (Exception $e) {
                $errorMessage = "Failed to find Workstation ID";
                $details = $conn->error;
                break;
            }

            $workstation_id = $result->fetch_assoc();

            $pwd = password_hash($pass, PASSWORD_DEFAULT);

            $sql3 =
                /** @lang text */
                "INSERT INTO user (user_name, user_type, password, workstation_id)" .
                "VALUES ('$workstation_name', 'WORKSTATION' , '$pwd', $workstation_id[workstation_id])";

            try {
                $result = $conn->query($sql3);
            } catch (Exception $e) {
                $errorMessage = "Failed to create User";
                $details = $conn->error;
                break;
            }

            $successMessage = "Workstation Added Successfully";

            $workstation_name = "";
            $workstation_capacity = "";
            $part_id = "";
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
            <h2>New Workstation</h2>
            <form method='post'>
                <div class='form-container medium'>
                <div class='col-2'>
                <div class='col'>
                    <div class='input-box'>
                        <input type='text' name='workstation_name' value='$workstation_name' required>
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
    echo "<option value='" .
        $row["part_id"] .
        "'>" .
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
                    <button type='submit' class='btn'>Add</button>
                    <a class='btn' href='workstation-dashboard.php'>Cancel</a>
                </div>
            </form>";

if (!empty($errorMessage)) {
    echo "<p title='$details' class='err-msg'>$errorMessage</p>";
}

if (!empty($successMessage)) {
    echo "<p class='suc-msg'>$successMessage</p>";
}

echo "  </div>
    </div>
";


?>
