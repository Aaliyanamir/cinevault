<?php
require_once __DIR__ . '/../includes/config.php';

$db = getDB();
$stmt = $db->query("
    SELECT g.*, COUNT(mg.movie_id) as movie_count
    FROM genres g
    LEFT JOIN movie_genres mg ON g.id = mg.genre_id
    GROUP BY g.id
    ORDER BY g.name ASC
");
$genres = $stmt->fetch_all(MYSQLI_ASSOC);
jsonResponse(['genres' => $genres]);
?>
