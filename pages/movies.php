<?php
require_once __DIR__ . '/../includes/config.php';

$action = $_GET['action'] ?? 'list';
$db     = getDB();

switch ($action) {
    case 'featured':
        fetchFeatured($db);
        break;
    case 'trending':
        fetchTrending($db);
        break;
    case 'latest':
        fetchLatest($db);
        break;
    case 'single':
        fetchSingle($db);
        break;
    case 'search':
        searchMovies($db);
        break;
    case 'genre':
        fetchByGenre($db);
        break;
    case 'all':
        fetchAll($db);
        break;
    case 'increment_views':
        incrementViews($db);
        break;
    default:
        jsonResponse(['error' => 'Invalid action'], 400);
}

function getGenresForMovies($db, $movieIds) {
    if (empty($movieIds)) return [];
    $placeholders = implode(',', array_fill(0, count($movieIds), '?'));
    $types = str_repeat('i', count($movieIds));
    $stmt = $db->prepare("
        SELECT mg.movie_id, g.name, g.slug
        FROM movie_genres mg
        JOIN genres g ON mg.genre_id = g.id
        WHERE mg.movie_id IN ($placeholders)
    ");
    $stmt->bind_param($types, ...$movieIds);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $map = [];
    foreach ($rows as $row) {
        $map[$row['movie_id']][] = ['name' => $row['name'], 'slug' => $row['slug']];
    }
    return $map;
}

function attachGenres($db, &$movies) {
    if (empty($movies)) return;
    $ids = array_column($movies, 'id');
    $genreMap = getGenresForMovies($db, $ids);
    foreach ($movies as &$movie) {
        $movie['genres'] = $genreMap[$movie['id']] ?? [];
    }
}

function fetchFeatured($db) {
    $stmt = $db->query("SELECT * FROM movies WHERE is_featured = 1 ORDER BY RAND() LIMIT 5");
    $movies = $stmt->fetch_all(MYSQLI_ASSOC);
    attachGenres($db, $movies);
    jsonResponse(['movies' => $movies]);
}

function fetchTrending($db) {
    $limit = intval($_GET['limit'] ?? 12);
    $stmt = $db->prepare("SELECT * FROM movies WHERE is_trending = 1 ORDER BY views DESC LIMIT ?");
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $movies = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    attachGenres($db, $movies);
    jsonResponse(['movies' => $movies]);
}

function fetchLatest($db) {
    $limit = intval($_GET['limit'] ?? 12);
    $stmt = $db->prepare("SELECT * FROM movies ORDER BY created_at DESC LIMIT ?");
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $movies = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    attachGenres($db, $movies);
    jsonResponse(['movies' => $movies]);
}

function fetchSingle($db) {
    $slug = sanitize($_GET['slug'] ?? '');
    $id   = intval($_GET['id'] ?? 0);

    if ($slug) {
        $stmt = $db->prepare("SELECT * FROM movies WHERE slug = ?");
        $stmt->bind_param("s", $slug);
    } elseif ($id) {
        $stmt = $db->prepare("SELECT * FROM movies WHERE id = ?");
        $stmt->bind_param("i", $id);
    } else {
        jsonResponse(['error' => 'Movie not found'], 404);
    }

    $stmt->execute();
    $movie = $stmt->get_result()->fetch_assoc();
    if (!$movie) jsonResponse(['error' => 'Movie not found'], 404);

    // Get genres
    $gstmt = $db->prepare("
        SELECT g.name, g.slug FROM movie_genres mg
        JOIN genres g ON mg.genre_id = g.id
        WHERE mg.movie_id = ?
    ");
    $gstmt->bind_param("i", $movie['id']);
    $gstmt->execute();
    $movie['genres'] = $gstmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Related movies by genre
    if (!empty($movie['genres'])) {
        $genreSlug = $movie['genres'][0]['slug'];
        $relStmt = $db->prepare("
            SELECT DISTINCT m.id, m.title, m.slug, m.poster, m.year, m.rating, m.quality, m.duration
            FROM movies m
            JOIN movie_genres mg ON m.id = mg.movie_id
            JOIN genres g ON mg.genre_id = g.id
            WHERE g.slug = ? AND m.id != ?
            LIMIT 6
        ");
        $relStmt->bind_param("si", $genreSlug, $movie['id']);
        $relStmt->execute();
        $related = $relStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        attachGenres($db, $related);
        $movie['related'] = $related;
    } else {
        $movie['related'] = [];
    }

    jsonResponse(['movie' => $movie]);
}

function searchMovies($db) {
    $q     = sanitize($_GET['q'] ?? '');
    $limit = intval($_GET['limit'] ?? 20);
    if (empty($q)) jsonResponse(['movies' => []]);

    $search = "%$q%";
    $stmt = $db->prepare("
        SELECT * FROM movies
        WHERE title LIKE ? OR description LIKE ? OR cast_members LIKE ? OR director LIKE ?
        ORDER BY rating DESC
        LIMIT ?
    ");
    $stmt->bind_param("ssssi", $search, $search, $search, $search, $limit);
    $stmt->execute();
    $movies = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    attachGenres($db, $movies);
    jsonResponse(['movies' => $movies]);
}

function fetchByGenre($db) {
    $slug  = sanitize($_GET['slug'] ?? '');
    $page  = max(1, intval($_GET['page'] ?? 1));
    $limit = 20;
    $offset = ($page - 1) * $limit;

    if (empty($slug)) jsonResponse(['error' => 'Genre required'], 400);

    // Count
    $cstmt = $db->prepare("
        SELECT COUNT(*) as total FROM movies m
        JOIN movie_genres mg ON m.id = mg.movie_id
        JOIN genres g ON mg.genre_id = g.id
        WHERE g.slug = ?
    ");
    $cstmt->bind_param("s", $slug);
    $cstmt->execute();
    $total = $cstmt->get_result()->fetch_assoc()['total'];

    $stmt = $db->prepare("
        SELECT DISTINCT m.* FROM movies m
        JOIN movie_genres mg ON m.id = mg.movie_id
        JOIN genres g ON mg.genre_id = g.id
        WHERE g.slug = ?
        ORDER BY m.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->bind_param("sii", $slug, $limit, $offset);
    $stmt->execute();
    $movies = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    attachGenres($db, $movies);
    jsonResponse(['movies' => $movies, 'total' => $total, 'page' => $page, 'pages' => ceil($total / $limit)]);
}

function fetchAll($db) {
    $page   = max(1, intval($_GET['page'] ?? 1));
    $limit  = 20;
    $offset = ($page - 1) * $limit;
    $sort   = in_array($_GET['sort'] ?? '', ['rating','year','views','title']) ? $_GET['sort'] : 'created_at';

    $cstmt = $db->query("SELECT COUNT(*) as total FROM movies");
    $total = $cstmt->fetch_assoc()['total'];

    $stmt = $db->prepare("SELECT * FROM movies ORDER BY $sort DESC LIMIT ? OFFSET ?");
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $movies = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    attachGenres($db, $movies);
    jsonResponse(['movies' => $movies, 'total' => $total, 'page' => $page, 'pages' => ceil($total / $limit)]);
}

function incrementViews($db) {
    $id = intval($_POST['id'] ?? 0);
    if (!$id) jsonResponse(['error' => 'Invalid'], 400);
    $db->prepare("UPDATE movies SET views = views + 1 WHERE id = ?")->bind_param("i", $id) && true;
    $stmt = $db->prepare("UPDATE movies SET views = views + 1 WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    jsonResponse(['success' => true]);
}
?>
