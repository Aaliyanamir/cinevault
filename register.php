<?php
require_once __DIR__ . '/includes/config.php';

// Agar already logged in hai toh home pe bhejo
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $db = getDB();
    
    // Check if user exists
    $check = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $check->bind_param("ss", $username, $email);
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows > 0) {
        $error = 'Username or email already exists!';
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $email, $hashed);
        
        if ($stmt->execute()) {
            $_SESSION['user_id'] = $stmt->insert_id;
            $_SESSION['username'] = $username;
            $success = 'Account created successfully!';
            header('refresh:2;url=index.php');
        } else {
            $error = 'Registration failed!';
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Register - CineVault</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{background:#080808;font-family:'Segoe UI',sans-serif;color:#f0f0f0;min-height:100vh;display:flex;align-items:center;justify-content:center}
.register-box{background:#141414;border:1px solid #2a2a2a;border-radius:16px;padding:40px;width:400px}
h1{font-size:28px;text-align:center;margin-bottom:5px}
h1 span{color:#c0392b}
.sub{text-align:center;color:#777;margin-bottom:30px;font-size:13px}
.input-group{margin-bottom:20px}
label{display:block;font-size:11px;font-weight:700;text-transform:uppercase;color:#777;margin-bottom:6px}
input{width:100%;padding:12px;background:#1a1a1a;border:1px solid #2a2a2a;border-radius:8px;color:#fff;font-size:14px}
input:focus{outline:none;border-color:#c0392b}
button{width:100%;padding:12px;background:#c0392b;border:none;border-radius:8px;color:#fff;font-size:16px;font-weight:700;cursor:pointer;margin-top:10px}
button:hover{background:#e74c3c}
.error{background:rgba(192,57,43,0.2);border:1px solid #c0392b;padding:12px;border-radius:8px;margin-bottom:20px;color:#e74c3c;font-size:13px}
.success{background:rgba(39,174,96,0.2);border:1px solid #27ae60;padding:12px;border-radius:8px;margin-bottom:20px;color:#2ecc71;font-size:13px}
.login-link{text-align:center;margin-top:20px;font-size:13px}
.login-link a{color:#c0392b;text-decoration:none}
</style>
</head>
<body>
<div class="register-box">
    <h1>CINE<span>VAULT</span></h1>
    <p class="sub">Create your free account</p>
    
    <?php if($error): ?>
    <div class="error"><?= $error ?></div>
    <?php endif; ?>
    
    <?php if($success): ?>
    <div class="success"><?= $success ?> Redirecting...</div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="input-group">
            <label>Username</label>
            <input type="text" name="username" required>
        </div>
        <div class="input-group">
            <label>Email</label>
            <input type="email" name="email" required>
        </div>
        <div class="input-group">
            <label>Password (min 6 characters)</label>
            <input type="password" name="password" minlength="6" required>
        </div>
        <button type="submit">Create Account</button>
    </form>
    
    <div class="login-link">
        Already have an account? <a href="login.php">Sign In</a>
    </div>
</div>
</body>
</html>