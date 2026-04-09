<?php
require_once __DIR__ . '/includes/config.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = 'Please fill all fields';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address';
    } else {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO contacts (name, email, subject, message, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssss", $name, $email, $subject, $message);
        
        if ($stmt->execute()) {
            $success = 'Thank you! Your message has been sent. We will contact you soon.';
        } else {
            $error = 'Failed to send message. Please try again.';
        }
    }
}

$loggedIn = isLoggedIn();
$username = $_SESSION['username'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Contact Us - <?= SITE_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
:root {
  --black: #080808; --dark-2: #141414; --dark-3: #1a1a1a; --dark-4: #222;
  --red: #c0392b; --red-light: #e74c3c; --text: #f0f0f0; --text-sec: #a0a0a0;
  --text-muted: #555; --border: #2a2a2a; --gold: #f0b429;
  --font-display: 'Bebas Neue', sans-serif; --font-body: 'Inter', sans-serif;
}
*{margin:0;padding:0;box-sizing:border-box}
body{background:var(--black);color:var(--text);font-family:var(--font-body)}

.navbar{position:fixed;top:0;left:0;right:0;z-index:1000;height:64px;display:flex;align-items:center;padding:0 40px;background:rgba(8,8,8,0.97);backdrop-filter:blur(12px);border-bottom:1px solid rgba(255,255,255,0.04)}
.nav-logo{font-family:var(--font-display);font-size:26px;letter-spacing:3px;text-decoration:none;color:var(--text)}
.nav-logo span{color:var(--red)}
.nav-links{display:flex;gap:24px;margin-left:36px;list-style:none}
.nav-links a{font-size:13px;color:var(--text-sec);text-decoration:none;transition:color 0.2s}
.nav-links a:hover,.nav-links a.active{color:var(--text)}
.nav-search{margin-left:auto;display:flex;align-items:center;gap:12px}
.search-box{display:flex;align-items:center;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:6px;padding:6px 12px;gap:8px}
.search-box input{background:none;border:none;outline:none;color:var(--text);font-size:13px;width:200px}
.nav-auth{display:flex;align-items:center;gap:10px;margin-left:20px}
.btn{display:inline-flex;padding:7px 16px;border-radius:6px;font-size:13px;font-weight:600;text-decoration:none}
.btn-outline{background:transparent;border:1px solid #333;color:var(--text-sec)}
.btn-red{background:var(--red);color:#fff}
.user-menu{position:relative}
.user-trigger{display:flex;align-items:center;gap:8px;background:var(--dark-3);border:none;padding:6px 12px;border-radius:6px;color:var(--text);cursor:pointer}
.user-avatar{width:30px;height:30px;border-radius:50%;background:var(--red);display:flex;align-items:center;justify-content:center;font-weight:bold}
.user-dropdown{position:absolute;top:100%;right:0;background:var(--dark-3);border:1px solid var(--border);border-radius:8px;min-width:150px;display:none}
.user-menu:hover .user-dropdown{display:block}
.user-dropdown a{display:block;padding:10px 15px;font-size:13px;color:var(--text-sec);text-decoration:none}

.page-header{padding:100px 40px 40px;text-align:center;background:linear-gradient(135deg, var(--dark-3) 0%, var(--black) 100%)}
.page-header h1{font-family:var(--font-display);font-size:56px;letter-spacing:3px}
.page-header h1 span{color:var(--red)}
.page-header p{color:var(--text-muted);margin-top:10px}

.contact-section{display:grid;grid-template-columns:1fr 1fr;gap:40px;padding:40px}
.contact-info{background:var(--dark-3);border-radius:16px;padding:30px;border:1px solid var(--border)}
.contact-info h3{font-size:20px;margin-bottom:20px}
.info-item{display:flex;align-items:center;gap:15px;margin-bottom:20px;padding:12px;background:var(--dark-4);border-radius:10px}
.info-item .icon{font-size:24px}
.info-item .text strong{display:block;margin-bottom:4px}
.info-item .text p{color:var(--text-sec);font-size:13px}
.contact-form{background:var(--dark-3);border-radius:16px;padding:30px;border:1px solid var(--border)}
.form-group{margin-bottom:20px}
.form-group label{display:block;font-size:12px;font-weight:600;margin-bottom:8px;color:var(--text-sec)}
.form-group input,.form-group textarea{width:100%;padding:12px;background:var(--dark-4);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:14px}
.form-group input:focus,.form-group textarea:focus{outline:none;border-color:var(--red)}
.submit-btn{background:var(--red);color:#fff;padding:12px 28px;border:none;border-radius:8px;font-size:16px;font-weight:600;cursor:pointer;width:100%}
.submit-btn:hover{background:var(--red-light)}
.success{background:rgba(39,174,96,0.2);border:1px solid #27ae60;padding:12px;border-radius:8px;margin-bottom:20px;color:#2ecc71}
.error{background:rgba(192,57,43,0.2);border:1px solid var(--red);padding:12px;border-radius:8px;margin-bottom:20px;color:var(--red-light)}

footer{padding:48px 40px 32px;border-top:1px solid var(--border);margin-top:40px}
.footer-top{display:flex;justify-content:space-between;margin-bottom:32px;flex-wrap:wrap;gap:40px}
.footer-logo{font-family:var(--font-display);font-size:28px;color:var(--text)}
.footer-logo span{color:var(--red)}
.footer-links{display:flex;gap:48px;flex-wrap:wrap}
.footer-col h4{font-size:12px;text-transform:uppercase;color:var(--text-muted);margin-bottom:14px}
.footer-col li{margin-bottom:9px;list-style:none}
.footer-col a{font-size:13px;color:var(--text-sec);text-decoration:none}
.footer-bottom{border-top:1px solid var(--border);padding-top:20px;display:flex;justify-content:space-between;font-size:12px;color:var(--text-muted);flex-wrap:wrap;gap:10px}

@media (max-width:768px){
    .navbar{padding:0 20px}
    .nav-links{display:none}
    .page-header{padding:80px 20px 30px}
    .page-header h1{font-size:40px}
    .contact-section{padding:20px;grid-template-columns:1fr}
    footer{padding:32px 20px 24px}
    .footer-top{flex-direction:column}
}
</style>
</head>
<body>

<nav class="navbar">
  <a class="nav-logo" href="index.php">CINE<span>VAULT</span></a>
  <ul class="nav-links">
    <li><a href="index.php">Home</a></li>
    <li><a href="movies.php">Browse</a></li>
    <li><a href="collections.php">Collections</a></li>
    <li><a href="charts.php">Top Charts</a></li>
    <li><a href="contact.php" class="active">Contact</a></li>
    <li><a href="about.php">About</a></li>
  </ul>
  <div class="nav-search">
    <div class="search-box">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
      <input type="text" id="searchInput" placeholder="Search movies...">
    </div>
    <div class="nav-auth">
      <?php if ($loggedIn): ?>
      <div class="user-menu">
        <button class="user-trigger">
          <div class="user-avatar"><?= strtoupper(substr($username, 0, 1)) ?></div>
          <span><?= htmlspecialchars($username) ?></span>
        </button>
        <div class="user-dropdown">
          <a href="profile.php">My Profile</a>
          <a href="watchlist.php">My Watchlist</a>
          <a href="logout.php">Logout</a>
        </div>
      </div>
      <?php else: ?>
      <a href="login.php" class="btn btn-outline">Sign In</a>
      <a href="register.php" class="btn btn-red">Join Free</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<div class="page-header">
  <h1>CONTACT <span>US</span></h1>
  <p>Have questions? We'd love to hear from you.</p>
</div>

<div class="contact-section">
  <div class="contact-info">
    <h3>📬 Get in Touch</h3>
    <div class="info-item">
      <div class="icon">📍</div>
      <div class="text">
        <strong>Address</strong>
        <p>123 Movie Street, Cinema City, CC 12345</p>
      </div>
    </div>
    <div class="info-item">
      <div class="icon">📧</div>
      <div class="text">
        <strong>Email</strong>
        <p><?= SITE_EMAIL ?></p>
      </div>
    </div>
    <div class="info-item">
      <div class="icon">📞</div>
      <div class="text">
        <strong>Phone</strong>
        <p>+1 (555) 123-4567</p>
      </div>
    </div>
    <div class="info-item">
      <div class="icon">⏰</div>
      <div class="text">
        <strong>Working Hours</strong>
        <p>Monday - Friday: 9AM - 6PM</p>
      </div>
    </div>
  </div>
  
  <div class="contact-form">
    <h3>📝 Send us a Message</h3>
    <?php if ($success): ?>
    <div class="success"><?= $success ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="error"><?= $error ?></div>
    <?php endif; ?>
    <form method="POST">
      <div class="form-group">
        <label>Your Name</label>
        <input type="text" name="name" required>
      </div>
      <div class="form-group">
        <label>Email Address</label>
        <input type="email" name="email" required>
      </div>
      <div class="form-group">
        <label>Subject</label>
        <input type="text" name="subject" required>
      </div>
      <div class="form-group">
        <label>Message</label>
        <textarea name="message" rows="5" required></textarea>
      </div>
      <button type="submit" class="submit-btn">Send Message</button>
    </form>
  </div>
</div>
<footer>
  <div class="footer-top">
    <div class="footer-brand">
      <div class="footer-logo">CINE<span>VAULT</span></div>
      <p class="footer-tagline" style="color:var(--text-muted);margin-top:6px">Premium movie streaming, anytime anywhere.</p>
    </div>
    <div class="footer-links">
      <div class="footer-col"><h4>Navigate</h4><ul><li><a href="index.php">🏠 Home</a></li><li><a href="movies.php">🎬 Browse Movies</a></li><li><a href="collections.php">📁 Collections</a></li><li><a href="coming-soon.php">⏰ Coming Soon</a></li></ul></div>
      <div class="footer-col"><h4>Top Charts</h4><ul><li><a href="charts.php">📊 Most Viewed</a></li><li><a href="charts.php">⭐ Top Rated</a></li><li><a href="charts.php">🔥 Weekly Trending</a></li></ul></div>
      <div class="footer-col"><h4>Support</h4><ul><li><a href="contact.php">📧 Contact Us</a></li><li><a href="about.php">ℹ️ About Us</a></li><li><a href="sitemap.php">🗺️ Sitemap</a></li></ul></div>
      <div class="footer-col"><h4>Account</h4><ul><?php if($loggedIn): ?><li><a href="profile.php">👤 My Profile</a></li><li><a href="watchlist.php">📋 My Watchlist</a></li><li><a href="logout.php">🚪 Logout</a></li><?php else: ?><li><a href="login.php">🔐 Sign In</a></li><li><a href="register.php">📝 Register</a></li><?php endif; ?></ul></div>
    </div>
  </div>
  <div class="footer-bottom"><span>&copy; <?= date('Y') ?> CineVault. All rights reserved.</span><span>Built for entertainment purposes only.</span></div>
</footer>

<script>
document.getElementById('searchInput')?.addEventListener('keypress', function(e) {
    if (e.key === 'Enter' && this.value.trim()) {
        window.location.href = 'movies.php?q=' + encodeURIComponent(this.value.trim());
    }
});
</script>
</body>
</html>