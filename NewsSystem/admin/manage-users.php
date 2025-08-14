<?php global $conn;
include "../connector.php";
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Online News</title>
        <link rel="stylesheet" href="admin.css">
    </head>
<body>
    <?php include "./nav.php"; ?>
    <div class="content">
    <?php echo "
        <a href='user-create.php?admin_id=$admin_id' class='submit'>Add User</a>
        "; ?>
        <table class="table table-bordered table-striped">
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
            $sql = "SELECT * FROM user WHERE user_id != $admin_id";
            $result = $conn->query($sql);
            if (!$result) {
                die("Invalid query" . $conn->connect_error);
            }

            while ($row = $result->fetch_assoc()) {
                echo "
                    <tr>
                        <td>$row[user_name]</td>
                        <td>$row[pass]</td>
                        <td>$row[user_type]</td>
                        <td class='action'>
                            <a href='./user-edit.php?user_id=$row[user_id]&admin_id=$admin_id'>Edit</a>
                            <a href='./user-delete.php?user_id=$row[user_id]&admin_id=$admin_id'>Delete</a>
                        </td>
                    </tr>
                    ";
            }
            ?>
            </tbody>
        </table>
    </div>
</body>
</html>
