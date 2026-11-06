<?php
session_start();
include("connection.php");

$step = (int)($_GET['step'] ?? 1);
$msg  = $_GET['msg'] ?? '';
$error = '';

// Step 2 — verify username + email, store in session
if($_SERVER['REQUEST_METHOD'] == 'POST' && $step == 2) {
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);

    $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? AND email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        $_SESSION['reset_user_id'] = $row['user_id'];
        header("Location: forgot_password.php?step=3");
        exit();
    } else {
        header("Location: forgot_password.php?step=1&error=No+account+found+with+that+username+and+email+combination");
        exit();
    }
}

// Step 3 — set new password
if($_SERVER['REQUEST_METHOD'] == 'POST' && $step == 3) {
    if(empty($_SESSION['reset_user_id'])) {
        header("Location: forgot_password.php?step=1"); exit();
    }
    $new_pw  = $_POST['new_password'];
    $user_id = (int)$_SESSION['reset_user_id'];

    if(strlen($new_pw) < 8 || !preg_match('/[A-Z]/', $new_pw) || !preg_match('/[0-9]/', $new_pw) || !preg_match('/[^A-Za-z0-9]/', $new_pw)) {
        header("Location: forgot_password.php?step=3&error=Password+does+not+meet+requirements");
        exit();
    }

    $hashed = password_hash($new_pw, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
    $stmt->bind_param("si", $hashed, $user_id);
    if($stmt->execute()) {
        unset($_SESSION['reset_user_id']);
        header("Location: login.php?msg=reset_done");
    } else {
        header("Location: forgot_password.php?step=3&error=Failed+to+update+password");
    }
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Password — Infinity Sportswear Hub</title>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    background: #111214;
    font-family: 'Outfit', sans-serif;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
}
.card {
    background: #fff;
    width: 420px;
    max-width: 95%;
    border-radius: 12px;
    padding: 36px 32px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.5);
}
.card h2 { font-size: 20px; font-weight: 800; color: #111214; margin-bottom: 4px; }
.card p  { font-size: 13px; color: #6b7280; margin-bottom: 24px; line-height: 1.5; }
.steps { display: flex; gap: 6px; margin-bottom: 24px; }
.step-dot {
    flex: 1; height: 4px; border-radius: 2px; background: #e9ecef;
}
.step-dot.active { background: #f0b429; }
.step-dot.done   { background: #2f9e44; }
label {
    display: block; font-size: 11px; font-weight: 700;
    color: #6b7280; text-transform: uppercase;
    letter-spacing: 0.5px; margin-bottom: 5px; margin-top: 14px;
}
input {
    width: 100%; padding: 10px 12px;
    border: 1.5px solid #e9ecef; border-radius: 7px;
    font-size: 13px; font-family: 'Outfit', sans-serif;
    color: #111214; transition: border 0.15s;
}
input:focus { outline: none; border-color: #f0b429; box-shadow: 0 0 0 3px rgba(240,180,41,0.12); }
.btn {
    width: 100%; padding: 11px; margin-top: 20px;
    background: #f0b429; color: #111214;
    border: none; border-radius: 7px;
    font-size: 14px; font-weight: 700;
    cursor: pointer; font-family: 'Outfit', sans-serif;
}
.btn:hover { background: #f5c842; }
.error-msg {
    padding: 10px 12px; background: #fff5f5;
    border: 1px solid #ffc9c9; border-radius: 6px;
    color: #a50000; font-size: 13px; margin-bottom: 16px;
}
.back-link {
    display: block; text-align: center; margin-top: 16px;
    font-size: 12px; color: #6b7280; text-decoration: none;
}
.back-link:hover { color: #f0b429; }
.pw-rules { margin-top: 6px; padding: 8px 10px; background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 6px; font-size: 11px; color: #6b7280; }
.pw-rules ul { padding-left: 16px; }
.pw-rules li { margin-top: 3px; }
.pw-rules li.ok   { color: #2f9e44; }
.pw-rules li.fail { color: #e03131; }
</style>
</head>
<body>
<div class="card">

    <!-- Step indicators -->
    <div class="steps">
        <div class="step-dot <?php echo $step>=1?'active':''; ?> <?php echo $step>1?'done':''; ?>"></div>
        <div class="step-dot <?php echo $step>=2?'active':''; ?> <?php echo $step>2?'done':''; ?>"></div>
        <div class="step-dot <?php echo $step>=3?'active':''; ?>"></div>
    </div>

    <?php if(!empty($_GET['error'])): ?>
    <div class="error-msg"><?php echo htmlspecialchars($_GET['error']); ?></div>
    <?php endif; ?>

    <?php if($step == 1 || $step == 2): ?>
    <!-- STEP 1 — Enter username + email -->
    <h2>Reset Your Password</h2>
    <p>Enter your username and the email address linked to your account. We will verify your identity before letting you set a new password.</p>
    <form method="POST" action="forgot_password.php?step=2">
        <label>Username</label>
        <input type="text" name="username" required placeholder="Your username">
        <label>Email Address</label>
        <input type="email" name="email" required placeholder="Your registered email">
        <button type="submit" class="btn">Verify Identity</button>
    </form>

    <?php elseif($step == 3): ?>
    <!-- STEP 2 — Set new password -->
    <h2>Set New Password</h2>
    <p>Identity verified. Enter your new password below.</p>
    <form method="POST" action="forgot_password.php?step=3">
        <label>New Password</label>
        <div style="position:relative">
            <input type="password" name="new_password" id="new-pw" required placeholder="Enter new password" oninput="checkRules(this.value)">
            <span onclick="togglePw()" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);cursor:pointer;font-size:11px;color:#9ca3af;font-weight:600">Show</span>
        </div>
        <div class="pw-rules">
            <ul>
                <li id="r-len">At least 8 characters</li>
                <li id="r-upper">At least one uppercase letter</li>
                <li id="r-num">At least one number</li>
                <li id="r-special">At least one special character</li>
            </ul>
        </div>
        <button type="submit" class="btn">Save New Password</button>
    </form>
    <?php endif; ?>

    <a href="login.php" class="back-link">← Back to Login</a>
</div>

<script>
function checkRules(pw) {
    document.getElementById('r-len').className   = pw.length >= 8 ? 'ok' : 'fail';
    document.getElementById('r-upper').className = /[A-Z]/.test(pw) ? 'ok' : 'fail';
    document.getElementById('r-num').className   = /[0-9]/.test(pw) ? 'ok' : 'fail';
    document.getElementById('r-special').className = /[^A-Za-z0-9]/.test(pw) ? 'ok' : 'fail';
}
function togglePw() {
    const inp = document.getElementById('new-pw');
    inp.type = inp.type === 'password' ? 'text' : 'password';
}
</script>
</body>
</html>
