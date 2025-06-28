<?php
session_start();
error_log("This is a test log message from test_log.php. Current time: " . date('Y-m-d H:i:s'));
echo "Check your error log file!";
// Intentionally cause an error to see if it logs
$undefined_variable = $non_existent_variable;
?>