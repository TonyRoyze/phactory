<?php
session_start();
include "../connector.php";
include "../functions.php";

$user_data = checkLogin($conn);

include "./admin-header.php";
?>
<link rel='stylesheet' href='../styles/help.css'>

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
            <h1>Help Center</h1>
        </div>
        <div class="help-content">
            <h2>User Roles and Functionalities</h2>
            
            <h3>Admin</h3>
            <ul>
                <li>Manage users (create, edit, delete)</li>
                <li>Manage workstations (create, edit, delete)</li>
                <li>Manage parts (create, edit, delete)</li>
                <li>View system-wide reports and analytics</li>
            </ul>

            <h3>Manager</h3>
            <ul>
                <li>Create and manage orders</li>
                <li>Assign orders to workstations</li>
                <li>Monitor order status and progress</li>
                <li>Generate reports on production and efficiency</li>
            </ul>

            <h3>Workstation Operator</h3>
            <ul>
                <li>View assigned orders</li>
                <li>Update order status (start, pause, complete)</li>
                <li>Report issues or maintenance needs</li>
            </ul>

            <h2>Common Tasks</h2>
            
            <h3>Creating a New Order</h3>
            <ol>
                <li>Log in as a Manager</li>
                <li>Navigate to the Order Dashboard</li>
                <li>Click on "Create New Order"</li>
                <li>Fill in the required information (part, quantity, workstation)</li>
                <li>Submit the order</li>
            </ol>

            <h3>Updating Order Status</h3>
            <ol>
                <li>Log in as a Workstation Operator</li>
                <li>View your assigned orders</li>
                <li>Select the order you want to update</li>
                <li>Choose the new status (e.g., In Progress, Completed)</li>
                <li>Save the changes</li>
            </ol>

            <h2>Need Further Assistance?</h2>
            <p>If you need additional help or have specific questions, please contact your system administrator or refer to the detailed user manual.</p>
        </div>
    </div>
</body>
</html>
