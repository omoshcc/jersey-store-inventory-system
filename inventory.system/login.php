<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Infinity Sportswear Hub — Login</title>

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
.login-wrapper {
    display: flex;
    width: 900px;
    max-width: 100%;
    min-height: 560px;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(0,0,0,0.5);
}
.login-left {
    flex: 1;
    background: linear-gradient(160deg, #111214 0%, #1a1c21 100%);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px;
    border-right: 1px solid #23262d;
    position: relative;
    overflow: hidden;
}
.login-left::before {
    content: '';
    position: absolute;
    width: 400px; height: 400px;
    background: radial-gradient(circle, rgba(240,180,41,0.07) 0%, transparent 70%);
    top: 50%; left: 50%;
    transform: translate(-50%,-50%);
    pointer-events: none;
}
.login-left img {
    width: 100px;
    border-radius: 16px;
    border: 2px solid rgba(240,180,41,0.3);
    margin-bottom: 24px;
}
.login-left h1 {
    font-size: 28px;
    font-weight: 800;
    color: #ffffff;
    text-align: center;
    margin-bottom: 8px;
    letter-spacing: -0.5px;
}
.login-left p {
    font-size: 14px;
    color: #6b7280;
    text-align: center;
    line-height: 1.6;
    max-width: 280px;
}
.gold-line {
    width: 48px; height: 3px;
    background: #f0b429;
    border-radius: 2px;
    margin: 16px auto;
}
.feature-list {
    margin-top: 32px;
    display: flex;
    flex-direction: column;
    gap: 12px;
    width: 100%;
    max-width: 260px;
}
.feature-item {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 13px;
    color: #9ca3af;
}
.feature-dot {
    width: 28px; height: 28px;
    background: rgba(240,180,41,0.12);
    border: 1px solid rgba(240,180,41,0.25);
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    flex-shrink: 0;
}

.login-right {
    width: 440px;
    min-width: 340px;
    background: #ffffff;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 48px 40px;
}
.login-right > * { width: 100%; }
.login-right h2 {
    font-size: 22px;
    font-weight: 800;
    color: #111214;
    margin-bottom: 4px;
    letter-spacing: -0.3px;
    text-align: center;
}
.login-right .sub {
    font-size: 13px;
    color: #6b7280;
    margin-bottom: 28px;
    text-align: center;
}
.login-left {
    flex: 1;
    background: linear-gradient(160deg, #111214 0%, #1a1c21 100%);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px;
    border-right: 1px solid #23262d;
    position: relative;
    overflow: hidden;
    text-align: center;
}

.tabs {
    display: flex;
    width: 100%;
    border: 1.5px solid #e9ecef;
    border-radius: 8px;
    overflow: hidden;
    margin-bottom: 24px;
}
.tab-btn {
    flex: 1;
    padding: 10px;
    background: #f8f9fa;
    border: none;
    cursor: pointer;
    font-size: 13px;
    font-weight: 600;
    color: #6b7280;
    font-family: 'Outfit', sans-serif;
    transition: all 0.15s;
}
.tab-btn.active {
    background: #111214;
    color: #f0b429;
}
.form-section { display: none; width: 100%; }
.form-section.active { display: block; }

label {
    display: block;
    font-size: 11px;
    font-weight: 700;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-top: 14px;
    margin-bottom: 5px;
}
input, select {
    width: 100%;
    padding: 10px 12px;
    border: 1.5px solid #e9ecef;
    border-radius: 7px;
    font-size: 13px;
    color: #111214;
    font-family: 'Outfit', sans-serif;
    transition: border 0.15s, box-shadow 0.15s;
}
input:focus, select:focus {
    outline: none;
    border-color: #f0b429;
    box-shadow: 0 0 0 3px rgba(240,180,41,0.12);
}
.password-wrapper { position: relative; }
.toggle-pw {
    position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
    cursor: pointer; font-size: 11px; color: #9ca3af; user-select: none;
    font-weight: 600;
}
.pw-rules {
    margin-top: 6px; padding: 8px 10px;
    background: #f8f9fa; border: 1px solid #e9ecef;
    border-radius: 6px; font-size: 11px; color: #6b7280;
}
.pw-rules ul { padding-left: 16px; }
.pw-rules li { margin-top: 3px; }
.pw-rules li.ok   { color: #2f9e44; }
.pw-rules li.fail { color: #e03131; }
.error-msg {
    margin-top: 12px; padding: 10px 12px;
    background: #fff5f5; border: 1px solid #ffc9c9;
    border-radius: 6px; color: #a50000; font-size: 13px;
}
.success-msg {
    margin-top: 12px; padding: 10px 12px;
    background: #ebfbee; border: 1px solid #b2f2bb;
    border-radius: 6px; color: #1a6b2e; font-size: 13px;
}
.submit-btn {
    width: 100%; padding: 11px; margin-top: 20px;
    background: #f0b429;
    color: #111214;
    border: none; border-radius: 7px;
    font-size: 14px; font-weight: 700;
    cursor: pointer; font-family: 'Outfit', sans-serif;
    transition: all 0.15s;
    letter-spacing: 0.2px;
}
.submit-btn:hover {
    background: #f5c842;
    box-shadow: 0 3px 10px rgba(240,180,41,0.4);
}
.forgot-link {
    display: block; text-align: right; margin-top: 8px;
    font-size: 12px; color: #6b7280; text-decoration: none; font-weight: 500;
}
.forgot-link:hover { color: #f0b429; }

@media (max-width: 700px) {
    .login-left { display: none; }
    .login-right { width: 100%; padding: 32px 24px; }
}
</style>
</head>
<body>

<!-- WRAPPER -->
<div class="login-wrapper">

<!-- LEFT PANEL -->
<div class="login-left">
    <img src="logo.jpeg" alt="Infinity Sportshub">
    <h1>Infinity Sportswear Hub</h1>
    <div class="gold-line"></div>
    <p>Inventory Management System</p>
</div>

<!-- RIGHT PANEL -->
<div class="login-right">
    <h2>Infinity Sportswear Hub</h2>
    <div class="sub">Sign in or create your account</div>

    <div class="tabs">
        <button class="tab-btn active" id="btn-login" onclick="switchTab('login')">Login</button>
        <button class="tab-btn" id="btn-signup" onclick="switchTab('signup')">Sign Up</button>
    </div>

    <!-- LOGIN FORM -->
    <div class="form-section active" id="tab-login">
        <?php if (!empty($_GET['msg'])): ?>
            <?php if ($_GET['msg'] === 'created'): ?>
                <div class="success-msg">Account created. You can now log in.</div>
            <?php elseif ($_GET['msg'] === 'wrong_password'): ?>
                <div class="error-msg">Incorrect password. Please try again.</div>
            <?php elseif ($_GET['msg'] === 'not_found'): ?>
                <div class="error-msg">No account found with that username.</div>
            <?php elseif ($_GET['msg'] === 'reset_done'): ?>
                <div class="success-msg">Password reset successfully. You can now log in.</div>
            <?php endif; ?>
        <?php endif; ?>

        <form action="login_process.php" method="POST">
            <label>Username</label>
            <input type="text" name="username" required placeholder="Enter your username">

            <label>Password</label>
            <div class="password-wrapper">
                <input type="password" name="password" id="login-pw" required placeholder="Enter your password">
                <span class="toggle-pw" onclick="togglePw('login-pw', this)">Show</span>
            </div>

            <a href="forgot_password.php" class="forgot-link">Forgot your password?</a>

            <button type="submit" class="submit-btn">Login</button>
        </form>
    </div>

    <!-- SIGNUP FORM -->
    <div class="form-section" id="tab-signup">
        <?php if (!empty($_GET['signup_error'])): ?>
            <div class="error-msg"><?php echo htmlspecialchars($_GET['signup_error']); ?></div>
        <?php endif; ?>

        <?php
        $r_fullname  = htmlspecialchars($_GET['r_fullname']  ?? '');
        $r_email     = htmlspecialchars($_GET['r_email']     ?? '');
        $r_username  = htmlspecialchars($_GET['r_username']  ?? '');
        $r_role      = $_GET['r_role'] ?? 'Manager';
        ?>

        <form action="signup_process.php" method="POST" onsubmit="return validateSignup()">
            <label>Full Name</label>
            <input type="text" name="full_name" required placeholder="Enter your full name"
                value="<?php echo $r_fullname; ?>"
                pattern="[a-zA-Z\s]+" title="Full name must contain letters only — no numbers or symbols"
                oninput="this.value=this.value.replace(/[^a-zA-Z\s]/g,'')">

            <label>Email</label>
            <input type="email" name="email" id="signup-email" required placeholder="example@domain.com"
                value="<?php echo $r_email; ?>">

            <label>Username</label>
            <input type="text" name="username" required placeholder="Choose a username"
                value="<?php echo $r_username; ?>">

            <label>Password</label>
            <div class="password-wrapper">
                <input type="password" name="password" id="signup-pw" required placeholder="Create a password" oninput="checkPwRules(this.value)">
                <span class="toggle-pw" onclick="togglePw('signup-pw', this)">Show</span>
            </div>
            <div class="pw-rules">
                <ul>
                    <li id="rule-len">At least 8 characters</li>
                    <li id="rule-upper">At least one uppercase letter</li>
                    <li id="rule-num">At least one number</li>
                    <li id="rule-special">At least one special character (!@#$%^&* etc.)</li>
                </ul>
            </div>

            <label>Role</label>
            <select name="role">
                <option value="Manager"       <?php echo $r_role=='Manager'?'selected':''; ?>>Manager</option>
                <option value="Storekeeper"   <?php echo $r_role=='Storekeeper'?'selected':''; ?>>Storekeeper</option>
                <option value="Sales Assistant" <?php echo $r_role=='Sales Assistant'?'selected':''; ?>>Sales Assistant</option>
            </select>

            <div id="signup-error" class="error-msg" style="display:none;"></div>

            <button type="submit" class="submit-btn">Create Account</button>
        </form>
    </div>
</div><!-- end .login-right -->
</div><!-- end .login-wrapper -->

<script>
function switchTab(tab) {
    document.getElementById('tab-login').classList.remove('active');
    document.getElementById('tab-signup').classList.remove('active');
    document.getElementById('btn-login').classList.remove('active');
    document.getElementById('btn-signup').classList.remove('active');
    document.getElementById('tab-' + tab).classList.add('active');
    document.getElementById('btn-' + tab).classList.add('active');
}

function togglePw(id, btn) {
    const input = document.getElementById(id);
    input.type = input.type === 'password' ? 'text' : 'password';
    btn.textContent = input.type === 'password' ? 'Show' : 'Hide';
}

function checkPwRules(val) {
    setRule('rule-len',     val.length >= 8);
    setRule('rule-upper',   /[A-Z]/.test(val));
    setRule('rule-num',     /[0-9]/.test(val));
    setRule('rule-special', /[^A-Za-z0-9]/.test(val));
}

function setRule(id, pass) {
    document.getElementById(id).className = pass ? 'ok' : 'fail';
}

function validateSignup() {
    const email = document.getElementById('signup-email').value;
    const pw    = document.getElementById('signup-pw').value;
    const errBox = document.getElementById('signup-error');

    if (!email.includes('@')) {
        errBox.textContent = 'Email must contain @.';
        errBox.style.display = 'block';
        return false;
    }
    if (pw.length < 8 || !/[A-Z]/.test(pw) || !/[0-9]/.test(pw) || !/[^A-Za-z0-9]/.test(pw)) {
        errBox.textContent = 'Password does not meet all requirements listed above.';
        errBox.style.display = 'block';
        return false;
    }
    errBox.style.display = 'none';
    return true;
}

const params = new URLSearchParams(window.location.search);
if (params.get('tab') === 'signup') switchTab('signup');
</script>
</body>
</html>
