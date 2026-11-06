<?php
$server_name = "localhost";
$database_username = "root";
$database_password = "";
$database_name = "inventory_system";

$conn = new mysqli($server_name, $database_username, $database_password, $database_name);

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}
?>
