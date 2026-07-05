## Deploy on cPanel (subdirectory, like `bpti/`)

BPTI works at `/bpti/` because it has `index.php` in that folder. Laravel normally keeps its entry point in `public/index.php`. This project adds a root `index.php` and `.htaccess` so `/assessment-portal/` behaves the same way.

Use this when the app lives at:

`https://connectionscounseling.org/assessment-portal/`

## Why the URL 404s today

Two common causes on shared hosting:

1. **Missing root `index.php` or `.htaccess`.** Without them, `/assessment-portal/` has no web entry file (unlike `/bpti/index.php`).
2. **PHP version for this app.** BPTI may run on an older PHP, but Laravel 12 requires **PHP 8.2+**. Test `/assessment-portal/public/index.php` - if you see a Composer platform error, upgrade PHP for this folder only.

In cPanel, confirm the folder contains `index.php`, `.htaccess`, and `public/index.php`.

## Upload layout

Target path on the server:

```text
public_html/
  assessment-portal/
    .htaccess              <- routes /assessment-portal/* into public/
    app/
    bootstrap/
    config/
    database/
    public/
      .htaccess
      index.php            <- Laravel entry point
      build/               <- from npm run build
    resources/
    routes/
    storage/
    vendor/
    .env                   <- production config (not committed)
```

Do **not** point the domain document root at the Laravel project root. Keep the main site as-is and run this app in a subdirectory.

## Production setup (SSH or cPanel Terminal)

```bash
cd ~/public_html/assessment-portal

# If vendor/ was not uploaded, install on server:
composer install --no-dev --optimize-autoloader

# Create production env
cp .env.example .env
php artisan key:generate

# Edit .env (see below), then:
php artisan migrate --force
php artisan db:seed --class=EvaluationPortalSeeder   # optional demo data
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Build frontend assets locally before upload, or on server if Node is available:

```bash
npm ci
npm run build
```

## Required `.env` values (production)

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://connectionscounseling.org/assessment-portal

DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=connectionscouns_assess
DB_USERNAME=<cpanel-mysql-user>
DB_PASSWORD=<mysql-password>
```

Create database `connectionscouns_assess` in cPanel -> MySQL Databases and grant the app user full privileges.

## Permissions

```text
storage/                 775 (writable by web server)
bootstrap/cache/         775
```

In cPanel File Manager: select `storage` -> Permissions -> recurse into subdirectories.

## Quick URL tests

After upload, these should work:

| URL | Expected |
|-----|----------|
| `https://connectionscounseling.org/assessment-portal/public/index.php` | Laravel home (direct test) |
| `https://connectionscounseling.org/assessment-portal/` | Laravel home (via root `.htaccess`) |
| `https://connectionscounseling.org/assessment-portal/up` | Health check JSON/text |

If the first URL works but the second does not, the root `.htaccess` is missing or `mod_rewrite` is disabled.

If neither works, the upload is incomplete or PHP version is below 8.2.

## WordPress / main site conflict

If you still see the main site's themed 404 page:

1. Confirm `public_html/assessment-portal/public/index.php` exists on the server.
2. Confirm `public_html/assessment-portal/.htaccess` exists (routes into `public/`).
3. Try the direct URL: `/assessment-portal/public/index.php`.
4. In cPanel -> MultiPHP Manager, set PHP 8.2+ for the domain or subdirectory.

## PHP extensions required

`openssl`, `pdo_mysql`, `mbstring`, `curl`, `fileinfo`, `tokenizer`, `xml`, `ctype`, `json`
