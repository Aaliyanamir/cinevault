<?php
require_once __DIR__ . '/includes/config.php';

$db = getDB();

// Top 10 Most Viewed
$mostViewed = $db->query("
    SELECT id, title, slug, poster, year, rating, views, quality 
    FROM movies 
    ORDER BY views DESC 
    LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

// Top 10 Highest Rated (IMDB)
$topRated = $db->query("
    SELECT id, title, slug, poster, year, rating, imdb_rating, quality 
    FROM movies 
    WHERE rating > 0 
    ORDER BY rating DESC, views DESC 
    LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

// Top 10 This Week (based on views in last 7 days)
$weeklyTop = $db->query("
    SELECT m.id, m.title, m.slug, m.poster, m.year, m.rating, m.quality,
           COALESCE(SUM(wh.watch_count), 0) as weekly_views
    FROM movies m
    LEFT JOIN watch_history wh ON m.id = wh.movie_id AND wh.last_watched > DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY m.id
    ORDER BY weekly_views DESC, m.views DESC
    LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

// Latest Releases (Last 30 days or newest)
$latestReleases = $db->query("
    SELECT id, title, slug, poster, year, rating, quality, created_at 
    FROM movies 
    ORDER BY created_at DESC, year DESC 
    LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

// Genre Statistics
$genreStats = $db->query("
    SELECT g.name, g.slug, COUNT(mg.movie_id) as movie_count
    FROM genres g
    LEFT JOIN movie_genres mg ON g.id = mg.genre_id
    GROUP BY g.id
    ORDER BY movie_count DESC
    LIMIT 8
")->fetch_all(MYSQLI_ASSOC);

$loggedIn = isLoggedIn();
$username = $_SESSION['username'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Top Charts - <?= SITE_NAME ?></title>
<style>
:root {
  --black: #080808; --dark-2: #141414; --dark-3: #1a1a1a; --dark-4: #222;
  --red: #c0392b; --red-light: #e74c3c; --red-dim: #7d1f1f;
  --text: #f0f0f0; --text-sec: #a0a0a0; --text-muted: #555;
  --border: #2a2a2a; --gold: #f0b429;
  --font-display: 'Bebas Neue', sans-serif; --font-body: 'Inter', sans-serif;
}
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:var(--font-body);background:var(--black);color:var(--text);line-height:1.6}
a{text-decoration:none;color:inherit}

/* Navbar */
.navbar{position:fixed;top:0;left:0;right:0;z-index:1000;height:64px;display:flex;align-items:center;padding:0 40px;background:rgba(8,8,8,0.97);backdrop-filter:blur(12px);border-bottom:1px solid rgba(255,255,255,0.04)}
.nav-logo{font-family:var(--font-display);font-size:26px;letter-spacing:3px}
.nav-logo span{color:var(--red)}
.nav-links{display:flex;gap:24px;margin-left:36px;list-style:none}
.nav-links a{font-size:13px;color:var(--text-sec);transition:color 0.2s}
.nav-links a:hover,.nav-links a.active{color:var(--text)}
.nav-right{margin-left:auto;display:flex;align-items:center;gap:12px}
.btn-outline{padding:7px 16px;background:transparent;border:1px solid var(--border);color:var(--text-sec);border-radius:6px;cursor:pointer}
.user-menu{position:relative}
.user-trigger{display:flex;align-items:center;gap:8px;background:var(--dark-3);border:none;padding:6px 12px;border-radius:6px;color:var(--text);cursor:pointer}
.user-avatar{width:30px;height:30px;border-radius:50%;background:var(--red);display:flex;align-items:center;justify-content:center;font-weight:bold}
.user-dropdown{position:absolute;top:100%;right:0;background:var(--dark-3);border:1px solid var(--border);border-radius:8px;min-width:150px;display:none}
.user-menu:hover .user-dropdown{display:block}
.user-dropdown a{display:block;padding:10px 15px;font-size:13px;color:var(--text-sec)}
.user-dropdown a:hover{background:var(--dark-4)}

/* Page Header */
.page-header{padding:100px 40px 40px;text-align:center;background:linear-gradient(135deg, var(--dark-3) 0%, var(--black) 100%)}
.page-header h1{font-family:var(--font-display);font-size:64px;letter-spacing:4px}
.page-header h1 span{color:var(--red)}
.page-header p{color:var(--text-muted);margin-top:10px}

/* Charts Layout */
.charts-container{display:grid;grid-template-columns:repeat(2,1fr);gap:30px;padding:40px}
.chart-card{background:var(--dark-3);border-radius:16px;border:1px solid var(--border);overflow:hidden}
.chart-header{background:linear-gradient(135deg, var(--red-dim), var(--red));padding:18px 24px}
.chart-header h2{font-family:var(--font-display);font-size:24px;letter-spacing:1px}
.chart-header p{font-size:12px;opacity:0.8;margin-top:4px}
.chart-list{padding:0}
.chart-item{display:flex;align-items:center;gap:15px;padding:12px 20px;border-bottom:1px solid var(--border);transition:background 0.2s;cursor:pointer}
.chart-item:hover{background:var(--dark-4)}
.chart-rank{width:40px;font-size:28px;font-weight:800;font-family:var(--font-display)}
.chart-rank.top1{color:var(--gold)}
.chart-rank.top2{color:#c0c0c0}
.chart-rank.top3{color:#cd7f32}
.chart-poster{width:45px;height:67px;border-radius:6px;object-fit:cover;background:var(--dark-4)}
.chart-info{flex:1}
.chart-info h3{font-size:15px;margin-bottom:3px}
.chart-info .meta{font-size:11px;color:var(--text-muted);display:flex;gap:10px}
.chart-rating{color:var(--gold);font-size:13px;font-weight:600}
.chart-views{font-size:12px;color:var(--text-sec);min-width:70px;text-align:right}

/* Genre Stats */
.genre-stats{background:var(--dark-3);border-radius:16px;border:1px solid var(--border);padding:20px;margin:0 40px 40px}
.genre-stats h3{font-family:var(--font-display);font-size:20px;margin-bottom:15px}
.genre-grid{display:flex;flex-wrap:wrap;gap:10px}
.genre-pill{padding:8px 18px;background:var(--dark-4);border-radius:30px;font-size:13px;color:var(--text-sec);cursor:pointer;transition:all 0.2s}
.genre-pill:hover{background:var(--red);color:#fff}
.genre-pill .count{color:var(--text-muted);margin-left:5px;font-size:11px}
.genre-pill:hover .count{color:#fff}

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

/* Full Width Card */
.full-width{margin:0 40px 40px;grid-column:span 2}

/* Empty State */
.empty-state{text-align:center;padding:60px 20px;color:var(--text-muted)}

@media (max-width:768px){
    .navbar{padding:0 20px}
    .nav-links{display:none}
    .page-header{padding:80px 20px 30px}
    .page-header h1{font-size:40px}
    .charts-container{padding:20px;grid-template-columns:1fr}
    .genre-stats{margin:0 20px 30px}
    .full-width{margin:0 20px 30px}
    .chart-item{padding:10px 15px}
    .chart-rank{width:30px;font-size:22px}
    .chart-poster{width:35px;height:52px}
    .chart-info h3{font-size:13px}
}
</style>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<nav class="navbar">
  <a class="nav-logo" href="index.php">CINE<span>VAULT</span></a>
  <ul class="nav-links">
    <li><a href="index.php">Home</a></li>
    <li><a href="movies.php">Browse</a></li>
    <li><a href="collections.php">Collections</a></li>
    <li><a href="charts.php" class="active">Top Charts</a></li>
    <li><a href="contact.php" class="active">Contact</a></li>
    <li><a href="about.php" class="active">About</a></li>
  </ul>
  <div class="nav-right">
    <?php if ($loggedIn): ?>
    <div class="user-menu">
      <button class="user-trigger">
        <div class="user-avatar"><?= strtoupper(substr($username, 0, 1)) ?></div>
        <span><?= htmlspecialchars($username) ?></span>
      </button>
      <div class="user-dropdown">
        <a href="profile.php">My Profile</a>
        <a href="javascript:void(0)" onclick="logout()">Logout</a>
      </div>
    </div>
    <?php else: ?>
    <button class="btn-outline" onclick="openAuth()">Sign In</button>
    <?php endif; ?>
  </div>
</nav>

<div class="page-header">
  <h1>TOP <span>CHARTS</span></h1>
  <p>Discover the most popular and highest rated movies</p>
</div>

<div class="charts-container">
    <!-- Most Viewed All Time -->
    <div class="chart-card">
        <div class="chart-header">
            <h2>🔥 Most Viewed</h2>
            <p>All time most watched movies</p>
        </div>
        <div class="chart-list">
            <?php foreach ($mostViewed as $index => $movie): ?>
            <div class="chart-item" onclick="location.href='movie-detail.php?slug=<?= $movie['slug'] ?>'">
                <div class="chart-rank <?= $index == 0 ? 'top1' : ($index == 1 ? 'top2' : ($index == 2 ? 'top3' : '')) ?>"><?= $index + 1 ?></div>
                <img class="chart-poster" src="<?= htmlspecialchars($movie['poster'] ?: 'https://via.placeholder.com/45x67?text=No+Image') ?>" alt="">
                <div class="chart-info">
                    <h3><?= htmlspecialchars($movie['title']) ?></h3>
                    <div class="meta"><?= $movie['year'] ?> • <?= $movie['quality'] ?></div>
                </div>
                <div class="chart-views"><?= number_format($movie['views']) ?> views</div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Top Rated -->
    <div class="chart-card">
        <div class="chart-header">
            <h2>⭐ Top Rated</h2>
            <p>Highest rated movies of all time</p>
        </div>
        <div class="chart-list">
            <?php foreach ($topRated as $index => $movie): ?>
            <div class="chart-item" onclick="location.href='movie-detail.php?slug=<?= $movie['slug'] ?>'">
                <div class="chart-rank <?= $index == 0 ? 'top1' : ($index == 1 ? 'top2' : ($index == 2 ? 'top3' : '')) ?>"><?= $index + 1 ?></div>
                <img class="chart-poster" src="<?= htmlspecialchars($movie['poster'] ?: 'https://via.placeholder.com/45x67?text=No+Image') ?>" alt="">
                <div class="chart-info">
                    <h3><?= htmlspecialchars($movie['title']) ?></h3>
                    <div class="meta"><?= $movie['year'] ?> • <?= $movie['quality'] ?></div>
                </div>
                <div class="chart-rating">⭐ <?= $movie['rating'] ?>/10</div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Weekly Trending -->
    <div class="chart-card">
        <div class="chart-header">
            <h2>📈 Weekly Trending</h2>
            <p>Most watched this week</p>
        </div>
        <div class="chart-list">
            <?php foreach ($weeklyTop as $index => $movie): ?>
            <div class="chart-item" onclick="location.href='movie-detail.php?slug=<?= $movie['slug'] ?>'">
                <div class="chart-rank <?= $index == 0 ? 'top1' : ($index == 1 ? 'top2' : ($index == 2 ? 'top3' : '')) ?>"><?= $index + 1 ?></div>
                <img class="chart-poster" src="<?= htmlspecialchars($movie['poster'] ?: 'https://via.placeholder.com/45x67?text=No+Image') ?>" alt="">
                <div class="chart-info">
                    <h3><?= htmlspecialchars($movie['title']) ?></h3>
                    <div class="meta"><?= $movie['year'] ?> • <?= $movie['quality'] ?></div>
                </div>
                <div class="chart-views">🔥 <?= number_format($movie['weekly_views']) ?> this week</div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Latest Releases -->
    <div class="chart-card">
        <div class="chart-header">
            <h2>🆕 Latest Releases</h2>
            <p>Newly added movies</p>
        </div>
        <div class="chart-list">
            <?php foreach ($latestReleases as $index => $movie): ?>
            <div class="chart-item" onclick="location.href='movie-detail.php?slug=<?= $movie['slug'] ?>'">
                <div class="chart-rank"><?= $index + 1 ?></div>
                <img class="chart-poster" src="<?= htmlspecialchars($movie['poster'] ?: 'https://via.placeholder.com/45x67?text=No+Image') ?>" alt="">
                <div class="chart-info">
                    <h3><?= htmlspecialchars($movie['title']) ?></h3>
                    <div class="meta"><?= $movie['year'] ?> • <?= $movie['quality'] ?></div>
                </div>
                <div class="chart-rating">⭐ <?= $movie['rating'] ?>/10</div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Genre Statistics -->
<div class="genre-stats">
    <h3>🎭 Popular Genres</h3>
    <div class="genre-grid">
        <?php foreach ($genreStats as $genre): ?>
        <div class="genre-pill" onclick="location.href='movies.php?genre=<?= $genre['slug'] ?>'">
            <?= htmlspecialchars($genre['name']) ?>
            <span class="count">(<?= $genre['movie_count'] ?>)</span>
        </div>
        <?php endforeach; ?>
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
async function logout() {
    await fetch('pages/auth.php', { method: 'POST', body: new URLSearchParams({ action: 'logout' }) });
    location.reload();
}

function openAuth() {
    alert('Please login to continue');
}
</script>
</body>
</html>