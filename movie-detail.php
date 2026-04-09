<?php
require_once __DIR__ . '/includes/config.php';

$slug = $_GET['slug'] ?? '';
if (empty($slug)) {
    header('Location: index.php');
    exit;
}

$db = getDB();
$stmt = $db->prepare("SELECT * FROM movies WHERE slug = ?");
$stmt->bind_param("s", $slug);
$stmt->execute();
$movie = $stmt->get_result()->fetch_assoc();

if (!$movie) {
    header('Location: index.php');
    exit;
}

// Get genres
$genreStmt = $db->prepare("SELECT g.name, g.slug FROM movie_genres mg JOIN genres g ON mg.genre_id = g.id WHERE mg.movie_id = ?");
$genreStmt->bind_param("i", $movie['id']);
$genreStmt->execute();
$genres = $genreStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$loggedIn = isLoggedIn();
$username = $_SESSION['username'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($movie['title']) ?> - <?= SITE_NAME ?></title>
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

.navbar{position:fixed;top:0;left:0;right:0;z-index:1000;height:64px;display:flex;align-items:center;padding:0 40px;background:rgba(8,8,8,0.97);backdrop-filter:blur(12px);border-bottom:1px solid rgba(255,255,255,0.04)}
.nav-logo{font-family:var(--font-display);font-size:26px;letter-spacing:3px}
.nav-logo span{color:var(--red)}
.nav-links{display:flex;gap:24px;margin-left:36px;list-style:none}
.nav-links a{font-size:13px;color:var(--text-sec);transition:color 0.2s}
.nav-links a:hover{color:var(--text)}
.nav-right{margin-left:auto;display:flex;align-items:center;gap:12px}
.user-menu{position:relative}
.user-trigger{display:flex;align-items:center;gap:8px;background:var(--dark-3);border:none;padding:6px 12px;border-radius:6px;color:var(--text);cursor:pointer}
.user-avatar{width:30px;height:30px;border-radius:50%;background:var(--red);display:flex;align-items:center;justify-content:center;font-weight:bold}
.user-dropdown{position:absolute;top:100%;right:0;background:var(--dark-3);border:1px solid var(--border);border-radius:8px;min-width:150px;display:none}
.user-menu:hover .user-dropdown{display:block}
.user-dropdown a{display:block;padding:10px 15px;font-size:13px;color:var(--text-sec)}

.movie-hero{min-height:80vh;display:flex;align-items:center;padding:100px 60px 60px;background-size:cover;background-position:center}
.movie-hero-content{max-width:600px}
.movie-hero-content h1{font-family:var(--font-display);font-size:64px;letter-spacing:3px;margin-bottom:20px}
.movie-meta{display:flex;gap:15px;margin-bottom:20px;flex-wrap:wrap}
.movie-meta span{background:var(--dark-4);padding:5px 12px;border-radius:20px;font-size:12px}
.description{font-size:16px;line-height:1.8;color:var(--text-sec);margin-bottom:30px}
.action-buttons{display:flex;gap:15px}
.btn-watch{background:var(--red);color:#fff;padding:12px 28px;border:none;border-radius:8px;font-size:16px;font-weight:600;cursor:pointer}
.btn-watchlist{background:rgba(255,255,255,0.1);color:#fff;padding:12px 28px;border:1px solid var(--border);border-radius:8px;font-size:16px;cursor:pointer}

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
    .movie-hero{padding:80px 20px 40px}
    .movie-hero-content h1{font-size:40px}
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
    <?php if ($loggedIn): ?>
    <div class="user-menu">
      <button class="user-trigger">
        <div class="user-avatar"><?= strtoupper(substr($username, 0, 1)) ?></div>
        <span><?= htmlspecialchars($username) ?></span>
      </button>
      <div class="user-dropdown">
        <a href="profile.php">My Profile</a>
        <a href="watchlist.php">My Watchlist</a>
        <a href="javascript:void(0)" onclick="logout()">Logout</a>
      </div>
    </div>
    <?php else: ?>
    <button class="btn-outline" onclick="openAuth()">Sign In</button>
    <?php endif; ?>
  </div>
</nav>

<div class="movie-hero" style="background-image: linear-gradient(to right, rgba(8,8,8,0.95), rgba(8,8,8,0.7)), url('<?= $movie['backdrop'] ?>')">
  <div class="movie-hero-content">
    <h1><?= htmlspecialchars($movie['title']) ?></h1>
    <div class="movie-meta">
      <span><?= $movie['year'] ?></span>
      <span><?= floor($movie['duration']/60) ?>h <?= $movie['duration']%60 ?>m</span>
      <span><?= $movie['quality'] ?></span>
      <span><?= $movie['language'] ?></span>
      <span>⭐ <?= $movie['rating'] ?>/10</span>
    </div>
    <p class="description"><?= nl2br(htmlspecialchars($movie['description'])) ?></p>
    <div class="action-buttons">
      <button class="btn-watch" onclick="openPlayer()">▶ Watch Now</button>
      <button class="btn-watchlist" onclick="toggleWatchlist(<?= $movie['id'] ?>)">+ Add to Watchlist</button>
    </div>
  </div>
</div>

<!-- Rating Section -->
<div class="rating-section" style="padding: 40px; background: #141414; border-radius: 12px; margin: 30px 40px;">
    <h3 style="font-family: 'Bebas Neue', sans-serif; font-size: 24px; margin-bottom: 20px;">⭐ Rate this Movie</h3>
    
    <?php if ($loggedIn): ?>
    <div>
        <div class="star-rating" id="starRating" style="display: flex; gap: 8px; margin-bottom: 15px; flex-wrap: wrap;">
            <?php for ($i = 1; $i <= 10; $i++): ?>
            <span class="star" data-value="<?= $i ?>" style="font-size: 28px; cursor: pointer; color: #555; transition: color 0.2s;">★</span>
            <?php endfor; ?>
        </div>
        <textarea id="reviewText" placeholder="Write your review (optional)..." style="width: 100%; padding: 12px; background: #1a1a1a; border: 1px solid #333; border-radius: 8px; color: #fff; resize: vertical; margin-bottom: 10px;" rows="3"></textarea>
        <button onclick="submitRating(<?= $movie['id'] ?>)" style="padding: 10px 20px; background: #c0392b; border: none; border-radius: 6px; color: #fff; cursor: pointer;">Submit Rating</button>
        <div id="ratingMsg" style="margin-top: 10px; font-size: 13px;"></div>
    </div>
    <?php else: ?>
    <div style="background: #1a1a1a; padding: 20px; text-align: center; border-radius: 8px;">
        <p>Please <a href="login.php" style="color: #c0392b;">login</a> to rate this movie.</p>
    </div>
    <?php endif; ?>
    
    <div id="ratingStats" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #333;">
        <div style="display: flex; align-items: center; gap: 20px; flex-wrap: wrap;">
            <div style="font-size: 42px; font-weight: bold; color: #f0b429;" id="avgRating">0.0</div>
            <div><span id="ratingCount">0</span> user ratings</div>
        </div>
    </div>
</div>

<!-- Comments Section -->
<div class="comments-section" style="padding: 40px; background: #141414; border-radius: 12px; margin: 30px 40px;">
    <h3 style="font-family: 'Bebas Neue', sans-serif; font-size: 24px; margin-bottom: 20px;">💬 User Comments</h3>
    
    <!-- Comment Form -->
    <?php if ($loggedIn): ?>
    <div class="comment-form" style="margin-bottom: 30px;">
        <textarea id="commentText" placeholder="Share your thoughts about this movie..." style="width: 100%; padding: 12px; background: #1a1a1a; border: 1px solid #333; border-radius: 8px; color: #fff; resize: vertical; margin-bottom: 10px;" rows="3"></textarea>
        <button onclick="submitComment(<?= $movie['id'] ?>)" style="padding: 10px 20px; background: #c0392b; border: none; border-radius: 6px; color: #fff; cursor: pointer;">Post Comment</button>
    </div>
    <?php else: ?>
    <div style="background: #1a1a1a; padding: 20px; text-align: center; border-radius: 8px; margin-bottom: 30px;">
        <p>Please <a href="login.php" style="color: #c0392b;">login</a> to leave a comment.</p>
    </div>
    <?php endif; ?>
    
    <!-- Comments List -->
    <div id="commentsList" style="max-height: 500px; overflow-y: auto;">
        <div style="text-align: center; padding: 40px; color: #777;">Loading comments...</div>
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


<!-- Video Modal -->
<div class="modal-overlay" id="videoModal" onclick="closeModalOutside(event)" style="position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.95);display:none;align-items:center;justify-content:center">
  <div style="background:var(--dark-2);border-radius:12px;width:90%;max-width:1000px;position:relative">
    <button onclick="closePlayer()" style="position:absolute;top:-40px;right:0;background:none;border:none;color:#fff;font-size:30px;cursor:pointer">&times;</button>
    <iframe id="playerIframe" src="" width="100%" height="500px" frameborder="0" allowfullscreen></iframe>
  </div>
</div>

<script>
function openPlayer() {
    document.getElementById('videoModal').style.display = 'flex';
    document.getElementById('playerIframe').src = '<?= $movie['embed_url'] ?>';
    fetch('pages/movies.php', {method:'POST', body: new URLSearchParams({action:'increment_views', id:<?= $movie['id'] ?>}) });
}
function closePlayer() {
    document.getElementById('videoModal').style.display = 'none';
    document.getElementById('playerIframe').src = '';
}
function closeModalOutside(e) { if(e.target === document.getElementById('videoModal')) closePlayer(); }

async function toggleWatchlist(movieId) {
    <?php if (!$loggedIn): ?>
    alert('Please login to add to watchlist');
    return;
    <?php endif; ?>
    const res = await fetch('pages/watchlist.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=add&movie_id=${movieId}`
    });
    const data = await res.json();
    alert(data.message || (data.success ? 'Added to watchlist' : 'Error'));
}

async function logout() {
    await fetch('pages/auth.php', { method: 'POST', body: new URLSearchParams({ action: 'logout' }) });
    location.href = 'index.php';
}
function openAuth() { alert('Please login to continue'); }
</script>


<script>
// Star Rating
let currentRating = 0;
const stars = document.querySelectorAll('.star-rating .star');
if (stars.length) {
    stars.forEach(star => {
        star.addEventListener('click', function() {
            currentRating = parseInt(this.dataset.value);
            stars.forEach(s => {
                if (parseInt(s.dataset.value) <= currentRating) {
                    s.style.color = '#f0b429';
                } else {
                    s.style.color = '#555';
                }
            });
        });
    });
}

// Load current rating
async function loadRating(movieId) {
    try {
        const response = await fetch(`api/ratings.php?action=get&movie_id=${movieId}`);
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('avgRating').innerText = data.average.toFixed(1);
            document.getElementById('ratingCount').innerText = data.count;
            
            if (data.user_rating) {
                currentRating = data.user_rating.rating;
                stars.forEach(s => {
                    if (parseInt(s.dataset.value) <= currentRating) {
                        s.style.color = '#f0b429';
                    } else {
                        s.style.color = '#555';
                    }
                });
                if (data.user_rating.review) {
                    document.getElementById('reviewText').value = data.user_rating.review;
                }
            }
        }
    } catch (error) {
        console.error('Error loading rating:', error);
    }
}

// Submit rating
async function submitRating(movieId) {
    if (currentRating === 0) {
        document.getElementById('ratingMsg').innerHTML = '<span style="color: #e74c3c;">Please select a rating</span>';
        return;
    }
    
    const review = document.getElementById('reviewText').value;
    const response = await fetch('api/ratings.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=submit&movie_id=${movieId}&rating=${currentRating}&review=${encodeURIComponent(review)}`
    });
    const data = await response.json();
    
    if (data.success) {
        document.getElementById('ratingMsg').innerHTML = '<span style="color: #2ecc71;">✅ Rating submitted! Thank you for your feedback.</span>';
        loadRating(movieId);
    } else {
        document.getElementById('ratingMsg').innerHTML = '<span style="color: #e74c3c;">❌ ' + data.error + '</span>';
    }
}

// Load rating on page load
loadRating(<?= $movie['id'] ?>);
</script>
<script>
// Load Comments
async function loadComments(movieId) {
    try {
        const response = await fetch(`api/comments.php?action=get&movie_id=${movieId}`);
        const data = await response.json();
        
        const container = document.getElementById('commentsList');
        
        if (data.success && data.comments.length > 0) {
            container.innerHTML = data.comments.map(comment => `
                <div style="background: #1a1a1a; border-radius: 8px; padding: 15px; margin-bottom: 15px;">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                        <div style="width: 35px; height: 35px; background: #c0392b; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold;">${comment.username.charAt(0).toUpperCase()}</div>
                        <div>
                            <strong>${comment.username}</strong>
                            <div style="font-size: 11px; color: #777;">${new Date(comment.created_at).toLocaleDateString()}</div>
                        </div>
                    </div>
                    <p style="color: #a0a0a0; line-height: 1.6;">${comment.comment}</p>
                    ${comment.replies && comment.replies.length > 0 ? `
                        <div style="margin-left: 45px; margin-top: 15px; padding-left: 15px; border-left: 2px solid #c0392b;">
                            ${comment.replies.map(reply => `
                                <div style="margin-bottom: 10px;">
                                    <strong>${reply.username}</strong>
                                    <p style="color: #777; font-size: 13px; margin-top: 5px;">${reply.comment}</p>
                                </div>
                            `).join('')}
                        </div>
                    ` : ''}
                </div>
            `).join('');
        } else {
            container.innerHTML = '<div style="text-align: center; padding: 40px; color: #777;">No comments yet. Be the first to comment!</div>';
        }
    } catch (error) {
        document.getElementById('commentsList').innerHTML = '<div style="text-align: center; padding: 40px; color: #777;">Error loading comments</div>';
    }
}

// Submit Comment
async function submitComment(movieId) {
    const comment = document.getElementById('commentText').value;
    if (!comment.trim()) {
        alert('Please enter a comment');
        return;
    }
    
    const response = await fetch('api/comments.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=add&movie_id=${movieId}&comment=${encodeURIComponent(comment)}`
    });
    const data = await response.json();
    
    if (data.success) {
        document.getElementById('commentText').value = '';
        loadComments(movieId);
        alert(data.message);
    } else {
        alert(data.error);
    }
}

// Load comments on page load
loadComments(<?= $movie['id'] ?>);
</script>
</body>
</html>