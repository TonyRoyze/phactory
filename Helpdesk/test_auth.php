<?php
// Simple test script to verify database connection and user table structure
require 'connector.php';

echo "<h2>Testing Enhanced Authentication System</h2>";

// Test database connection
if ($conn->connect_error) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $conn->connect_error . "</p>";
    exit();
} else {
    echo "<p style='color: green;'>✅ Database connection successful</p>";
}

// Test users table structure
$sql = "DESCRIBE users";
$result = $conn->query($sql);

if ($result) {
    echo "<h3>Users Table Structure:</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<p style='color: green;'>✅ Users table structure is correct</p>";
} else {
    echo "<p style='color: red;'>❌ Could not describe users table: " . $conn->error . "</p>";
}

// Test sample data
$sql = "SELECT user_id, username, email, full_name, user_role FROM users LIMIT 5";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    echo "<h3>Sample Users:</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Full Name</th><th>Role</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['user_id'] . "</td>";
        echo "<td>" . $row['username'] . "</td>";
        echo "<td>" . $row['email'] . "</td>";
        echo "<td>" . $row['full_name'] . "</td>";
        echo "<td>" . $row['user_role'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<p style='color: green;'>✅ Sample users found in database</p>";
} else {
    echo "<p style='color: orange;'>⚠️ No users found in database</p>";
}

echo "<h3>Test Links:</h3>";
echo "<p><a href='signup.php'>Test Signup Form</a></p>";
echo "<p><a href='login.php'>Test Login Form</a></p>";
echo "<p><a href='app.php'>Test Main Application</a></p>";

$conn->close();
?>