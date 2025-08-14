<?php global $conn;
session_start();
include "../connector.php";
include "../functions.php";

$user_data = checkLogin($conn);

$part_name = "";
$part_desc = "";
$errorMessage = "";
$details = "";
$successMessage = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $part_name = $_POST["part_name"];
    $part_desc = $_POST["part_desc"];

    do {
        $sql =
            /** @lang text */
            "INSERT INTO part (part_name, part_desc)" .
            "VALUES ('$part_name', '$part_desc')";

        try {
            $result = $conn->query($sql);
        } catch (Exception $e) {
            $errorMessage = "Invalid query";
            $details = $conn->error;
            break;
        }

        $successMessage = "Part Added Successfully";

        $part_name = "";
        $part_desc = "";
    } while (false);
}
?>

<?php
include "./admin-header.php";
echo "
    <div class='wrapper medium'>
        <div class='form-box'>
            <h2>New Part</h2>
            <form method='post'>
                <div class='form-container medium'>
                    <div class='input-box'>
                        <input type='text' name='part_name' value='$part_name' required>
                        <label>Part Name</label>
                    </div>
                    <div class='textarea-box'>
                        <textarea name='part_desc' value='$part_desc' required></textarea>
                        <label>Part Description</label>
                    </div>
                </div>
                <div class='footer'>
                    <button type='submit' class='btn'>Add</button>
                    <a class='btn' href='part-dashboard.php'>Cancel</a>
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
