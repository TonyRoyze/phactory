<?php global $conn;
session_start();
include "../connector.php";
include "../functions.php";

$user_data = checkLogin($conn);

include "./manager-header.php";
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
            <h2>Manager Dashboard Overview</h2>
            <p>As a manager, you have the responsibility to oversee production orders and manage workstation assignments. Here are the key features and tasks you can perform:</p>
            
            <h3>Order Management</h3>
            <ul>
                <li>Create new production orders</li>
                <li>View and edit existing orders</li>
                <li>Assign orders to specific workstations</li>
                <li>Monitor order status and progress</li>
            </ul>

            <h3>Workstation Overview</h3>
            <ul>
                <li>View active and inactive workstations</li>
                <li>Check workstation capacity and current assignments</li>
                <li>Balance workload across available workstations</li>
            </ul>

            <h3>Reporting and Analytics</h3>
            <ul>
                <li>Generate production reports</li>
                <li>Analyze efficiency metrics</li>
                <li>Track order completion rates</li>
            </ul>

            <h2>Common Tasks</h2>
            
            <h3>Creating a New Order</h3>
            <ol>
                <li>Click on "Create New Order" in the Order Dashboard</li>
                <li>Select the part to be produced</li>
                <li>Enter the required quantity</li>
                <li>Choose an available workstation</li>
                <li>Submit the order</li>
            </ol>

            <h3>Editing an Existing Order</h3>
            <ol>
                <li>Locate the order in the Order Dashboard</li>
                <li>Click on the "Edit" button next to the order</li>
                <li>Modify the necessary details (quantity, workstation, status)</li>
                <li>Save the changes</li>
            </ol>

            <h2>Best Practices</h2>
            <ul>
                <li>Regularly check the Order Dashboard for updates and new assignments</li>
                <li>Prioritize orders based on deadlines and workstation availability</li>
                <li>Communicate with workstation operators about any changes or urgent orders</li>
                <li>Monitor workstation performance and address any efficiency issues promptly</li>
            </ul>

            <h2>Need Further Assistance?</h2>
            <p>If you encounter any issues or have questions about using the Manager Dashboard, please contact the system administrator or refer to the detailed user manual for more in-depth information.</p>
        </div>
    </div>
</body>
</html>
