<?php
// test_db_connection.php

echo "Attempting to include db.php...<br>";
require_once 'db.php';
echo "db.php included.<br>";

if (isset($conn) && is_object($conn) && $conn instanceof mysqli) {
    echo "SUCCESS: \$conn is a valid mysqli object!<br>";
    echo "Host Info: " . $conn->host_info . "<br>";
    // Try a simple query to confirm full functionality
    $result = $conn->query("SELECT 1+1 AS test_sum");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "Query Test: 1+1 = " . $row['test_sum'] . "<br>";
    } else {
        echo "ERROR: Simple query failed: " . $conn->error . "<br>";
    }
    $conn->close();
} else {
    echo "FAIL: \$conn is NOT a valid mysqli object after including db.php.<br>";
    echo "Type of \$conn: " . gettype($conn) . "<br>";
    if (isset($conn)) {
        echo "Value of \$conn: ";
        var_dump($conn);
    }
    if ($conn->connect_error ?? null) { // Use null coalescing to prevent error if $conn is truly null
        echo "MySQLi Connection Error: " . $conn->connect_error . "<br>";
    } else {
        echo "No MySQLi connection error reported, but \$conn is still not an object. Check db.php content closely for syntax errors outside of the connection block.<br>";
    }
}
?>