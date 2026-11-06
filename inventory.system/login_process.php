<?php
session_start();
include("connection.php");

$username = trim($_POST["username"]);
$password = $_POST["password"];
// Prepared statement: query structure is fixed before user input is added
$stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
if (!$stmt) {
    header("Location: login.php?msg=not_found");
    exit();
}
// bind_param() sends username as a STRING value, not executable SQL —
// prevents SQL injection (e.g. ' OR '1'='1 is treated as plain text)
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
     // password_verify() compares against the hashed password, never plaintext
    if (password_verify($password, $row["password"])) {
        $_SESSION["user_id"]   = $row["user_id"];
        $_SESSION["full_name"] = $row["full_name"];
        $_SESSION["role"]      = $row["role"];
        header("Location: dashboard.php");
        exit();
    } else {
        header("Location: login.php?msg=wrong_password");
        exit();
    }
} else {
    header("Location: login.php?msg=not_found");
    exit();
}

$conn->close();
?>
