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

if (
    $_SERVER["REQUEST_METHOD"] == "POST" &&
    !empty($part_id) &&
    !empty($quantity) &&
    !empty($workstation_id)
) {
    do {
        $checkWorkstationSql = "SELECT is_active FROM workstation WHERE workstation_id = $workstation_id";

        try {
            $result = $conn->query($checkWorkstationSql);
            $workstation = $result->fetch_assoc();
        } catch (Exception $e) {
            $errorMessage = "Failed to create order";
            $details = $conn->error;
            break;
        }

        if (!$workstation || $workstation["is_active"] == 0) {
            $errorMessage = "Cannot create order for inactive workstation";
            break;
        }

        $sql =
            /** @lang text */
            "INSERT INTO orders (part_id, quantity, workstation_id, order_status)" .
            "VALUES ($part_id, $quantity, $workstation_id, 0)";

        try {
            $result = $conn->query($sql);
        } catch (Exception $e) {
            $errorMessage = "Failed to create order";
            $details = $conn->error;
            break;
        }

        $successMessage = "Order Added Successfully";

        $workstation_name = "";
        $quantity = "";
        $part_name = "";
    } while (false);
}
?>

<?php
include "./manager-header.php";
echo "
    <div class='wrapper medium flex-start'>
        <div class='form-box'>
            <h2>New Order</h2>
            <form method='post'>
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

echo "              </select>
                    </div>";

if (!empty($part_id)) {
    echo "
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

    echo "      </select>
                </div>
                    <div class='input-box'>
                        <input type='number' name='quantity' value='$quantity' max='$workstation[workstation_capacity]' required>
                        <label>Quantity</label>
                    </div>
                <div class='footer'>
                    <button type='submit' class='btn'>Add</button>
                    <a class='btn' href='./order-dashboard.php'>Cancel</a>
                </div>";
}
echo "</form>";

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
