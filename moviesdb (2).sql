-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 09, 2026 at 11:44 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `moviesdb`
--

-- --------------------------------------------------------

--
-- Table structure for table `collections`
--

CREATE TABLE `collections` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `cover_image` varchar(500) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `collections`
--

INSERT INTO `collections` (`id`, `name`, `slug`, `description`, `cover_image`, `is_active`, `display_order`, `created_at`) VALUES
(1, 'Christopher Nolan Collection', 'nolan', 'All masterpieces by Christopher Nolan', '', 1, 1, '2026-04-08 15:10:26'),
(2, 'Action Classics', 'action-classics', 'Best action movies of all time', '', 1, 2, '2026-04-08 15:10:26'),
(3, 'Sci-Fi Greatest', 'sci-fi-greatest', 'Top science fiction films', '', 1, 3, '2026-04-08 15:10:26'),
(4, 'Horror', 'orror', 'Annabelle: Creation (2017) is a supernatural horror film that serves as a prequel to Annabelle (2014), detailing the origin of the possessed doll. It follows a dollmaker and his wife who, twelve years after their daughter Annabelle\'s (\"Bee\") tragic death, open their home to a nun and orphaned girls, who become the target of a demonic entity attached to one of his creations', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wCEAAkGBwgHBgkIBwgKCgkLDRYPDQwMDRsUFRAWIB0iIiAdHx8kKDQsJCYxJx8fLT0tMTU3Ojo6Iys/RD84QzQ5OjcBCgoKDQwNGg8PGjclHyU3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3N//AABEIATgBOAMBIgACEQEDEQH/xAAcAAABBQEBAQAAAAAAAAAAAAAEAAIDBQYBBwj/xABAEAABAwIEAwYDBgUEAQQDAAABAgMRAAQFEiExBkFREyIyYXGBFJGhByNCUrHBFTNi0fAkcuHxghY0osImQ1T/xAAYAQADAQEAAAAAAAAAAAAAAAABAgMABP/EACgRAAICAgMAAQQCAgMAAAAAAAABAhEhMQMSQRMiMlFhBIFx8RShwf/aAAwDAQACEQMRAD8A8xfEqSPLS', 1, 5, '2026-04-09 07:54:36'),
(5, 'War Movies', 'ar-ovies', 'Best Action War Movies Historical', '', 1, 0, '2026-04-09 08:03:56');

-- --------------------------------------------------------

--
-- Table structure for table `collection_movies`
--

CREATE TABLE `collection_movies` (
  `collection_id` int(11) NOT NULL,
  `movie_id` int(11) NOT NULL,
  `display_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `collection_movies`
--

INSERT INTO `collection_movies` (`collection_id`, `movie_id`, `display_order`) VALUES
(1, 1, 0),
(1, 2, 1),
(2, 16, 0),
(2, 17, 1),
(2, 28, 2),
(2, 29, 3),
(2, 30, 11),
(2, 31, 10),
(2, 32, 7),
(2, 33, 6),
(2, 34, 8),
(2, 35, 9),
(2, 36, 12),
(2, 37, 4),
(2, 38, 5),
(3, 3, 0),
(3, 6, 2),
(3, 14, 4),
(3, 15, 3),
(3, 24, 8),
(3, 25, 1),
(3, 39, 5),
(3, 40, 7),
(3, 41, 6),
(4, 19, 1),
(4, 21, 0),
(4, 22, 2),
(5, 16, 0),
(5, 17, 1),
(5, 18, 2);

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `movie_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `comment` text NOT NULL,
  `is_approved` tinyint(1) DEFAULT 0,
  `likes` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`id`, `user_id`, `movie_id`, `parent_id`, `comment`, `is_approved`, `likes`, `created_at`) VALUES
(3, 1, 1, NULL, 'This is a test comment from user. Movie is awesome!', 1, 0, '2026-04-09 04:39:25'),
(4, 1, 2, NULL, 'Great movie! Loved the acting.', 1, 0, '2026-04-09 04:39:25');

-- --------------------------------------------------------

--
-- Table structure for table `contacts`
--

CREATE TABLE `contacts` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `subject` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(4) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `contacts`
--

INSERT INTO `contacts` (`id`, `name`, `email`, `subject`, `message`, `is_read`, `created_at`) VALUES
(3, 'Aaliyan amir', 'aaliyanaamir2005@gmail.com', 'movies', 'i am checkiing', 1, '2026-04-09 09:10:43');

-- --------------------------------------------------------

--
-- Table structure for table `email_subscribers`
--

CREATE TABLE `email_subscribers` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `subscribed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `verified_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `email_subscribers`
--

INSERT INTO `email_subscribers` (`id`, `email`, `is_active`, `subscribed_at`, `verified_at`) VALUES
(1, 'aaliyanaamir2005@gmail.com', 1, '2026-04-09 05:15:23', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `genres`
--

CREATE TABLE `genres` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `slug` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `genres`
--

INSERT INTO `genres` (`id`, `name`, `slug`) VALUES
(1, 'Action', 'action'),
(2, 'Drama', 'drama'),
(3, 'Comedy', 'comedy'),
(4, 'Thriller', 'thriller'),
(5, 'Horror', 'horror'),
(6, 'Sci-Fi', 'sci-fi'),
(7, 'Romance', 'romance'),
(8, 'Animation', 'animation'),
(9, 'Crime', 'crime'),
(10, 'Adventure', 'adventure'),
(12, 'Historical', 'istorical');

-- --------------------------------------------------------

--
-- Table structure for table `movies`
--

CREATE TABLE `movies` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `poster` varchar(500) DEFAULT NULL,
  `backdrop` varchar(500) DEFAULT NULL,
  `trailer_url` varchar(500) DEFAULT NULL,
  `embed_url` varchar(500) NOT NULL,
  `year` int(11) DEFAULT NULL,
  `duration` int(11) DEFAULT NULL COMMENT 'in minutes',
  `rating` decimal(3,1) DEFAULT 0.0,
  `imdb_rating` decimal(3,1) DEFAULT 0.0,
  `director` varchar(255) DEFAULT NULL,
  `cast_members` text DEFAULT NULL,
  `language` varchar(50) DEFAULT 'English',
  `quality` varchar(20) DEFAULT 'HD',
  `views` int(11) DEFAULT 0,
  `is_featured` tinyint(1) DEFAULT 0,
  `is_trending` tinyint(1) DEFAULT 0,
  `is_upcoming` tinyint(1) DEFAULT 0,
  `release_date` date DEFAULT NULL,
  `meta_title` varchar(200) DEFAULT NULL,
  `meta_description` varchar(500) DEFAULT NULL,
  `meta_keywords` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `movies`
--

INSERT INTO `movies` (`id`, `title`, `slug`, `description`, `poster`, `backdrop`, `trailer_url`, `embed_url`, `year`, `duration`, `rating`, `imdb_rating`, `director`, `cast_members`, `language`, `quality`, `views`, `is_featured`, `is_trending`, `is_upcoming`, `release_date`, `meta_title`, `meta_description`, `meta_keywords`, `created_at`) VALUES
(1, 'Inception', 'inception', 'A thief who steals corporate secrets through the use of dream-sharing technology is given the inverse task of planting an idea into the mind of a C.E.O.', 'https://image.tmdb.org/t/p/w500/9gk7adHYeDvHkCSEqAvQNLV5Uge.jpg', 'https://image.tmdb.org/t/p/original/s3TBrRGB1iav7gFOCNx3H31MoES.jpg', NULL, 'https://www.youtube.com/embed/YoHD9XEInc0', 2010, 148, 0.0, 8.8, 'Christopher Nolan', 'Leonardo DiCaprio, Joseph Gordon-Levitt, Elliot Page', 'English', 'FHD', 1, 1, 1, 0, NULL, NULL, NULL, NULL, '2026-04-06 10:35:12'),
(2, 'The Dark Knight', 'the-dark-knight', 'When the menace known as the Joker wreaks havoc and chaos on the people of Gotham, Batman must accept one of the greatest psychological and physical tests of his ability to fight injustice.', 'https://image.tmdb.org/t/p/w500/qJ2tW6WMUDux911r6m7haRef0WH.jpg', 'https://image.tmdb.org/t/p/original/hqkIcbrOHL86UncnHIsHVcVmzue.jpg', NULL, 'https://www.youtube.com/embed/EXeTwQWrcwY', 2008, 152, 9.5, 9.0, 'Christopher Nolan', 'Christian Bale, Heath Ledger, Aaron Eckhart', 'English', 'FHD', 2, 1, 1, 0, NULL, NULL, NULL, NULL, '2026-04-06 10:35:12'),
(3, 'Interstellar', 'interstellar', 'A team of explorers travel through a wormhole in space in an attempt to ensure humanity survival.', 'https://image.tmdb.org/t/p/w500/gEU2QniE6E77NI6lCU6MxlNBvIx.jpg', 'https://image.tmdb.org/t/p/original/xJHokMbljvjADYdit5fK5VQsXEG.jpg', NULL, 'https://www.youtube.com/embed/zSWdZVtXT7E', 2014, 169, 9.0, 8.6, 'Christopher Nolan', 'Matthew McConaughey, Anne Hathaway, Jessica Chastain', 'English', 'FHD', 1, 1, 0, 0, NULL, NULL, NULL, NULL, '2026-04-06 10:35:12'),
(5, 'Pulp Fiction', 'pulp-fiction', 'The lives of two mob hitmen, a boxer, a gangster and his wife, and a pair of diner bandits intertwine in four tales of violence and redemption.', 'https://image.tmdb.org/t/p/w500/d5iIlFn5s0ImszYzBPb8JPIfbXD.jpg', 'https://image.tmdb.org/t/p/original/suaEOtk1N1sgg2MTM7oZd2cfVp3.jpg', '', 'https://www.youtube.com/embed/s7EdQ4FqbhY', 1994, 154, 9.0, 8.9, 'Quentin Tarantino', 'John Travolta, Uma Thurman, Samuel L. Jackson', 'English', 'HD', 0, 0, 1, 0, NULL, NULL, NULL, NULL, '2026-04-06 10:35:12'),
(6, 'The Matrix', 'the-matrix', 'A computer hacker learns from mysterious rebels about the true nature of his reality and his role in the war against its controllers.', 'https://image.tmdb.org/t/p/w500/f89U3ADr1oiB1s9GkdPOEpXUk5H.jpg', 'https://image.tmdb.org/t/p/original/fNG7i7RqMErkcqhohV2a6cV1Ehy.jpg', NULL, 'https://www.youtube.com/embed/vKQi3bBA1y8', 1999, 136, 9.1, 8.7, 'The Wachowskis', 'Keanu Reeves, Laurence Fishburne, Carrie-Anne Moss', 'English', 'FHD', 1, 0, 0, 0, NULL, NULL, NULL, NULL, '2026-04-06 10:35:12'),
(7, 'Avengers: Endgame', 'avengers-endgame', 'After the devastating events of Avengers: Infinity War, the universe is in ruins. The Avengers assemble once more to reverse Thanos actions and restore balance.', 'https://image.tmdb.org/t/p/w500/or06FN3Dka5tukK1e9sl16pB3iy.jpg', 'https://image.tmdb.org/t/p/original/7RyHsO4yDXtBv1zUU3mTpHeQ0d5.jpg', NULL, 'https://www.youtube.com/embed/TcMBFSGVi1c', 2019, 181, 9.0, 8.4, 'Anthony and Joe Russo', 'Robert Downey Jr., Chris Evans, Mark Ruffalo', 'English', 'FHD', 2, 1, 0, 0, NULL, NULL, NULL, NULL, '2026-04-06 10:35:12'),
(8, 'Parasite', 'parasite', 'Greed and class discrimination threaten the newly formed symbiotic relationship between the wealthy Park family and the destitute Kim clan.', 'https://image.tmdb.org/t/p/w500/7IiTTgloJzvGI1TAYymCfbfl3vT.jpg', 'https://image.tmdb.org/t/p/original/ApiBzeaa95TNYliSbQ8pJv4Nakl.jpg', NULL, 'https://www.youtube.com/embed/5xH0HfJHsaY', 2019, 132, 9.4, 8.5, 'Bong Joon-ho', 'Song Kang-ho, Lee Sun-kyun, Cho Yeo-jeong', 'Korean', 'FHD', 0, 0, 1, 0, NULL, NULL, NULL, NULL, '2026-04-06 10:35:12'),
(12, 'The Shawshank Redeption', 'he-hawshank-edeption', 'The film tells the story of banker Andy Dufresne (Tim Robbins), who is sentenced to life in Shawshank State Penitentiary for the murders of his wife and her lover, despite his claims of innocence', 'https://pbcdnw.aoneroom.com/image/2024/05/29/a03c4c72cf8c046b8ff8abaeb8a25aa4.jpg?x-oss-process=image/resize%2Cw_250', 'https://pbcdnw.aoneroom.com/image/2024/05/29/a03c4c72cf8c046b8ff8abaeb8a25aa4.jpg?x-oss-process=image/resize%2Cw_250', '', 'https://v.moviebox.ph/YiyFCqvtKX1', 1994, 144, 7.0, 7.0, 'Frank Darabont', 'Morgan Freeman , Andy Dufrense', 'English', 'HD', 1, 1, 1, 0, NULL, NULL, NULL, NULL, '2026-04-07 07:14:48'),
(13, 'Peaky Blinders The Imortal Man', 'eaky-linders-he-mortal-an', 'Peaky Blinders: The Immortal Man (2026) is a feature-length film ,  Set in 1940 during World War II, a reluctant Tommy Shelby (Cillian Murphy) emerges from exile to protect his family from a Nazi-driven plot. It acts as a final continuation of the original series.', 'https://image.tmdb.org/t/p/w300/mKSnqcEOpr2hUq7moF4bJVBGZcO.jpg', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTfxN8cG3n487E9Vt3s3h9vqdWp8XZjeIkxZg&s', '', 'https://www.moviesbazar.tv/watch/movie/peaky-blinders-the-immortal-man/15574124', 2026, 154, 7.0, 8.0, 'Tom Harper', 'Cillian Murphy, Rebecca Ferguson, Sophie Rundle', 'English', 'HD', 1, 1, 1, 0, NULL, NULL, NULL, NULL, '2026-04-07 07:38:06'),
(14, 'The Twilight Saga: Breaking Dwan Part 1', 'he-wilight-aga-reaking-wan-art-1', 'The Twilight Saga is a five-film romantic fantasy series (2008–2012) based on Stephenie Meyer\'s novels, featuring Bella Swan (Kristen Stewart) and vampire Edward Cullen (Robert Pattinson). The films follow their forbidden romance and the dangers of the vampire world.', 'https://image.tmdb.org/t/p/w300/tQ7y6OcWsjloEyRiwsNZQvefsRF.jpg', 'https://image.tmdb.org/t/p/w300/tQ7y6OcWsjloEyRiwsNZQvefsRF.jpg', '', 'https://www.moviesbazar.tv/watch/movie/the-twilight-saga-breaking-dawn-part-1/1324999', 2011, 149, 7.0, 7.5, 'Catherine Hardwicke', 'Kristen Stewart, Robert Pattinson, Taylor Lautner', 'English', 'HD', 0, 1, 1, 0, NULL, NULL, NULL, NULL, '2026-04-07 07:43:00'),
(15, 'The Twilight Saga : Breaking Dawn Part 2', 'he-wilight-aga-reaking-awn-art-2', 'The Twilight Saga is a five-film romantic fantasy series (2008–2012) based on Stephenie Meyer\'s novels, featuring Bella Swan (Kristen Stewart) and vampire Edward Cullen (Robert Pattinson). The films follow their forbidden romance and the dangers of the vampire world.', 'https://image.tmdb.org/t/p/w300/efUk9ofFSidBPgs7x0CF4fC6LZj.jpg', 'https://image.tmdb.org/t/p/w300/efUk9ofFSidBPgs7x0CF4fC6LZj.jpg', '', 'https://www.moviesbazar.tv/watch/movie/the-twilight-saga-breaking-dawn-part-2/1673434', 2012, 155, 8.0, 7.5, 'Catherine Hardwicke', 'Kristen Stewart, Robert Pattinson, Taylor Lautner', 'English', 'HD', 0, 1, 1, 0, NULL, NULL, NULL, NULL, '2026-04-07 07:47:23'),
(16, '300', '300', '300 (2006) is a stylized epic action film  based on the graphic novel by Frank Miller and Lynn Varley. The film is a fictionalized retelling of the 480 B.C. Battle of Thermopylae, focusing on King Leonidas (Gerard Butler) leading 300 Spartans against Xerxes (Rodrigo Santoro) and his massive Persian army.', 'https://pbcdnw.aoneroom.com/image/2025/11/01/c5e5e979cbb3d0c112467cd9eb1eab2b.jpg?x-oss-process=image/resize%2Cw_250', 'https://pbcdnw.aoneroom.com/image/2025/11/01/c5e5e979cbb3d0c112467cd9eb1eab2b.jpg?x-oss-process=image/resize%2Cw_250', '', 'https://v.moviebox.ph/6R9Bmowo5i7', 206, 157, 0.0, 9.0, 'Zack Snyder', 'Gerad Butler , Lena Heady', 'English', 'HD', 1, 1, 1, 0, NULL, NULL, NULL, NULL, '2026-04-07 07:51:42'),
(17, '300 The Rise of an Empire', '300-he-ise-of-an-mpire', '300: Rise of an Empire (2014) is a stylized historical action film  It acts as a companion piece to the 2006 film 300, focusing on naval battles—specifically the Battle of Artemisium and Battle of Salamis—as Greek general Themistocles tries to unite Greece against the invading Persian forces led by King Xerxes and Artemisia.', 'https://pbcdnw.aoneroom.com/image/2024/04/15/efc15857263c9e482d3b8680c2e7e28c.jpg?x-oss-process=image/resize%2Cw_250', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wCEAAkGBxMTEhUTExMWFhUXGBoZGBgYFx0dGBgaGRgXHRoYGBsdHiggHRolHhgXIjEiJSkrLi4uGB8zODMsNygtLisBCgoKDg0OGhAQGy0mICUtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLf/AABEIALEBHAMBIgACEQEDEQH/xAAcAAACAgMBAQAAAAAAAAAAAAAEBQMGAAIHAQj/xAA+EAABAwIEAwUGBAUEAQUAAAABAgMRACEEBRIxQVFhBhMicYEHMpGhsfBCUsHhFCNictEzgrLxQzVTdJKi/8QAGQEAAwEBAQAAAAAAAAAAAAAAAAECAwQF/8QAMREAAgIBAwMBBQgCAwAAAAAAAAECEQMSITEEQVETMmGB0fAFFCIjcZGxweHxJEKh/9oADAMBAAIRAxEAP', '', 'https://v.moviebox.ph/QczLcIXTtl6', 2014, 142, 8.0, 9.0, 'Nuam Murro', 'Sullivan Steplitto ,  Eva Green , Leana Heady', 'English', 'HD', 0, 1, 1, 0, NULL, NULL, NULL, NULL, '2026-04-07 07:58:35'),
(18, 'Troy', 'roy', 'An adaptation of Homer\'s great epic, the film follows the assault on Troy by the united Greek forces and chronicles the fates of the men involved', 'https://pbcdnw.aoneroom.com/image/2022/11/15/b38bb5ac781fbee8be6644a6d02c5d38.jpg?x-oss-process=image/resize%2Cw_250', 'https://pbcdnw.aoneroom.com/image/2022/11/15/b38bb5ac781fbee8be6644a6d02c5d38.jpg?x-oss-process=image/resize%2Cw_250', '', 'https://v.moviebox.ph/ilaEOcO7l85', 2004, 243, 8.0, 9.0, 'Wolfgang Petersen', 'Brad pitt , Eric Bana', 'hindi', 'HD', 0, 1, 1, 0, NULL, NULL, NULL, NULL, '2026-04-07 08:01:11'),
(19, 'Annabella Comes Home', 'nnabella', 'While babysitting the daughter of Ed and Lorraine Warren, a teenager and her friend unknowingly awaken an evil spirit trapped in a doll.', 'https://pbcdnw.aoneroom.com/image/2022/10/14/277141abd555c5721b95a3e378033e97.jpg?x-oss-process=image/resize%2Cw_250', 'https://pbcdnw.aoneroom.com/image/2022/10/14/277141abd555c5721b95a3e378033e97.jpg?x-oss-process=image/resize%2Cw_250', '', 'https://v.moviebox.ph/08IElaN6Z3a', 2019, 146, 7.0, 8.0, 'Gary Dauberman', 'Patric Wilson , Vera farmiga', 'Hindi', 'HD', 0, 0, 1, 0, NULL, NULL, NULL, NULL, '2026-04-07 08:08:02'),
(21, 'Annabella', 'nnabella-comes-home', 'A couple begins to experience terrifying supernatural occurrences involving a vintage doll shortly after their home is invaded by satanic cultists.', 'https://pbcdnw.aoneroom.com/image/2024/10/11/59099ad8b744c84a2fda376941cc4408.jpg?x-oss-process=image/resize%2Cw_250', 'https://pbcdnw.aoneroom.com/image/2024/10/11/59099ad8b744c84a2fda376941cc4408.jpg?x-oss-process=image/resize%2Cw_250', '', 'https://v.moviebox.ph/yxavQJ1tYi', 2014, 139, 7.0, 8.0, 'John R. Leonetti', 'Word horton , Annabella Wallis', 'Hindi', 'HD', 0, 0, 1, 0, NULL, NULL, NULL, NULL, '2026-04-07 08:08:41'),
(22, 'Conjuring', 'onjuring', 'Paranormal investigators Ed and Lorraine Warren work to help a family terrorized by a dark presence in their farmhouse.', 'https://pbcdnw.aoneroom.com/image/2022/11/21/4b54b3dd670edcf77078ce5527b93b06.jpg?x-oss-process=image/resize%2Cw_250', 'https://pbcdnw.aoneroom.com/image/2022/11/21/4b54b3dd670edcf77078ce5527b93b06.jpg?x-oss-process=image/resize%2Cw_250', '', 'https://v.moviebox.ph/A2sd79xA4s4', 2013, 152, 7.0, 7.5, 'James Wan', 'Patric Wilson , Wera Farmiga', 'Hindi', 'HD', 0, 0, 1, 0, NULL, NULL, NULL, NULL, '2026-04-07 08:15:02'),
(23, 'Avengers Infinity War', 'vengers-nfinity-ar', 'The Avengers and their allies must be willing to sacrifice all in an attempt to defeat the powerful Thanos before his blitz of devastation and ruin puts an end to the universe.', 'https://pbcdnw.aoneroom.com/image/2024/06/13/e64d6804a01251619a53edb050fdaf3b.jpg?x-oss-process=image/resize%2Cw_250', 'https://pbcdnw.aoneroom.com/image/2024/06/13/e64d6804a01251619a53edb050fdaf3b.jpg?x-oss-process=image/resize%2Cw_250', '', 'https://v.moviebox.ph/qfDlQgN9x46', 2018, 229, 8.0, 9.0, 'xyz', 'Robert Downey Junior , Chris Hemsworth , CHris Evans', 'Hindi', 'HD', 0, 0, 1, 0, NULL, NULL, NULL, NULL, '2026-04-07 08:21:34'),
(24, 'Zack Snyder Justice League', 'ack-nyder-ustice-eague', 'Determined to ensure that Superman\'s ultimate sacrifice wasn\'t in vain, Bruce Wayne recruits a team of metahumans to protect the world from an approaching threat of catastrophic proportions.', 'https://pbcdnw.aoneroom.com/image/2026/01/26/93186a626f212a8c51ed9ce6ea4f3db2.jpg?x-oss-process=image/resize%2Cw_250', 'https://pbcdnw.aoneroom.com/image/2026/01/26/93186a626f212a8c51ed9ce6ea4f3db2.jpg?x-oss-process=image/resize%2Cw_250', '', 'https://v.moviebox.ph/y3179G7HTv9', 2021, 242, 7.0, 7.5, 'Zack Snyder', 'Henry Cavil , Jason Momoa , Gal Gabbot', 'English', 'HD', 0, 0, 0, 0, NULL, NULL, NULL, NULL, '2026-04-07 08:21:36'),
(25, 'Man of Steel', 'an-of-teel', 'An alien child is evacuated from his dying world and sent to Earth to live among humans. His peace is threatened when other survivors of his home planet invade Earth.', 'https://pbcdnw.aoneroom.com/image/2022/09/02/85b3054d1dd0bac96abff279f3a7ecab.jpg?x-oss-process=image/resize%2Cw_250', 'https://pbcdnw.aoneroom.com/image/2022/09/02/85b3054d1dd0bac96abff279f3a7ecab.jpg?x-oss-process=image/resize%2Cw_250', '', 'https://v.moviebox.ph/QUl4eTX7rm9\\', 2013, 143, 7.0, 8.0, 'Zack Snyder', 'Henry Cavil , Amy Adams', 'Hindi', 'HD', 0, 0, 1, 0, NULL, NULL, NULL, NULL, '2026-04-07 08:24:16'),
(26, 'The Hangover', 'he-angover', 'The Hangover (2009) was directed by Todd Phillips and is described as an unapologetic, critically acclaimed comedy about three friends who lose their groom-to-be during a wild Las Vegas bachelor party, waking up with no memory of the previous night\'s chaotic events.', 'https://pbcdnw.aoneroom.com/image/2022/11/21/7572d4141059bbea3abd37c94954ae81.jpg?x-oss-process=image/resize%2Cw_250', 'https://pbcdnw.aoneroom.com/image/2022/11/21/7572d4141059bbea3abd37c94954ae81.jpg?x-oss-process=image/resize%2Cw_250', '', 'https://v.moviebox.ph/6TOoRStJ374', 2009, 100, 7.0, 7.0, 'Todd Philips', '', 'Hindi', 'HD', 0, 0, 1, 0, NULL, NULL, NULL, NULL, '2026-04-07 08:27:23'),
(27, 'Dictator', 'ictator', 'The Dictator (2012) is a political satire black comedy film centered on Admiral General Aladeen, the eccentric and oppressive ruler of the fictional Republic of Wadiya.', 'https://pbcdnw.aoneroom.com/image/2024/10/11/de31c98b30263518ad184d47b7a42e11.jpg?x-oss-process=image/resize%2Cw_250', 'https://pbcdnw.aoneroom.com/image/2024/10/11/de31c98b30263518ad184d47b7a42e11.jpg?x-oss-process=image/resize%2Cw_250', '', 'https://v.moviebox.ph/aZa7pNqMU18', 2012, 79, 7.0, 7.0, 'Larry Charles', 'Sacha Baron Cohen , Anna Faris', 'Hindi', 'HD', 1, 0, 1, 0, NULL, NULL, NULL, NULL, '2026-04-07 08:33:47'),
(28, 'Fast And Furious', 'ast-nd-urious', 'The Fast and the Furious (2001) is an action-crime thriller directed by Rob Cohen, centered on Los Angeles undercover police officer Brian O\'Conner (Paul Walker) infiltrating the street racing world of Dominic Toretto (Vin Diesel).', 'https://pbcdnw.aoneroom.com/image/2026/01/27/377d97791107af0d2fa8c154a0e86394.jpg?x-oss-process=image/resize%2Cw_250', 'https://pbcdnw.aoneroom.com/image/2026/01/27/377d97791107af0d2fa8c154a0e86394.jpg?x-oss-process=image/resize%2Cw_250', '', 'https://v.moviebox.ph/ccBbKjAkNV3', 1996, 0, 7.0, 7.0, 'Rob cohen', 'Vin Deisel , Paul Walker', 'English', 'HD', 0, 0, 0, 0, NULL, NULL, NULL, NULL, '2026-04-07 08:39:22'),
(29, 'Fast And Furious  2', 'ast-nd-urious-2', '2 Fast 2 Furious (2003) is an action-thriller directed by John Singleton. It follows former cop Brian O\'Conner (Paul Walker) as he teams up with Roman Pearce (Tyrese Gibson) to take down a Miami drug lord, Carter Verone, while working with undercover agent Monica Fuentes (Eva Mendes).', 'https://pbcdnw.aoneroom.com/image/2025/11/01/c9ef1b90bbf970a8a1b26d9a3a70000d.jpg?x-oss-process=image/resize%2Cw_250', 'zhttps://pbcdnw.aoneroom.com/image/2025/11/01/c9ef1b90bbf970a8a1b26d9a3a70000d.jpg?x-oss-process=image/resize%2Cw_250', '', 'https://v.moviebox.ph/sisSIs554d2', 2006, 107, 7.0, 7.0, 'John Singleton', 'Paul Walker', 'English', 'HD', 0, 0, 0, 0, NULL, NULL, NULL, NULL, '2026-04-07 08:41:39'),
(30, 'The Fast and the Furious: Tokyo Drift', 'he-ast-and-the-urious-okyo-rift', 'A teenager becomes a major competitor in the world of drift racing after moving in with his father in Tokyo to avoid a jail sentence in America.', 'https://pbcdnw.aoneroom.com/image/2026/01/27/84ec1a493cf49f727c6f9613352c4ea7.jpg?x-oss-process=image/resize%2Cw_250', 'https://pbcdnw.aoneroom.com/image/2026/01/27/84ec1a493cf49f727c6f9613352c4ea7.jpg?x-oss-process=image/resize%2Cw_250', '', 'https://v.moviebox.ph/0eJyJLHbjq6', 2006, 104, 7.0, 7.0, 'Justin Lin', 'Lucas Black ,', 'English', 'HD', 0, 0, 1, 0, NULL, NULL, NULL, NULL, '2026-04-07 08:44:22'),
(31, 'The Fast and the Furious: 4', 'he-ast-and-the-urious-4', 'Brian O\'Conner, back working for the FBI in Los Angeles, teams up with Dominic Toretto to bring down a heroin importer by infiltrating his operation.', 'https://pbcdnw.aoneroom.com/image/2024/09/04/d1813e7e7a78e4780b1bda6ba04f681e.jpg?x-oss-process=image/resize%2Cw_250', 'https://pbcdnw.aoneroom.com/image/2024/09/04/d1813e7e7a78e4780b1bda6ba04f681e.jpg?x-oss-process=image/resize%2Cw_250', '', 'https://v.moviebox.ph/epFmsFGlwl', 2009, 107, 7.0, 7.0, 'Justin Lin', 'Paul Walker ,  Vin Deisel', 'hindi', 'HD', 0, 0, 0, 0, NULL, NULL, NULL, NULL, '2026-04-07 08:46:57'),
(32, 'The Fast and  Furious: 5', 'he-ast-and-urious-5', 'Dominic Toretto and his crew of street racers plan a massive heist to buy their freedom while in the sights of a powerful Brazilian drug lord and a dangerous federal agent.', 'https://pbcdnw.aoneroom.com/image/2024/08/07/9ab9f9e10133a2dbf9f7e95f2484b4fc.jpg?x-oss-process=image/resize%2Cw_250', 'https://pbcdnw.aoneroom.com/image/2024/08/07/9ab9f9e10133a2dbf9f7e95f2484b4fc.jpg?x-oss-process=image/resize%2Cw_250', '', 'https://v.moviebox.ph/yFQ1gtYCkP1', 2011, 130, 7.0, 7.0, 'Justin Lin', 'Paul Walker ,  Vin Deisel', 'hindi', 'HD', 0, 0, 0, 0, NULL, NULL, NULL, NULL, '2026-04-07 08:49:31'),
(33, 'The Fast and  Furious Presents Hobbs And Shaw', 'he-ast-and-urious-6', 'Lawman Luke Hobbs (Dwayne \"The Rock\" Johnson) and outcast Deckard Shaw (Jason Statham) form an unlikely alliance when a cyber-genetically enhanced villain threatens the future of humanity.', 'https://pbcdnw.aoneroom.com/image/2022/10/14/f1563267c9f21d638ee3c811174c0e34.jpg?x-oss-process=image/resize%2Cw_250', 'https://pbcdnw.aoneroom.com/image/2022/10/14/f1563267c9f21d638ee3c811174c0e34.jpg?x-oss-process=image/resize%2Cw_250', '', 'https://v.moviebox.ph/aDTFA7LXfL7', 2019, 133, 7.0, 7.0, 'Justin Lin', 'Dwane Johnson , jason Satham', 'Hindi', 'HD', 0, 0, 0, 0, NULL, NULL, NULL, NULL, '2026-04-07 08:51:52'),
(34, 'The Fast and  Furious: 9', 'he-ast-and-urious-10', 'Dom and the crew must take on an international terrorist who turns out to be Dom and Mia\'s estranged brother.', 'https://pbcdnw.aoneroom.com/image/2024/09/12/dacfbc1c707e1405ce6ef19d9bb81f50.jpg?x-oss-process=image/resize%2Cw_250', 'https://pbcdnw.aoneroom.com/image/2024/09/12/dacfbc1c707e1405ce6ef19d9bb81f50.jpg?x-oss-process=image/resize%2Cw_250', '', 'https://v.moviebox.ph/mpDu89qKmW6', 2016, 143, 7.0, 7.0, 'Justin Lin', 'Vin Deisel , Dwane Johnson ,  John Cena', 'Hindi', 'HD', 0, 0, 1, 0, NULL, NULL, NULL, NULL, '2026-04-07 08:59:10'),
(35, 'The Fast And Furious 10', 'he-ast-nd-urious-10', 'Dom Toretto and his family are targeted by the vengeful son of drug kingpin Hernan Reyes.', 'https://pbcdnw.aoneroom.com/image/2024/04/15/82a1a4be224dbff09386a85bc263ff9b.jpg?x-oss-process=image/resize%2Cw_250', 'https://pbcdnw.aoneroom.com/image/2024/04/15/82a1a4be224dbff09386a85bc263ff9b.jpg?x-oss-process=image/resize%2Cw_250', '', 'https://v.moviebox.ph/iN6aEgy9Bw3', 2023, 141, 7.0, 7.0, 'Justin Lin', 'Vin Deisel , Dwane Johnson , Jason Momoa , John Cena', 'Hindi', 'HD', 0, 0, 0, 0, NULL, NULL, NULL, NULL, '2026-04-07 09:06:32'),
(36, 'The Fate Of the Furious 8', 'he-ate-f-the-urious-8', 'When a mysterious woman seduces Dominic Toretto into the world of terrorism and a betrayal of those closest to him, the crew face trials that will test them as never before.', 'https://pbcdnw.aoneroom.com/image/2022/11/21/7aaa692e89bee90db67c0bbf7cc8e428.jpg?x-oss-process=image/resize%2Cw_250', 'https://pbcdnw.aoneroom.com/image/2022/11/21/7aaa692e89bee90db67c0bbf7cc8e428.jpg?x-oss-process=image/resize%2Cw_250', '', 'https://v.moviebox.ph/0urUQi7Wnta', 2017, 136, 0.0, 7.0, '', 'Vin Deisel , Dwane Johnson', 'Hindi', 'HD', 0, 0, 0, 0, NULL, NULL, NULL, NULL, '2026-04-07 09:09:06'),
(37, 'FAst and Furious 6', 'st-and-urious-6', 'Hobbs has Dominic and Brian reassemble their crew to take down a team of mercenaries, but Dominic unexpectedly gets sidetracked with facing his presumed deceased girlfriend, Letty.', 'https://pbcdnw.aoneroom.com/image/2026/01/27/ba05ed69f784830f85a10c76e413ca48.jpg?x-oss-process=image/resize%2Cw_250', 'https://pbcdnw.aoneroom.com/image/2026/01/27/ba05ed69f784830f85a10c76e413ca48.jpg?x-oss-process=image/resize%2Cw_250', '', 'https://v.moviebox.ph/YgJp9tNyZT1', 2013, 130, 7.0, 7.0, 'Justin Lin', 'Vin Deisel , Paul Walker', 'English', 'HD', 0, 0, 0, 0, NULL, NULL, NULL, NULL, '2026-04-07 09:10:56'),
(38, 'Furious 7', 'urious-7', 'Deckard Shaw seeks revenge against Dominic Toretto and his family for his comatose brother.', 'https://pbcdnw.aoneroom.com/image/2022/11/21/7ced6048e540934cab900bec6b63be8b.jpg?x-oss-process=image/resize%2Cw_250', 'https://pbcdnw.aoneroom.com/image/2022/11/21/7ced6048e540934cab900bec6b63be8b.jpg?x-oss-process=image/resize%2Cw_250', '', 'https://v.moviebox.ph/swxB0qpyMH', 2015, 137, 7.0, 7.0, 'Justin Lin', 'Vin Deisel , Dwane Johnson', 'English', 'HD', 0, 0, 0, 0, NULL, NULL, NULL, NULL, '2026-04-07 09:12:34'),
(39, 'Twilight', 'wilight', 'When Bella Swan moves to a small town in the Pacific Northwest, she falls in love with Edward Cullen, a mysterious classmate who reveals himself to be a 108-year-old vampire.', 'https://pbcdnw.aoneroom.com/image/2024/04/15/448fa2b5a44b1b7201eb2565c94bee93.jpg?x-oss-process=image/resize%2Cw_250', 'https://pbcdnw.aoneroom.com/image/2024/04/15/448fa2b5a44b1b7201eb2565c94bee93.jpg?x-oss-process=image/resize%2Cw_250', '', 'https://v.moviebox.ph/Cl5AO9KekG8', 2008, 122, 7.0, 8.0, 'Catherine Hardwicke', 'Robert Pattinson , Kristen Stweat', 'Hindi', 'HD', 0, 0, 0, 0, NULL, NULL, NULL, NULL, '2026-04-08 10:53:22'),
(40, 'Twilight Saga New Moon', 'wilight-aga-ew-oon', 'Edward leaves Bella after an attack that nearly claimed her life, and, in her depression, she falls into yet another difficult relationship - this time with her close friend, Jacob Black.', 'https://pbcdnw.aoneroom.com/image/2022/09/02/db095513dece0f15b5231e72c7f59147.jpg?x-oss-process=image/resize%2Cw_250', 'https://pbcdnw.aoneroom.com/image/2022/09/02/db095513dece0f15b5231e72c7f59147.jpg?x-oss-process=image/resize%2Cw_250', '', 'https://v.moviebox.ph/kgrnDraA6ba', 2009, 130, 7.0, 7.0, 'Chris Weitz', 'Robert Pattinson , Kristen Stweat', 'Hindi', 'HD', 0, 0, 0, 0, NULL, NULL, NULL, NULL, '2026-04-08 10:55:30'),
(41, 'Twilight Saga Eclipse', 'wilight-aga-clipse', 'As a string of mysterious killings grips Seattle, Bella, whose high school graduation is fast approaching, is forced to choose between her love for vampire Edward and her friendship with Warewolf Jacob', 'https://pbcdnw.aoneroom.com/image/2022/09/02/2b9c71eef48f4a1d4ae9373d9c65ac82.jpg?x-oss-process=image/resize%2Cw_250', 'https://pbcdnw.aoneroom.com/image/2022/09/02/2b9c71eef48f4a1d4ae9373d9c65ac82.jpg?x-oss-process=image/resize%2Cw_250', '', 'https://v.moviebox.ph/sGoXK2NYJ5a', 2010, 124, 7.5, 8.0, 'David Slade', 'Robert Pattinson , Kristen Stweat', 'Hindi', 'HD', 0, 0, 0, 0, NULL, NULL, NULL, NULL, '2026-04-08 10:58:02'),
(42, 'Mission Impossible 1', 'ission-mpossible-1', 'An American agent, under false suspicion of disloyalty, must discover and expose the real spy without the help of his organization.', 'https://pbcdnw.aoneroom.com/image/2025/12/04/dce03a1bbf88bfab8ada1200e7442a11.jpg?x-oss-process=image/resize%2Cw_250', 'https://pbcdnw.aoneroom.com/image/2025/12/04/dce03a1bbf88bfab8ada1200e7442a11.jpg?x-oss-process=image/resize%2Cw_250', '', 'https://v.moviebox.ph/cCa28TrXB67', 1996, 110, 7.0, 8.0, 'Brian De Palma', 'Tom Cruise , Emmanuelle Béart', 'Hindi', 'HD', 1, 0, 0, 0, NULL, NULL, NULL, NULL, '2026-04-08 11:03:10'),
(43, 'Superbad', 'uperbad', 'Two co-dependent high school seniors are forced to deal with separation anxiety after their plan to stage a booze-soaked party goes awry.', 'https://pbcdnw.aoneroom.com/image/2022/09/02/b8045ad6b0646aab0c96c13a3d7f4cfb.jpg?x-oss-process=image/resize%2Cw_250', 'https://pbcdnw.aoneroom.com/image/2022/09/02/b8045ad6b0646aab0c96c13a3d7f4cfb.jpg?x-oss-process=image/resize%2Cw_250', '', 'https://v.moviebox.ph/GvJuC3TPhX9', 2007, 113, 7.0, 7.0, 'Greg Mottola', 'Jonah Hill , Micheal Cera', 'Hindi', 'HD', 0, 0, 0, 0, NULL, NULL, NULL, NULL, '2026-04-08 13:44:49'),
(44, 'This is the end', 'his-is-the-end', 'Six Los Angeles celebrities are stuck in James Franco\'s house after a series of devastating events just destroyed the city. Inside, the group not only have to face the apocalypse, but themselves.', 'https://pbcdnw.aoneroom.com/image/2022/09/02/b8370b9e836462882a87f2465a80f7f4.jpg?x-oss-process=image/resize%2Cw_250', 'https://pbcdnw.aoneroom.com/image/2022/09/02/b8370b9e836462882a87f2465a80f7f4.jpg?x-oss-process=image/resize%2Cw_250', '', 'https://v.moviebox.ph/GFA1rX9Xnh1', 2013, 107, 7.0, 9.0, 'Seth Rogan', 'Jonah Hill , Seth Rogan', 'Hindi', 'HD', 0, 0, 0, 0, NULL, NULL, NULL, NULL, '2026-04-08 13:47:07'),
(45, 'Mission Impossible 2', 'ission-mpossible-2', 'IMF agent Ethan Hunt is sent to Sydney to find and destroy a genetically modified disease called \"Chimera\".', 'https://pbcdnw.aoneroom.com/image/2024/10/11/0b55fe52f996e1137470de33ac870d22.jpg?x-oss-process=image/resize%2Cw_250', 'https://pbcdnw.aoneroom.com/image/2024/10/11/0b55fe52f996e1137470de33ac870d22.jpg?x-oss-process=image/resize%2Cw_250', '', 'https://v.moviebox.ph/E2xIjmQ6W68', 2000, 123, 7.0, 7.5, '', 'Tom Cruise , Dougray Scott', 'Hindi', 'HD', 0, 0, 0, 0, NULL, NULL, NULL, NULL, '2026-04-08 13:49:33');

-- --------------------------------------------------------

--
-- Table structure for table `movie_genres`
--

CREATE TABLE `movie_genres` (
  `movie_id` int(11) NOT NULL,
  `genre_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `movie_genres`
--

INSERT INTO `movie_genres` (`movie_id`, `genre_id`) VALUES
(1, 4),
(1, 6),
(1, 9),
(2, 1),
(2, 4),
(2, 9),
(3, 1),
(3, 2),
(3, 6),
(5, 2),
(5, 4),
(5, 9),
(6, 1),
(6, 4),
(6, 6),
(7, 1),
(7, 6),
(7, 10),
(8, 2),
(8, 4),
(8, 9),
(12, 2),
(13, 1),
(13, 2),
(13, 9),
(14, 2),
(14, 6),
(14, 7),
(14, 10),
(15, 2),
(15, 6),
(15, 7),
(15, 10),
(16, 1),
(16, 12),
(17, 1),
(17, 12),
(18, 1),
(18, 2),
(18, 12),
(19, 5),
(21, 5),
(22, 5),
(23, 1),
(23, 6),
(23, 10),
(24, 1),
(24, 6),
(24, 10),
(25, 1),
(25, 2),
(25, 6),
(26, 2),
(26, 3),
(27, 3),
(28, 1),
(28, 2),
(28, 10),
(29, 1),
(29, 2),
(29, 10),
(30, 1),
(30, 2),
(30, 10),
(31, 1),
(31, 2),
(31, 9),
(31, 10),
(32, 1),
(32, 2),
(32, 9),
(32, 10),
(33, 1),
(33, 2),
(33, 9),
(33, 10),
(34, 1),
(34, 2),
(34, 9),
(34, 10),
(35, 1),
(35, 9),
(35, 10),
(36, 1),
(36, 9),
(36, 10),
(37, 1),
(37, 9),
(37, 10),
(38, 1),
(38, 9),
(38, 10),
(39, 1),
(39, 2),
(39, 6),
(39, 7),
(40, 1),
(40, 2),
(40, 6),
(40, 7),
(40, 10),
(41, 1),
(41, 2),
(41, 6),
(41, 7),
(41, 10),
(42, 1),
(42, 9),
(43, 2),
(43, 3),
(44, 3),
(44, 6),
(45, 1),
(45, 10);

-- --------------------------------------------------------

--
-- Table structure for table `recently_viewed`
--

CREATE TABLE `recently_viewed` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `movie_id` int(11) NOT NULL,
  `viewed_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `site_settings`
--

CREATE TABLE `site_settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` varchar(20) DEFAULT 'text',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `site_settings`
--

INSERT INTO `site_settings` (`setting_key`, `setting_value`, `setting_type`, `updated_at`) VALUES
('comments_auto_approve', '0', 'boolean', '2026-04-09 04:48:03'),
('items_per_page', '20', 'number', '2026-04-08 15:10:26'),
('maintenance_mode', '0', 'boolean', '2026-04-08 15:10:26'),
('ratings_auto_approve', '0', 'boolean', '2026-04-09 04:48:03'),
('site_name', 'CineVault', 'text', '2026-04-08 15:10:26'),
('site_tagline', 'Premium Movie Streaming', 'text', '2026-04-08 15:10:26');

-- --------------------------------------------------------

--
-- Table structure for table `sliders`
--

CREATE TABLE `sliders` (
  `id` int(11) NOT NULL,
  `movie_id` int(11) NOT NULL,
  `title` varchar(200) DEFAULT NULL,
  `subtitle` varchar(200) DEFAULT NULL,
  `cta_text` varchar(50) DEFAULT NULL,
  `cta_link` varchar(200) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `theme` varchar(20) DEFAULT 'dark',
  `role` varchar(20) DEFAULT 'user',
  `email_notifications` tinyint(1) DEFAULT 1,
  `last_seen` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `avatar`, `created_at`, `theme`, `role`, `email_notifications`, `last_seen`) VALUES
(1, 'Aaliyan', 'aliyanamir911@gmail.com', '$2y$10$MQZf.3/eyHcTgZN7srt9wekOA/ThK9nkFTwCuC2WfipuYqvQHE5sG', NULL, '2026-04-07 08:10:36', 'dark', 'user', 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_ratings`
--

CREATE TABLE `user_ratings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `movie_id` int(11) NOT NULL,
  `rating` decimal(2,1) NOT NULL,
  `review` text DEFAULT NULL,
  `is_approved` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_ratings`
--

INSERT INTO `user_ratings` (`id`, `user_id`, `movie_id`, `rating`, `review`, `is_approved`, `created_at`) VALUES
(1, 1, 16, 9.9, '', 0, '2026-04-08 18:01:50'),
(3, 1, 3, 9.0, 'Masterpiece! Christopher Nolan at his best.', 1, '2026-04-09 04:39:25'),
(4, 1, 36, 9.9, '', 0, '2026-04-09 04:49:58');

-- --------------------------------------------------------

--
-- Table structure for table `watchlist`
--

CREATE TABLE `watchlist` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `movie_id` int(11) NOT NULL,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `watchlist`
--

INSERT INTO `watchlist` (`id`, `user_id`, `movie_id`, `added_at`) VALUES
(1, 1, 15, '2026-04-07 08:11:11'),
(3, 1, 43, '2026-04-08 15:27:40'),
(4, 1, 12, '2026-04-08 17:02:44'),
(5, 1, 14, '2026-04-08 17:08:56'),
(6, 1, 44, '2026-04-08 17:57:53');

-- --------------------------------------------------------

--
-- Table structure for table `watch_history`
--

CREATE TABLE `watch_history` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `movie_id` int(11) NOT NULL,
  `progress` int(11) DEFAULT 0,
  `last_watched` datetime DEFAULT current_timestamp(),
  `watch_count` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `collections`
--
ALTER TABLE `collections`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `collection_movies`
--
ALTER TABLE `collection_movies`
  ADD PRIMARY KEY (`collection_id`,`movie_id`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `movie_id` (`movie_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `contacts`
--
ALTER TABLE `contacts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `email_subscribers`
--
ALTER TABLE `email_subscribers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `genres`
--
ALTER TABLE `genres`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `movies`
--
ALTER TABLE `movies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `movie_genres`
--
ALTER TABLE `movie_genres`
  ADD PRIMARY KEY (`movie_id`,`genre_id`),
  ADD KEY `genre_id` (`genre_id`);

--
-- Indexes for table `recently_viewed`
--
ALTER TABLE `recently_viewed`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `movie_id` (`movie_id`);

--
-- Indexes for table `site_settings`
--
ALTER TABLE `site_settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indexes for table `sliders`
--
ALTER TABLE `sliders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `movie_id` (`movie_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_ratings`
--
ALTER TABLE `user_ratings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `movie_id` (`movie_id`);

--
-- Indexes for table `watchlist`
--
ALTER TABLE `watchlist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_watchlist` (`user_id`,`movie_id`),
  ADD KEY `movie_id` (`movie_id`);

--
-- Indexes for table `watch_history`
--
ALTER TABLE `watch_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `movie_id` (`movie_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `collections`
--
ALTER TABLE `collections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `contacts`
--
ALTER TABLE `contacts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `email_subscribers`
--
ALTER TABLE `email_subscribers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `genres`
--
ALTER TABLE `genres`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `movies`
--
ALTER TABLE `movies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `recently_viewed`
--
ALTER TABLE `recently_viewed`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sliders`
--
ALTER TABLE `sliders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `user_ratings`
--
ALTER TABLE `user_ratings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `watchlist`
--
ALTER TABLE `watchlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `watch_history`
--
ALTER TABLE `watch_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `movie_genres`
--
ALTER TABLE `movie_genres`
  ADD CONSTRAINT `movie_genres_ibfk_1` FOREIGN KEY (`movie_id`) REFERENCES `movies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `movie_genres_ibfk_2` FOREIGN KEY (`genre_id`) REFERENCES `genres` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `watchlist`
--
ALTER TABLE `watchlist`
  ADD CONSTRAINT `watchlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `watchlist_ibfk_2` FOREIGN KEY (`movie_id`) REFERENCES `movies` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
