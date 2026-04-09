<?php
// ============================================================
// CineVault Admin Panel - With Live Charts
// Access: yoursite.com/movies-site/admin.php
// ============================================================
define('ADMIN_PASS', 'admin123'); // CHANGE THIS

session_start();

// Login handling
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_login'])) {
    if ($_POST['admin_pass'] === ADMIN_PASS) { 
        $_SESSION['cv_admin'] = true; 
        $_SESSION['user_role'] = 'admin';
        header('Location: admin.php'); 
        exit; 
    }
    else { $loginError = 'Wrong password.'; }
}

if (isset($_GET['logout'])) { 
    unset($_SESSION['cv_admin']); 
    session_destroy();
    header('Location: admin.php'); 
    exit; 
}

if (empty($_SESSION['cv_admin'])) { 
    showLogin($loginError ?? ''); 
    exit; 
}

require_once __DIR__ . '/includes/config.php';
$db = getDB();

$flashMsg = ''; 
$flashType = 'success';
$tab = $_GET['tab'] ?? 'dashboard';

// Handle POST requests (same as before - keeping it compact)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['admin_login'])) {
    $act = $_POST['form_action'] ?? '';
    
    if ($act === 'save_movie') {
        $id = intval($_POST['movie_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $poster = trim($_POST['poster'] ?? '');
        $backdrop = trim($_POST['backdrop'] ?? '');
        $embedUrl = trim($_POST['embed_url'] ?? '');
        $trailerUrl = trim($_POST['trailer_url'] ?? '');
        $year = intval($_POST['year'] ?? 0);
        $duration = intval($_POST['duration'] ?? 0);
        $rating = floatval($_POST['rating'] ?? 0);
        $imdb = floatval($_POST['imdb_rating'] ?? 0);
        $director = trim($_POST['director'] ?? '');
        $cast = trim($_POST['cast_members'] ?? '');
        $lang = trim($_POST['language'] ?? 'English');
        $quality = trim($_POST['quality'] ?? 'HD');
        $featured = isset($_POST['is_featured']) ? 1 : 0;
        $trending = isset($_POST['is_trending']) ? 1 : 0;
        $upcoming = isset($_POST['is_upcoming']) ? 1 : 0;
        $releaseDate = !empty($_POST['release_date']) ? $_POST['release_date'] : null;
        $metaTitle = trim($_POST['meta_title'] ?? '');
        $metaDesc = trim($_POST['meta_description'] ?? '');
        $metaKeywords = trim($_POST['meta_keywords'] ?? '');
        
        if (empty($title) || empty($embedUrl)) { 
            $flashMsg = 'Title and Embed URL are required.'; 
            $flashType = 'error'; 
        } else {
            if (empty($slug)) {
                $slug = trim(strtolower(preg_replace('/[^a-z0-9]+/', '-', $title)), '-');
            }
            
            if ($id) {
                $s = $db->prepare("UPDATE movies SET title=?, slug=?, description=?, poster=?, backdrop=?, embed_url=?, trailer_url=?, year=?, duration=?, rating=?, imdb_rating=?, director=?, cast_members=?, language=?, quality=?, is_featured=?, is_trending=?, is_upcoming=?, release_date=?, meta_title=?, meta_description=?, meta_keywords=? WHERE id=?");
                $s->bind_param("sssssssiiddssssiissssi", $title, $slug, $desc, $poster, $backdrop, $embedUrl, $trailerUrl, $year, $duration, $rating, $imdb, $director, $cast, $lang, $quality, $featured, $trending, $upcoming, $releaseDate, $metaTitle, $metaDesc, $metaKeywords, $id);
                $s->execute();
                $movieId = $id;
                $flashMsg = 'Movie updated!';
            } else {
                $s = $db->prepare("INSERT INTO movies (title, slug, description, poster, backdrop, embed_url, trailer_url, year, duration, rating, imdb_rating, director, cast_members, language, quality, is_featured, is_trending, is_upcoming, release_date, meta_title, meta_description, meta_keywords) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
                $s->bind_param("sssssssiiddssssiissss", $title, $slug, $desc, $poster, $backdrop, $embedUrl, $trailerUrl, $year, $duration, $rating, $imdb, $director, $cast, $lang, $quality, $featured, $trending, $upcoming, $releaseDate, $metaTitle, $metaDesc, $metaKeywords);
                $s->execute();
                $movieId = $s->insert_id;
                $flashMsg = 'Movie added!';
            }
            
            $d = $db->prepare("DELETE FROM movie_genres WHERE movie_id=?");
            $d->bind_param("i", $movieId);
            $d->execute();
            
            foreach (($_POST['genres'] ?? []) as $gid) {
                $ins = $db->prepare("INSERT IGNORE INTO movie_genres (movie_id, genre_id) VALUES (?,?)");
                $gidInt = intval($gid);
                $ins->bind_param("ii", $movieId, $gidInt);
                $ins->execute();
            }
        }
        header('Location: admin.php?tab=movies&msg=' . urlencode($flashMsg) . '&type=' . $flashType);
        exit;
    }
    
    if ($act === 'delete_movie') {
        $id = intval($_POST['movie_id'] ?? 0);
        $s = $db->prepare("DELETE FROM movies WHERE id=?");
        $s->bind_param("i", $id);
        $s->execute();
        header('Location: admin.php?tab=movies&msg=' . urlencode('Movie deleted.') . '&type=success');
        exit;
    }
    
    if ($act === 'toggle_field') {
        $id = intval($_POST['movie_id'] ?? 0);
        $field = in_array($_POST['field'], ['is_featured', 'is_trending', 'is_upcoming']) ? $_POST['field'] : null;
        if ($id && $field) {
            $s = $db->prepare("UPDATE movies SET $field = 1-$field WHERE id=?");
            $s->bind_param("i", $id);
            $s->execute();
        }
        header('Location: admin.php?tab=movies');
        exit;
    }
    
    if ($act === 'delete_user') {
        $id = intval($_POST['user_id'] ?? 0);
        $s = $db->prepare("DELETE FROM users WHERE id=?");
        $s->bind_param("i", $id);
        $s->execute();
        header('Location: admin.php?tab=users&msg=' . urlencode('User deleted.') . '&type=success');
        exit;
    }
    
    if ($act === 'add_genre') {
        $name = trim($_POST['genre_name'] ?? '');
        $slug = trim(strtolower(preg_replace('/[^a-z0-9]+/', '-', $name)), '-');
        if ($name) {
            $s = $db->prepare("INSERT IGNORE INTO genres (name, slug) VALUES (?,?)");
            $s->bind_param("ss", $name, $slug);
            $s->execute();
        }
        header('Location: admin.php?tab=genres&msg=' . urlencode('Genre added.') . '&type=success');
        exit;
    }
    
    if ($act === 'delete_genre') {
        $id = intval($_POST['genre_id'] ?? 0);
        $s = $db->prepare("DELETE FROM genres WHERE id=?");
        $s->bind_param("i", $id);
        $s->execute();
        header('Location: admin.php?tab=genres&msg=' . urlencode('Genre deleted.') . '&type=success');
        exit;
    }
    
    if ($act === 'rename_genre') {
        $id = intval($_POST['genre_id'] ?? 0);
        $name = trim($_POST['genre_name'] ?? '');
        $slug = trim(strtolower(preg_replace('/[^a-z0-9]+/', '-', $name)), '-');
        $s = $db->prepare("UPDATE genres SET name=?, slug=? WHERE id=?");
        $s->bind_param("ssi", $name, $slug, $id);
        $s->execute();
        header('Location: admin.php?tab=genres&msg=' . urlencode('Genre renamed.') . '&type=success');
        exit;
    }
    
    if ($act === 'save_slider') {
        $id = intval($_POST['slider_id'] ?? 0);
        $movieId = intval($_POST['movie_id'] ?? 0);
        $title = trim($_POST['slider_title'] ?? '');
        $subtitle = trim($_POST['slider_subtitle'] ?? '');
        $order = intval($_POST['display_order'] ?? 0);
        $active = isset($_POST['is_active']) ? 1 : 0;
        
        if ($id) {
            $s = $db->prepare("UPDATE sliders SET movie_id=?, title=?, subtitle=?, display_order=?, is_active=? WHERE id=?");
            $s->bind_param("issiii", $movieId, $title, $subtitle, $order, $active, $id);
        } else {
            $s = $db->prepare("INSERT INTO sliders (movie_id, title, subtitle, display_order, is_active) VALUES (?,?,?,?,?)");
            $s->bind_param("issii", $movieId, $title, $subtitle, $order, $active);
        }
        $s->execute();
        header('Location: admin.php?tab=sliders&msg=' . urlencode('Slider saved.') . '&type=success');
        exit;
    }
    
    if ($act === 'delete_slider') {
        $id = intval($_POST['slider_id'] ?? 0);
        $s = $db->prepare("DELETE FROM sliders WHERE id=?");
        $s->bind_param("i", $id);
        $s->execute();
        header('Location: admin.php?tab=sliders&msg=' . urlencode('Slider deleted.') . '&type=success');
        exit;
    }
    
    if ($act === 'save_collection') {
        $id = intval($_POST['collection_id'] ?? 0);
        $name = trim($_POST['collection_name'] ?? '');
        $slug = trim($_POST['collection_slug'] ?? '');
        $desc = trim($_POST['collection_desc'] ?? '');
        $cover = trim($_POST['collection_cover'] ?? '');
        $order = intval($_POST['display_order'] ?? 0);
        
        if (empty($slug)) {
            $slug = trim(strtolower(preg_replace('/[^a-z0-9]+/', '-', $name)), '-');
        }
        
        if ($id) {
            $s = $db->prepare("UPDATE collections SET name=?, slug=?, description=?, cover_image=?, display_order=? WHERE id=?");
            $s->bind_param("ssssii", $name, $slug, $desc, $cover, $order, $id);
        } else {
            $s = $db->prepare("INSERT INTO collections (name, slug, description, cover_image, display_order) VALUES (?,?,?,?,?)");
            $s->bind_param("ssssi", $name, $slug, $desc, $cover, $order);
        }
        $s->execute();
        
        if ($id) {
            $d = $db->prepare("DELETE FROM collection_movies WHERE collection_id=?");
            $d->bind_param("i", $id);
            $d->execute();
            
            foreach (($_POST['collection_movies'] ?? []) as $order => $movieId) {
                $ins = $db->prepare("INSERT INTO collection_movies (collection_id, movie_id, display_order) VALUES (?,?,?)");
                $ins->bind_param("iii", $id, $movieId, $order);
                $ins->execute();
            }
        }
        
        header('Location: admin.php?tab=collections&msg=' . urlencode('Collection saved.') . '&type=success');
        exit;
    }
    
    if ($act === 'delete_collection') {
        $id = intval($_POST['collection_id'] ?? 0);
        $s = $db->prepare("DELETE FROM collections WHERE id=?");
        $s->bind_param("i", $id);
        $s->execute();
        header('Location: admin.php?tab=collections&msg=' . urlencode('Collection deleted.') . '&type=success');
        exit;
    }
    
    if ($act === 'approve_comment') {
        $id = intval($_POST['comment_id'] ?? 0);
        $s = $db->prepare("UPDATE comments SET is_approved=1 WHERE id=?");
        $s->bind_param("i", $id);
        $s->execute();
        header('Location: admin.php?tab=comments&msg=' . urlencode('Comment approved.') . '&type=success');
        exit;
    }
    
    if ($act === 'delete_comment') {
        $id = intval($_POST['comment_id'] ?? 0);
        $s = $db->prepare("DELETE FROM comments WHERE id=?");
        $s->bind_param("i", $id);
        $s->execute();
        header('Location: admin.php?tab=comments&msg=' . urlencode('Comment deleted.') . '&type=success');
        exit;
    }
    
    if ($act === 'approve_rating') {
        $id = intval($_POST['rating_id'] ?? 0);
        $s = $db->prepare("UPDATE user_ratings SET is_approved=1 WHERE id=?");
        $s->bind_param("i", $id);
        $s->execute();
        
        $rating = $db->query("SELECT movie_id FROM user_ratings WHERE id=$id")->fetch_assoc();
        if ($rating) {
            $avg = $db->query("SELECT AVG(rating) as avg FROM user_ratings WHERE movie_id={$rating['movie_id']} AND is_approved=1")->fetch_assoc();
            $newRating = round($avg['avg'] ?? 0, 1);
            $db->query("UPDATE movies SET rating=$newRating WHERE id={$rating['movie_id']}");
        }
        header('Location: admin.php?tab=ratings&msg=' . urlencode('Rating approved.') . '&type=success');
        exit;
    }
    
    if ($act === 'delete_rating') {
        $id = intval($_POST['rating_id'] ?? 0);
        $rating = $db->query("SELECT movie_id FROM user_ratings WHERE id=$id")->fetch_assoc();
        $s = $db->prepare("DELETE FROM user_ratings WHERE id=?");
        $s->bind_param("i", $id);
        $s->execute();
        if ($rating) {
            $avg = $db->query("SELECT AVG(rating) as avg FROM user_ratings WHERE movie_id={$rating['movie_id']} AND is_approved=1")->fetch_assoc();
            $newRating = round($avg['avg'] ?? 0, 1);
            $db->query("UPDATE movies SET rating=$newRating WHERE id={$rating['movie_id']}");
        }
        header('Location: admin.php?tab=ratings&msg=' . urlencode('Rating deleted.') . '&type=success');
        exit;
    }
    
    if ($act === 'save_settings') {
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'setting_') === 0) {
                $settingKey = substr($key, 8);
                $settingValue = is_array($value) ? json_encode($value) : $value;
                $stmt = $db->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                $stmt->bind_param("sss", $settingKey, $settingValue, $settingValue);
                $stmt->execute();
            }
        }
        header('Location: admin.php?tab=settings&msg=' . urlencode('Settings saved.') . '&type=success');
        exit;
    }
}

// Get data for display
if (isset($_GET['msg'])) { 
    $flashMsg = $_GET['msg']; 
    $flashType = $_GET['type'] ?? 'success'; 
}

// Edit movie data
$editMovie = null;
$editMovieGenres = [];
if (isset($_GET['edit_movie'])) {
    $eid = intval($_GET['edit_movie']);
    $s = $db->prepare("SELECT * FROM movies WHERE id=?");
    $s->bind_param("i", $eid);
    $s->execute();
    $editMovie = $s->get_result()->fetch_assoc();
    $gs = $db->prepare("SELECT genre_id FROM movie_genres WHERE movie_id=?");
    $gs->bind_param("i", $eid);
    $gs->execute();
    $editMovieGenres = array_column($gs->get_result()->fetch_all(MYSQLI_ASSOC), 'genre_id');
    $tab = 'add_movie';
}

// ============================================
// LIVE CHART DATA
// ============================================

// 1. Weekly Views (Last 7 days)
$weeklyViews = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $dayName = date('D', strtotime($date));
    $views = $db->query("SELECT IFNULL(SUM(views),0) as total FROM movies WHERE DATE(created_at) = '$date'")->fetch_assoc();
    $weeklyViews[] = ['day' => $dayName, 'views' => $views['total']];
}

// 2. Monthly Views (Last 6 months)
$monthlyViews = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('M', strtotime("-$i months"));
    $monthNum = date('m', strtotime("-$i months"));
    $yearNum = date('Y', strtotime("-$i months"));
    $views = $db->query("SELECT IFNULL(SUM(views),0) as total FROM movies WHERE MONTH(created_at) = $monthNum AND YEAR(created_at) = $yearNum")->fetch_assoc();
    $monthlyViews[] = ['month' => $month, 'views' => $views['total']];
}

// 3. Genre Distribution (Movies per genre)
$genreStats = $db->query("
    SELECT g.name, COUNT(mg.movie_id) as count 
    FROM genres g 
    LEFT JOIN movie_genres mg ON g.id = mg.genre_id 
    GROUP BY g.id 
    ORDER BY count DESC 
    LIMIT 8
")->fetch_all(MYSQLI_ASSOC);

// 4. Top 10 Movies by Views
$topMovies = $db->query("SELECT title, views FROM movies ORDER BY views DESC LIMIT 10")->fetch_all(MYSQLI_ASSOC);

// 5. User Growth (Last 6 months)
$userGrowth = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('M', strtotime("-$i months"));
    $monthNum = date('m', strtotime("-$i months"));
    $yearNum = date('Y', strtotime("-$i months"));
    $users = $db->query("SELECT COUNT(*) as total FROM users WHERE MONTH(created_at) = $monthNum AND YEAR(created_at) = $yearNum")->fetch_assoc();
    $userGrowth[] = ['month' => $month, 'users' => $users['total']];
}

// 6. Rating Distribution
$ratingDistribution = [
    '0-3' => $db->query("SELECT COUNT(*) as count FROM movies WHERE rating >= 0 AND rating < 3")->fetch_assoc()['count'],
    '3-5' => $db->query("SELECT COUNT(*) as count FROM movies WHERE rating >= 3 AND rating < 5")->fetch_assoc()['count'],
    '5-7' => $db->query("SELECT COUNT(*) as count FROM movies WHERE rating >= 5 AND rating < 7")->fetch_assoc()['count'],
    '7-8' => $db->query("SELECT COUNT(*) as count FROM movies WHERE rating >= 7 AND rating < 8")->fetch_assoc()['count'],
    '8-9' => $db->query("SELECT COUNT(*) as count FROM movies WHERE rating >= 8 AND rating < 9")->fetch_assoc()['count'],
    '9-10' => $db->query("SELECT COUNT(*) as count FROM movies WHERE rating >= 9")->fetch_assoc()['count']
];

// Basic stats
$stats = [];
$stats['movies'] = $db->query("SELECT COUNT(*) FROM movies")->fetch_row()[0];
$stats['users'] = $db->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
$stats['genres'] = $db->query("SELECT COUNT(*) FROM genres")->fetch_row()[0];
$stats['wl'] = $db->query("SELECT COUNT(*) FROM watchlist")->fetch_row()[0];
$stats['views'] = $db->query("SELECT IFNULL(SUM(views),0) FROM movies")->fetch_row()[0];
$stats['featured'] = $db->query("SELECT COUNT(*) FROM movies WHERE is_featured=1")->fetch_row()[0];
$stats['trending'] = $db->query("SELECT COUNT(*) FROM movies WHERE is_trending=1")->fetch_row()[0];
$stats['upcoming'] = $db->query("SELECT COUNT(*) FROM movies WHERE is_upcoming=1")->fetch_row()[0];
$stats['comments'] = $db->query("SELECT COUNT(*) FROM comments WHERE is_approved=0")->fetch_row()[0];
$stats['ratings'] = $db->query("SELECT COUNT(*) FROM user_ratings WHERE is_approved=0")->fetch_row()[0];

// Get data for tables
$movies = $db->query("SELECT m.*, GROUP_CONCAT(g.name SEPARATOR ', ') as genre_names FROM movies m LEFT JOIN movie_genres mg ON m.id=mg.movie_id LEFT JOIN genres g ON mg.genre_id=g.id GROUP BY m.id ORDER BY m.created_at DESC")->fetch_all(MYSQLI_ASSOC);
$genres = $db->query("SELECT g.*, COUNT(mg.movie_id) as count FROM genres g LEFT JOIN movie_genres mg ON g.id=mg.genre_id GROUP BY g.id ORDER BY g.name")->fetch_all(MYSQLI_ASSOC);
$users = $db->query("SELECT u.*, COUNT(w.id) as wl_count FROM users u LEFT JOIN watchlist w ON u.id=w.user_id GROUP BY u.id ORDER BY u.created_at DESC")->fetch_all(MYSQLI_ASSOC);
$sliders = $db->query("SELECT s.*, m.title as movie_title FROM sliders s LEFT JOIN movies m ON s.movie_id = m.id ORDER BY s.display_order ASC")->fetch_all(MYSQLI_ASSOC);
$collections = $db->query("SELECT c.*, COUNT(cm.movie_id) as movie_count FROM collections c LEFT JOIN collection_movies cm ON c.id = cm.collection_id GROUP BY c.id ORDER BY c.display_order ASC")->fetch_all(MYSQLI_ASSOC);
$pendingComments = $db->query("SELECT c.*, u.username, m.title as movie_title FROM comments c LEFT JOIN users u ON c.user_id = u.id LEFT JOIN movies m ON c.movie_id = m.id WHERE c.is_approved = 0 ORDER BY c.created_at DESC")->fetch_all(MYSQLI_ASSOC);
$pendingRatings = $db->query("SELECT r.*, u.username, m.title as movie_title FROM user_ratings r LEFT JOIN users u ON r.user_id = u.id LEFT JOIN movies m ON r.movie_id = m.id WHERE r.is_approved = 0 ORDER BY r.created_at DESC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CineVault Admin - Analytics Dashboard</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
*{box-sizing:border-box;margin:0;padding:0}
:root{--bg:#080808;--bg2:#0f0f0f;--bg3:#141414;--bg4:#1a1a1a;--bg5:#222;--bg6:#2a2a2a;
  --red:#c0392b;--redl:#e74c3c;--redd:rgba(192,57,43,0.14);
  --text:#f0f0f0;--muted:#777;--dim:#3a3a3a;
  --border:#232323;--borderl:#333;
  --green:#27ae60;--blue:#3498db;--gold:#f0b429;
  --r:6px;--rl:10px}
body{font-family:'Segoe UI',system-ui,sans-serif;background:var(--bg);color:var(--text);min-height:100vh;display:flex}
a{text-decoration:none;color:inherit}

/* Sidebar */
.sidebar{width:260px;background:var(--bg2);border-right:1px solid var(--border);position:fixed;top:0;left:0;bottom:0;overflow-y:auto}
.sb-logo{padding:22px 20px 16px;border-bottom:1px solid var(--border)}
.sb-logo h1{font-size:19px;letter-spacing:3px}
.sb-logo h1 span{color:var(--red)}
.sb-logo p{font-size:10px;color:var(--muted);margin-top:3px}
.sb-nav{padding:10px 0}
.sb-sec{font-size:10px;font-weight:700;text-transform:uppercase;color:var(--dim);padding:14px 18px 5px}
.sb-item{display:flex;align-items:center;gap:10px;padding:10px 18px;font-size:13px;font-weight:500;color:var(--muted);cursor:pointer;transition:all 0.14s;border-left:2px solid transparent}
.sb-item:hover{background:var(--bg3);color:var(--text);border-left-color:var(--dim)}
.sb-item.active{background:var(--bg4);color:var(--text);border-left-color:var(--red)}
.sb-bottom{padding:14px 18px;border-top:1px solid var(--border)}
.sb-link{display:flex;align-items:center;gap:8px;font-size:12px;color:var(--muted);padding:7px 0}
.sb-link:hover{color:var(--redl)}

/* Main */
.main-wrap{margin-left:260px;flex:1}
.topbar{background:var(--bg2);border-bottom:1px solid var(--border);padding:13px 26px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:50}
.topbar-title{font-size:15px;font-weight:700}
.topbar-right{display:flex;align-items:center;gap:10px}
.ab{background:var(--redd);border:1px solid rgba(192,57,43,0.25);color:var(--redl);font-size:10px;padding:3px 9px;border-radius:20px}
.content{padding:24px}

/* Flash */
.flash{padding:11px 15px;border-radius:var(--r);font-size:13px;margin-bottom:18px;border-left:3px solid}
.flash.success{background:rgba(39,174,96,0.1);border-color:var(--green);color:#2ecc71}
.flash.error{background:var(--redd);border-color:var(--red);color:var(--redl)}

/* Stats Grid */
.stat-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:14px;margin-bottom:24px}
.stat-card{background:var(--bg3);border:1px solid var(--border);border-radius:var(--rl);padding:18px;cursor:pointer;position:relative;transition:all 0.2s}
.stat-card:hover{transform:translateY(-2px);border-color:var(--borderl)}
.stat-card::after{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:var(--red)}
.s-num{font-size:32px;font-weight:800;margin-bottom:5px}
.s-lbl{font-size:10px;font-weight:700;text-transform:uppercase;color:var(--muted)}

/* Charts Grid */
.charts-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:20px;margin-bottom:24px}
.chart-card{background:var(--bg3);border:1px solid var(--border);border-radius:var(--rl);padding:20px}
.chart-card h3{font-size:13px;font-weight:700;margin-bottom:15px;display:flex;align-items:center;gap:8px}
.chart-card h3::before{content:'';width:3px;height:14px;background:var(--red);border-radius:2px}
.chart-container{height:250px;position:relative}

/* Top Movies List */
.top-movies-list{background:var(--bg3);border:1px solid var(--border);border-radius:var(--rl);padding:20px}
.top-movies-list h3{font-size:13px;font-weight:700;margin-bottom:15px;display:flex;align-items:center;gap:8px}
.top-movies-list h3::before{content:'';width:3px;height:14px;background:var(--red);border-radius:2px}
.movie-rank-item{display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--border)}
.movie-rank-item:last-child{border-bottom:none}
.rank-number{width:30px;font-size:18px;font-weight:800;color:var(--gold)}
.rank-title{flex:1;font-size:13px}
.rank-views{font-size:12px;color:var(--muted)}

/* Table */
.table-wrap{background:var(--bg3);border:1px solid var(--border);border-radius:var(--rl);overflow:hidden;margin-bottom:20px}
.th{padding:14px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px}
.filter-input{background:var(--bg5);border:1px solid var(--border);color:var(--text);padding:6px 11px;border-radius:var(--r);font-size:12px}
table{width:100%;border-collapse:collapse}
th{text-align:left;padding:9px 14px;border-bottom:1px solid var(--border);font-size:10px;font-weight:700;text-transform:uppercase;color:var(--muted)}
td{padding:10px 14px;border-bottom:1px solid rgba(255,255,255,0.025);font-size:13px}
tr:hover td{background:rgba(255,255,255,0.012)}
.p-thumb{width:32px;height:46px;object-fit:cover;border-radius:3px}

/* Badges */
.b{display:inline-flex;font-size:10px;font-weight:700;padding:2px 7px;border-radius:3px;text-transform:uppercase}
.b-yes{background:rgba(39,174,96,0.14);color:#2ecc71;border:1px solid rgba(39,174,96,0.22)}
.b-no{background:var(--bg5);color:var(--muted);border:1px solid var(--border)}
.b-q{background:rgba(240,180,41,0.1);color:var(--gold);border:1px solid rgba(240,180,41,0.18)}
.b-bl{background:rgba(52,152,219,0.14);color:#5dade2;border:1px solid rgba(52,152,219,0.22)}
.btn{display:inline-flex;align-items:center;gap:5px;padding:6px 13px;border-radius:var(--r);font-size:12px;font-weight:700;border:none;cursor:pointer}
.btn-p{background:var(--red);color:#fff}.btn-p:hover{background:var(--redl)}
.btn-s{background:var(--bg5);color:var(--muted);border:1px solid var(--border)}.btn-s:hover{color:var(--text)}
.btn-d{background:var(--redd);color:var(--redl);border:1px solid rgba(192,57,43,0.25)}
.btn-sm{padding:4px 9px;font-size:11px}
.inline-form{display:inline}

/* Forms */
.fc{background:var(--bg3);border:1px solid var(--border);border-radius:var(--rl);padding:26px;margin-bottom:20px}
.fc h2{font-size:14px;margin-bottom:20px;display:flex;align-items:center;gap:8px}
.fc h2::before{content:'';width:3px;height:14px;background:var(--red)}
.fr{display:grid;gap:14px;margin-bottom:14px}
.fr.c2{grid-template-columns:1fr 1fr}
.fr.c3{grid-template-columns:1fr 1fr 1fr}
.fg{display:flex;flex-direction:column;gap:5px}
.fg label{font-size:10px;font-weight:700;text-transform:uppercase;color:var(--muted)}
.fg input,.fg textarea,.fg select{padding:8px 11px;background:var(--bg5);border:1px solid var(--border);border-radius:var(--r);color:var(--text);font-size:13px}
.fg textarea{min-height:85px}
.check-row{display:flex;flex-wrap:wrap;gap:8px;padding:8px 0}
.chk-lbl{display:flex;align-items:center;gap:6px;font-size:12px;color:var(--muted);cursor:pointer;padding:5px 11px;background:var(--bg5);border:1px solid var(--border);border-radius:20px}
.chk-lbl input{accent-color:var(--red)}
.sw-row{display:flex;gap:22px;padding:6px 0;flex-wrap:wrap}
.sw-lbl{display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer}
.f-actions{display:flex;gap:10px;padding-top:16px;border-top:1px solid var(--border);margin-top:18px}
.two-col{display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:22px}
.modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.86);z-index:999;align-items:center;justify-content:center}
.modal-box{background:var(--bg3);border:1px solid var(--border);border-radius:var(--rl);padding:26px;width:360px}
@media (max-width:768px){
    .sidebar{width:70px}
    .sidebar .sb-logo h1 span,.sidebar .sb-logo p,.sidebar .sb-item span,.sidebar .sb-sec,.sidebar .sb-bottom span{display:none}
    .main-wrap{margin-left:70px}
    .charts-grid{grid-template-columns:1fr}
    .two-col{grid-template-columns:1fr}
    .fr.c2,.fr.c3{grid-template-columns:1fr}
}
</style>
</head>
<body>

<aside class="sidebar">
  <div class="sb-logo"><h1>CINE<span>VAULT</span></h1><p>Admin</p></div>
  <nav class="sb-nav">
    <div class="sb-sec">Main</div>
    <div class="sb-item <?=$tab==='dashboard'?'active':''?>" onclick="location='admin.php?tab=dashboard'">📊 Dashboard</div>
    <div class="sb-item <?=$tab==='movies'?'active':''?>" onclick="location='admin.php?tab=movies'">🎬 Movies</div>
    <div class="sb-item <?=$tab==='add_movie'?'active':''?>" onclick="location='admin.php?tab=add_movie'">➕ Add Movie</div>
    <div class="sb-item <?=$tab==='collections'?'active':''?>" onclick="location='admin.php?tab=collections'">📁 Collections</div>
    <div class="sb-item <?=$tab==='sliders'?'active':''?>" onclick="location='admin.php?tab=sliders'">🎠 Sliders</div>
    <div class="sb-item <?=$tab==='genres'?'active':''?>" onclick="location='admin.php?tab=genres'">🏷️ Genres</div>
    <div class="sb-item <?=$tab==='users'?'active':''?>" onclick="location='admin.php?tab=users'">👥 Users</div>
    <div class="sb-item <?=$tab==='comments'?'active':''?>" onclick="location='admin.php?tab=comments'">💬 Comments</div>
    <div class="sb-item <?=$tab==='ratings'?'active':''?>" onclick="location='admin.php?tab=ratings'">⭐ Ratings</div>
    <div class="sb-item <?=$tab==='newsletter'?'active':''?>" onclick="location='admin.php?tab=newsletter'">
    📧 Newsletter</div>
    <div class="sb-item <?=$tab==='contacts'?'active':''?>" onclick="location='admin.php?tab=contacts'">
    📬 Contact Messages</div>
    <div class="sb-item <?=$tab==='settings'?'active':''?>" onclick="location='admin.php?tab=settings'">⚙️ Settings</div>
  </nav>
  <div class="sb-bottom">
    <a class="sb-link" href="index.php" target="_blank">🌐 View Site</a>
    <a class="sb-link" href="admin.php?logout=1">🚪 Logout</a>
  </div>
</aside>

<div class="main-wrap">
  <div class="topbar">
    <span class="topbar-title"><?php $tt=['dashboard'=>'Analytics Dashboard','movies'=>'Movies','add_movie'=>($editMovie?'Edit Movie':'Add Movie'),'collections'=>'Collections','sliders'=>'Sliders','genres'=>'Genres','users'=>'Users','comments'=>'Comments','ratings'=>'Ratings','newsletter'=>'Newsletter Subscribers','settings'=>'Settings','contacts'=>'Contact Messages'];?></span>
    <div class="topbar-right">
      <span class="ab">Admin</span>
      <?php if($tab!=='add_movie' && $tab!=='dashboard'):?>
      <button class="btn btn-p" onclick="location='admin.php?tab=add_movie'">+ Add Movie</button>
      <?php endif;?>
    </div>
  </div>
  <div class="content">

    <?php if($flashMsg):?><div class="flash <?=$flashType?>"><?=htmlspecialchars($flashMsg)?></div><?php endif;?>

    <!-- ========== DASHBOARD WITH LIVE CHARTS ========== -->
    <?php if($tab === 'dashboard'):?>
    
    <!-- Stats Cards -->
    <div class="stat-grid">
      <div class="stat-card" onclick="location='admin.php?tab=movies'"><div class="s-num"><?=$stats['movies']?></div><div class="s-lbl">Total Movies</div></div>
      <div class="stat-card" onclick="location='admin.php?tab=users'"><div class="s-num"><?=$stats['users']?></div><div class="s-lbl">Total Users</div></div>
      <div class="stat-card"><div class="s-num"><?=number_format($stats['views'])?></div><div class="s-lbl">Total Views</div></div>
      <div class="stat-card"><div class="s-num"><?=$stats['featured']?></div><div class="s-lbl">Featured</div></div>
      <div class="stat-card"><div class="s-num"><?=$stats['trending']?></div><div class="s-lbl">Trending</div></div>
      <div class="stat-card"><div class="s-num"><?=$stats['upcoming']?></div><div class="s-lbl">Upcoming</div></div>
      <div class="stat-card" onclick="location='admin.php?tab=comments'"><div class="s-num"><?=$stats['comments']?></div><div class="s-lbl">Pending Comments</div></div>
      <div class="stat-card" onclick="location='admin.php?tab=ratings'"><div class="s-num"><?=$stats['ratings']?></div><div class="s-lbl">Pending Ratings</div></div>
    </div>

    <!-- Charts Grid -->
    <div class="charts-grid">
      <!-- Weekly Views Chart -->
      <div class="chart-card">
        <h3>📈 Weekly Views (Last 7 Days)</h3>
        <div class="chart-container">
          <canvas id="weeklyChart"></canvas>
        </div>
      </div>
      
      <!-- Monthly Views Chart -->
      <div class="chart-card">
        <h3>📊 Monthly Views (Last 6 Months)</h3>
        <div class="chart-container">
          <canvas id="monthlyChart"></canvas>
        </div>
      </div>
      
      <!-- Genre Distribution -->
      <div class="chart-card">
        <h3>🎭 Movies by Genre</h3>
        <div class="chart-container">
          <canvas id="genreChart"></canvas>
        </div>
      </div>
      
      <!-- User Growth -->
      <div class="chart-card">
        <h3>👥 User Growth (Last 6 Months)</h3>
        <div class="chart-container">
          <canvas id="userGrowthChart"></canvas>
        </div>
      </div>
      
      <!-- Rating Distribution -->
      <div class="chart-card">
        <h3>⭐ Rating Distribution</h3>
        <div class="chart-container">
          <canvas id="ratingChart"></canvas>
        </div>
      </div>
      
      <!-- Top 10 Movies -->
      <div class="chart-card">
        <h3>🏆 Top 10 Most Viewed Movies</h3>
        <div class="top-movies-list" style="padding:0;background:transparent">
          <?php foreach (array_slice($topMovies, 0, 10) as $index => $movie): ?>
          <div class="movie-rank-item">
            <div class="rank-number"><?= $index + 1 ?></div>
            <div class="rank-title"><?= htmlspecialchars($movie['title']) ?></div>
            <div class="rank-views"><?= number_format($movie['views']) ?> views</div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Recent Movies Table -->
    <div class="table-wrap">
      <div class="th"><h3>📋 Recent Movies</h3><button class="btn btn-s" onclick="location='admin.php?tab=movies'">View All</button></div>
      <table style="width:100%">
        <thead><tr><th></th><th>Title</th><th>Year</th><th>Rating</th><th>Views</th><th>Featured</th><th>Trending</th></tr></thead>
        <tbody><?php foreach(array_slice($movies,0,8) as $m):?>
          <tr onclick="location='admin.php?edit_movie=<?=$m['id']?>'" style="cursor:pointer">
            <td><?=$m['poster']?"<img class='p-thumb' src='".htmlspecialchars($m['poster'])."'>":"<div class='p-thumb'></div>"?></td>
            <td style="font-weight:600"><?=htmlspecialchars($m['title'])?></td>
            <td><?=$m['year']?></td>
            <td style="color:var(--gold)"><?=$m['rating']?></td>
            <td><?=number_format($m['views'])?></td>
            <td><span class="b <?=$m['is_featured']?'b-yes':'b-no'?>"><?=$m['is_featured']?'Yes':'No'?></span></td>
            <td><span class="b <?=$m['is_trending']?'b-yes':'b-no'?>"><?=$m['is_trending']?'Yes':'No'?></span></td>
          </tr>
        <?php endforeach;?></tbody>
      </table>
    </div>

    <script>
    // Weekly Views Chart
    new Chart(document.getElementById('weeklyChart'), {
      type: 'line',
      data: {
        labels: <?= json_encode(array_column($weeklyViews, 'day')) ?>,
        datasets: [{
          label: 'Views',
          data: <?= json_encode(array_column($weeklyViews, 'views')) ?>,
          borderColor: '#c0392b',
          backgroundColor: 'rgba(192,57,43,0.1)',
          tension: 0.3,
          fill: true
        }]
      },
      options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { labels: { color: '#a0a0a0' } } } }
    });

    // Monthly Views Chart
    new Chart(document.getElementById('monthlyChart'), {
      type: 'bar',
      data: {
        labels: <?= json_encode(array_column($monthlyViews, 'month')) ?>,
        datasets: [{
          label: 'Views',
          data: <?= json_encode(array_column($monthlyViews, 'views')) ?>,
          backgroundColor: '#c0392b',
          borderRadius: 6
        }]
      },
      options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { labels: { color: '#a0a0a0' } } } }
    });

    // Genre Distribution Chart
    new Chart(document.getElementById('genreChart'), {
      type: 'doughnut',
      data: {
        labels: <?= json_encode(array_column($genreStats, 'name')) ?>,
        datasets: [{
          data: <?= json_encode(array_column($genreStats, 'count')) ?>,
          backgroundColor: ['#c0392b', '#e74c3c', '#3498db', '#2ecc71', '#f39c12', '#9b59b6', '#1abc9c', '#e67e22']
        }]
      },
      options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right', labels: { color: '#a0a0a0' } } } }
    });

    // User Growth Chart
    new Chart(document.getElementById('userGrowthChart'), {
      type: 'line',
      data: {
        labels: <?= json_encode(array_column($userGrowth, 'month')) ?>,
        datasets: [{
          label: 'New Users',
          data: <?= json_encode(array_column($userGrowth, 'users')) ?>,
          borderColor: '#27ae60',
          backgroundColor: 'rgba(39,174,96,0.1)',
          tension: 0.3,
          fill: true
        }]
      },
      options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { labels: { color: '#a0a0a0' } } } }
    });

    // Rating Distribution Chart
    new Chart(document.getElementById('ratingChart'), {
      type: 'bar',
      data: {
        labels: ['0-3', '3-5', '5-7', '7-8', '8-9', '9-10'],
        datasets: [{
          label: 'Number of Movies',
          data: [
            <?= $ratingDistribution['0-3'] ?>,
            <?= $ratingDistribution['3-5'] ?>,
            <?= $ratingDistribution['5-7'] ?>,
            <?= $ratingDistribution['7-8'] ?>,
            <?= $ratingDistribution['8-9'] ?>,
            <?= $ratingDistribution['9-10'] ?>
          ],
          backgroundColor: '#f0b429',
          borderRadius: 6
        }]
      },
      options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { labels: { color: '#a0a0a0' } } } }
    });
    </script>

    <!-- ========== OTHER TABS (MOVIES, ADD MOVIE, COLLECTIONS, SLIDERS, GENRES, USERS, COMMENTS, RATINGS, SETTINGS) ========== -->
    <!-- Keeping these same as before - compact version -->
    
    <?php elseif($tab === 'movies'):?>
    <div class="table-wrap"><div class="th"><h3>All Movies (<?=count($movies)?>)</h3><input type="text" class="filter-input" placeholder="Search..." oninput="filterTable(this,'moviesTable')"></div>
    <div style="overflow-x:auto"><table id="moviesTable"><thead><tr><th></th><th>Title</th><th>Year</th><th>Rating</th><th>Quality</th><th>Views</th><th>Featured</th><th>Trending</th><th>Upcoming</th><th>Actions</th></tr></thead>
    <tbody><?php foreach($movies as $m):?><tr><td><?=$m['poster']?"<img class='p-thumb' src='".htmlspecialchars($m['poster'])."'>":"<div class='p-thumb'></div>"?></td>
    <td style="font-weight:600"><?=htmlspecialchars($m['title'])?></td><td><?=$m['year']?></td><td style="color:var(--gold)"><?=$m['rating']?></td>
    <td><span class="b b-q"><?=$m['quality']?></span></td><td><?=number_format($m['views'])?></td>
    <td><form class="inline-form" method="POST"><input type="hidden" name="form_action" value="toggle_field"><input type="hidden" name="movie_id" value="<?=$m['id']?>"><input type="hidden" name="field" value="is_featured"><button type="submit" class="b <?=$m['is_featured']?'b-yes':'b-no'?>" style="cursor:pointer"><?=$m['is_featured']?'Yes':'No'?></button></form></td>
    <td><form class="inline-form" method="POST"><input type="hidden" name="form_action" value="toggle_field"><input type="hidden" name="movie_id" value="<?=$m['id']?>"><input type="hidden" name="field" value="is_trending"><button type="submit" class="b <?=$m['is_trending']?'b-yes':'b-no'?>" style="cursor:pointer"><?=$m['is_trending']?'Yes':'No'?></button></form></td>
    <td><form class="inline-form" method="POST"><input type="hidden" name="form_action" value="toggle_field"><input type="hidden"name="movie_id" value="<?=$m['id']?>"><input type="hidden" name="field" value="is_upcoming"><button type="submit" class="b <?=($m['is_upcoming']??0)?'b-yes':'b-no'?>" style="cursor:pointer"><?=($m['is_upcoming']??0)?'Yes':'No'?></button></form></td>
    <td><div style="display:flex;gap:5px"><a href="admin.php?edit_movie=<?=$m['id']?>" class="btn btn-bl btn-sm">Edit</a><form class="inline-form" method="POST" onsubmit="return confirm('Delete?')"><input type="hidden" name="form_action" value="delete_movie"><input type="hidden" name="movie_id" value="<?=$m['id']?>"><button type="submit" class="btn btn-d btn-sm">Delete</button></form></div></td></tr>
    <?php endforeach;?></tbody></table></div></div>

    <?php elseif($tab === 'add_movie'):?>
    <?php $e=$editMovie??[]; $isEdit=!empty($e);?>
    <form method="POST"><input type="hidden" name="form_action" value="save_movie"><?php if($isEdit):?><input type="hidden" name="movie_id" value="<?=$e['id']?>"><?php endif;?>
    <div class="fc"><h2><?=$isEdit?'Edit: '.htmlspecialchars($e['title']):'Add New Movie'?></h2>
    <div class="fr c2"><div class="fg"><label>Title *</label><input type="text" name="title" required value="<?=htmlspecialchars($e['title']??'')?>"></div><div class="fg"><label>Slug</label><input type="text" name="slug" placeholder="auto-generated" value="<?=htmlspecialchars($e['slug']??'')?>"></div></div>
    <div class="fr c1"><div class="fg"><label>Description</label><textarea name="description"><?=htmlspecialchars($e['description']??'')?></textarea></div></div>
    <div class="fr c1"><div class="fg"><label>Embed URL *</label><input type="text" name="embed_url" required value="<?=htmlspecialchars($e['embed_url']??'')?>"></div></div>
    <div class="fr c2"><div class="fg"><label>Trailer URL</label><input type="text" name="trailer_url" value="<?=htmlspecialchars($e['trailer_url']??'')?>"></div></div>
    <div class="fr c2"><div class="fg"><label>Poster URL</label><input type="text" name="poster" value="<?=htmlspecialchars($e['poster']??'')?>"></div><div class="fg"><label>Backdrop URL</label><input type="text" name="backdrop" value="<?=htmlspecialchars($e['backdrop']??'')?>"></div></div>
    <div class="fr c3"><div class="fg"><label>Year</label><input type="number" name="year" value="<?=$e['year']??date('Y')?>"></div><div class="fg"><label>Duration (min)</label><input type="number" name="duration" value="<?=$e['duration']??''?>"></div><div class="fg"><label>Language</label><input type="text" name="language" value="<?=htmlspecialchars($e['language']??'English')?>"></div></div>
    <div class="fr c3"><div class="fg"><label>Site Rating</label><input type="number" step="0.1" name="rating" value="<?=$e['rating']??'7.0'?>"></div><div class="fg"><label>IMDB Rating</label><input type="number" step="0.1" name="imdb_rating" value="<?=$e['imdb_rating']??'7.0'?>"></div><div class="fg"><label>Quality</label><select name="quality"><?php foreach(['CAM','DVDRip','HD','FHD','4K'] as $q){echo "<option ".((($e['quality']??'HD')==$q)?'selected':'').">$q</option>";}?></select></div></div>
    <div class="fr c2"><div class="fg"><label>Director</label><input type="text" name="director" value="<?=htmlspecialchars($e['director']??'')?>"></div><div class="fg"><label>Cast</label><input type="text" name="cast_members" value="<?=htmlspecialchars($e['cast_members']??'')?>"></div></div>
    <div class="fr c2"><div class="fg"><label>Release Date</label><input type="date" name="release_date" value="<?=$e['release_date']??''?>"></div></div>
    <div class="fg"><label>Genres</label><div class="check-row"><?php foreach($genres as $g){echo "<label class='chk-lbl'><input type='checkbox' name='genres[]' value='{$g['id']}' ".((in_array($g['id'],$editMovieGenres)?'checked':'')).">".htmlspecialchars($g['name'])."</label>";}?></div></div>
    <div class="sw-row"><label class="sw-lbl"><input type="checkbox" name="is_featured" <?=($e['is_featured']??0)?'checked':''?>> Featured</label><label class="sw-lbl"><input type="checkbox" name="is_trending" <?=($e['is_trending']??0)?'checked':''?>> Trending</label><label class="sw-lbl"><input type="checkbox" name="is_upcoming" <?=($e['is_upcoming']??0)?'checked':''?>> Upcoming</label></div>
    <div class="fc" style="margin-top:20px"><h2>SEO Settings</h2><div class="fr c1"><div class="fg"><label>Meta Title</label><input type="text" name="meta_title" value="<?=htmlspecialchars($e['meta_title']??'')?>"></div><div class="fg"><label>Meta Description</label><textarea name="meta_description"><?=htmlspecialchars($e['meta_description']??'')?></textarea></div><div class="fg"><label>Meta Keywords</label><input type="text" name="meta_keywords" value="<?=htmlspecialchars($e['meta_keywords']??'')?>"></div></div></div>
    <div class="f-actions"><button type="submit" class="btn btn-p btn-lg"><?=$isEdit?'Save Changes':'Add Movie'?></button><a href="admin.php?tab=movies" class="btn btn-s btn-lg">Cancel</a></div></div></form>

<?php elseif($tab === 'collections'):?>
<?php
// Get all collections
$collections = $db->query("
    SELECT c.*, COUNT(cm.movie_id) as movie_count 
    FROM collections c
    LEFT JOIN collection_movies cm ON c.id = cm.collection_id
    GROUP BY c.id
    ORDER BY c.display_order ASC
")->fetch_all(MYSQLI_ASSOC);

// Get all movies for dropdown
$allMovies = $db->query("SELECT id, title FROM movies ORDER BY title ASC")->fetch_all(MYSQLI_ASSOC);
?>

<div class="two-col">
    <!-- Add New Collection Form -->
    <div class="fc">
        <h2>Add New Collection</h2>
        <form method="POST">
            <input type="hidden" name="form_action" value="save_collection">
            <div class="fg"><label>Collection Name</label><input type="text" name="collection_name" required></div>
            <div class="fg"><label>Slug (auto)</label><input type="text" name="collection_slug" placeholder="leave empty for auto"></div>
            <div class="fg"><label>Description</label><textarea name="collection_desc"></textarea></div>
            <div class="fg"><label>Cover Image URL</label><input type="text" name="collection_cover" placeholder="https://..."></div>
            <div class="fg"><label>Display Order</label><input type="number" name="display_order" value="0"></div>
            <button type="submit" class="btn btn-p" style="margin-top:15px; width:100%">Create Collection</button>
        </form>
    </div>
    
    <!-- All Collections List -->
    <div class="table-wrap">
        <div class="th"><h3>All Collections</h3></div>
        <table style="width:100%">
            <thead><tr><th>Name</th><th>Movies</th><th>Order</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach($collections as $c): ?>
            <tr>
                <td>
                    <strong><?= htmlspecialchars($c['name']) ?></strong><br>
                    <small style="color:var(--muted)"><?= $c['slug'] ?></small>
                </td>
                <td><span class="b b-bl"><?= $c['movie_count'] ?> movies</span></td>
                <td><?= $c['display_order'] ?></td>
                <td>
                    <div style="display:flex; gap:5px; flex-wrap:wrap;">
                        <button class="btn btn-bl btn-sm" onclick="editCollection(<?= $c['id'] ?>, '<?= htmlspecialchars(addslashes($c['name'])) ?>', '<?= htmlspecialchars(addslashes($c['slug'])) ?>', '<?= htmlspecialchars(addslashes($c['description'] ?? '')) ?>', '<?= htmlspecialchars($c['cover_image'] ?? '') ?>', <?= $c['display_order'] ?>)">✏️ Edit</button>
                        <form method="POST" onsubmit="return confirm('Delete collection?')">
                            <input type="hidden" name="form_action" value="delete_collection">
                            <input type="hidden" name="collection_id" value="<?= $c['id'] ?>">
                            <button type="submit" class="btn btn-d btn-sm">🗑 Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Collection Modal -->
<div class="modal" id="editCollectionModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.9); z-index:1000; align-items:center; justify-content:center;">
    <div class="modal-box" style="background:var(--bg3); border-radius:12px; padding:25px; width:500px; max-width:90%; max-height:80vh; overflow-y:auto;">
        <h3 style="margin-bottom:20px;">✏️ Edit Collection & Add Movies</h3>
        <form method="POST" id="editCollectionForm">
            <input type="hidden" name="form_action" value="save_collection">
            <input type="hidden" name="collection_id" id="edit_collection_id">
            
            <div class="fg"><label>Collection Name</label><input type="text" name="collection_name" id="edit_collection_name" required></div>
            <div class="fg"><label>Slug</label><input type="text" name="collection_slug" id="edit_collection_slug"></div>
            <div class="fg"><label>Description</label><textarea name="collection_desc" id="edit_collection_desc"></textarea></div>
            <div class="fg"><label>Cover Image URL</label><input type="text" name="collection_cover" id="edit_collection_cover"></div>
            <div class="fg"><label>Display Order</label><input type="number" name="display_order" id="edit_display_order"></div>
            
            <div class="fg" style="margin-top:15px;">
                <label>Select Movies for this Collection</label>
                <div style="max-height:200px; overflow-y:auto; background:var(--bg4); border-radius:8px; padding:10px;">
                    <?php foreach($allMovies as $movie): ?>
                    <label class="chk-lbl" style="display:flex; align-items:center; gap:8px; margin-bottom:8px;">
                        <input type="checkbox" name="collection_movies[]" value="<?= $movie['id'] ?>" class="collection-movie-checkbox">
                        <?= htmlspecialchars($movie['title']) ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="f-actions" style="margin-top:20px;">
                <button type="submit" class="btn btn-p">💾 Save Collection</button>
                <button type="button" class="btn btn-s" onclick="closeEditModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

    <?php elseif($tab === 'sliders'):?>
    <div class="two-col"><div class="fc"><h2>Add Slider Item</h2><form method="POST"><input type="hidden" name="form_action" value="save_slider"><div class="fg"><label>Select Movie</label><select name="movie_id" required><option value="">-- Select Movie --</option><?php foreach($movies as $m){echo "<option value='{$m['id']}'>{$m['title']}</option>";}?></select></div><div class="fg"><label>Custom Title</label><input type="text" name="slider_title"></div><div class="fg"><label>Custom Subtitle</label><input type="text" name="slider_subtitle"></div><div class="fg"><label>Display Order</label><input type="number" name="display_order" value="0"></div><div class="sw-lbl"><label><input type="checkbox" name="is_active" checked> Active</label></div><button type="submit" class="btn btn-p" style="margin-top:15px;width:100%">Add to Slider</button></form></div>
    <div class="table-wrap"><div class="th"><h3>Active Sliders</h3></div><table><thead><tr><th>Movie</th><th>Title</th><th>Order</th><th>Status</th><th>Action</th></tr></thead><tbody><?php foreach($sliders as $s):?><tr><td><?=htmlspecialchars($s['movie_title']??'N/A')?></td><td><?=htmlspecialchars($s['title']??'-')?></td><td><?=$s['display_order']?></td><td><span class="b <?=$s['is_active']?'b-yes':'b-no'?>"><?=$s['is_active']?'Active':'Inactive'?></span></td><td><form method="POST" onsubmit="return confirm('Delete?')"><input type="hidden" name="form_action" value="delete_slider"><input type="hidden"name="slider_id" value="<?=$s['id']?>"><button type="submit" class="btn btn-d btn-sm">Delete</button></form></td></tr><?php endforeach;?></tbody></table></div></div>

    <?php elseif($tab === 'genres'):?>
    <div class="two-col"><div class="fc"><h2>Add Genre</h2><form method="POST"><input type="hidden" name="form_action" value="add_genre"><div class="fg"><label>Genre Name</label><input type="text" name="genre_name" required></div><button type="submit" class="btn btn-p" style="width:100%;margin-top:15px">Add Genre</button></form></div>
    <div class="table-wrap"><div class="th"><h3>All Genres (<?=count($genres)?>)</h3></div><table><thead><tr><th>Name</th><th>Slug</th><th>Movies</th><th>Actions</th></tr></thead><tbody><?php foreach($genres as $g):?><tr><td><strong><?=htmlspecialchars($g['name'])?></strong></td><td style="color:var(--muted)"><?=$g['slug']?></td><td><span class="b b-bl"><?=$g['count']?></span></td><td><button class="btn btn-bl btn-sm" onclick="openRename(<?=$g['id']?>,'<?=htmlspecialchars(addslashes($g['name']))?>')">Rename</button><?php if($g['count']==0){echo "<form class='inline-form' method='POST' onsubmit='return confirm(\"Delete?\")'><input type='hidden' name='form_action' value='delete_genre'><input type='hidden' name='genre_id' value='{$g['id']}'><button type='submit' class='btn btn-d btn-sm'>Delete</button></form>";}else{echo "<button class='btn btn-s btn-sm' disabled>Delete</button>";}?></td></tr><?php endforeach;?></tbody></table></div></div>

    <?php elseif($tab === 'users'):?>
    <div class="table-wrap"><div class="th"><h3>All Users (<?=count($users)?>)</h3><input type="text" class="filter-input" placeholder="Search..." oninput="filterTable(this,'usersTable')"></div><table id="usersTable"><thead><tr><th>Username</th><th>Email</th><th>Watchlist</th><th>Joined</th><th>Action</th></tr></thead><tbody><?php foreach($users as $u):?><tr><td><strong><?=htmlspecialchars($u['username'])?></strong></td><td><?=htmlspecialchars($u['email'])?></td><td><span class="b b-bl"><?=$u['wl_count']?> saved</span></td><td><?=date('d M Y',strtotime($u['created_at']))?></td><td><form method="POST" onsubmit="return confirm('Delete user?')"><input type="hidden" name="form_action" value="delete_user"><input type="hidden" name="user_id" value="<?=$u['id']?>"><button type="submit" class="btn btn-d btn-sm">Delete</button></form></td></tr><?php endforeach;?></tbody></table></div>

  <?php elseif($tab === 'comments'):?>
<?php
// Fetch pending comments with proper join
$pendingComments = $db->query("
    SELECT c.*, u.username, m.title as movie_title 
    FROM comments c 
    LEFT JOIN users u ON c.user_id = u.id 
    LEFT JOIN movies m ON c.movie_id = m.id 
    WHERE c.is_approved = 0 
    ORDER BY c.created_at DESC
")->fetch_all(MYSQLI_ASSOC);
?>
<div class="table-wrap">
    <div class="th"><h3>Pending Comments (<?= count($pendingComments) ?>)</h3></div>
    <table style="width:100%">
        <thead>
            <tr>
                <th>User</th>
                <th>Movie</th>
                <th>Comment</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($pendingComments)): ?>
            <tr>
                <td colspan="5" style="text-align:center; padding:40px; color:#777;">No pending comments</td>
            </tr>
            <?php else: ?>
            <?php foreach($pendingComments as $c): ?>
            <tr>
                <td><strong><?= htmlspecialchars($c['username'] ?? 'Unknown') ?></strong></td>
                <td><?= htmlspecialchars($c['movie_title'] ?? 'N/A') ?></td>
                <td style="max-width:300px"><?= htmlspecialchars(substr($c['comment'], 0, 100)) ?>...</td>
                <td><?= date('d M H:i', strtotime($c['created_at'])) ?></td>
                <td>
                    <div style="display:flex; gap:5px">
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="form_action" value="approve_comment">
                            <input type="hidden" name="comment_id" value="<?= $c['id'] ?>">
                            <button type="submit" class="btn btn-bl btn-sm">Approve</button>
                        </form>
                        <form method="POST" style="display:inline" onsubmit="return confirm('Delete this comment?')">
                            <input type="hidden" name="form_action" value="delete_comment">
                            <input type="hidden" name="comment_id" value="<?= $c['id'] ?>">
                            <button type="submit" class="btn btn-d btn-sm">Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    <!-- Approved Comments Section -->
      <?php
    $approvedComments = $db->query("
        SELECT c.*, u.username, m.title as movie_title 
        FROM comments c 
        LEFT JOIN users u ON c.user_id = u.id 
        LEFT JOIN movies m ON c.movie_id = m.id 
        WHERE c.is_approved = 1
        ORDER BY c.created_at DESC
    ")->fetch_all(MYSQLI_ASSOC);
    ?>
<div class="table-wrap" style="margin-top:20px">
    <div class="th"><h3> Approved Comments (<?= count($approvedComments) ?>)</h3></div>
   
    
    <?php if (empty($approvedComments)): ?>
    <div style="padding: 20px; text-align: center; color: #777;">No approved comments</div>
    <?php else: ?>
    <table style="width:100%">
        <thead>
            <tr>
                <th>User</th>
                <th>Movie</th>
                <th>Comment</th>
                <th>Date</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($approvedComments as $c): ?>
        <tr>
            <td><?= htmlspecialchars($c['username'] ?? 'Unknown') ?></td>
            <td><?= htmlspecialchars($c['movie_title'] ?? 'N/A') ?></td>
            <td><?= htmlspecialchars(substr($c['comment'], 0, 100)) ?>...</td>
            <td><?= date('d M Y', strtotime($c['created_at'])) ?></td>
            <td>
                <form method="POST" onsubmit="return confirm('Delete this approved comment?')">
                    <input type="hidden" name="form_action" value="delete_comment">
                    <input type="hidden" name="comment_id" value="<?= $c['id'] ?>">
                    <button type="submit" class="btn btn-d btn-sm">🗑 Delete</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
</div>

   <?php elseif($tab === 'ratings'):?>
<?php
// Pending ratings
$pendingRatings = $db->query("
    SELECT r.*, u.username, m.title as movie_title 
    FROM user_ratings r 
    LEFT JOIN users u ON r.user_id = u.id 
    LEFT JOIN movies m ON r.movie_id = m.id 
    WHERE r.is_approved = 0
    ORDER BY r.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

// Approved ratings
$approvedRatings = $db->query("
    SELECT r.*, u.username, m.title as movie_title 
    FROM user_ratings r 
    LEFT JOIN users u ON r.user_id = u.id 
    LEFT JOIN movies m ON r.movie_id = m.id 
    WHERE r.is_approved = 1
    ORDER BY r.created_at DESC
")->fetch_all(MYSQLI_ASSOC);
?>

<!-- ============================================ -->
<!-- PENDING RATINGS SECTION -->
<!-- ============================================ -->
<div class="table-wrap">
    <div class="th">
        <h3> Pending Ratings (<?= count($pendingRatings) ?>)</h3>
        <span style="font-size: 12px; color: #777;">Approve to show on website</span>
    </div>
    
    <?php if (empty($pendingRatings)): ?>
    <div style="padding: 20px; text-align: center; color: #777;">
        No pending ratings
    </div>
    <?php else: ?>
    <table style="width:100%">
        <thead>
            <tr>
                <th>User</th>
                <th>Movie</th>
                <th>Rating</th>
                <th>Review</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($pendingRatings as $r): ?>
            <tr>
                <td><strong><?= htmlspecialchars($r['username'] ?? 'Unknown') ?></strong></td>
                <td><?= htmlspecialchars($r['movie_title'] ?? 'N/A') ?></td>
                <td style="color: var(--gold); font-weight: 700;">⭐ <?= $r['rating'] ?>/10</td>
                <td style="max-width: 250px;">
                    <?= htmlspecialchars(substr($r['review'] ?? '', 0, 80)) ?>
                    <?= strlen($r['review'] ?? '') > 80 ? '...' : '' ?>
                </td>
                <td><?= date('d M Y H:i', strtotime($r['created_at'])) ?></td>
                <td>
                    <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="form_action" value="approve_rating">
                            <input type="hidden" name="rating_id" value="<?= $r['id'] ?>">
                            <button type="submit" class="btn btn-bl btn-sm">✅ Approve</button>
                        </form>
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this rating?')">
                            <input type="hidden" name="form_action" value="delete_rating">
                            <input type="hidden" name="rating_id" value="<?= $r['id'] ?>">
                            <button type="submit" class="btn btn-d btn-sm">🗑 Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<!-- ============================================ -->
<!-- APPROVED RATINGS SECTION -->
<!-- ============================================ -->
<div class="table-wrap" style="margin-top: 20px;">
    <div class="th">
        <h3> Approved Ratings (<?= count($approvedRatings) ?>)</h3>
        <span style="font-size: 12px; color: #777;">These ratings are visible on website</span>
    </div>
    
    <?php if (empty($approvedRatings)): ?>
    <div style="padding: 20px; text-align: center; color: #777;">
        No approved ratings yet
    </div>
    <?php else: ?>
    <table style="width:100%">
        <thead>
            <tr>
                <th>User</th>
                <th>Movie</th>
                <th>Rating</th>
                <th>Review</th>
                <th>Date</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($approvedRatings as $r): ?>
            <tr>
                <td><strong><?= htmlspecialchars($r['username'] ?? 'Unknown') ?></strong></td>
                <td><?= htmlspecialchars($r['movie_title'] ?? 'N/A') ?></td>
                <td style="color: var(--gold); font-weight: 700;">⭐ <?= $r['rating'] ?>/10</td>
                <td style="max-width: 250px;">
                    <?= htmlspecialchars(substr($r['review'] ?? '', 0, 80)) ?>
                    <?= strlen($r['review'] ?? '') > 80 ? '...' : '' ?>
                </td>
                <td><?= date('d M Y', strtotime($r['created_at'])) ?></td>
                <td>
                    <form method="POST" onsubmit="return confirm('Delete this approved rating? It will be removed from website and movie rating will be recalculated.')">
                        <input type="hidden" name="form_action" value="delete_rating">
                        <input type="hidden" name="rating_id" value="<?= $r['id'] ?>">
                        <button type="submit" class="btn btn-d btn-sm">🗑 Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php elseif($tab === 'newsletter'):?>
<?php
// Fetch all subscribers
$subscribers = $db->query("
    SELECT * FROM email_subscribers 
    ORDER BY subscribed_at DESC
")->fetch_all(MYSQLI_ASSOC);

// Get subscriber count
$subscriberCount = count($subscribers);

// Handle delete subscriber
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_subscriber'])) {
    $email = $_POST['email'] ?? '';
    if ($email) {
        $stmt = $db->prepare("DELETE FROM email_subscribers WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        header('Location: admin.php?tab=newsletter&msg=' . urlencode('Subscriber deleted') . '&type=success');
        exit;
    }
}

// Handle export subscribers
if (isset($_GET['export'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="subscribers_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Email', 'Subscribed Date', 'Status']);
    
    foreach ($subscribers as $sub) {
        fputcsv($output, [$sub['email'], $sub['subscribed_at'], $sub['is_active'] ? 'Active' : 'Inactive']);
    }
    fclose($output);
    exit;
}
?>
<div class="fc">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
        <h2 style="margin:0;">📧 Email Subscribers (<?= $subscriberCount ?>)</h2>
        <div style="display: flex; gap: 10px;">
            <a href="admin.php?tab=newsletter&export=1" class="btn btn-bl">📥 Export CSV</a>
        </div>
    </div>
    
    <?php if ($subscriberCount == 0): ?>
    <div style="text-align: center; padding: 60px 20px; color: #777;">
        <div style="font-size: 48px; margin-bottom: 15px;">📧</div>
        <h3>No subscribers yet</h3>
        <p>Newsletter form se users subscribe karenge toh yahan dikhenge</p>
    </div>
    <?php else: ?>
    <div style="overflow-x: auto;">
        <table style="width:100%">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Email</th>
                    <th>Subscribed Date</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($subscribers as $index => $sub): ?>
                <tr>
                    <td><?= $index + 1 ?></td>
                    <td>
                        <strong><?= htmlspecialchars($sub['email']) ?></strong>
                    </td>
                    <td><?= date('d M Y, h:i A', strtotime($sub['subscribed_at'])) ?></td>
                    <td>
                        <?php if ($sub['is_active']): ?>
                        <span class="b b-yes">Active</span>
                        <?php else: ?>
                        <span class="b b-no">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <form method="POST" onsubmit="return confirm('Delete subscriber: <?= htmlspecialchars($sub['email']) ?>?')" style="display:inline">
                            <input type="hidden" name="delete_subscriber" value="1">
                            <input type="hidden" name="email" value="<?= htmlspecialchars($sub['email']) ?>">
                            <button type="submit" class="btn btn-d btn-sm">🗑 Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    <!-- Stats Card - Fixed Version -->
<div class="stat-grid" style="margin-top: 20px;">
    <div class="stat-card">
        <div class="s-num"><?= $subscriberCount ?></div>
        <div class="s-lbl">Total SubsCribers</div>
    </div>
</div>

</div>

<?php elseif($tab === 'contacts'):?>
<?php
$db = getDB();

// Handle Mark as Read (without redirect - direct execution)
if (isset($_GET['mark_read'])) {
    $id = intval($_GET['contact_id']);
    if ($id > 0) {
        $stmt = $db->prepare("UPDATE contacts SET is_read = 1 WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }
    // Refresh the page without redirect
    echo "<script>window.location.href = 'admin.php?tab=contacts';</script>";
    exit;
}

// Handle Delete (without redirect - direct execution)
if (isset($_GET['delete_contact'])) {
    $id = intval($_GET['contact_id']);
    if ($id > 0) {
        $stmt = $db->prepare("DELETE FROM contacts WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }
    // Refresh the page without redirect
    echo "<script>window.location.href = 'admin.php?tab=contacts';</script>";
    exit;
}

// Fetch all contacts
$contacts = $db->query("SELECT * FROM contacts ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
$unreadCount = $db->query("SELECT COUNT(*) FROM contacts WHERE is_read = 0")->fetch_row()[0];
?>
<div class="fc">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2>📬 Contact Messages (<?= $unreadCount ?> unread)</h2>
        <a href="admin.php?tab=contacts" class="btn btn-s">🔄 Refresh</a>
    </div>
    
    <?php if (empty($contacts)): ?>
    <div style="text-align:center; padding:40px; color:var(--muted)">
        <div style="font-size: 48px; margin-bottom: 15px;">📭</div>
        <h3>No messages yet</h3>
        <p>Contact form submissions will appear here</p>
    </div>
    <?php else: ?>
    <div style="overflow-x:auto">
        <table style="width:100%">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Subject</th>
                    <th>Message</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($contacts as $c): ?>
            <tr style="<?= $c['is_read'] ? '' : 'background: rgba(192,57,43,0.1);' ?>">
                <td><?= $c['id'] ?></td>
                <td><strong><?= htmlspecialchars($c['name']) ?></strong></td>
                <td><?= htmlspecialchars($c['email']) ?></td>
                <td><?= htmlspecialchars($c['subject']) ?></td>
                <td style="max-width: 250px;"><?= htmlspecialchars(substr($c['message'], 0, 80)) ?>...</td>
                <td><?= date('d M Y, h:i A', strtotime($c['created_at'])) ?></td>
                <td>
                    <?php if ($c['is_read']): ?>
                    <span class="b b-yes">✓ Read</span>
                    <?php else: ?>
                    <span class="b b-pending" style="background:rgba(240,180,41,0.2); color:#f0b429;">⏳ Unread</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                        <?php if (!$c['is_read']): ?>
                        <a href="admin.php?tab=contacts&mark_read=1&contact_id=<?= $c['id'] ?>" class="btn btn-bl btn-sm" onclick="return confirm('Mark this message as read?')">✓ Mark Read</a>
                        <?php endif; ?>
                        <a href="admin.php?tab=contacts&delete_contact=1&contact_id=<?= $c['id'] ?>" class="btn btn-d btn-sm" onclick="return confirm('Delete this message?')">🗑 Delete</a>
                    </div>
                 </td>
             </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
<!-- Stats Card - Fixed Version -->
<div class="stat-grid" style="margin-top: 20px;">
    <div class="stat-card">
        <div class="s-num"><?= $unreadCount ?></div>
        <div class="s-lbl">Unread Messages</div>
    </div>
</div>

    <?php elseif($tab === 'settings'):?>
    <div class="fc"><h2>Site Settings</h2><form method="POST"><input type="hidden" name="form_action" value="save_settings"><div class="fr c1"><div class="fg"><label>Site Name</label><input type="text" name="setting_site_name" value="<?=htmlspecialchars(getSetting('site_name','CineVault'))?>"></div><div class="fg"><label>Site Tagline</label><input type="text" name="setting_site_tagline" value="<?=htmlspecialchars(getSetting('site_tagline','Premium Movie Streaming'))?>"></div><div class="fg"><label>Items Per Page</label><input type="number" name="setting_items_per_page" value="<?=getSetting('items_per_page','20')?>"></div><div class="sw-row"><label class="sw-lbl"><input type="checkbox" name="setting_comments_auto_approve" value="1" <?=getSetting('comments_auto_approve','0')=='1'?'checked':''?>> Auto-approve comments</label><label class="sw-lbl"><input type="checkbox" name="setting_ratings_auto_approve" value="1" <?=getSetting('ratings_auto_approve','0')=='1'?'checked':''?>> Auto-approve ratings</label></div></div><div class="f-actions"><button type="submit" class="btn btn-p">Save Settings</button></div></form></div>
    <?php endif;?>

  </div>
</div>

<!-- Rename Modal -->
<div class="modal" id="renameModal"><div class="modal-box"><h3>Rename Genre</h3><form method="POST"><input type="hidden" name="form_action" value="rename_genre"><input type="hidden" name="genre_id" id="rg_id"><div class="fg"><label>New Name</label><input type="text" name="genre_name" id="rg_name" required></div><div style="display:flex;gap:8px;margin-top:15px"><button type="submit" class="btn btn-p">Save</button><button type="button" class="btn btn-s" onclick="closeRename()">Cancel</button></div></form></div></div>

<script>
function filterTable(input, tableId) {
    const filter = input.value.toLowerCase();
    const rows = document.querySelectorAll('#' + tableId + ' tbody tr');
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
}
function openRename(id, name) {
    document.getElementById('rg_id').value = id;
    document.getElementById('rg_name').value = name;
    document.getElementById('renameModal').style.display = 'flex';
}
function closeRename() { document.getElementById('renameModal').style.display = 'none'; }
document.getElementById('renameModal')?.addEventListener('click', function(e) { if (e.target === this) closeRename(); });
</script>


<script>
function editCollection(id, name, slug, desc, cover, order) {
    document.getElementById('edit_collection_id').value = id;
    document.getElementById('edit_collection_name').value = name;
    document.getElementById('edit_collection_slug').value = slug;
    document.getElementById('edit_collection_desc').value = desc;
    document.getElementById('edit_collection_cover').value = cover;
    document.getElementById('edit_display_order').value = order;
    
    // Load current movies in this collection
    fetch(`pages/collections.php?action=get_movies&collection_id=${id}`)
        .then(res => res.json())
        .then(data => {
            if (data.movies) {
                document.querySelectorAll('.collection-movie-checkbox').forEach(cb => {
                    cb.checked = data.movies.includes(parseInt(cb.value));
                });
            }
        });
    
    document.getElementById('editCollectionModal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editCollectionModal').style.display = 'none';
}

document.getElementById('editCollectionModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeEditModal();
});
</script>
</body>
</html>
<?php

function showLogin($error=''){?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Admin Login</title><style>
*{margin:0;padding:0;box-sizing:border-box}body{background:#080808;display:flex;align-items:center;justify-content:center;min-height:100vh;font-family:'Segoe UI',sans-serif}.box{background:#141414;border:1px solid #232323;border-radius:12px;padding:44px 38px;width:360px}h1{font-size:22px;letter-spacing:3px;text-align:center;color:#f0f0f0;margin-bottom:4px}h1 span{color:#c0392b}p{text-align:center;font-size:11px;color:#555;margin-bottom:30px}label{display:block;font-size:10px;font-weight:700;text-transform:uppercase;color:#555;margin-bottom:6px}input{width:100%;padding:10px 13px;background:#1a1a1a;border:1px solid #232323;border-radius:6px;color:#f0f0f0;font-size:14px;margin-bottom:16px}input:focus{border-color:#7d1f1f;outline:none}button{width:100%;padding:12px;background:#c0392b;color:#fff;border:none;border-radius:6px;font-size:14px;font-weight:700;cursor:pointer}.err{background:rgba(192,57,43,0.1);border:1px solid rgba(192,57,43,0.25);color:#e74c3c;padding:10px;border-radius:6px;font-size:13px;margin-bottom:14px}
</style></head><body><div class="box"><h1>CINE<span>VAULT</span></h1><p>Admin Panel</p><?php if($error):?><div class="err"><?=htmlspecialchars($error)?></div><?php endif;?><form method="POST"><input type="hidden" name="admin_login" value="1"><label>Password</label><input type="password" name="admin_pass" autofocus><button type="submit">Enter</button></form></div></body></html>
<?php }?>

