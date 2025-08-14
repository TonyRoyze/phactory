<?php global $conn;
session_start();
include "../connector.php";
include "../functions.php";

$user_data = checkLogin($conn);

$errorMessage = "";
$details = "";
$successMessage = "";

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    if (!isset($_GET["part_id"])) {
        header("location: ./part-dashboard.php");
        exit();
    }

    $part_id = $_GET["part_id"];

    $sql = "SELECT * FROM part WHERE part_id = $part_id";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();

    if (!$row) {
        header("location: ./part-dashboard.php");
        exit();
    }

    $part_name = $row["part_name"];
    $part_desc = $row["part_desc"];
} else {
    $part_id = $_POST["part_id"];
    $part_name = $_POST["part_name"];
    $part_desc = htmlspecialchars($_POST["part_desc"]);

    do {
        $sql = "UPDATE part SET part_name = '$part_name', part_desc = '$part_desc' WHERE part_id = $part_id";

        try {
            $result = $conn->query($sql);
        } catch (Exception $e) {
            $errorMessage = "Invalid query";
            $details = $conn->error;
            break;
        }

        $successMessage = "Part Updated Successfully";

        header("location: ./part-dashboard.php");
        exit();
    } while (false);
}
?>

<?php
include "./admin-header.php";
echo "
    <div class='wrapper medium'>
        <div class='form-box'>
            <h2>Edit Part</h2>
            <form method='post'>
                <input type='hidden' name='part_id' value='$part_id'>
                <div class='form-container medium'>
                <div class='input-box'>
                    <input type='text' name='part_name' value='$part_name' required>
                    <label>Part Name</label>
                </div>
                <div class='textarea-box'>
                    <textarea name='part_desc' required>$part_desc</textarea>
                    <label>Part Description</label>
                </div>
                </div>
                <div class='footer'>
                    <button type='submit' class='btn'>Update</button>
                    <a class='btn' href='part-dashboard.php'>Cancel</a>
                </div>
            </form>" .
    (empty($errorMessage)
        ? ""
        : "<p title='$details' class='err-msg'>$errorMessage</p>") .
    "</div>
    </div>
";


?>
