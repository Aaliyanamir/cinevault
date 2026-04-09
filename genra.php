<!DOCTYPE html>
<html>
<head>
    <title>Genres</title>
</head>
<body>

<h1>Movie Genres</h1>

<ul id="genreList">
    <li>Loading...</li>
</ul>

</body>
</html>



<script>
    fetch('pages/genres.php')
  .then(res => res.json())
  .then(data => {
    const list = document.getElementById('genreList');
    list.innerHTML = '';

    data.genres.forEach(genre => {
      const li = document.createElement('li');
      li.textContent = `${genre.name} (${genre.movie_count} movies)`;
      list.appendChild(li);
    });
  })
  .catch(err => {
    console.error(err);
  });


  if (data.status !== "success") {
  throw new Error("API error");
}
</script>