<?php
require_once '../includes/config.php';

$user_id = $_SESSION['user_id'];

if(isset($_GET['recent'])){
  $limit = 6;
}else{
  $limit = 20;
}

$query = "SELECT m.id,m.title,m.poster 
          FROM history h
          JOIN movies m ON h.movie_id = m.id
          WHERE h.user_id=?
          ORDER BY h.id DESC
          LIMIT $limit";

$stmt = $conn->prepare($query);
$stmt->bind_param("i",$user_id);
$stmt->execute();
$result = $stmt->get_result();

$movies = [];

while($row = $result->fetch_assoc()){
  $movies[] = $row;
}

echo json_encode(['movies'=>$movies]);