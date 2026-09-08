# College Class Management System

PHP 8 + MySQL application for **BigRock / cPanel** shared hosting: teacher class selection, attendance monitoring (teacher PIN + monitor verification), admin compliance, and **read-only student** live status.

## Requirements

- PHP 8.1+ with extensions: `pdo_mysql`, `json`, `session`, `openssl`
- MySQL 8 / MariaDB
- Apache with `mod_rewrite` (typical on cPanel)

## See a local demo (Windows or any OS)

1. **Install** PHP 8+ and MySQL (e.g. [Laragon](https://laragon.org/), [XAMPP](https://www.apachefriends.org/), or MySQL + PHP from the vendor).
2. **Create a database** (e.g. `college_cms`) and import:
   ```bash
   mysql -u root -p college_cms < database/schema.sql
   mysql -u root -p college_cms < database/seed.sql
   ```
3. **Edit** `app/config/config.php` — set `db` (host, name, user, password) and `base_url` to your local URL, for example:  
   `http://127.0.0.1:8080`  
   (If you use another port, match it here; links and redirects use `base_url`.)
4. **Start PHP’s built-in server** from the `public_html` folder (uses `router.php` so `/login` etc. work; `.htaccess` is not read by this server):
   ```bash
   cd public_html
   php -S 127.0.0.1:8080 router.php
   ```
5. **Open a browser** to: `http://127.0.0.1:8080`  
6. **Log in** with a seeded user (e.g. `admin@college.edu` / `password`). Change passwords after testing.

> **Note:** Earlier we saw `php` not in your PATH. Use “Terminal” inside Laragon/XAMPP, or add PHP to your system PATH, or run the full path to `php.exe`.

**Production (BigRock / cPanel):** Point the domain’s document root at `public_html/`, then use Apache (which applies `.htaccess`); you do not need `router.php` in production.

### This Windows machine (already prepared)

- **PHP 8.2** (winget) with **pdo_mysql** and **openssl** enabled in `php.ini` next to `php.exe`.
- **MySQL 8.4** running on **port 3307** with a local data directory under `tools/mysql_data/` (dev-only, empty root password). Database **`college_cms`** is loaded from `database/schema.sql` and `database/seed.sql`.
- **`app/config/config.php`** is set to `base_url` `http://127.0.0.1:8080` and `db.port` **3307** (see also optional `port` in `app/core/Database.php`).

**Start the demo** (from project root):

```powershell
powershell -ExecutionPolicy Bypass -File scripts\start-local-demo.ps1
```

Then open **http://127.0.0.1:8080** and sign in: `admin@college.edu` / `password`.

## Install

1. Upload the project so that **`public_html/`** is the website document root (or map the domain to that folder).
2. Place **`app/`** and **`storage/`** **outside** the public web root if your host allows; otherwise ensure `app/config/` is not browsable.
3. Copy `app/config/config.sample.php` to `app/config/config.php` and set database, `base_url` (full URL including path if in subdirectory), `cron_key`, and mail settings.
4. Create database and import schema:
   ```bash
   mysql -u USER -p DBNAME < database/schema.sql
   mysql -u USER -p DBNAME < database/seed.sql
   ```
5. Demo logins (after seed):  
   - `admin@college.edu` / `password`  
   - `teacher@college.edu` / `password`  
   - `monitor@college.edu` / `password`  
   - `student@college.edu` / `password`  
   Change passwords immediately. Teacher must set a **check-in PIN** under Profile before using Check-in.
6. Cron (optional): hourly  
   `php /home/USER/public_html/cron_reminders.php YOUR_CRON_KEY`

## Security notes

- Uses prepared statements, CSRF tokens on POST, password hashing (`password_hash`), session cookie flags, login rate limiting, and audit logging.
- Configure **HTTPS** and uncomment HSTS / HTTPS redirect in `.htaccess` when SSL is active.
- Never commit `app/config/config.php` (see `.gitignore`).

## Documentation

See `docs/` for architecture overview and role manuals for the academic review.
