<?php
require_once __DIR__ . '/includes/config.php';

$db = getDB();

// Get all movies for sitemap
$movies = $db->query("SELECT id, slug, title, created_at FROM movies ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);

// Get all genres
$genres = $db->query("SELECT slug, name FROM genres ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// Get all collections
$collections = $db->query("SELECT slug, name FROM collections ORDER BY name")->fetch_all(MYSQLI_ASSOC);

$loggedIn = isLoggedIn();
$username = $_SESSION['username'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sitemap - <?= SITE_NAME ?></title>
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
.nav-logo{font-family:var(--font-display);font-size:26px;letter-spacing:3px;  text-decoration:none; color:#f0f0f0; }
.nav-logo span{color:var(--red)}
.nav-links{display:flex;gap:24px;margin-left:36px;list-style:none}
.nav-links a{font-size:13px;color:var(--text-sec);text-decoration:none}
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

.sitemap-section{padding:40px;max-width:1200px;margin:0 auto}
.sitemap-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:30px}
.sitemap-col{background:var(--dark-3);border-radius:12px;padding:20px;border:1px solid var(--border)}
.sitemap-col h3{font-family:var(--font-display);font-size:20px;margin-bottom:15px;padding-bottom:10px;border-bottom:1px solid var(--border)}
.sitemap-col ul{list-style:none}
.sitemap-col li{margin-bottom:10px}
.sitemap-col a{color:var(--text-sec);text-decoration:none;font-size:13px;transition:color 0.2s}
.sitemap-col a:hover{color:var(--red)}
.movie-list{max-height:300px;overflow-y:auto}
.movie-list li{padding:5px 0}
.count-badge{background:var(--dark-4);padding:2px 8px;border-radius:20px;font-size:10px;margin-left:8px}

/* Footer Styles */
footer {
  padding: 48px 40px 32px;
  border-top: 1px solid var(--border);
  margin-top: 60px;
  background: var(--dark-2);
}

.footer-top {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 32px;
  flex-wrap: wrap;
  gap: 40px;
}

.footer-brand {
  flex-shrink: 0;
}

.footer-logo {
  font-family: var(--font-display);
  font-size: 28px;
  letter-spacing: 3px;
  color: var(--text);
}

.footer-logo span {
  color: var(--red);
}

.footer-tagline {
  color: var(--text-muted);
  margin-top: 6px;
  font-size: 13px;
}

.footer-links {
  display: flex;
  gap: 48px;
  flex-wrap: wrap;
}

.footer-col h4 {
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 1.5px;
  text-transform: uppercase;
  color: var(--text-muted);
  margin-bottom: 14px;
}

.footer-col ul {
  list-style: none;
}

.footer-col li {
  margin-bottom: 9px;
}

.footer-col a {
  font-size: 13px;
  color: var(--text-sec);
  text-decoration: none;
  transition: color 0.2s;
}

.footer-col a:hover {
  color: var(--red-light);
}

.footer-bottom {
  border-top: 1px solid var(--border);
  padding-top: 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 12px;
  color: var(--text-muted);
  flex-wrap: wrap;
  gap: 10px;
}

/* Responsive */
@media (max-width: 768px) {
  footer {
    padding: 32px 20px 24px;
  }
  
  .footer-top {
    flex-direction: column;
  }
  
  .footer-links {
    gap: 24px;
  }
  
  .footer-bottom {
    flex-direction: column;
    text-align: center;
  }
}

@media (max-width:768px){
    .navbar{padding:0 20px}
    .nav-links{display:none}
    .page-header{padding:80px 20px 30px}
    .page-header h1{font-size:40px}
    .sitemap-section{padding:20px}
    .sitemap-grid{grid-template-columns:1fr;gap:15px}
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
    <li><a href="coming-soon.php">Coming Soon</a></li>
    <li><a href="contact.php">Contact</a></li>
    <li><a href="about.php">About</a></li>
    <li><a href="sitemap.php" class="active">Sitemap</a></li>
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
  <h1>SITE<span>MAP</span></h1>
  <p>Complete navigation guide for <?= SITE_NAME ?></p>
</div>

<div class="sitemap-section">
  <div class="sitemap-grid">
    <!-- Main Pages -->
    <div class="sitemap-col">
      <h3>📄 Main Pages</h3>
      <ul>
        <li><a href="index.php">🏠 Home</a></li>
        <li><a href="movies.php">🎬 Browse Movies</a></li>
        <li><a href="collections.php">📁 Collections</a></li>
        <li><a href="charts.php">📊 Top Charts</a></li>
        <li><a href="coming-soon.php">⏰ Coming Soon</a></li>
        <li><a href="contact.php">📧 Contact Us</a></li>
        <li><a href="about.php">ℹ️ About Us</a></li>
        <li><a href="sitemap.php">🗺️ Sitemap</a></li>
      </ul>
    </div>

    <!-- Account Pages -->
    <div class="sitemap-col">
      <h3>👤 Account</h3>
      <ul>
        <li><a href="login.php">🔐 Sign In</a></li>
        <li><a href="register.php">📝 Register</a></li>
        <li><a href="profile.php">👤 My Profile</a></li>
        <li><a href="watchlist.php">📋 My Watchlist</a></li>
      </ul>
    </div>

    <!-- Genres -->
    <div class="sitemap-col">
      <h3>🎭 Genres</h3>
      <ul>
        <?php foreach (array_slice($genres, 0, 10) as $genre): ?>
        <li><a href="movies.php?genre=<?= $genre['slug'] ?>"><?= htmlspecialchars($genre['name']) ?></a></li>
        <?php endforeach; ?>
        <?php if (count($genres) > 10): ?>
        <li><a href="movies.php">View All Genres →</a></li>
        <?php endif; ?>
      </ul>
    </div>

    <!-- Collections -->
    <div class="sitemap-col">
      <h3>📁 Collections</h3>
      <ul>
        <?php foreach ($collections as $collection): ?>
        <li><a href="collections.php?slug=<?= $collection['slug'] ?>"><?= htmlspecialchars($collection['name']) ?></a></li>
        <?php endforeach; ?>
        <?php if (empty($collections)): ?>
        <li>No collections yet</li>
        <?php endif; ?>
      </ul>
    </div>
  </div>

  <!-- All Movies Section -->
  <div class="sitemap-col" style="margin-top:30px; width:100%">
    <h3>🎬 All Movies (<?= count($movies) ?>)</h3>
    <div class="movie-list" style="display:grid; grid-template-columns:repeat(auto-fill,minmax(200px,1fr)); gap:5px;">
      <?php foreach ($movies as $movie): ?>
      <li><a href="movie-detail.php?slug=<?= $movie['slug'] ?>"><?= htmlspecialchars($movie['title']) ?></a> <span class="count-badge"><?= date('Y', strtotime($movie['created_at'])) ?></span></li>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<footer>
  <div class="footer-top">
    <div class="footer-brand">
      <div class="footer-logo">CINE<span>VAULT</span></div>
      <p class="footer-tagline">Premium movie streaming, anytime anywhere.</p>
    </div>
    <div class="footer-links">
      <div class="footer-col">
        <h4>Navigate</h4>
        <ul>
          <li><a href="index.php">🏠 Home</a></li>
          <li><a href="movies.php">🎬 Browse Movies</a></li>
          <li><a href="collections.php">📁 Collections</a></li>
          <li><a href="coming-soon.php">⏰ Coming Soon</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h4>Top Charts</h4>
        <ul>
          <li><a href="charts.php">📊 Most Viewed</a></li>
          <li><a href="charts.php">⭐ Top Rated</a></li>
          <li><a href="charts.php">🔥 Weekly Trending</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h4>Support</h4>
        <ul>
          <li><a href="contact.php">📧 Contact Us</a></li>
          <li><a href="about.php">ℹ️ About Us</a></li>
          <li><a href="sitemap.php">🗺️ Sitemap</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h4>Account</h4>
        <ul>
          <li><a href="profile.php">👤 My Profile</a></li>
          <li><a href="watchlist.php">📋 My Watchlist</a></li>
          <?php if (isset($_SESSION['user_id'])): ?>
          <li><a href="logout.php">🚪 Logout</a></li>
          <?php else: ?>
          <li><a href="login.php">🔐 Sign In</a></li>
          <li><a href="register.php">📝 Register</a></li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </div>
  <div class="footer-bottom">
    <span>&copy; <?= date('Y') ?> CineVault. All rights reserved.</span>
    <span>Built for entertainment purposes only.</span>
  </div>
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