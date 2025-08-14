<?php global $conn;
session_start();
include "../connector.php";
include "../functions.php";
$user_data = checkLogin($conn);

$workstation_id = "";
$is_active = "";

if (isset($_POST["btn-status"])) {
    $order_id = $_POST["order_id"];
    $current_status = $_POST["order_status"];
    $is_active = $_GET["is_active"];
    $currentColor = $is_active == 0 ? "red" : "green";

    if ($is_active == 1) {
        $new_status = ($current_status + 1) % 4;

        $sql = "UPDATE orders SET order_status = $new_status WHERE order_id = $order_id";

        try {
            $result = $conn->query($sql);
        } catch (Exception $e) {
            $errorMessage = "Invalid query";
            $details = $conn->error;
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    $workstation_id = $_GET["workstation_id"];
    $is_active = $_GET["is_active"];
    $currentColor = $is_active == 0 ? "red" : "green";
}

if (isset($_POST["btn-power"])) {
    $workstation_id = $_POST["workstation_id"];
    $is_active = $_POST["is_active"];

    $new_is_active = ($is_active + 1) % 2;

    $sql = "UPDATE workstation SET is_active = $new_is_active WHERE workstation_id = $workstation_id";

    try {
        $result = $conn->query($sql);
    } catch (Exception $e) {
        $errorMessage = "Invalid query";
        $details = $conn->error;
    }

    $currentColor = $is_active == 0 ? "red" : "green";
    $workstation_id = $_GET["workstation_id"];

    header(
        "location: /workstation/workstation-dashboard.php?workstation_id=$workstation_id&is_active=$new_is_active"
    );
}
?>

<?php include "./workstation-header.php"; ?>

<body>
    <div class='navigation'>
        <div class='container'>
                <?php include "./nav.php"; ?>
        </div>
        <a class='btn-animate bg-red' href='../logout.php'>
          <div class='sign'><svg viewBox='0 0 512 512'><path d='M377.9 105.9L500.7 228.7c7.2 7.2 11.3 17.1 11.3 27.3s-4.1 20.1-11.3 27.3L377.9 406.1c-6.4 6.4-15 9.9-24 9.9c-18.7 0-33.9-15.2-33.9-33.9l0-62.1-128 0c-17.7 0-32-14.3-32-32l0-64c0-17.7 14.3-32 32-32l128 0 0-62.1c0-18.7 15.2-33.9 33.9-33.9c9 0 17.6 3.6 24 9.9zM160 96L96 96c-17.7 0-32 14.3-32 32l0 256c0 17.7 14.3 32 32 32l64 0c17.7 0 32 14.3 32 32s-14.3 32-32 32l-64 0c-53 0-96-43-96-96L0 128C0 75 43 32 96 32l64 0c17.7 0 32 14.3 32 32s-14.3 32-32 32z'></path></svg></div>
          <div class='text'>Logout</div>
        </a>
    </div>
    <div class="dashboard">
        <div class="table-name">
            <h1>Order Details</h1>
<?php echo "<form method='post'>
                <input type='hidden' name='workstation_id' value='$workstation_id'>
                <input type='hidden' name='is_active' value='$is_active'>
                <button class='btn-power bg-default' name='btn-power'>
                    <div class='sign-power'>
                        <svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='$currentColor' stroke-width='3' stroke-linecap='round' stroke-linejoin='round' class='lucide lucide-power'>
                        <path d='M12 2v10'/><path d='M18.4 6.6a9 9 0 1 1-12.77.04'/></svg>
                    </div>
                </button>
            </form> "; ?>
        </div>
        <table class="table">
            <thead>
            <tr>
                <th>Part Name</th>
                <th>Quantity</th>
                <th>WorkStation</th>
                <th>Order Status</th>
            </tr>
            </thead>
            <tbody>
            <?php
            $workstation_id = $_GET["workstation_id"];
            $sql =
                "SELECT order_id, part_name, quantity, user_name, order_status " .
                "FROM orders JOIN user ON orders.workstation_id = user.workstation_id " .
                "JOIN part ON orders.part_id = part.part_id " .
                "WHERE order_status < 3 AND orders.workstation_id = $workstation_id";

            $result = $conn->query($sql);

            if (!$result) {
                die("Invalid query" . $conn->connect_error);
            }

            while ($row = $result->fetch_assoc()) {
                echo "
                    <tr>
                        <td>{$row["part_name"]}</td>
                        <td>{$row["quantity"]}</td>
                        <td>{$row["user_name"]}</td>
                        <td>
                            <form method='post'>
                                <input type='hidden' name='order_id' value='{$row["order_id"]}'>
                                <input type='hidden' name='order_status' value='{$row["order_status"]}'>
                                <button type='submit' name='btn-status' class='btn-status " .
                    ($row["order_status"] == 0
                        ? "red"
                        : ($row["order_status"] == 1
                            ? "yellow"
                            : ($row["order_status"] == 2
                                ? "green"
                                : "blue"))) .
                    "'>" .
                    ($row["order_status"] == 0
                        ? "Not Accepted"
                        : ($row["order_status"] == 1
                            ? "In Progress"
                            : ($row["order_status"] == 2
                                ? "Shipped"
                                : "Complete"))) .
                    "</button>
                            </form>
                        </td>
                    </tr>";
            }
            ?>
            </tbody>
        </table>
    </div>
</body>
