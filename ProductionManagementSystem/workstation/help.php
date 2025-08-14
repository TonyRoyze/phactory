<?php global $conn;
session_start();
include "../connector.php";
include "../functions.php";

$user_data = checkLogin($conn);

include "./workstation-header.php";
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
            <h2>Workstation Dashboard Guide</h2>
            
            <h3>Your Responsibilities</h3>
            <ul>
                <li>View and manage assigned orders</li>
                <li>Update order status as you progress</li>
                <li>Report any issues or maintenance needs</li>
            </ul>

            <h3>Dashboard Overview</h3>
            <ul>
                <li><strong>Order Details Table:</strong> Shows all your current orders</li>
                <li><strong>Power Button:</strong> Activate or deactivate your workstation</li>
                <li><strong>Order Status:</strong> Indicates the current state of each order</li>
            </ul>

            <h3>Common Tasks</h3>
            
            <h4>Updating Order Status</h4>
            <ol>
                <li>Locate the order in the Order Details table</li>
                <li>Click on the status button for that order</li>
            </ol>

            <h4>Activating/Deactivating Your Workstation</h4>
            <ol>
                <li>Find the power button at the top of the dashboard</li>
                <li>Click the button to toggle your workstation's active status</li>
            </ol>


            <h3>Understanding Order Status</h3>
            <ul>
                <li><strong>Pending:</strong> Order assigned but not started</li>
                <li><strong>In Progress:</strong> Currently working on this order</li>
                <li><strong>Paused:</strong> Temporarily stopped work on this order</li>
                <li><strong>Completed:</strong> Order finished and ready for next stage</li>
            </ul>

            <h3>Best Practices</h3>
            <ul>
                <li>Update order status promptly to keep managers informed</li>
                <li>Report any issues immediately to minimize downtime</li>
                <li>Ensure your workstation is active when you're ready to work</li>
                <li>Double-check completed orders before marking them as finished</li>
            </ul>

            <h3>Need Further Assistance?</h3>
            <p>If you encounter any issues or have questions about using the Workstation Dashboard, please contact your supervisor or the system administrator for support.</p>
        </div>
    </div>
</body>
</html>
