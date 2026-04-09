<?php
require_once __DIR__ . '/includes/config.php';
$loggedIn = isLoggedIn();
$username = $_SESSION['username'] ?? '';
$db = getDB();

// Fetch all genres
$genres = $db->query("SELECT g.*, COUNT(mg.movie_id) as count FROM genres g LEFT JOIN movie_genres mg ON g.id=mg.genre_id GROUP BY g.id ORDER BY g.name")->fetch_all(MYSQLI_ASSOC);

// Fetch sort options
$sort  = in_array($_GET['sort'] ?? '', ['rating','year','views','title','created_at']) ? $_GET['sort'] : 'created_at';
$genre = $_GET['genre'] ?? 'all';
$search = trim($_GET['q'] ?? '');
$page  = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

// Build query
$where = []; $params = []; $types = '';

if ($genre !== 'all') {
    $where[] = "g.slug = ?";
    $params[] = $genre; $types .= 's';
}
if ($search !== '') {
    $where[] = "(m.title LIKE ? OR m.description LIKE ? OR m.cast_members LIKE ?)";
    $sw = "%$search%";
    $params[] = $sw; $params[] = $sw; $params[] = $sw;
    $types .= 'sss';
}

$join  = $genre !== 'all' ? "JOIN movie_genres mg ON m.id=mg.movie_id JOIN genres g ON mg.genre_id=g.id" : "LEFT JOIN movie_genres mg ON m.id=mg.movie_id LEFT JOIN genres g ON mg.genre_id=g.id";
$whereSQL = $where ? "WHERE " . implode(" AND ", $where) : "";

// Count
$countSQL = "SELECT COUNT(DISTINCT m.id) FROM movies m $join $whereSQL";
$cstmt = $db->prepare($countSQL);
if ($params) { $cstmt->bind_param($types, ...$params); }
$cstmt->execute();
$total = $cstmt->get_result()->fetch_row()[0];
$pages = max(1, ceil($total / $limit));

// Movies
$moviesSQL = "SELECT DISTINCT m.* FROM movies m $join $whereSQL ORDER BY m.$sort DESC LIMIT ? OFFSET ?";
$mstmt = $db->prepare($moviesSQL);
$allParams = array_merge($params, [$limit, $offset]);
$allTypes  = $types . 'ii';
$mstmt->bind_param($allTypes, ...$allParams);
$mstmt->execute();
$movies = $mstmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Attach genres to each movie
if ($movies) {
    $ids = array_column($movies, 'id');
    $ph  = implode(',', array_fill(0, count($ids), '?'));
    $gs  = $db->prepare("SELECT mg.movie_id, g.name, g.slug FROM movie_genres mg JOIN genres g ON mg.genre_id=g.id WHERE mg.movie_id IN ($ph)");
    $gs->bind_param(str_repeat('i', count($ids)), ...$ids);
    $gs->execute();
    $gRows = $gs->get_result()->fetch_all(MYSQLI_ASSOC);
    $gMap  = [];
    foreach ($gRows as $gr) $gMap[$gr['movie_id']][] = $gr;
    foreach ($movies as &$mv) $mv['genres'] = $gMap[$mv['id']] ?? [];
}

// Active genre name
$activeGenreName = 'All Movies';
foreach ($genres as $g) { if ($g['slug'] === $genre) { $activeGenreName = $g['name']; break; } }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?= htmlspecialchars($activeGenreName) ?> — CineVault</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
:root {
  --black:#080808; --dark-2:#141414; --dark-3:#1a1a1a; --dark-4:#222; --dark-5:#2a2a2a;
  --red:#c0392b; --red-light:#e74c3c; --red-dim:#7d1f1f;
  --text:#f0f0f0; --text-sec:#a0a0a0; --text-muted:#555;
  --border:#2a2a2a; --borderl:#333;
  --gold:#f0b429;
  --font-display:'Bebas Neue',sans-serif;
  --font-body:'Inter',sans-serif;
  --r:6px; --rl:12px;
  --transition:0.2s ease;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
body{font-family:var(--font-body);background:var(--black);color:var(--text);min-height:100vh;-webkit-font-smoothing:antialiased}
a{text-decoration:none;color:inherit}
img{display:block;max-width:100%}
::-webkit-scrollbar{width:6px;height:6px}
::-webkit-scrollbar-track{background:var(--dark-2)}
::-webkit-scrollbar-thumb{background:var(--dark-5);border-radius:3px}
::-webkit-scrollbar-thumb:hover{background:var(--red-dim)}

/* NAVBAR */
.navbar{position:fixed;top:0;left:0;right:0;z-index:1000;height:64px;display:flex;align-items:center;padding:0 40px;background:rgba(8,8,8,0.97);backdrop-filter:blur(12px);border-bottom:1px solid rgba(255,255,255,0.04)}
.nav-logo{font-family:var(--font-display);font-size:26px;letter-spacing:3px;flex-shrink:0}
.nav-logo span{color:var(--red)}
.nav-links{display:flex;gap:24px;margin-left:36px}
.nav-links a{font-size:13px;font-weight:500;color:var(--text-sec);transition:color var(--transition)}
.nav-links a:hover,.nav-links a.active{color:var(--text)}
.nav-right{margin-left:auto;display:flex;align-items:center;gap:10px}
.btn{display:inline-flex;align-items:center;gap:6px;padding:7px 16px;border-radius:var(--r);font-size:13px;font-weight:600;border:none;cursor:pointer;font-family:var(--font-body);transition:all var(--transition)}
.btn-red{background:var(--red);color:#fff}.btn-red:hover{background:var(--red-light)}
.btn-outline{background:transparent;border:1px solid var(--borderl);color:var(--text-sec)}.btn-outline:hover{color:var(--text);border-color:var(--text-sec)}

/* PAGE HEADER */
.page-head{padding:100px 40px 0;position:relative;overflow:hidden}
.page-head-bg{position:absolute;inset:0;background:radial-gradient(ellipse at 20% 50%, rgba(192,57,43,0.08) 0%, transparent 60%);pointer-events:none}
.page-head-title{font-family:var(--font-display);font-size:clamp(40px,6vw,72px);letter-spacing:3px;line-height:1;margin-bottom:6px}
.page-head-title span{color:var(--red)}
.page-head-sub{font-size:13px;color:var(--text-muted);letter-spacing:0.5px}

/* FILTERS BAR */
.filters-wrap{position:sticky;top:64px;z-index:100;background:rgba(8,8,8,0.96);backdrop-filter:blur(12px);border-bottom:1px solid var(--border);padding:0 40px}
.filters-inner{display:flex;align-items:center;gap:14px;height:60px;flex-wrap:nowrap;overflow-x:auto;-ms-overflow-style:none;scrollbar-width:none}
.filters-inner::-webkit-scrollbar{display:none}

/* SEARCH */
.search-wrap{display:flex;align-items:center;gap:8px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:var(--r);padding:7px 12px;transition:border-color var(--transition);flex-shrink:0}
.search-wrap:focus-within{border-color:var(--red-dim)}
.search-wrap input{background:none;border:none;outline:none;color:var(--text);font-size:13px;width:180px;font-family:var(--font-body)}
.search-wrap input::placeholder{color:var(--text-muted)}

/* CATEGORY DROPDOWN */
.cat-select-wrap{position:relative;flex-shrink:0}
.cat-btn{display:flex;align-items:center;gap:8px;padding:8px 14px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:var(--r);font-size:13px;font-weight:600;color:var(--text);cursor:pointer;transition:all var(--transition);white-space:nowrap}
.cat-btn:hover,.cat-btn.open{border-color:var(--red-dim);background:rgba(192,57,43,0.1)}
.cat-btn svg{transition:transform 0.2s}
.cat-btn.open svg{transform:rotate(180deg)}
.cat-dropdown{position:absolute;top:calc(100% + 6px);left:0;background:var(--dark-3);border:1px solid var(--border);border-radius:var(--rl);min-width:220px;max-height:340px;overflow-y:auto;box-shadow:0 16px 40px rgba(0,0,0,0.7);z-index:200;opacity:0;visibility:hidden;transform:translateY(-8px);transition:all 0.18s ease}
.cat-dropdown.open{opacity:1;visibility:visible;transform:translateY(0)}
.cat-option{display:flex;align-items:center;justify-content:space-between;padding:10px 14px;font-size:13px;color:var(--text-sec);cursor:pointer;transition:background var(--transition)}
.cat-option:hover{background:rgba(255,255,255,0.05);color:var(--text)}
.cat-option.active{color:var(--red-light);background:rgba(192,57,43,0.08)}
.cat-option .count{font-size:11px;color:var(--text-muted);background:var(--dark-5);padding:1px 7px;border-radius:20px}

/* SORT */
.sort-select{padding:8px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:var(--r);color:var(--text);font-size:13px;outline:none;cursor:pointer;font-family:var(--font-body);transition:border-color var(--transition);flex-shrink:0}
.sort-select:focus{border-color:var(--red-dim)}
.sort-select option{background:var(--dark-4)}

/* RESULTS INFO */
.results-info{margin-left:auto;font-size:12px;color:var(--text-muted);white-space:nowrap;flex-shrink:0}
.results-info strong{color:var(--text-sec)}

/* GENRE PILLS */
.genre-pills-wrap{padding:20px 40px 0}
.genre-pills{display:flex;gap:8px;flex-wrap:wrap}
.g-pill{padding:6px 16px;border-radius:50px;background:var(--dark-3);border:1px solid var(--border);font-size:12px;font-weight:500;color:var(--text-sec);cursor:pointer;transition:all var(--transition);text-decoration:none}
.g-pill:hover{border-color:var(--borderl);color:var(--text)}
.g-pill.active{background:var(--red);border-color:var(--red);color:#fff}

/* MOVIES GRID */
.movies-section{padding:28px 40px 60px}
.movies-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(165px,1fr));gap:16px}

/* CARD */
.movie-card{position:relative;border-radius:var(--rl);overflow:hidden;cursor:pointer;background:var(--dark-2);transition:transform 0.25s ease,box-shadow 0.25s ease;animation:cardIn 0.4s ease both}
.movie-card:hover{transform:translateY(-6px) scale(1.02);box-shadow:0 16px 40px rgba(0,0,0,0.7),0 0 0 1px rgba(192,57,43,0.25);z-index:2}
@keyframes cardIn{from{opacity:0;transform:translateY(18px)}to{opacity:1;transform:translateY(0)}}
.card-poster{aspect-ratio:2/3;position:relative;overflow:hidden}
.card-poster img{width:100%;height:100%;object-fit:cover;transition:transform 0.4s ease}
.movie-card:hover .card-poster img{transform:scale(1.06)}
.card-placeholder{width:100%;height:100%;background:linear-gradient(135deg,var(--dark-4),var(--dark-3));display:flex;align-items:center;justify-content:center;color:var(--text-muted);font-size:36px}
.card-overlay{position:absolute;inset:0;background:linear-gradient(to top,rgba(0,0,0,0.92) 0%,transparent 55%);opacity:0;transition:opacity 0.25s ease;display:flex;align-items:center;justify-content:center}
.movie-card:hover .card-overlay{opacity:1}
.play-btn{width:50px;height:50px;border-radius:50%;background:rgba(255,255,255,0.92);display:flex;align-items:center;justify-content:center;transform:scale(0.8);transition:transform 0.2s ease}
.movie-card:hover .play-btn{transform:scale(1)}
.card-quality{position:absolute;top:8px;left:8px;background:rgba(0,0,0,0.78);backdrop-filter:blur(4px);border:1px solid rgba(240,180,41,0.35);color:var(--gold);font-size:10px;font-weight:700;letter-spacing:1px;padding:2px 6px;border-radius:3px}
.wl-btn{position:absolute;top:8px;right:8px;width:30px;height:30px;border-radius:50%;background:rgba(0,0,0,0.72);backdrop-filter:blur(4px);border:1px solid rgba(255,255,255,0.15);color:#fff;display:flex;align-items:center;justify-content:center;opacity:0;transition:opacity var(--transition),background var(--transition);z-index:2;cursor:pointer}
.movie-card:hover .wl-btn{opacity:1}
.wl-btn:hover{background:var(--red);border-color:var(--red)}
.wl-btn.saved{opacity:1;background:var(--red);border-color:var(--red)}
.card-info{padding:10px 10px 12px}
.card-title{font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-bottom:5px}
.card-meta{display:flex;align-items:center;justify-content:space-between;font-size:11px;color:var(--text-muted)}
.card-rating{display:flex;align-items:center;gap:3px;color:var(--gold);font-weight:700}
.card-genres{display:flex;gap:4px;flex-wrap:wrap;margin-top:5px}
.genre-tag{font-size:10px;font-weight:500;padding:2px 7px;border-radius:20px;background:var(--dark-4);color:var(--text-muted)}

/* EMPTY */
.empty-state{text-align:center;padding:80px 20px;color:var(--text-muted)}
.empty-state h3{font-family:var(--font-display);font-size:32px;letter-spacing:2px;margin-bottom:10px;color:var(--dark-5)}
.empty-state p{font-size:14px;margin-bottom:24px}
.empty-state a{display:inline-flex;padding:10px 24px;background:var(--red);color:#fff;border-radius:var(--r);font-size:14px;font-weight:600;transition:background var(--transition)}
.empty-state a:hover{background:var(--red-light)}

/* PAGINATION */
.pagination{display:flex;justify-content:center;gap:8px;padding:20px 0 40px;flex-wrap:wrap}
.pag-btn{padding:8px 14px;border-radius:var(--r);border:1px solid var(--border);background:var(--dark-3);color:var(--text-sec);font-size:13px;cursor:pointer;font-family:var(--font-body);transition:all var(--transition)}
.pag-btn:hover{border-color:var(--borderl);color:var(--text)}
.pag-btn.active{background:var(--red);border-color:var(--red);color:#fff}
.pag-btn:disabled{opacity:0.35;cursor:not-allowed}

/* MODAL */
.modal-overlay{position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.92);backdrop-filter:blur(6px);display:flex;align-items:center;justify-content:center;padding:20px;opacity:0;visibility:hidden;transition:opacity 0.3s ease,visibility 0.3s ease}
.modal-overlay.open{opacity:1;visibility:visible}
.modal-box{background:var(--dark-2);border-radius:var(--rl);border:1px solid var(--border);width:100%;max-width:1000px;max-height:92vh;overflow-y:auto;transform:translateY(20px) scale(0.97);transition:transform 0.3s ease;box-shadow:0 40px 80px rgba(0,0,0,0.8)}
.modal-overlay.open .modal-box{transform:translateY(0) scale(1)}
.player-wrap{position:relative;background:#000;border-radius:var(--rl) var(--rl) 0 0;overflow:hidden}
.player-wrap::before{content:'';display:block;padding-top:56.25%}
.player-wrap iframe{position:absolute;inset:0;width:100%;height:100%;border:none}
.modal-close{position:absolute;top:14px;right:14px;z-index:10;width:34px;height:34px;border-radius:50%;background:rgba(0,0,0,0.72);backdrop-filter:blur(4px);border:1px solid rgba(255,255,255,0.18);color:#fff;font-size:18px;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:background var(--transition)}
.modal-close:hover{background:var(--red);border-color:var(--red)}
.modal-info{padding:24px 28px}
.modal-info-top{display:flex;gap:18px;margin-bottom:20px}
.modal-poster{flex-shrink:0;width:90px;height:135px;border-radius:var(--r);overflow:hidden;box-shadow:0 8px 24px rgba(0,0,0,0.5)}
.modal-poster img{width:100%;height:100%;object-fit:cover}
.modal-text h2{font-family:var(--font-display);font-size:28px;letter-spacing:1.5px;margin-bottom:8px;line-height:1}
.modal-meta{display:flex;align-items:center;gap:12px;font-size:12px;color:var(--text-sec);flex-wrap:wrap;margin-bottom:10px}
.modal-meta .sep{color:var(--text-muted)}
.star{color:var(--gold);font-weight:700}
.modal-genres{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:12px}
.modal-genre-tag{padding:3px 11px;border-radius:20px;background:var(--dark-4);border:1px solid var(--border);font-size:12px;color:var(--text-sec)}
.modal-desc{font-size:13px;line-height:1.75;color:var(--text-sec)}
.modal-actions{display:flex;gap:10px;margin-top:16px;flex-wrap:wrap}
.btn-wl{display:flex;align-items:center;gap:7px;padding:9px 18px;background:rgba(255,255,255,0.1);color:#fff;border:1px solid rgba(255,255,255,0.18);border-radius:var(--r);font-size:13px;font-weight:600;cursor:pointer;backdrop-filter:blur(4px);transition:all var(--transition);font-family:var(--font-body)}
.btn-wl:hover{background:rgba(255,255,255,0.18)}
.btn-wl.saved{border-color:var(--red-dim);color:var(--red-light)}
.modal-details{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px;padding:18px 28px;border-top:1px solid var(--border)}
.detail label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--text-muted);display:block;margin-bottom:3px}
.detail span{font-size:13px;color:var(--text-sec)}

/* AUTH MODAL */
.auth-modal{position:fixed;inset:0;z-index:9998;background:rgba(0,0,0,0.88);backdrop-filter:blur(8px);display:flex;align-items:center;justify-content:center;padding:20px;opacity:0;visibility:hidden;transition:opacity 0.3s ease,visibility 0.3s ease}
.auth-modal.open{opacity:1;visibility:visible}
.auth-box{background:var(--dark-2);border:1px solid var(--border);border-radius:var(--rl);width:100%;max-width:400px;padding:36px;box-shadow:0 40px 80px rgba(0,0,0,0.7);position:relative;transform:translateY(14px);transition:transform 0.3s ease}
.auth-modal.open .auth-box{transform:translateY(0)}
.auth-logo{font-family:var(--font-display);font-size:22px;letter-spacing:3px;text-align:center;margin-bottom:4px}
.auth-logo span{color:var(--red)}
.auth-sub{text-align:center;font-size:12px;color:var(--text-muted);margin-bottom:24px}
.auth-tabs{display:flex;border-bottom:1px solid var(--border);margin-bottom:20px}
.a-tab{flex:1;padding:9px;text-align:center;font-size:13px;font-weight:600;color:var(--text-muted);cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-1px;transition:all var(--transition)}
.a-tab.active{color:var(--text);border-bottom-color:var(--red)}
.a-form{display:none}.a-form.active{display:block}
.f-group{margin-bottom:14px}
.f-label{display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-muted);margin-bottom:6px}
.f-input{width:100%;padding:10px 13px;background:var(--dark-3);border:1px solid var(--border);border-radius:var(--r);color:var(--text);font-size:13px;outline:none;font-family:var(--font-body);transition:border-color var(--transition)}
.f-input:focus{border-color:var(--red-dim)}
.f-input::placeholder{color:var(--text-muted)}
.f-err{font-size:12px;color:var(--red-light);margin-top:5px;display:none}
.a-submit{width:100%;padding:11px;background:var(--red);color:#fff;border:none;border-radius:var(--r);font-size:14px;font-weight:700;cursor:pointer;margin-top:6px;font-family:var(--font-body);transition:background var(--transition)}
.a-submit:hover{background:var(--red-light)}
.a-close{position:absolute;top:14px;right:14px;width:30px;height:30px;border-radius:50%;background:var(--dark-4);border:1px solid var(--border);color:var(--text-muted);font-size:15px;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:all var(--transition)}
.a-close:hover{background:var(--red);color:#fff;border-color:var(--red)}

/* TOAST */
.toast-wrap{position:fixed;bottom:28px;right:28px;z-index:99999;display:flex;flex-direction:column;gap:8px}
.toast{background:var(--dark-3);border:1px solid var(--border);border-radius:var(--r);padding:11px 16px;font-size:13px;font-weight:500;color:var(--text);box-shadow:0 8px 24px rgba(0,0,0,0.5);transform:translateX(120%);transition:transform 0.3s ease;max-width:260px}
.toast.show{transform:translateX(0)}
.toast.success{border-left:3px solid #27ae60}
.toast.error{border-left:3px solid var(--red)}
.toast.info{border-left:3px solid #3498db}

/* SPINNER */
.spinner{width:36px;height:36px;border-radius:50%;border:3px solid var(--dark-5);border-top-color:var(--red);animation:spin 0.7s linear infinite;margin:48px auto}
@keyframes spin{to{transform:rotate(360deg)}}

/* USER MENU */
.user-menu{position:relative}
.user-trigger{display:flex;align-items:center;gap:7px;background:none;border:none;color:var(--text);font-size:13px;font-weight:500;cursor:pointer;padding:6px 10px;border-radius:var(--r);transition:background var(--transition);font-family:var(--font-body)}
.user-trigger:hover{background:var(--dark-4)}
.u-avatar{width:28px;height:28px;border-radius:50%;background:var(--red-dim);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#fff;flex-shrink:0}
.u-dropdown{position:absolute;top:calc(100% + 6px);right:0;background:var(--dark-3);border:1px solid var(--border);border-radius:var(--rl);min-width:170px;box-shadow:0 8px 32px rgba(0,0,0,0.6);overflow:hidden;opacity:0;visibility:hidden;transform:translateY(-6px);transition:all 0.16s ease}
.user-menu.open .u-dropdown{opacity:1;visibility:visible;transform:translateY(0)}
.dd-item{display:flex;align-items:center;gap:9px;padding:10px 14px;font-size:13px;color:var(--text-sec);cursor:pointer;background:none;border:none;width:100%;text-align:left;font-family:var(--font-body);transition:background var(--transition),color var(--transition)}
.dd-item:hover{background:var(--dark-4);color:var(--text)}
.dd-item.danger:hover{color:var(--red-light)}
.dd-div{height:1px;background:var(--border);margin:4px 0}
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

/* RESPONSIVE */
@media(max-width:768px){
  .navbar{padding:0 20px}
  .nav-links{display:none}
  .page-head{padding:84px 20px 0}
  .filters-wrap{padding:0 20px}
  .genre-pills-wrap{padding:16px 20px 0}
  .movies-section{padding:20px 20px 48px}
  .movies-grid{grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:12px}
  .modal-info{padding:16px 18px}
  .modal-info-top{flex-direction:column;gap:12px}
  .modal-details{padding:14px 18px}
}
</style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
  <a class="nav-logo" href="index.php">CINE<span>VAULT</span></a>
  <ul class="nav-links" style="list-style:none;display:flex;gap:24px;margin-left:36px">
    <li><a href="index.php">Home</a></li>
    <li><a href="movies.php" class="active">Browse</a></li>
    <li><a href="collections.php" onclick="sessionStorage.setItem('goView','genres-page')">Collections</a></li>
    <li><a href="charts.php" onclick="requireAuth(()=>window.location='index.php?wl=1')">Top Charts</a></li>
    <li><a href="Contact.php" class="active">Contact</a></li>
    <li><a href="about.php" class="active">About</a></li>
  </ul>
  <div class="nav-right">
    <?php if ($loggedIn): ?>
    <div class="user-menu" id="userMenu">
      <button class="user-trigger" onclick="toggleMenu()">
        <div class="u-avatar"><?= strtoupper(substr($username,0,1)) ?></div>
        <span><?= htmlspecialchars($username) ?></span>
        <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg>
      </button>
      <div class="u-dropdown">
        <button class="dd-item" onclick="window.location='index.php'">
          <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
          My Watchlist
        </button>
        <div class="dd-div"></div>
        <button class="dd-item danger" onclick="logoutUser()">
          <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
          Sign Out
        </button>
      </div>
    </div>
    <?php else: ?>
    <button class="btn btn-outline" onclick="openAuth('login')">Sign In</button>
    <button class="btn btn-red" onclick="openAuth('register')">Join Free</button>
    <?php endif; ?>
  </div>
</nav>

<!-- PAGE HEADER -->
<div class="page-head">
  <div class="page-head-bg"></div>
  <div style="position:relative;z-index:1">
    <h1 class="page-head-title">
      <?php if ($search): ?>
        SEARCH <span>RESULTS</span>
      <?php elseif ($genre !== 'all'): ?>
        <?= strtoupper(htmlspecialchars($activeGenreName)) ?> <span>MOVIES</span>
      <?php else: ?>
        ALL <span>MOVIES</span>
      <?php endif; ?>
    </h1>
    <p class="page-head-sub">
      <?php if ($search): ?>
        <?= $total ?> result<?= $total != 1 ? 's' : '' ?> for "<?= htmlspecialchars($search) ?>"
      <?php else: ?>
        <?= $total ?> movie<?= $total != 1 ? 's' : '' ?> available<?= $genre !== 'all' ? ' in '.$activeGenreName : '' ?>
      <?php endif; ?>
    </p>
  </div>
</div>

<!-- GENRE PILLS -->
<div class="genre-pills-wrap" style="padding-top:24px;padding-bottom:8px">
  <div class="genre-pills">
    <a href="movies.php?sort=<?= $sort ?>" class="g-pill <?= $genre==='all'?'active':'' ?>">All</a>
    <?php foreach ($genres as $g): ?>
    <a href="movies.php?genre=<?= urlencode($g['slug']) ?>&sort=<?= $sort ?>" class="g-pill <?= $genre===$g['slug']?'active':'' ?>">
      <?= htmlspecialchars($g['name']) ?>
    </a>
    <?php endforeach; ?>
  </div>
</div>

<!-- FILTERS BAR -->
<div class="filters-wrap">
  <div class="filters-inner">

    <!-- Search -->
    <form method="GET" action="movies.php" style="display:flex;align-items:center;gap:6px">
      <?php if ($genre !== 'all'): ?><input type="hidden" name="genre" value="<?= htmlspecialchars($genre) ?>"><?php endif; ?>
      <input type="hidden" name="sort" value="<?= $sort ?>">
      <div class="search-wrap">
        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        <input type="text" name="q" placeholder="Search movies..." value="<?= htmlspecialchars($search) ?>" autocomplete="off">
      </div>
      <?php if ($search): ?>
      <a href="movies.php?genre=<?= urlencode($genre) ?>&sort=<?= $sort ?>" style="font-size:12px;color:var(--text-muted);white-space:nowrap;padding:0 4px">Clear</a>
      <?php endif; ?>
    </form>

    <!-- Category Dropdown -->
    <div class="cat-select-wrap" id="catWrap">
      <button class="cat-btn" id="catBtn" onclick="toggleCat()">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
        <?= $genre === 'all' ? 'All Categories' : htmlspecialchars($activeGenreName) ?>
        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg>
      </button>
      <div class="cat-dropdown" id="catDropdown">
        <div class="cat-option <?= $genre==='all'?'active':'' ?>" onclick="selectGenre('all')">
          <span>All Categories</span>
          <span class="count"><?= array_sum(array_column($genres,'count')) ?></span>
        </div>
        <?php foreach ($genres as $g): ?>
        <div class="cat-option <?= $genre===$g['slug']?'active':'' ?>" onclick="selectGenre('<?= htmlspecialchars($g['slug']) ?>')">
          <span><?= htmlspecialchars($g['name']) ?></span>
          <span class="count"><?= $g['count'] ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Sort -->
    <select class="sort-select" onchange="changeSort(this.value)">
      <option value="created_at" <?= $sort==='created_at'?'selected':'' ?>>Newest First</option>
      <option value="rating"     <?= $sort==='rating'    ?'selected':'' ?>>Top Rated</option>
      <option value="year"       <?= $sort==='year'      ?'selected':'' ?>>By Year</option>
      <option value="views"      <?= $sort==='views'     ?'selected':'' ?>>Most Watched</option>
      <option value="title"      <?= $sort==='title'     ?'selected':'' ?>>A - Z</option>
    </select>

    <!-- Results count -->
    <div class="results-info">
      <strong><?= number_format($total) ?></strong> movies
      <?php if ($pages > 1): ?> &middot; page <?= $page ?>/<?= $pages ?><?php endif; ?>
    </div>

  </div>
</div>

<!-- MOVIES GRID -->
<div class="movies-section">
  <?php if ($movies): ?>
  <div class="movies-grid">
    <?php foreach ($movies as $i => $m):
      $genreTagsHtml = implode('', array_map(fn($g) => '<span class="genre-tag">'.htmlspecialchars($g['name']).'</span>', array_slice($m['genres'],0,2)));
      $delay = ($i % 20) * 40;
    ?>
    <div class="movie-card" style="animation-delay:<?= $delay ?>ms" onclick="openMovie(<?= $m['id'] ?>)">
      <div class="card-poster">
        <?php if ($m['poster']): ?>
        <img src="<?= htmlspecialchars($m['poster']) ?>" alt="<?= htmlspecialchars($m['title']) ?>" loading="lazy" onerror="this.parentNode.innerHTML='<div class=\'card-placeholder\'>&#9654;</div>'">
        <?php else: ?>
        <div class="card-placeholder">&#9654;</div>
        <?php endif; ?>
        <div class="card-overlay">
          <div class="play-btn">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="#111"><polygon points="5 3 19 12 5 21 5 3"/></svg>
          </div>
        </div>
        <span class="card-quality"><?= htmlspecialchars($m['quality']) ?></span>
        <button class="wl-btn" id="wl-<?= $m['id'] ?>" onclick="toggleWatchlist(event,<?= $m['id'] ?>)" title="Save to Watchlist">
          <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
        </button>
      </div>
      <div class="card-info">
        <div class="card-title"><?= htmlspecialchars($m['title']) ?></div>
        <div class="card-meta">
          <div class="card-rating">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="var(--gold)" stroke="none"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
            <?= number_format($m['rating'],1) ?>
          </div>
          <span><?= $m['year'] ?></span>
          <?php if ($m['duration']): $h=floor($m['duration']/60); $min=$m['duration']%60; ?>
          <span><?= $h?$h.'h '.$min.'m':$min.'m' ?></span>
          <?php endif; ?>
        </div>
        <?php if ($genreTagsHtml): ?><div class="card-genres"><?= $genreTagsHtml ?></div><?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- PAGINATION -->
  <?php if ($pages > 1): ?>
  <div class="pagination">
    <?php if ($page > 1): ?>
    <button class="pag-btn" onclick="goPage(<?= $page-1 ?>)">&lsaquo; Prev</button>
    <?php endif; ?>
    <?php
    $start = max(1, $page-2); $end = min($pages, $page+2);
    if ($start > 1) echo '<span style="padding:8px 6px;color:var(--text-muted)">...</span>';
    for ($p = $start; $p <= $end; $p++):
    ?>
    <button class="pag-btn <?= $p===$page?'active':'' ?>" onclick="goPage(<?= $p ?>)"><?= $p ?></button>
    <?php endfor;
    if ($end < $pages) echo '<span style="padding:8px 6px;color:var(--text-muted)">...</span>';
    ?>
    <?php if ($page < $pages): ?>
    <button class="pag-btn" onclick="goPage(<?= $page+1 ?>)">Next &rsaquo;</button>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <?php else: ?>
  <div class="empty-state">
    <h3>NO MOVIES FOUND</h3>
    <p><?= $search ? 'No results for "'.htmlspecialchars($search).'"' : 'No movies in this category yet.' ?></p>
    <a href="movies.php">Browse All Movies</a>
  </div>
  <?php endif; ?>
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

<!-- MOVIE MODAL -->
<div class="modal-overlay" id="movieModal" onclick="closeModalOutside(event)">
  <div class="modal-box" id="modalBox">
    <div class="player-wrap">
      <button class="modal-close" onclick="closeModal()">&times;</button>
      <iframe id="movieIframe" src="" allowfullscreen allow="accelerometer;autoplay;clipboard-write;encrypted-media;gyroscope;picture-in-picture"></iframe>
    </div>
    <div id="modalContent"></div>
  </div>
</div>

<!-- AUTH MODAL -->
<div class="auth-modal" id="authModal" onclick="closeAuthOutside(event)">
  <div class="auth-box">
    <button class="a-close" onclick="closeAuth()">&times;</button>
    <div class="auth-logo">CINE<span>VAULT</span></div>
    <p class="auth-sub">Sign in to save your watchlist</p>
    <div class="auth-tabs">
      <div class="a-tab active" id="tab-login" onclick="switchTab('login')">Sign In</div>
      <div class="a-tab" id="tab-register" onclick="switchTab('register')">Register</div>
    </div>
    <form class="a-form active" id="form-login" onsubmit="handleLogin(event)">
      <div class="f-group"><label class="f-label">Email</label><input type="email" class="f-input" id="loginEmail" placeholder="you@example.com" required></div>
      <div class="f-group"><label class="f-label">Password</label><input type="password" class="f-input" id="loginPassword" placeholder="Your password" required></div>
      <div class="f-err" id="loginError"></div>
      <button type="submit" class="a-submit" id="loginBtn">Sign In</button>
    </form>
    <form class="a-form" id="form-register" onsubmit="handleRegister(event)">
      <div class="f-group"><label class="f-label">Username</label><input type="text" class="f-input" id="regUsername" placeholder="Choose username" required></div>
      <div class="f-group"><label class="f-label">Email</label><input type="email" class="f-input" id="regEmail" placeholder="you@example.com" required></div>
      <div class="f-group"><label class="f-label">Password</label><input type="password" class="f-input" id="regPassword" placeholder="Min 6 characters" required></div>
      <div class="f-err" id="registerError"></div>
      <button type="submit" class="a-submit" id="registerBtn">Create Account</button>
    </form>
  </div>
</div>

<div class="toast-wrap" id="toastWrap"></div>

<script>
const LOGGED_IN = <?= $loggedIn ? 'true' : 'false' ?>;
const BASE = '<?= rtrim(SITE_URL, '/') ?>';

// ---- UTILS ----
function toast(msg, type='info', dur=3000) {
  const w = document.getElementById('toastWrap');
  const el = document.createElement('div');
  el.className = `toast ${type}`;
  el.textContent = msg;
  w.appendChild(el);
  requestAnimationFrame(() => el.classList.add('show'));
  setTimeout(() => { el.classList.remove('show'); setTimeout(() => el.remove(), 400); }, dur);
}
async function api(url) { return (await fetch(url)).json(); }
async function post(url, data) {
  const fd = new FormData();
  for (const k in data) fd.append(k, data[k]);
  return (await fetch(url, { method:'POST', body:fd })).json();
}
function requireAuth(cb) {
  if (LOGGED_IN) cb();
  else openAuth('login');
}
function minToDur(m) {
  if (!m) return '';
  const h = Math.floor(m/60), min = m%60;
  return h ? `${h}h ${min}m` : `${min}m`;
}

// ---- CATEGORY DROPDOWN ----
function toggleCat() {
  document.getElementById('catBtn').classList.toggle('open');
  document.getElementById('catDropdown').classList.toggle('open');
}
function selectGenre(slug) {
  const url = new URL(window.location);
  if (slug === 'all') url.searchParams.delete('genre');
  else url.searchParams.set('genre', slug);
  url.searchParams.delete('page');
  window.location = url.toString();
}
document.addEventListener('click', e => {
  const wrap = document.getElementById('catWrap');
  if (wrap && !wrap.contains(e.target)) {
    document.getElementById('catBtn')?.classList.remove('open');
    document.getElementById('catDropdown')?.classList.remove('open');
  }
});

// ---- SORT ----
function changeSort(val) {
  const url = new URL(window.location);
  url.searchParams.set('sort', val);
  url.searchParams.delete('page');
  window.location = url.toString();
}

// ---- PAGINATION ----
function goPage(p) {
  const url = new URL(window.location);
  url.searchParams.set('page', p);
  window.location = url.toString();
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

// ---- OPEN MOVIE ----
async function openMovie(id) {
  const modal = document.getElementById('movieModal');
  const iframe = document.getElementById('movieIframe');
  const content = document.getElementById('modalContent');
  modal.classList.add('open');
  document.body.style.overflow = 'hidden';
  content.innerHTML = '<div class="spinner"></div>';
  iframe.src = '';

  const data = await api(`pages/movies.php?action=single&id=${id}`);
  const m = data.movie;
  if (!m) { toast('Movie not found','error'); closeModal(); return; }

  iframe.src = m.embed_url;
  post('pages/movies.php', { action:'increment_views', id:m.id });

  let inWl = false;
  if (LOGGED_IN) {
    const wc = await api(`pages/watchlist.php?action=check&movie_id=${m.id}`);
    inWl = wc.in_watchlist;
  }

  const genres = (m.genres||[]).map(g=>`<span class="modal-genre-tag">${g.name}</span>`).join('');
  const dur = minToDur(m.duration);

  content.innerHTML = `
    <div class="modal-info">
      <div class="modal-info-top">
        <div class="modal-poster"><img src="${m.poster||''}" alt="${m.title}" onerror="this.style.display='none'"></div>
        <div class="modal-text">
          <h2>${m.title}</h2>
          <div class="modal-meta">
            <span class="star">&#9733; ${parseFloat(m.rating).toFixed(1)}</span>
            <span class="sep">·</span><span>IMDB ${m.imdb_rating}</span>
            <span class="sep">·</span><span>${m.year}</span>
            ${dur?`<span class="sep">·</span><span>${dur}</span>`:''}
            <span class="sep">·</span><span>${m.language}</span>
          </div>
          ${genres?`<div class="modal-genres">${genres}</div>`:''}
          <p class="modal-desc">${m.description||''}</p>
          <div class="modal-actions">
            <button class="btn-wl ${inWl?'saved':''}" id="mWlBtn" onclick="toggleModalWl(${m.id})">
              <svg width="14" height="14" fill="${inWl?'currentColor':'none'}" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
              ${inWl?'Saved':'Add to Watchlist'}
            </button>
          </div>
        </div>
      </div>
    </div>
    <div class="modal-details">
      ${m.director?`<div class="detail"><label>Director</label><span>${m.director}</span></div>`:''}
      ${m.cast_members?`<div class="detail" style="grid-column:span 2"><label>Cast</label><span>${m.cast_members}</span></div>`:''}
      <div class="detail"><label>Year</label><span>${m.year}</span></div>
      ${dur?`<div class="detail"><label>Duration</label><span>${dur}</span></div>`:''}
      <div class="detail"><label>Language</label><span>${m.language}</span></div>
      <div class="detail"><label>Quality</label><span>${m.quality}</span></div>
    </div>
  `;
}

function closeModal() {
  document.getElementById('movieModal').classList.remove('open');
  document.getElementById('movieIframe').src = '';
  document.body.style.overflow = '';
}
function closeModalOutside(e) { if (e.target===document.getElementById('movieModal')) closeModal(); }

// ---- WATCHLIST ----
async function toggleWatchlist(e, id) {
  e.stopPropagation();
  if (!LOGGED_IN) { openAuth('login'); return; }
  const btn = document.getElementById(`wl-${id}`);
  const isIn = btn?.classList.contains('saved');
  const res = await post('pages/watchlist.php', { action: isIn?'remove':'add', movie_id: id });
  if (res.success) {
    btn?.classList.toggle('saved', !isIn);
    toast(isIn?'Removed from watchlist':'Added to watchlist', isIn?'info':'success');
  } else if (res.login_required) { openAuth('login'); }
}
async function toggleModalWl(id) {
  if (!LOGGED_IN) { openAuth('login'); return; }
  const btn = document.getElementById('mWlBtn');
  const isIn = btn?.classList.contains('saved');
  const res = await post('pages/watchlist.php', { action: isIn?'remove':'add', movie_id: id });
  if (res.success) {
    btn.classList.toggle('saved', !isIn);
    btn.innerHTML = `<svg width="14" height="14" fill="${!isIn?'currentColor':'none'}" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg> ${!isIn?'Saved':'Add to Watchlist'}`;
    toast(isIn?'Removed':'Added to watchlist', isIn?'info':'success');
  }
}

// ---- USER MENU ----
function toggleMenu() { document.getElementById('userMenu')?.classList.toggle('open'); }
document.addEventListener('click', e => {
  const m = document.getElementById('userMenu');
  if (m && !m.contains(e.target)) m.classList.remove('open');
});
async function logoutUser() {
  await post('pages/auth.php', { action:'logout' });
  window.location.reload();
}

// ---- AUTH ----
function openAuth(tab='login') {
  document.getElementById('authModal').classList.add('open');
  document.body.style.overflow = 'hidden';
  switchTab(tab);
}
function closeAuth() { document.getElementById('authModal').classList.remove('open'); document.body.style.overflow=''; }
function closeAuthOutside(e) { if (e.target===document.getElementById('authModal')) closeAuth(); }
function switchTab(tab) {
  ['login','register'].forEach(t => {
    document.getElementById(`tab-${t}`).classList.toggle('active', t===tab);
    document.getElementById(`form-${t}`).classList.toggle('active', t===tab);
  });
}
async function handleLogin(e) {
  e.preventDefault();
  const btn = document.getElementById('loginBtn');
  const err = document.getElementById('loginError');
  btn.disabled=true; btn.textContent='Signing in...'; err.style.display='none';
  const res = await post('pages/auth.php', { action:'login', email:document.getElementById('loginEmail').value, password:document.getElementById('loginPassword').value });
  if (res.success) { toast(`Welcome back, ${res.username}!`,'success'); setTimeout(()=>location.reload(),700); }
  else { err.textContent=res.error||'Login failed'; err.style.display='block'; btn.disabled=false; btn.textContent='Sign In'; }
}
async function handleRegister(e) {
  e.preventDefault();
  const btn = document.getElementById('registerBtn');
  const err = document.getElementById('registerError');
  btn.disabled=true; btn.textContent='Creating...'; err.style.display='none';
  const res = await post('pages/auth.php', { action:'register', username:document.getElementById('regUsername').value, email:document.getElementById('regEmail').value, password:document.getElementById('regPassword').value });
  if (res.success) { toast(`Welcome, ${res.username}!`,'success'); setTimeout(()=>location.reload(),700); }
  else { err.textContent=res.error||'Failed'; err.style.display='block'; btn.disabled=false; btn.textContent='Create Account'; }
}

// ---- KEYBOARD ----
document.addEventListener('keydown', e => {
  if (e.key==='Escape') {
    if (document.getElementById('movieModal').classList.contains('open')) closeModal();
    else if (document.getElementById('authModal').classList.contains('open')) closeAuth();
  }
});
</script>
</body>
</html>
