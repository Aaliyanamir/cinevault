<?php
require_once __DIR__ . '/includes/config.php';

if (!isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$db = getDB();
$userId = $_SESSION['user_id'];

// Get watchlist movies
$stmt = $db->prepare("
    SELECT m.*, w.added_at
    FROM watchlist w
    JOIN movies m ON w.movie_id = m.id
    WHERE w.user_id = ?
    ORDER BY w.added_at DESC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$watchlist = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Attach genres
foreach ($watchlist as &$movie) {
    $genreStmt = $db->prepare("
        SELECT g.name, g.slug FROM movie_genres mg 
        JOIN genres g ON mg.genre_id = g.id 
        WHERE mg.movie_id = ?
    ");
    $genreStmt->bind_param("i", $movie['id']);
    $genreStmt->execute();
    $movie['genres'] = $genreStmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$loggedIn = true;
$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Watchlist - <?= SITE_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
:root {
  --black: #080808; --dark-2: #141414; --dark-3: #1a1a1a; --dark-4: #222;
  --red: #c0392b; --red-light: #e74c3c; --text: #f0f0f0; --text-sec: #a0a0a0;
  --text-muted: #555; --border: #2a2a2a; --gold: #f0b429;
  --font-display: 'Bebas Neue', sans-serif; --font-body: 'Inter', sans-serif;
}
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:var(--font-body);background:var(--black);color:var(--text)}
a{text-decoration:none;color:inherit}

/* Navbar */
.navbar{position:fixed;top:0;left:0;right:0;z-index:1000;height:64px;display:flex;align-items:center;padding:0 40px;background:rgba(8,8,8,0.97);backdrop-filter:blur(12px);border-bottom:1px solid rgba(255,255,255,0.04)}
.nav-logo{font-family:var(--font-display);font-size:26px;letter-spacing:3px}
.nav-logo span{color:var(--red)}
.nav-links{display:flex;gap:24px;margin-left:36px;list-style:none}
.nav-links a{font-size:13px;color:var(--text-sec);transition:color 0.2s}
.nav-links a:hover,.nav-links a.active{color:var(--text)}
.nav-right{margin-left:auto;display:flex;align-items:center;gap:12px}
.user-menu{position:relative}
.user-trigger{display:flex;align-items:center;gap:8px;background:var(--dark-3);border:none;padding:6px 12px;border-radius:6px;color:var(--text);cursor:pointer}
.user-avatar{width:30px;height:30px;border-radius:50%;background:var(--red);display:flex;align-items:center;justify-content:center;font-weight:bold}
.user-dropdown{position:absolute;top:100%;right:0;background:var(--dark-3);border:1px solid var(--border);border-radius:8px;min-width:150px;display:none}
.user-menu:hover .user-dropdown{display:block}
.user-dropdown a{display:block;padding:10px 15px;font-size:13px;color:var(--text-sec)}
.user-dropdown a:hover{background:var(--dark-4)}

/* Page Header */
.page-header{padding:100px 40px 40px}
.page-header h1{font-family:var(--font-display);font-size:48px;letter-spacing:3px}
.page-header h1 span{color:var(--red)}
.page-header p{color:var(--text-muted);margin-top:10px}

/* Movies Grid */
.movies-section{padding:20px 40px 60px}
.movies-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:20px}
.movie-card{background:var(--dark-3);border-radius:12px;overflow:hidden;cursor:pointer;transition:transform 0.2s}
.movie-card:hover{transform:translateY(-5px)}
.movie-card img{width:100%;aspect-ratio:2/3;object-fit:cover}
.card-info{padding:12px}
.card-info h4{font-size:14px;margin-bottom:4px}
.card-info .year{font-size:11px;color:var(--text-muted)}
.rating{color:var(--gold);font-size:12px;margin-top:4px}
.remove-btn{width:100%;margin-top:10px;padding:8px;background:rgba(192,57,43,0.2);border:1px solid var(--red);border-radius:6px;color:var(--red-light);font-size:12px;cursor:pointer;transition:all 0.2s}
.remove-btn:hover{background:var(--red);color:#fff}

/* Empty State */
.empty-state{text-align:center;padding:80px 20px}
.empty-state h3{font-size:28px;margin-bottom:10px;color:var(--text-muted)}
.empty-state p{color:var(--text-muted);margin-bottom:20px}
.empty-state a{display:inline-block;padding:12px 24px;background:var(--red);border-radius:8px;color:#fff}

@media (max-width:768px){
    .navbar{padding:0 20px}
    .nav-links{display:none}
    .page-header{padding:80px 20px 20px}
    .movies-section{padding:20px}
    .movies-grid{grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:12px}
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
    <li><a href="about.php" class="active">About</a></li>
  </ul>
  <div class="nav-right">
    <div class="user-menu">
      <button class="user-trigger">
        <div class="user-avatar"><?= strtoupper(substr($username, 0, 1)) ?></div>
        <span><?= htmlspecialchars($username) ?></span>
      </button>
      <div class="user-dropdown">
        <a href="profile.php">My Profile</a>
        <a href="watchlist.php" class="active">My Watchlist</a>
        <a href="javascript:void(0)" onclick="logout()">Logout</a>
      </div>
    </div>
  </div>
</nav>

<div class="page-header">
  <h1>MY <span>WATCHLIST</span></h1>
  <p>Movies you've saved to watch later</p>
</div>

<div class="movies-section">
  <?php if (empty($watchlist)): ?>
  <div class="empty-state">
    <h3>Your watchlist is empty</h3>
    <p>Start adding movies to your watchlist</p>
    <a href="movies.php">Browse Movies</a>
  </div>
  <?php else: ?>
  <div class="movies-grid">
    <?php foreach ($watchlist as $movie): ?>
    <div class="movie-card">
      <div onclick="location.href='movie-detail.php?slug=<?= $movie['slug'] ?>'">
        <img src="<?= htmlspecialchars($movie['poster'] ?: 'https://via.placeholder.com/300x450?text=No+Poster') ?>" alt="<?= htmlspecialchars($movie['title']) ?>">
        <div class="card-info">
          <h4><?= htmlspecialchars($movie['title']) ?></h4>
          <div class="year"><?= $movie['year'] ?> • <?= $movie['quality'] ?></div>
          <div class="rating">⭐ <?= $movie['rating'] ?>/10</div>
        </div>
      </div>
      <button class="remove-btn" onclick="removeFromWatchlist(<?= $movie['id'] ?>, this)">Remove from Watchlist</button>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<script>
async function removeFromWatchlist(movieId, btn) {
    const res = await fetch('pages/watchlist.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=remove&movie_id=${movieId}`
    });
    const data = await res.json();
    if (data.success) {
        btn.closest('.movie-card').remove();
        location.reload();
    }
}

async function logout() {
    await fetch('pages/auth.php', { method: 'POST', body: new URLSearchParams({ action: 'logout' }) });
    location.href = 'index.php';
}
</script>
</body>
</html>