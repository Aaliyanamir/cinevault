<?php
require_once __DIR__ . '/includes/config.php';

$loggedIn = isLoggedIn();
$username = $_SESSION['username'] ?? '';
$db = getDB();

// Get data for homepage
$featuredMovies = $db->query("SELECT * FROM movies WHERE is_featured = 1 ORDER BY RAND() LIMIT 5")->fetch_all(MYSQLI_ASSOC);
$trendingMovies = $db->query("SELECT * FROM movies WHERE is_trending = 1 ORDER BY views DESC LIMIT 12")->fetch_all(MYSQLI_ASSOC);
$latestMovies = $db->query("SELECT * FROM movies ORDER BY created_at DESC LIMIT 12")->fetch_all(MYSQLI_ASSOC);
$upcomingMovies = $db->query("SELECT * FROM movies WHERE is_upcoming = 1 OR (release_date IS NOT NULL AND release_date > CURDATE()) ORDER BY release_date ASC LIMIT 8")->fetch_all(MYSQLI_ASSOC);
$topRated = $db->query("SELECT * FROM movies WHERE rating > 0 ORDER BY rating DESC LIMIT 10")->fetch_all(MYSQLI_ASSOC);
$genres = $db->query("SELECT * FROM genres ORDER BY name LIMIT 12")->fetch_all(MYSQLI_ASSOC);

// Get continue watching for logged in user
$continueWatching = [];
if ($loggedIn) {
    $continueWatching = getContinueWatching($_SESSION['user_id'], 10);
}

// Attach genres to movies
function attachGenresToMovies($db, &$movies) {
    if (empty($movies)) return;
    $ids = array_column($movies, 'id');
    if (empty($ids)) return;
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));
    $stmt = $db->prepare("SELECT mg.movie_id, g.name, g.slug FROM movie_genres mg JOIN genres g ON mg.genre_id = g.id WHERE mg.movie_id IN ($placeholders)");
    $stmt->bind_param($types, ...$ids);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $map = [];
    foreach ($rows as $row) {
        $map[$row['movie_id']][] = ['name' => $row['name'], 'slug' => $row['slug']];
    }
    foreach ($movies as &$movie) {
        $movie['genres'] = $map[$movie['id']] ?? [];
    }
}

attachGenresToMovies($db, $trendingMovies);
attachGenresToMovies($db, $latestMovies);
attachGenresToMovies($db, $upcomingMovies);
attachGenresToMovies($db, $topRated);
attachGenresToMovies($db, $continueWatching);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= SITE_NAME ?> - Watch Movies Online Free</title>
<meta name="description" content="Watch the latest movies online for free. Stream HD quality movies, TV shows, and more. No signup required!">
<meta name="keywords" content="movies, watch movies, free movies, online streaming, HD movies">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
:root {
  --black: #080808; --dark-2: #141414; --dark-3: #1a1a1a; --dark-4: #222; --dark-5: #2a2a2a;
  --red: #c0392b; --red-light: #e74c3c; --red-dim: #7d1f1f;
  --text: #f0f0f0; --text-sec: #a0a0a0; --text-muted: #555;
  --border: #2a2a2a; --border-light: #333; --gold: #f0b429;
  --font-display: 'Bebas Neue', sans-serif; --font-body: 'Inter', sans-serif;
  --radius: 6px; --radius-lg: 12px; --transition: 0.2s ease;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
body{font-family:var(--font-body);background:var(--black);color:var(--text);min-height:100vh;overflow-x:hidden}
a{text-decoration:none;color:inherit}
img{display:block;max-width:100%}
::-webkit-scrollbar{width:6px;height:6px}
::-webkit-scrollbar-track{background:var(--dark-2)}
::-webkit-scrollbar-thumb{background:var(--dark-5);border-radius:3px}
::-webkit-scrollbar-thumb:hover{background:var(--red-dim)}

/* Navbar */
.navbar{position:fixed;top:0;left:0;right:0;z-index:1000;height:64px;display:flex;align-items:center;padding:0 40px;background:rgba(8,8,8,0.97);backdrop-filter:blur(12px);border-bottom:1px solid rgba(255,255,255,0.04)}
.navbar.scrolled{background:rgba(8,8,8,0.99);box-shadow:0 2px 20px rgba(0,0,0,0.5)}
.nav-logo{font-family:var(--font-display);font-size:26px;letter-spacing:3px;flex-shrink:0}
.nav-logo span{color:var(--red)}
.nav-links{display:flex;gap:24px;margin-left:36px;list-style:none}
.nav-links a{font-size:13px;font-weight:500;color:var(--text-sec);transition:color var(--transition)}
.nav-links a:hover,.nav-links a.active{color:var(--text)}
.nav-search{margin-left:auto;display:flex;align-items:center;gap:12px;position:relative}
.search-box{display:flex;align-items:center;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:var(--radius);padding:6px 12px;gap:8px}
.search-box:focus-within{border-color:var(--red-dim);background:rgba(255,255,255,0.08)}
.search-box input{background:none;border:none;outline:none;color:var(--text);font-size:13px;width:200px}
.search-box input::placeholder{color:var(--text-muted)}
.nav-auth{display:flex;align-items:center;gap:10px;margin-left:20px}
.btn{display:inline-flex;align-items:center;gap:6px;padding:7px 16px;border-radius:var(--radius);font-size:13px;font-weight:600;border:none;cursor:pointer;transition:all var(--transition)}
.btn-outline{background:transparent;border:1px solid var(--border-light);color:var(--text-sec)}
.btn-outline:hover{border-color:var(--text-sec);color:var(--text)}
.btn-red{background:var(--red);color:#fff}
.btn-red:hover{background:var(--red-light)}
.user-menu{position:relative}
.user-trigger{display:flex;align-items:center;gap:8px;background:var(--dark-3);border:none;padding:6px 12px;border-radius:var(--radius);color:var(--text);cursor:pointer}
.user-avatar{width:30px;height:30px;border-radius:50%;background:var(--red-dim);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#fff}
.user-dropdown{position:absolute;top:100%;right:0;background:var(--dark-3);border:1px solid var(--border);border-radius:var(--radius-lg);min-width:160px;opacity:0;visibility:hidden;transform:translateY(-8px);transition:all 0.18s ease;z-index:100}
.user-menu:hover .user-dropdown{opacity:1;visibility:visible;transform:translateY(0)}
.user-dropdown a{display:block;padding:10px 15px;font-size:13px;color:var(--text-sec);transition:background var(--transition)}
.user-dropdown a:hover{background:var(--dark-4);color:var(--text)}

/* Search Results Dropdown */
.search-results{position:absolute;top:calc(100% + 8px);right:0;width:320px;background:var(--dark-3);border:1px solid var(--border);border-radius:var(--radius-lg);box-shadow:0 16px 40px rgba(0,0,0,0.7);z-index:200;display:none;max-height:400px;overflow-y:auto}
.search-result-item{display:flex;align-items:center;gap:12px;padding:12px 14px;cursor:pointer;transition:background var(--transition)}
.search-result-item:hover{background:var(--dark-4)}
.search-result-img{width:40px;height:60px;border-radius:4px;object-fit:cover;background:var(--dark-4)}
.search-result-info h4{font-size:13px;font-weight:600;margin-bottom:3px}
.search-result-info span{font-size:11px;color:var(--text-muted)}
.search-empty{padding:30px;text-align:center;color:var(--text-muted);font-size:13px}

/* Rest of the styles (same as before - keeping it compact) */
.hero{position:relative;height:85vh;min-height:560px;display:flex;align-items:flex-end;overflow:hidden}
.hero-bg{position:absolute;inset:0;background-size:cover;background-position:center top;filter:brightness(0.45)}
.hero-overlay{position:absolute;inset:0;background:linear-gradient(to right, rgba(8,8,8,0.92) 0%, rgba(8,8,8,0.6) 40%, rgba(8,8,8,0.1) 70%, transparent 100%)}
.hero-overlay-bottom{position:absolute;bottom:0;left:0;right:0;height:40%;background:linear-gradient(to top, var(--black), transparent)}
.hero-content{position:relative;z-index:2;padding:0 60px 72px;max-width:580px}
.hero-badges{display:flex;align-items:center;gap:10px;margin-bottom:14px}
.badge{font-size:11px;font-weight:700;letter-spacing:1.5px;padding:3px 9px;border-radius:3px;text-transform:uppercase}
.badge-red{background:var(--red);color:#fff}
.badge-quality{background:var(--dark-4);color:var(--gold);border:1px solid rgba(240,180,41,0.3)}
.hero-title{font-family:var(--font-display);font-size:clamp(44px,6vw,78px);line-height:0.95;letter-spacing:2px;margin-bottom:16px}
.hero-meta{display:flex;align-items:center;gap:14px;margin-bottom:14px;font-size:13px;color:var(--text-sec)}
.hero-desc{font-size:14px;line-height:1.7;color:var(--text-sec);margin-bottom:28px;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden}
.hero-actions{display:flex;gap:12px;flex-wrap:wrap}
.btn-play{background:#fff;color:var(--black);padding:11px 28px;font-size:15px;font-weight:700;border:none;border-radius:var(--radius);display:flex;align-items:center;gap:8px;cursor:pointer}
.hero-dots{position:absolute;bottom:30px;right:60px;z-index:3;display:flex;gap:8px}
.dot{width:6px;height:6px;border-radius:50%;background:var(--text-muted);cursor:pointer}
.dot.active{width:24px;border-radius:3px;background:var(--red)}
.section{padding:48px 40px}
.section-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:24px}
.section-title{font-family:var(--font-display);font-size:26px;letter-spacing:2px;display:flex;align-items:center;gap:12px}
.section-title::before{content:'';display:block;width:4px;height:22px;background:var(--red);border-radius:2px}
.section-link{font-size:12px;font-weight:600;color:var(--text-muted);text-transform:uppercase}
.movies-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:16px}
.scroll-row-wrap{position:relative}
.scroll-row{display:flex;gap:12px;overflow-x:auto;padding-bottom:8px;scrollbar-width:none}
.scroll-row::-webkit-scrollbar{display:none}
.scroll-row .movie-card{flex:0 0 180px}
.scroll-btn{position:absolute;top:50%;transform:translateY(-50%);width:38px;height:80px;background:rgba(0,0,0,0.7);border:1px solid var(--border);color:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;z-index:5}
.scroll-btn-left{left:-19px}
.scroll-btn-right{right:-19px}
.movie-card{position:relative;border-radius:var(--radius-lg);overflow:hidden;cursor:pointer;background:var(--dark-2);transition:transform 0.25s ease}
.movie-card:hover{transform:translateY(-6px) scale(1.02);box-shadow:0 16px 40px rgba(0,0,0,0.7),0 0 0 1px rgba(192,57,43,0.3)}
.card-poster{aspect-ratio:2/3;position:relative;overflow:hidden}
.card-poster img{width:100%;height:100%;object-fit:cover;transition:transform 0.4s ease}
.movie-card:hover .card-poster img{transform:scale(1.06)}
.card-overlay{position:absolute;inset:0;background:linear-gradient(to top, rgba(0,0,0,0.9) 0%, transparent 50%);opacity:0;transition:opacity 0.25s ease;display:flex;align-items:center;justify-content:center}
.movie-card:hover .card-overlay{opacity:1}
.play-icon{width:48px;height:48px;border-radius:50%;background:rgba(255,255,255,0.92);display:flex;align-items:center;justify-content:center;transform:scale(0.8);transition:transform 0.2s ease}
.movie-card:hover .play-icon{transform:scale(1)}
.card-quality{position:absolute;top:8px;left:8px;background:rgba(0,0,0,0.75);backdrop-filter:blur(4px);border:1px solid rgba(240,180,41,0.4);color:var(--gold);font-size:10px;font-weight:700;padding:2px 6px;border-radius:3px}
.card-info{padding:10px 10px 12px}
.card-title{font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-bottom:5px}
.card-meta{display:flex;align-items:center;justify-content:space-between;font-size:11px;color:var(--text-muted)}
.card-rating{display:flex;align-items:center;gap:3px;color:var(--gold);font-weight:600}
.progress-bar{height:3px;background:var(--dark-5);border-radius:3px;margin-top:8px;overflow:hidden}
.progress-fill{height:100%;background:var(--red);border-radius:3px}
.genres-section{padding:24px 40px}
.genres-bar{display:flex;gap:10px;flex-wrap:wrap}
.genre-pill{padding:7px 18px;border-radius:50px;background:var(--dark-3);border:1px solid var(--border);font-size:13px;font-weight:500;color:var(--text-sec);cursor:pointer}
.genre-pill:hover,.genre-pill.active{background:var(--red);border-color:var(--red);color:#fff}
.modal-overlay{position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.92);backdrop-filter:blur(6px);display:flex;align-items:center;justify-content:center;padding:20px;opacity:0;visibility:hidden;transition:opacity 0.3s ease}
.modal-overlay.open{opacity:1;visibility:visible}
.modal-box{background:var(--dark-2);border-radius:var(--radius-lg);border:1px solid var(--border);width:100%;max-width:1000px;max-height:92vh;overflow-y:auto}
.player-wrap{position:relative;background:#000;border-radius:var(--radius-lg) var(--radius-lg) 0 0;overflow:hidden}
.player-wrap::before{content:'';display:block;padding-top:56.25%}
.player-wrap iframe{position:absolute;inset:0;width:100%;height:100%;border:none}
.modal-close{position:absolute;top:14px;right:14px;width:34px;height:34px;border-radius:50%;background:rgba(0,0,0,0.7);border:1px solid rgba(255,255,255,0.2);color:#fff;display:flex;align-items:center;justify-content:center;cursor:pointer}
.auth-modal{position:fixed;inset:0;z-index:9998;background:rgba(0,0,0,0.88);backdrop-filter:blur(8px);display:flex;align-items:center;justify-content:center;padding:20px;opacity:0;visibility:hidden}
.auth-modal.open{opacity:1;visibility:visible}
.auth-box{background:var(--dark-2);border:1px solid var(--border);border-radius:var(--radius-lg);width:100%;max-width:400px;padding:36px;position:relative}
.auth-logo{font-family:var(--font-display);font-size:22px;letter-spacing:3px;text-align:center}
.auth-logo span{color:var(--red)}
.auth-tabs{display:flex;border-bottom:1px solid var(--border);margin:20px 0}
.auth-tab{flex:1;padding:9px;text-align:center;font-size:13px;font-weight:600;color:var(--text-muted);cursor:pointer;border-bottom:2px solid transparent}
.auth-tab.active{color:var(--text);border-bottom-color:var(--red)}
.auth-form{display:none}
.auth-form.active{display:block}
.form-group{margin-bottom:14px}
.form-label{display:block;font-size:11px;font-weight:600;text-transform:uppercase;color:var(--text-muted);margin-bottom:6px}
.form-input{width:100%;padding:10px 13px;background:var(--dark-3);border:1px solid var(--border);border-radius:var(--radius);color:var(--text);font-size:13px}
.auth-submit{width:100%;padding:11px;background:var(--red);color:#fff;border:none;border-radius:var(--radius);font-size:14px;font-weight:700;cursor:pointer;margin-top:6px}
.toast-wrap{position:fixed;bottom:28px;right:28px;z-index:99999;display:flex;flex-direction:column;gap:8px}
.toast{background:var(--dark-3);border:1px solid var(--border);border-radius:var(--radius);padding:11px 16px;font-size:13px;transform:translateX(120%);transition:transform 0.3s ease}
.toast.show{transform:translateX(0)}
.toast.success{border-left:3px solid #27ae60}
.spinner{width:36px;height:36px;border-radius:50%;border:3px solid var(--dark-5);border-top-color:var(--red);animation:spin 0.7s linear infinite;margin:48px auto}
@keyframes spin{to{transform:rotate(360deg)}}
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
.newsletter-section {
    background: #1a1a1a;
    padding: 40px;
    margin: 40px 0 0;
    text-align: center;
    border-radius: 12px;
}
.newsletter-section h3 {
    font-family: 'Bebas Neue', sans-serif;
    font-size: 28px;
    margin-bottom: 10px;
}
.newsletter-section p {
    color: #a0a0a0;
    margin-bottom: 20px;
}
@media (max-width:768px){
    .navbar{padding:0 20px}
    .nav-links{display:none}
    .hero-content{padding:0 24px 60px}
    .section{padding:36px 20px}
    .movies-grid{grid-template-columns:repeat(auto-fill,minmax(140px,1fr))}
}
</style>
</head>
<body>

<nav class="navbar" id="navbar">
  <a class="nav-logo" href="index.php">CINE<span>VAULT</span></a>
  <ul class="nav-links">
    <li><a href="index.php" class="active">Home</a></li>
    <li><a href="movies.php">Browse</a></li>
    <li><a href="collections.php">Collections</a></li>
    <li><a href="charts.php">Top Charts</a></li>
    <li><a href="contact.php" class="active">Contact</a></li>
    <li><a href="about.php" class="active">About</a></li>
  </ul>
  <div class="nav-search">
    <div class="search-box">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
      <input type="text" id="searchInput" placeholder="Search movies..." autocomplete="off">
    </div>
    <div id="searchResults" class="search-results"></div>
   <div class="nav-auth">
    <?php if ($loggedIn): ?>
    <div class="user-menu">
        <button class="user-trigger">
            <div class="user-avatar"><?= strtoupper(substr($username, 0, 1)) ?></div>
            <span><?= htmlspecialchars($username) ?></span>
        </button>
        <div class="user-dropdown">
            <a href="profile.php">👤 My Profile</a>
            <a href="watchlist.php">📋 My Watchlist</a>
            <a href="logout.php">🚪 Logout</a>
        </div>
    </div>
    <?php else: ?>
    <a href="login.php" class="btn btn-outline">Sign In</a>
    <a href="register.php" class="btn btn-red">Join Free</a>
    <?php endif; ?>
</div>
  </div>
</nav>

<!-- Hero Banner -->
<section class="hero" id="heroBanner">
  <div class="hero-bg" id="heroBg"></div>
  <div class="hero-overlay"></div>
  <div class="hero-overlay-bottom"></div>
  <div class="hero-content" id="heroContent">
    <div class="spinner"></div>
  </div>
  <div class="hero-dots" id="heroDots"></div>
</section>

<!-- Continue Watching Section (Logged In Only) -->
<?php if ($loggedIn && !empty($continueWatching)): ?>
<section class="section" style="padding-top:0">
  <div class="section-header">
    <h2 class="section-title">Continue Watching</h2>
  </div>
  <div class="scroll-row-wrap">
    <button class="scroll-btn scroll-btn-left" onclick="scrollRow('continueRow',-1)">‹</button>
    <div class="scroll-row" id="continueRow">
      <?php foreach ($continueWatching as $movie): ?>
      <div class="movie-card" onclick="location.href='movie-detail.php?slug=<?= $movie['slug'] ?>'">
        <div class="card-poster">
          <img src="<?= htmlspecialchars($movie['poster'] ?: 'https://via.placeholder.com/300x450?text=No+Poster') ?>" alt="<?= htmlspecialchars($movie['title']) ?>">
          <div class="card-overlay"><div class="play-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="#111"><polygon points="5 3 19 12 5 21 5 3"/></svg></div></div>
          <span class="card-quality"><?= $movie['quality'] ?></span>
        </div>
        <div class="card-info">
          <div class="card-title"><?= htmlspecialchars($movie['title']) ?></div>
          <div class="card-meta"><div class="card-rating">⭐ <?= $movie['rating'] ?></div><span><?= $movie['year'] ?></span></div>
          <?php if ($movie['progress'] > 0): ?>
          <div class="progress-bar"><div class="progress-fill" style="width: <?= min(100, ($movie['progress'] / ($movie['duration'] * 60)) * 100) ?>%"></div></div>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <button class="scroll-btn scroll-btn-right" onclick="scrollRow('continueRow',1)">›</button>
  </div>
</section>
<?php endif; ?>

<!-- Trending Section -->
<section class="section">
  <div class="section-header">
    <h2 class="section-title">Trending Now</h2>
    <a href="movies.php" class="section-link">View All ›</a>
  </div>
  <div class="scroll-row-wrap">
    <button class="scroll-btn scroll-btn-left" onclick="scrollRow('trendingRow',-1)">‹</button>
    <div class="scroll-row" id="trendingRow">
      <?php foreach ($trendingMovies as $movie): ?>
      <div class="movie-card" onclick="location.href='movie-detail.php?slug=<?= $movie['slug'] ?>'">
        <div class="card-poster">
          <img src="<?= htmlspecialchars($movie['poster'] ?: 'https://via.placeholder.com/300x450?text=No+Poster') ?>" alt="<?= htmlspecialchars($movie['title']) ?>">
          <div class="card-overlay"><div class="play-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="#111"><polygon points="5 3 19 12 5 21 5 3"/></svg></div></div>
          <span class="card-quality"><?= $movie['quality'] ?></span>
        </div>
        <div class="card-info">
          <div class="card-title"><?= htmlspecialchars($movie['title']) ?></div>
          <div class="card-meta"><div class="card-rating">⭐ <?= $movie['rating'] ?></div><span><?= $movie['year'] ?></span></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <button class="scroll-btn scroll-btn-right" onclick="scrollRow('trendingRow',1)">›</button>
  </div>
</section>

<!-- Latest Releases -->
<section class="section" style="padding-top:0">
  <div class="section-header">
    <h2 class="section-title">Latest Releases</h2>
    <a href="movies.php?sort=created_at" class="section-link">View All ›</a>
  </div>
  <div class="movies-grid">
    <?php foreach (array_slice($latestMovies, 0, 12) as $movie): ?>
    <div class="movie-card" onclick="location.href='movie-detail.php?slug=<?= $movie['slug'] ?>'">
      <div class="card-poster">
        <img src="<?= htmlspecialchars($movie['poster'] ?: 'https://via.placeholder.com/300x450?text=No+Poster') ?>" alt="<?= htmlspecialchars($movie['title']) ?>">
        <div class="card-overlay"><div class="play-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="#111"><polygon points="5 3 19 12 5 21 5 3"/></svg></div></div>
        <span class="card-quality"><?= $movie['quality'] ?></span>
      </div>
      <div class="card-info">
        <div class="card-title"><?= htmlspecialchars($movie['title']) ?></div>
        <div class="card-meta"><div class="card-rating">⭐ <?= $movie['rating'] ?></div><span><?= $movie['year'] ?></span></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- Genres Bar -->
<div class="genres-section">
  <div class="genres-bar">
    <div class="genre-pill active" onclick="location.href='movies.php'">All</div>
    <?php foreach ($genres as $genre): ?>
    <div class="genre-pill" onclick="location.href='movies.php?genre=<?= $genre['slug'] ?>'"><?= htmlspecialchars($genre['name']) ?></div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Top Rated Movies -->
<section class="section">
  <div class="section-header">
    <h2 class="section-title">Top Rated</h2>
    <a href="movies.php?sort=rating" class="section-link">View All ›</a>
  </div>
  <div class="movies-grid">
    <?php foreach (array_slice($topRated, 0, 8) as $movie): ?>
    <div class="movie-card" onclick="location.href='movie-detail.php?slug=<?= $movie['slug'] ?>'">
      <div class="card-poster">
        <img src="<?= htmlspecialchars($movie['poster'] ?: 'https://via.placeholder.com/300x450?text=No+Poster') ?>" alt="<?= htmlspecialchars($movie['title']) ?>">
        <div class="card-overlay"><div class="play-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="#111"><polygon points="5 3 19 12 5 21 5 3"/></svg></div></div>
        <span class="card-quality"><?= $movie['quality'] ?></span>
      </div>
      <div class="card-info">
        <div class="card-title"><?= htmlspecialchars($movie['title']) ?></div>
        <div class="card-meta"><div class="card-rating">⭐ <?= $movie['rating'] ?></div><span><?= $movie['year'] ?></span></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- Coming Soon Section -->
<?php if (!empty($upcomingMovies)): ?>
<section class="section" style="padding-top:0">
  <div class="section-header">
    <h2 class="section-title">Coming Soon</h2>
    <a href="coming-soon.php" class="section-link">View All ›</a>
  </div>
  <div class="scroll-row-wrap">
    <button class="scroll-btn scroll-btn-left" onclick="scrollRow('upcomingRow',-1)">‹</button>
    <div class="scroll-row" id="upcomingRow">
      <?php foreach ($upcomingMovies as $movie): ?>
      <div class="movie-card" onclick="location.href='movie-detail.php?slug=<?= $movie['slug'] ?>'">
        <div class="card-poster">
          <img src="<?= htmlspecialchars($movie['poster'] ?: 'https://via.placeholder.com/300x450?text=No+Poster') ?>" alt="<?= htmlspecialchars($movie['title']) ?>">
          <div class="card-overlay"><div class="play-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="#111"><polygon points="5 3 19 12 5 21 5 3"/></svg></div></div>
          <span class="card-quality"><?= $movie['quality'] ?></span>
        </div>
        <div class="card-info">
          <div class="card-title"><?= htmlspecialchars($movie['title']) ?></div>
          <div class="card-meta"><span><?= $movie['release_date'] ? date('M d, Y', strtotime($movie['release_date'])) : 'Coming Soon' ?></span></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <button class="scroll-btn scroll-btn-right" onclick="scrollRow('upcomingRow',1)">›</button>
  </div>
</section>
<?php endif; ?>

<!-- Newsletter Section - Footer ke andar ya pehle -->
<div class="newsletter-section" style="background: #1a1a1a; padding: 40px; margin: 40px 0 0; text-align: center; border-radius: 12px;">
    <h3 style="font-family: 'Bebas Neue', sans-serif; font-size: 28px;">📧 Subscribe to Newsletter</h3>
    <p style="color: #a0a0a0; margin-bottom: 20px;">Get latest movie updates directly in your inbox!</p>
    <form id="newsletterForm" style="display: flex; gap: 10px; max-width: 400px; margin: 0 auto; flex-wrap: wrap; justify-content: center;">
        <input type="email" id="newsletterEmail" placeholder="Enter your email" required style="flex: 1; padding: 12px; background: #222; border: 1px solid #333; border-radius: 8px; color: #fff; min-width: 200px;">
        <button type="submit" style="padding: 12px 24px; background: #c0392b; border: none; border-radius: 8px; color: #fff; font-weight: 600; cursor: pointer;">Subscribe</button>
    </form>
    <div id="newsletterMsg" style="margin-top: 15px; font-size: 13px;"></div>
</div>

<!-- Footer -->
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

<!-- Movie Modal -->
<div class="modal-overlay" id="movieModal" onclick="closeModalOutside(event)">
  <div class="modal-box">
    <div class="player-wrap"><button class="modal-close" onclick="closeMovieModal()">&times;</button><iframe id="movieIframe" src="" allowfullscreen></iframe></div>
    <div id="modalContent"></div>
  </div>
</div>

<!-- Auth Modal -->
<div class="auth-modal" id="authModal" onclick="closeAuthOutside(event)">
  <div class="auth-box"><button class="auth-close" onclick="closeAuthModal()">&times;</button>
    <div class="auth-logo">CINE<span>VAULT</span></div>
    <div class="auth-tabs"><div class="auth-tab active" onclick="switchAuthTab('login')">Sign In</div><div class="auth-tab" onclick="switchAuthTab('register')">Register</div></div>
    <form class="auth-form active" id="form-login" onsubmit="handleLogin(event)"><div class="form-group"><label class="form-label">Email</label><input type="email" class="form-input" id="loginEmail" required></div><div class="form-group"><label class="form-label">Password</label><input type="password" class="form-input" id="loginPassword" required></div><div id="loginError" style="color:var(--red-light);font-size:12px;margin-bottom:10px"></div><button type="submit" class="auth-submit">Sign In</button></form>
    <form class="auth-form" id="form-register" onsubmit="handleRegister(event)"><div class="form-group"><label class="form-label">Username</label><input type="text" class="form-input" id="regUsername" required></div><div class="form-group"><label class="form-label">Email</label><input type="email" class="form-input" id="regEmail" required></div><div class="form-group"><label class="form-label">Password</label><input type="password" class="form-input" id="regPassword" minlength="6" required></div><div id="registerError" style="color:var(--red-light);font-size:12px;margin-bottom:10px"></div><button type="submit" class="auth-submit">Create Account</button></form>
  </div>
</div>

<div class="toast-wrap" id="toastWrap"></div>

<script>
const LOGGED_IN = <?= $loggedIn ? 'true' : 'false' ?>;
let heroMovies = [];
let heroIndex = 0;
let heroTimer = null;
let searchTimeout = null;

// Hero Data
<?php if (!empty($featuredMovies)): ?>
heroMovies = <?= json_encode($featuredMovies) ?>;
if (heroMovies.length) { renderHero(0); startHeroTimer(); }
<?php endif; ?>

function renderHero(index) {
    if (!heroMovies[index]) return;
    const m = heroMovies[index];
    document.getElementById('heroBg').style.backgroundImage = `url('${m.backdrop || m.poster}')`;
    document.getElementById('heroContent').innerHTML = `
        <div class="hero-badges"><span class="badge badge-red">Featured</span><span class="badge badge-quality">${m.quality}</span></div>
        <h1 class="hero-title">${m.title}</h1>
        <div class="hero-meta"><div class="rating">⭐ ${m.rating}</div><span>•</span><span>${m.year}</span><span>•</span><span>${m.language}</span></div>
        <p class="hero-desc">${m.description || ''}</p>
        <div class="hero-actions"><button class="btn-play" onclick="openMovie(${m.id}, '${m.slug}')">▶ Watch Now</button></div>
    `;
    document.querySelectorAll('.dot').forEach((d, i) => d.classList.toggle('active', i === index));
}

function startHeroTimer() { heroTimer = setInterval(() => { heroIndex = (heroIndex + 1) % heroMovies.length; renderHero(heroIndex); }, 6000); }

// ============================================
// AJAX LIVE SEARCH - Like your old one
// ============================================
const searchInput = document.getElementById('searchInput');
const searchResults = document.getElementById('searchResults');

searchInput.addEventListener('input', function() {
    const query = this.value.trim();
    
    if (searchTimeout) clearTimeout(searchTimeout);
    
    if (query.length < 2) {
        searchResults.style.display = 'none';
        return;
    }
    
    searchTimeout = setTimeout(async () => {
        searchResults.style.display = 'block';
        searchResults.innerHTML = '<div class="search-empty">Searching...</div>';
        
        try {
            const response = await fetch(`pages/movies.php?action=search&q=${encodeURIComponent(query)}&limit=8`);
            const data = await response.json();
            const movies = data.movies || [];
            
            if (movies.length === 0) {
                searchResults.innerHTML = '<div class="search-empty">No movies found</div>';
            } else {
                searchResults.innerHTML = movies.map(m => `
                    <div class="search-result-item" onclick="openMovie(${m.id}, '${m.slug}')">
                        <img class="search-result-img" src="${m.poster || ''}" alt="${m.title}" onerror="this.style.background='#222'">
                        <div class="search-result-info">
                            <h4>${m.title}</h4>
                            <span>${m.year} • ${m.quality} • ⭐ ${m.rating}</span>
                        </div>
                    </div>
                `).join('');
            }
        } catch (error) {
            searchResults.innerHTML = '<div class="search-empty">Error loading results</div>';
        }
    }, 300);
});

// Close search results when clicking outside
document.addEventListener('click', function(e) {
    if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
        searchResults.style.display = 'none';
    }
});

// ============================================
// Movie Functions
// ============================================
function openMovie(id, slug) {
    const modal = document.getElementById('movieModal');
    const iframe = document.getElementById('movieIframe');
    modal.classList.add('open');
    document.body.style.overflow = 'hidden';
    iframe.src = '';
    searchResults.style.display = 'none';
    
    fetch(`pages/movies.php?action=single&id=${id}`).then(r=>r.json()).then(data=>{
        if(data.movie){
            iframe.src = data.movie.embed_url;
            fetch('pages/movies.php',{method:'POST',body:new URLSearchParams({action:'increment_views',id:id})});
            if(LOGGED_IN){
                fetch('api/watch-progress.php',{method:'POST',body:new URLSearchParams({action:'save',movie_id:id,progress:0})});
            }
            document.getElementById('modalContent').innerHTML = `<div class="modal-info" style="padding:20px"><h2>${data.movie.title}</h2><p>${data.movie.description || ''}</p></div>`;
        }
    });
}

function closeMovieModal() { 
    document.getElementById('movieModal').classList.remove('open'); 
    document.getElementById('movieIframe').src = ''; 
    document.body.style.overflow = ''; 
}
function closeModalOutside(e) { if(e.target === document.getElementById('movieModal')) closeMovieModal(); }
function scrollRow(rowId, dir) { const row = document.getElementById(rowId); if(row) row.scrollBy({ left: dir * 600, behavior: 'smooth' }); }

// ============================================
// Auth Functions
// ============================================
function openAuthModal(tab) { 
    document.getElementById('authModal').classList.add('open'); 
    document.body.style.overflow = 'hidden'; 
    switchAuthTab(tab); 
}
function closeAuthModal() { 
    document.getElementById('authModal').classList.remove('open'); 
    document.body.style.overflow = ''; 
}
function closeAuthOutside(e) { if(e.target === document.getElementById('authModal')) closeAuthModal(); }
function switchAuthTab(tab) { 
    document.getElementById('tab-login').classList.toggle('active', tab==='login'); 
    document.getElementById('tab-register').classList.toggle('active', tab==='register'); 
    document.getElementById('form-login').classList.toggle('active', tab==='login'); 
    document.getElementById('form-register').classList.toggle('active', tab==='register'); 
}

async function handleLogin(e) { 
    e.preventDefault(); 
    const res=await fetch('pages/auth.php',{method:'POST',body:new URLSearchParams({action:'login',email:document.getElementById('loginEmail').value,password:document.getElementById('loginPassword').value})}); 
    const data=await res.json(); 
    if(data.success){ toast('Welcome back!','success'); setTimeout(()=>location.reload(),800); } 
    else { document.getElementById('loginError').innerText=data.error; } 
}

async function handleRegister(e) { 
    e.preventDefault(); 
    const res=await fetch('pages/auth.php',{method:'POST',body:new URLSearchParams({action:'register',username:document.getElementById('regUsername').value,email:document.getElementById('regEmail').value,password:document.getElementById('regPassword').value})}); 
    const data=await res.json(); 
    if(data.success){ toast('Account created!','success'); setTimeout(()=>location.reload(),800); } 
    else { document.getElementById('registerError').innerText=data.error; } 
}

async function logoutUser() { 
    await fetch('pages/auth.php',{method:'POST',body:new URLSearchParams({action:'logout'})}); 
    location.reload(); 
}

function toast(msg, type='info') { 
    const wrap=document.getElementById('toastWrap'); 
    const el=document.createElement('div'); 
    el.className=`toast ${type}`; 
    el.textContent=msg; 
    wrap.appendChild(el); 
    requestAnimationFrame(()=>el.classList.add('show')); 
    setTimeout(()=>{el.classList.remove('show'); setTimeout(()=>el.remove(),400);},3000); 
}

window.addEventListener('scroll',()=>{document.getElementById('navbar').classList.toggle('scrolled',window.scrollY>50);});

</script>

<script>
</script>


<script>
// Newsletter Subscription
const newsletterForm = document.getElementById('newsletterForm');
if (newsletterForm) {
    newsletterForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const email = document.getElementById('newsletterEmail').value;
        const msgDiv = document.getElementById('newsletterMsg');
        
        msgDiv.innerHTML = '<span style="color: #f0b429;">Subscribing...</span>';
        
        const response = await fetch('api/newsletter.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'email=' + encodeURIComponent(email)
        });
        const data = await response.json();
        
        if (data.success) {
            msgDiv.innerHTML = '<span style="color: #2ecc71;">✅ Subscribed successfully!</span>';
            document.getElementById('newsletterEmail').value = '';
        } else {
            msgDiv.innerHTML = '<span style="color: #e74c3c;">❌ ' + data.error + '</span>';
        }
    });
}
</script>
</body>
</html>