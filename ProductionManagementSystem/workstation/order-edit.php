<?php global $conn;
session_start();
include "../connector.php";
include "../functions.php";



$quantity = "";
$part_id = "";
$workstation_id = "";
$details = "";
$successMessage = "";

if ($_SERVER["REQUEST_METHOD"] == "GET") {

    if (!isset($_GET["order_id"])) {
        header("location: /order-dashboard.php");
        exit();
    }

    $order_id = $_GET["order_id"];
    
    $sql = "SELECT * FROM orders WHERE order_id='$order_id'";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();

    // print_r($row);

    if (!$row) {
        header("location: /order-dashboard.php");
        exit();
    }  
    
    $part_id = $row["part_id"];
    $quantity = $row["quantity"];
    $workstation_id = $row["workstation_id"];
} else {

    $part_id = $_POST["part_id"];
    $quantity = $_POST["quantity"];
    $workstation_id = $_POST["workstation_id"];


        do {
            $sql =
                /** @lang text */
                "UPDATE orders " .
                "SET part_id = $part_id, quantity = $quantity, workstation_id = $workstation_id" .
                "WHERE order_id = $order_id";

            try {
                $result = $conn->query($sql);
            } catch (Exception $e) {
                $errorMessage = "Failed to update Order";
                $details = $conn->error;
                break;
            }


            $successMessage = "Order Updated Successfully";

        } while (false);

}
?>

<?php
include "./workstation-header.php";
echo "
    <div class='wrapper medium'>
        <div class='form-box'>
            <h2>New Order</h2>
            <form method='post'>
                <div class='form-container medium'>
                    
                    <div class='input-box'>
                        <select name='part_id' required>";

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
                        <input type='number' name='quantity' value='$quantity' required>
                        <label>Quantity</label>
                    </div>

                </div>
                <div class='input-box'>
                        <select name='workstation_id' required>";

$sql = "SELECT * FROM user JOIN workstation ON user.workstation_id = workstation.workstation_id";
$result = $conn->query($sql);

if (!$result) {
    die("Invalid query: " . $conn->connect_error);
}
while ($row = $result->fetch_assoc()) {
    // print_r($row);
    echo "<option value='" .
        $row["workstation_id"] .
        "'>" .
        $row["user_name"] .
        "</option>";
}

echo "              </select>
                    </div>
                <div class='footer'>
                    <button type='submit' class='btn'>Update</button>
                    <a class='btn' href='./order-dashboard.php'>Cancel</a>
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
