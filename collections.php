<?php
require_once __DIR__ . '/includes/config.php';

$db = getDB();

// Get all collections
$collections = $db->query("
    SELECT c.*, COUNT(cm.movie_id) as movie_count 
    FROM collections c
    LEFT JOIN collection_movies cm ON c.id = cm.collection_id
    GROUP BY c.id
    ORDER BY c.display_order ASC, c.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

// If specific collection selected
$selectedCollection = null;
$collectionMovies = [];
$currentSlug = $_GET['slug'] ?? '';

if ($currentSlug) {
    $stmt = $db->prepare("SELECT * FROM collections WHERE slug = ?");
    $stmt->bind_param("s", $currentSlug);
    $stmt->execute();
    $selectedCollection = $stmt->get_result()->fetch_assoc();
    
    if ($selectedCollection) {
        $movieStmt = $db->prepare("
            SELECT m.* FROM movies m
            JOIN collection_movies cm ON m.id = cm.movie_id
            WHERE cm.collection_id = ?
            ORDER BY cm.display_order ASC
        ");
        $movieStmt->bind_param("i", $selectedCollection['id']);
        $movieStmt->execute();
        $collectionMovies = $movieStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Get genres for each movie
        foreach ($collectionMovies as &$movie) {
            $genreStmt = $db->prepare("
                SELECT g.name, g.slug FROM movie_genres mg 
                JOIN genres g ON mg.genre_id = g.id 
                WHERE mg.movie_id = ?
            ");
            $genreStmt->bind_param("i", $movie['id']);
            $genreStmt->execute();
            $movie['genres'] = $genreStmt->get_result()->fetch_all(MYSQLI_ASSOC);
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
<title><?= $selectedCollection ? htmlspecialchars($selectedCollection['name']) . ' - ' : '' ?>Collections - <?= SITE_NAME ?></title>
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

/* Collections Grid */
.collections-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:24px;padding:40px}
.collection-card{background:var(--dark-3);border-radius:16px;overflow:hidden;border:1px solid var(--border);cursor:pointer;transition:all 0.3s}
.collection-card:hover{transform:translateY(-5px);border-color:var(--red)}
.collection-cover{height:160px;background-size:cover;background-position:center;position:relative}
.collection-cover .overlay{position:absolute;inset:0;background:linear-gradient(to bottom, transparent, var(--black));display:flex;align-items:flex-end;justify-content:center;padding:15px}
.collection-cover .movie-count{background:var(--red);padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600}
.collection-info{padding:20px}
.collection-info h3{font-size:20px;margin-bottom:8px}
.collection-info p{font-size:13px;color:var(--text-sec);line-height:1.5}

/* Collection Banner */
.collection-banner{position:relative;height:300px;background-size:cover;background-position:center;display:flex;align-items:center;justify-content:center;margin-top:64px}
.collection-banner .overlay{position:absolute;inset:0;background:linear-gradient(135deg, rgba(0,0,0,0.85), rgba(0,0,0,0.6))}
.collection-banner h1{position:relative;z-index:1;font-family:var(--font-display);font-size:56px;letter-spacing:4px;text-align:center}
.collection-banner h1 span{color:var(--red)}
.collection-banner p{position:relative;z-index:1;text-align:center;color:var(--text-sec);margin-top:10px}

/* Back Button */
.back-btn{display:inline-block;margin:20px 40px;padding:8px 20px;background:var(--dark-3);border:1px solid var(--border);border-radius:8px;color:var(--text-sec);cursor:pointer;transition:all 0.2s}
.back-btn:hover{border-color:var(--red);color:var(--text)}

/* Movies Grid */
.movies-section{padding:20px 40px 60px}
.section-title{font-family:var(--font-display);font-size:28px;margin-bottom:24px}
.section-title span{color:var(--red)}
.movies-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:20px}
.movie-card{background:var(--dark-3);border-radius:12px;overflow:hidden;cursor:pointer;transition:transform 0.2s}
.movie-card:hover{transform:translateY(-5px)}
.movie-card img{width:100%;aspect-ratio:2/3;object-fit:cover}
.card-info{padding:12px}
.card-info h4{font-size:14px;margin-bottom:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.card-info .year{font-size:11px;color:var(--text-muted)}
.rating{color:var(--gold);font-size:12px;margin-top:4px}

/* Empty State */
.empty-state{text-align:center;padding:80px 20px;color:var(--text-muted)}
.empty-state h3{font-size:24px;margin-bottom:10px}


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
    .collections-grid{padding:20px}
    .movies-section{padding:20px}
    .movies-grid{grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:12px}
    .collection-banner{height:200px}
    .collection-banner h1{font-size:32px}
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
    <li><a href="collections.php" class="active">Collections</a></li>
    <li><a href="charts.php">Top Charts</a></li>
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

<?php if ($selectedCollection): ?>
    <!-- Collection Banner -->
    <div class="collection-banner" style="background-image: linear-gradient(135deg, rgba(0,0,0,0.85), rgba(0,0,0,0.6)), url('<?= htmlspecialchars($selectedCollection['cover_image'] ?: 'https://via.placeholder.com/1200x300?text=' . urlencode($selectedCollection['name'])) ?>')">
        <div class="overlay"></div>
        <div style="position:relative;z-index:1">
            <h1><?= htmlspecialchars($selectedCollection['name']) ?> <span>Collection</span></h1>
            <p><?= htmlspecialchars($selectedCollection['description'] ?? 'Curated collection of must-watch movies') ?></p>
        </div>
    </div>
    
    <div class="back-btn" onclick="location.href='collections.php'">← Back to All Collections</div>
    
    <div class="movies-section">
        <h2 class="section-title">Movies in <span><?= htmlspecialchars($selectedCollection['name']) ?></span></h2>
        
        <?php if (empty($collectionMovies)): ?>
        <div class="empty-state">
            <h3>No movies in this collection yet</h3>
            <p>Check back soon for updates!</p>
        </div>
        <?php else: ?>
        <div class="movies-grid">
            <?php foreach ($collectionMovies as $movie): ?>
            <div class="movie-card" onclick="location.href='movie-detail.php?slug=<?= $movie['slug'] ?>'">
                <img src="<?= htmlspecialchars($movie['poster'] ?: 'https://via.placeholder.com/300x450?text=No+Poster') ?>" alt="<?= htmlspecialchars($movie['title']) ?>">
                <div class="card-info">
                    <h4><?= htmlspecialchars($movie['title']) ?></h4>
                    <div class="year"><?= $movie['year'] ?> • <?= $movie['quality'] ?></div>
                    <div class="rating">⭐ <?= $movie['rating'] ?>/10</div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

<?php else: ?>
    <!-- Collections Listing -->
    <div class="page-header">
        <h1>CURATED <span>COLLECTIONS</span></h1>
        <p>Hand-picked movie collections for every mood</p>
    </div>
    
    <div class="collections-grid">
        <?php foreach ($collections as $collection): ?>
        <div class="collection-card" onclick="location.href='collections.php?slug=<?= $collection['slug'] ?>'">
            <div class="collection-cover" style="background-image: linear-gradient(to bottom, rgba(0,0,0,0.3), rgba(0,0,0,0.9)), url('<?= htmlspecialchars($collection['cover_image'] ?: 'https://via.placeholder.com/400x200?text=' . urlencode($collection['name'])) ?>')">
                <div class="overlay">
                    <div class="movie-count"><?= $collection['movie_count'] ?> Movies</div>
                </div>
            </div>
            <div class="collection-info">
                <h3><?= htmlspecialchars($collection['name']) ?></h3>
                <p><?= htmlspecialchars(substr($collection['description'] ?? 'Explore this curated collection', 0, 100)) ?>...</p>
            </div>
        </div>
        <?php endforeach; ?>
        
        <?php if (empty($collections)): ?>
        <div class="empty-state" style="grid-column:1/-1">
            <h3>No collections available</h3>
            <p>Check back soon for curated movie collections!</p>
        </div>
        <?php endif; ?>
    </div>
<?php endif; ?>


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