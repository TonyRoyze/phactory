<?php global $conn;
session_start();
include "../connector.php";
include "../functions.php";
$user_data = checkLogin($conn);

$part_id = isset($_POST["part_id"]) ? $_POST["part_id"] : "";
$quantity = isset($_POST["quantity"]) ? $_POST["quantity"] : "";
$workstation_id = isset($_POST["workstation_id"])
    ? $_POST["workstation_id"]
    : "";

$errorMessage = "";
$details = "";
$successMessage = "";

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    if (!isset($_GET["order_id"])) {
        header("location: /order-dashboard.php");
        exit();
    }

    $order_id = $_GET["order_id"];

    $sql = "SELECT * FROM orders WHERE order_id=$order_id";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();

    if (!$row) {
        header("location: /order-dashboard.php");
        exit();
    }

    $part_id = $row["part_id"];
    $quantity = $row["quantity"];
    $workstation_id = $row["workstation_id"];
} elseif (
    $_SERVER["REQUEST_METHOD"] == "POST" &&
    !empty($part_id) &&
    !empty($quantity) &&
    !empty($workstation_id)
) {
    $order_id = $_POST["order_id"];
    $part_id = $_POST["part_id"];
    $quantity = $_POST["quantity"];
    $workstation_id = $_POST["workstation_id"];

    do {
        $sql =
            /** @lang text */
            "UPDATE orders " .
            "SET part_id = $part_id, quantity = $quantity, workstation_id = $workstation_id " .
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
include "./manager-header.php";
echo "
    <div class='wrapper medium'>
        <div class='form-box'>
            <h2>Edit Order</h2>
            <form method='post'>
                <input type='hidden' name='order_id' value='$order_id'>
                <div class='form-container medium'>

                    <div class='input-box'>
                        <select name='part_id' required onchange='this.form.submit()'>
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

echo "                  </select>
                    </div>
                    <div class='input-box'>
                    <select name='workstation_id' required onchange='this.form.submit()'>
                        <option value=''>Select Workstation</option>";

$sql =
    "SELECT user.user_name, user.user_id, workstation.workstation_id " .
    "FROM user JOIN workstation " .
    "ON user.workstation_id = workstation.workstation_id " .
    "WHERE part_id = $part_id";
$result = $conn->query($sql);

if (!$result) {
    die("Invalid query: " . $conn->connect_error);
}
while ($row = $result->fetch_assoc()) {
    $selected = $row["workstation_id"] == $workstation_id ? "selected" : "";
    echo "<option value='" .
        $row["workstation_id"] .
        "' $selected>" .
        $row["user_name"] .
        "</option>";
}

$sql = "SELECT workstation_capacity FROM workstation WHERE workstation_id = $workstation_id";
$result = $conn->query($sql);

if (!$result) {
    die("Invalid query: " . $conn->connect_error);
}

$workstation = $result->fetch_assoc();

echo "                  </select>
                    </div>
                    <div class='input-box'>
                        <input type='number' name='quantity' value='$quantity' max='$workstation[workstation_capacity]' required>
                        <label>Quantity</label>
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
