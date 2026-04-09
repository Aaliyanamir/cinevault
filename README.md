# CineVault - Movies Website
## Setup Instructions

### Requirements
- PHP 7.4+ with PDO and MySQLi
- MySQL 5.7+ or MariaDB 10+
- Apache / Nginx with mod_rewrite

---

### Step 1 — Database Setup
1. Open phpMyAdmin or your MySQL client
2. Run the entire contents of **database.sql**
3. This creates the database, tables, and sample movies

---

### Step 2 — Configuration
Open `includes/config.php` and update:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_mysql_username');
define('DB_PASS', 'your_mysql_password');
define('DB_NAME', 'moviesdb');
define('SITE_URL', 'http://yourdomain.com/movies-site');
```

---

### Step 3 — Upload Files
Upload the entire `movies-site` folder to your hosting:
- For localhost: place in `htdocs/` or `www/` folder
- For live server: upload to `public_html/` or domain root

---

### Step 4 — Access the Site
- **Main Site:** `http://yourdomain.com/movies-site/`
- **Admin Panel:** `http://yourdomain.com/movies-site/admin.php`
  - Default admin password: `admin123`
  - Change it in `admin.php` line: `define('ADMIN_PASS', 'admin123');`

---

### File Structure
```
movies-site/
├── index.php          → Main frontend (all views)
├── admin.php          → Admin panel to manage movies
├── database.sql       → Database setup + sample data
├── includes/
│   └── config.php     → DB config, helper functions
└── pages/
    ├── auth.php       → Login / Register / Logout API
    ├── movies.php     → Movies API (fetch, search, filter)
    ├── genres.php     → Genres API
    └── watchlist.php  → Watchlist API (requires login)
```

---

### How to Add Movies
1. Go to **Admin Panel** → `admin.php`
2. Fill in movie details
3. **Embed URL** = the iframe `src` URL of your video player
   - YouTube: `https://www.youtube.com/embed/VIDEO_ID`
   - VidSrc: `https://vidsrc.to/embed/movie/IMDB_ID`
   - Your own player: your stream URL
4. Mark as Featured or Trending as needed

---

### Authentication
- Users only need to log in when saving to **Watchlist**
- Browsing and watching movies works without login
- Sessions are handled with PHP `$_SESSION`

---

### Customization
- **Site name:** Change `SITE_NAME` in `config.php` and update text in `index.php`
- **Colors:** Edit CSS variables at top of `index.php` (`:root { }`)
- **Admin password:** Line 4 of `admin.php`

---

### Notes
- The demo uses YouTube embeds. Replace with your actual video embed links.
- Poster/backdrop images use TMDB CDN URLs in demo data — replace with your own.
- For production, add HTTPS and consider rate limiting on auth endpoints.
