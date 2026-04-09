<?php
require_once __DIR__ . '/includes/config.php';

// Agar user login nahi hai to redirect
if (!isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$db = getDB();
$userId = $_SESSION['user_id'];
$user = getUser($userId);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $theme = $_POST['theme'] ?? 'dark';
        
        if (!empty($username) && !empty($email)) {
            $stmt = $db->prepare("UPDATE users SET username = ?, email = ?, theme = ? WHERE id = ?");
            $stmt->bind_param("sssi", $username, $email, $theme, $userId);
            $stmt->execute();
            $_SESSION['username'] = $username;
            $flashMsg = "Profile updated!";
        }
    }
    
    if ($action === 'change_password') {
        $oldPass = $_POST['old_password'] ?? '';
        $newPass = $_POST['new_password'] ?? '';
        
        $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $userData = $stmt->get_result()->fetch_assoc();
        
        if (password_verify($oldPass, $userData['password'])) {
            $newHash = password_hash($newPass, PASSWORD_BCRYPT);
            $update = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update->bind_param("si", $newHash, $userId);
            $update->execute();
            $flashMsg = "Password changed!";
        } else {
            $errorMsg = "Current password is incorrect";
        }
    }
}

// Get user stats
$stats = [];
$stats['watchlist_count'] = $db->query("SELECT COUNT(*) FROM watchlist WHERE user_id = $userId")->fetch_row()[0];
$stats['ratings_count'] = $db->query("SELECT COUNT(*) FROM user_ratings WHERE user_id = $userId")->fetch_row()[0];
$stats['comments_count'] = $db->query("SELECT COUNT(*) FROM comments WHERE user_id = $userId")->fetch_row()[0];

// Get watch history
$watchHistory = getContinueWatching($userId, 10);

// Get user ratings
$ratings = $db->prepare("
    SELECT ur.*, m.title, m.slug, m.poster, m.year 
    FROM user_ratings ur 
    JOIN movies m ON ur.movie_id = m.id 
    WHERE ur.user_id = ? 
    ORDER BY ur.created_at DESC 
    LIMIT 10
");
$ratings->bind_param("i", $userId);
$ratings->execute();
$userRatings = $ratings->get_result()->fetch_all(MYSQLI_ASSOC);

$loggedIn = true;
$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Profile - <?= SITE_NAME ?></title>
<style>
:root {
  --black: #080808; --dark-2: #141414; --dark-3: #1a1a1a; --dark-4: #222;
  --red: #c0392b; --red-light: #e74c3c; --text: #f0f0f0; --text-sec: #a0a0a0;
  --text-muted: #555; --border: #2a2a2a; --gold: #f0b429;
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
.nav-links a:hover{color:var(--text)}
.nav-right{margin-left:auto;display:flex;align-items:center;gap:12px}
.btn-outline{padding:7px 16px;background:transparent;border:1px solid var(--border);color:var(--text-sec);border-radius:6px;cursor:pointer}
.user-menu{position:relative}
.user-trigger{display:flex;align-items:center;gap:8px;background:var(--dark-3);border:none;padding:6px 12px;border-radius:6px;color:var(--text);cursor:pointer}
.user-avatar{width:30px;height:30px;border-radius:50%;background:var(--red);display:flex;align-items:center;justify-content:center;font-weight:bold}
.user-dropdown{position:absolute;top:100%;right:0;background:var(--dark-3);border:1px solid var(--border);border-radius:8px;min-width:150px;display:none}
.user-menu:hover .user-dropdown{display:block}
.user-dropdown a{display:block;padding:10px 15px;font-size:13px;color:var(--text-sec)}
.user-dropdown a:hover{background:var(--dark-4)}

/* Main Container */
.main-container{padding:100px 40px 60px;max-width:1200px;margin:0 auto}

/* Profile Header */
.profile-header{display:flex;align-items:center;gap:30px;margin-bottom:40px;padding:30px;background:var(--dark-3);border-radius:16px;border:1px solid var(--border)}
.profile-avatar{width:100px;height:100px;border-radius:50%;background:var(--red);display:flex;align-items:center;justify-content:center;font-size:48px;font-weight:bold}
.profile-info h1{font-size:32px;margin-bottom:8px}
.profile-info p{color:var(--text-muted)}
.stats-grid{display:flex;gap:20px;margin-top:15px}
.stat-badge{background:var(--dark-4);padding:5px 12px;border-radius:20px;font-size:12px}

/* Tabs */
.tabs{display:flex;gap:5px;margin-bottom:30px;border-bottom:1px solid var(--border)}
.tab-btn{padding:12px 24px;background:none;border:none;color:var(--text-sec);cursor:pointer;font-size:14px;transition:all 0.2s}
.tab-btn.active{color:var(--red);border-bottom:2px solid var(--red)}

/* Tab Content */
.tab-content{display:none}
.tab-content.active{display:block}

/* Form Styles */
.form-card{background:var(--dark-3);border:1px solid var(--border);border-radius:16px;padding:30px;margin-bottom:30px}
.form-group{margin-bottom:20px}
.form-group label{display:block;font-size:12px;font-weight:600;text-transform:uppercase;color:var(--text-muted);margin-bottom:8px}
.form-group input, .form-group select{width:100%;padding:12px;background:var(--dark-4);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:14px}
.btn-submit{padding:12px 24px;background:var(--red);border:none;border-radius:8px;color:#fff;font-weight:600;cursor:pointer}
.btn-submit:hover{background:var(--red-light)}

/* Movie Grid */
.movies-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:16px}
.movie-card{background:var(--dark-3);border-radius:12px;overflow:hidden;cursor:pointer;transition:transform 0.2s}
.movie-card:hover{transform:translateY(-5px)}
.movie-card img{width:100%;aspect-ratio:2/3;object-fit:cover}
.card-info{padding:10px}
.card-info h4{font-size:14px;margin-bottom:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.card-info .year{font-size:11px;color:var(--text-muted)}
.rating-badge{color:var(--gold);font-size:12px;margin-top:5px}

/* Flash Message */
.flash{padding:12px 18px;border-radius:8px;margin-bottom:20px;background:rgba(39,174,96,0.1);border-left:3px solid #27ae60;color:#2ecc71}
.error{background:rgba(192,57,43,0.1);border-left-color:var(--red);color:var(--red-light)}

/* Empty State */
.empty-state{text-align:center;padding:60px 20px;color:var(--text-muted)}
.empty-state h3{font-size:20px;margin-bottom:10px}
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
        <a href="javascript:void(0)" onclick="logout()">Logout</a>
      </div>
    </div>
  </div>
</nav>

<div class="main-container">
    <?php if (isset($flashMsg)): ?>
    <div class="flash"><?= $flashMsg ?></div>
    <?php endif; ?>
    <?php if (isset($errorMsg)): ?>
    <div class="flash error"><?= $errorMsg ?></div>
    <?php endif; ?>

    <!-- Profile Header -->
    <div class="profile-header">
        <div class="profile-avatar"><?= strtoupper(substr($username, 0, 1)) ?></div>
        <div class="profile-info">
            <h1><?= htmlspecialchars($username) ?></h1>
            <p><?= htmlspecialchars($user['email']) ?></p>
            <div class="stats-grid">
                <div class="stat-badge">📋 Watchlist: <?= $stats['watchlist_count'] ?></div>
                <div class="stat-badge">⭐ Ratings: <?= $stats['ratings_count'] ?></div>
                <div class="stat-badge">💬 Comments: <?= $stats['comments_count'] ?></div>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="tabs">
        <button class="tab-btn active" onclick="showTab('profile')">Profile Settings</button>
        <button class="tab-btn" onclick="showTab('history')">Continue Watching</button>
        <button class="tab-btn" onclick="showTab('ratings')">My Ratings</button>
        <button class="tab-btn" onclick="showTab('password')">Change Password</button>
    </div>

    <!-- Tab 1: Profile Settings -->
    <div id="tab-profile" class="tab-content active">
        <div class="form-card">
            <h3 style="margin-bottom:20px">Edit Profile</h3>
            <form method="POST">
                <input type="hidden" name="action" value="update_profile">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Theme Preference</label>
                    <select name="theme">
                        <option value="dark" <?= $user['theme'] == 'dark' ? 'selected' : '' ?>>Dark</option>
                        <option value="dim" <?= $user['theme'] == 'dim' ? 'selected' : '' ?>>Dim</option>
                        <option value="light" <?= $user['theme'] == 'light' ? 'selected' : '' ?>>Light</option>
                    </select>
                </div>
                <button type="submit" class="btn-submit">Save Changes</button>
            </form>
        </div>
    </div>

    <!-- Tab 2: Continue Watching -->
    <div id="tab-history" class="tab-content">
        <div class="form-card">
            <h3 style="margin-bottom:20px">Continue Watching</h3>
            <?php if (empty($watchHistory)): ?>
                <div class="empty-state">
                    <h3>No watch history yet</h3>
                    <p>Start watching movies to see them here</p>
                </div>
            <?php else: ?>
                <div class="movies-grid">
                <?php foreach ($watchHistory as $movie): ?>
                    <div class="movie-card" onclick="location.href='movie-detail.php?slug=<?= $movie['slug'] ?>'">
                        <img src="<?= htmlspecialchars($movie['poster']) ?>" alt="<?= htmlspecialchars($movie['title']) ?>">
                        <div class="card-info">
                            <h4><?= htmlspecialchars($movie['title']) ?></h4>
                            <span class="year"><?= $movie['year'] ?></span>
                            <?php if ($movie['progress'] > 0): ?>
                            <div class="progress-bar" style="margin-top:8px">
                                <div style="background:var(--red);height:3px;width:<?= min(100, ($movie['progress'] / ($movie['duration'] * 60)) * 100) ?>%;border-radius:3px"></div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tab 3: My Ratings -->
    <div id="tab-ratings" class="tab-content">
        <div class="form-card">
            <h3 style="margin-bottom:20px">Movies I've Rated</h3>
            <?php if (empty($userRatings)): ?>
                <div class="empty-state">
                    <h3>No ratings yet</h3>
                    <p>Rate movies to share your opinion</p>
                </div>
            <?php else: ?>
                <div class="movies-grid">
                <?php foreach ($userRatings as $rating): ?>
                    <div class="movie-card" onclick="location.href='movie-detail.php?slug=<?= $rating['slug'] ?>'">
                        <img src="<?= htmlspecialchars($rating['poster']) ?>" alt="<?= htmlspecialchars($rating['title']) ?>">
                        <div class="card-info">
                            <h4><?= htmlspecialchars($rating['title']) ?></h4>
                            <span class="year"><?= $rating['year'] ?></span>
                            <div class="rating-badge">⭐ <?= $rating['rating'] ?>/10</div>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tab 4: Change Password -->
    <div id="tab-password" class="tab-content">
        <div class="form-card">
            <h3 style="margin-bottom:20px">Change Password</h3>
            <form method="POST">
                <input type="hidden" name="action" value="change_password">
                <div class="form-group">
                    <label>Current Password</label>
                    <input type="password" name="old_password" required>
                </div>
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" required minlength="6">
                </div>
                <button type="submit" class="btn-submit">Update Password</button>
            </form>
        </div>
    </div>
</div>

<script>
function showTab(tab) {
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById(`tab-${tab}`).classList.add('active');
    event.target.classList.add('active');
}

async function logout() {
    await fetch('pages/auth.php', { method: 'POST', body: new URLSearchParams({ action: 'logout' }) });
    location.reload();
}
</script>
</body>
</html>