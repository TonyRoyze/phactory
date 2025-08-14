<?php global $conn;
session_start();
include "../connector.php";
include "../functions.php";

$user_data = checkLogin($conn);

$searchQuery = "";
if (isset($_GET["search"])) {
    $searchQuery = $_GET["search"];
}
?>

<?php include "./admin-header.php"; ?>

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
            <h1>User Details</h1>
            <div class="table-action">
                <div class="group">
                    <svg class="icon" aria-hidden="true" viewBox="0 0 24 24"><g>
                        <path d="M21.53 20.47l-3.66-3.66C19.195 15.24 20 13.214 20 11c0-4.97-4.03-9-9-9s-9 4.03-9 9 4.03 9 9 9c2.215 0 4.24-.804 5.808-2.13l3.66 3.66c.147.146.34.22.53.22s.385-.073.53-.22c.295-.293.295-.767.002-1.06zM3.5 11c0-4.135 3.365-7.5 7.5-7.5s7.5 3.365 7.5 7.5-3.365 7.5-7.5 7.5-7.5-3.365-7.5-7.5z">
                        </path></g>
                    </svg>
                    <form method="get">
                        <input placeholder="Search" type="search" class="input" name="search" value="<?php echo htmlspecialchars(
                            $searchQuery
                        ); ?>">
                    </form>
                </div>
                <a class='btn-animate bg-default' href='user-create.php'>
                <div class='sign'><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-plus">
                    <path d="M5 12h14"/><path d="M12 5v14"/></svg></div>
                <div class='text'>Create</div>
                </a>
            </div>
        </div>
        <table class="table">
            <thead>
            <tr>
                <th>User Name</th>
                <th>Password</th>
                <th>User Type</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php
            if (!empty($searchQuery)) {
                $sql =
                    "SELECT * FROM user WHERE workstation_id IS NULL AND user_name LIKE ?";
                $stmt = $conn->prepare($sql);
                $searchParam = "%$searchQuery%";
                $stmt->bind_param("s", $searchParam);
                $stmt->execute();
                $result = $stmt->get_result();
            } else {
                $sql = "SELECT * FROM user WHERE workstation_id IS NULL";
                $result = $conn->query($sql);
            }

            if (!$result) {
                die("Invalid query" . $conn->connect_error);
            }

            while ($row = $result->fetch_assoc()) {
                $pass = substr($row["password"], 0, 30) . "...";
                echo "
                    <tr>
                        <td>$row[user_name]</td>
                        <td>$pass</td>
                        <td>$row[user_type]</td>
                        <td class='action'>
                        <a class='btn-edit bg-default' href='user-edit.php?user_id=$row[user_id]'>
                            <div class='sign'><svg viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='3' stroke-linecap='round' stroke-linejoin='round' class='lucide lucide-pencil'>
                                <path d='M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z'/>
                                <path d='m15 5 4 4'/></svg>
                            </div>
                        </a>
                        <a class='btn-delete bg-red' href='user-delete.php?user_id=$row[user_id]'>
                            <div class='sign'><svg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='3' stroke-linecap='round' stroke-linejoin='round' class='lucide lucide-trash-2'>
                            <path d='M3 6h18'/><path d='M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6'/><path d='M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2'/><line x1='10' x2='10' y1='11' y2='17'/><line x1='14' x2='14' y1='11' y2='17'/></svg>
                            </div>
                        </a>
                        </td>
                    </tr>
                    ";
            }
            ?>
            </tbody>
        </table>
    </div>
</body>
