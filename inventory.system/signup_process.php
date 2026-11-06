<?php
include("connection.php");

$full_name = trim($_POST["full_name"]);
$email     = trim($_POST["email"]);
$username  = trim($_POST["username"]);
$password  = $_POST["password"];
$role      = $_POST["role"];

function redirect_error($msg, $full_name, $email, $username, $role) {
    $params = http_build_query([
        'tab'          => 'signup',
        'signup_error' => $msg,
        'r_fullname'   => $full_name,
        'r_email'      => $email,
        'r_username'   => $username,
        'r_role'       => $role,
    ]);
    header("Location: login.php?" . $params);
    exit();
}

// Full name — letters and spaces only
if (empty($full_name)) {
    redirect_error("Full name is required", $full_name, $email, $username, $role);
}
if (!preg_match('/^[a-zA-Z\s]+$/', $full_name)) {
    redirect_error("Full name must contain letters only — no numbers or symbols", $full_name, $email, $username, $role);
}

// Email

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirect_error("Please enter a valid email address", $full_name, $email, $username, $role);
}

// Password rules
if (strlen($password) < 8) {
    redirect_error("Password must be at least 8 characters", $full_name, $email, $username, $role);
}
if (!preg_match('/[A-Z]/', $password)) {
    redirect_error("Password must contain at least one uppercase letter", $full_name, $email, $username, $role);
}
if (!preg_match('/[0-9]/', $password)) {
    redirect_error("Password must contain at least one number", $full_name, $email, $username, $role);
}
if (!preg_match('/[^A-Za-z0-9]/', $password)) {
    redirect_error("Password must contain at least one special character", $full_name, $email, $username, $role);
}

// Username or email already taken (single combined check to avoid enumeration)
$stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
$stmt->bind_param("ss", $username, $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    redirect_error("An account with this username or email already exists.", $full_name, $email, $username, $role);
}
$stmt->close();

// Insert
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$stmt3 = $conn->prepare("INSERT INTO users (full_name, username, email, password, role) VALUES (?, ?, ?, ?, ?)");
$stmt3->bind_param("sssss", $full_name, $username, $email, $hashed_password, $role);

if ($stmt3->execute()) {
    header("Location: login.php?msg=created");
    exit();
} else {
    redirect_error("Registration failed. Please try again.", $full_name, $email, $username, $role);
}
$conn->close();
?>
