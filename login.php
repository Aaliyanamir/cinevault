<?php
require_once __DIR__ . '/includes/config.php';

// Agar already logged in hai toh home pe bhejo
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $db = getDB();
    $stmt = $db->prepare("SELECT id, username, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        header('Location: index.php');
        exit;
    } else {
        $error = 'Invalid email or password!';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Login - CineVault</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{background:#080808;font-family:'Segoe UI',sans-serif;color:#f0f0f0;min-height:100vh;display:flex;align-items:center;justify-content:center}
.login-box{background:#141414;border:1px solid #2a2a2a;border-radius:16px;padding:40px;width:400px}
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
.register-link{text-align:center;margin-top:20px;font-size:13px}
.register-link a{color:#c0392b;text-decoration:none}
</style>
</head>
<body>
<div class="login-box">
    <h1>CINE<span>VAULT</span></h1>
    <p class="sub">Sign in to your account</p>
    
    <?php if($error): ?>
    <div class="error"><?= $error ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="input-group">
            <label>Email</label>
            <input type="email" name="email" required>
        </div>
        <div class="input-group">
            <label>Password</label>
            <input type="password" name="password" required>
        </div>
        <button type="submit">Sign In</button>
    </form>
    
    <div class="register-link">
        Don't have an account? <a href="register.php">Create one</a>
    </div>
</div>
</body>
</html>