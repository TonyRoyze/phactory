<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

?>

<?php include './header.php'; ?>
<section id="users">
    <h2 class="users-title">Users</h2>
    <a href="./add_user.php" class="btn btn-add">Add User</a>
    <div class="users-container">
        <?php
        include '../connector.php';

        $sql = "SELECT * FROM users";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            echo '<table class="user-table">';
            echo '<tr><th>User ID</th><th>Username</th><th>Email</th><th>Actions</th></tr>';
            while($row = $result->fetch_assoc()) {
                echo '<tr>';
                echo '<td style="width: 10%;">' . $row['user_id'] . '</td>';
                echo '<td style="width: 40%;">' . $row['username'] . '</td>';
                echo '<td style="width: 30%;">' . ($row['user_type'] == 0 ? 'User' : 'Admin') . '</td>';
                echo '<td style="width: 20%;" class="table-actions"><a href="./edit_user.php?user_id=' . $row['user_id'] . '" class="btn">Edit</a><a href="./delete_user.php?user_id=' . $row['user_id'] . '" class="btn">Delete</a></td>';
                echo '</tr>';
            }
            echo '</table>';
        } else {
            echo '<p>No users found.</p>';
        }

        $conn->close();
        ?>
    </div>
</section>
<?php include '../footer.php'; ?>
</body>
</html>
