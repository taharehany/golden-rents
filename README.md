# Golden Rose: PHP/MySQL car listings

The existing landing page remains a static HTML/CSS/jQuery page with no build step. Its car section loads published records from the native PHP API; the original cards remain only as a no-JavaScript/API-failure fallback. The dashboard is at `admin/`.

## Layout

```
index.html                 existing public page
api/index.php              REST-style JSON API
admin/                     login and car dashboard
app/bootstrap.php          PDO, sessions, validation and upload helpers
app/config.local.php       local secrets (create; never commit)
database/schema.sql        MySQL installation schema
database/import_cars.php  idempotent import of the 17 original listings
uploads/                   dashboard-uploaded images
```

`cars` is ordered by `sort_order` so the original on-page sequence is retained. The import preserves the original spelling, names, prices, model/year values, AED currency, and `img/services/` image paths. The original markup's additional fixed details (fuel/transmission/doors) were presentation-only rather than data fields; the dynamic card shows model/year and brand and uses description when provided.

## Local setup and verification

1. Create a MySQL/MariaDB database and import `database/schema.sql`.
2. Copy `app/config.example.php` to `app/config.local.php`; enter database values and a long random `setup_token`. Prefer putting this file above the public document root and adapting the small `$localConfig` path in `app/bootstrap.php`.
3. Visit `database/import_cars.php?token=YOUR_SETUP_TOKEN` once. It inserts only name/image pairs not already in `cars`; it never deletes or overwrites records.
4. Visit `admin/setup.php`, submit the same token, a username, and a 12+-character password. It stores `password_hash()` output only.
5. Delete `admin/setup.php` and remove `setup_token` from `config.local.php`. Log in at `admin/`.

The public URL is `api/cars`; `api/cars/{id}` retrieves one published car. Admin endpoints are under `api/admin/*`; they require the secure PHP session and CSRF request header supplied by the dashboard.

## cPanel deployment

1. In **MySQL Databases**, create a database and user, add the user to the database with **ALL PRIVILEGES**. Record the cPanel-prefixed names.
2. Open **phpMyAdmin**, select that database, choose **Import**, and import `database/schema.sql`.
3. Upload the repository contents to `public_html` (or the chosen addon-domain document root). There is no Composer, Node.js, or production build requirement. If deploying in a subdirectory, retain the layout; the relative API URLs work there.
4. In cPanel **MultiPHP Manager**, choose a currently supported PHP 8.0+ release and ensure PDO MySQL, Fileinfo, JSON, and sessions are enabled (they normally are).
5. Create the protected `app/config.local.php` as described above. Best: put it one directory above `public_html` and change `$localConfig` in `app/bootstrap.php` to that absolute path. If it must stay in `app/`, the included `app/.htaccess` blocks direct HTTP access; verify it returns 403. Never put credentials in JavaScript.
6. Ensure `uploads/` exists and is writable by PHP: directories `755` normally suffice; use `775` only if the host requires it. Files should normally be `644`. Do not use `777`. Keep `uploads/.htaccess`, which blocks PHP-like execution where Apache permits it.
7. Run the import and one-time administrator setup steps above, then remove `admin/setup.php` and the setup token. Do not leave either setup URL active.
8. Enable a valid SSL certificate in cPanel (AutoSSL) and force HTTPS using the hosting control panel or an existing site-wide redirect. Session cookies automatically receive the `Secure` flag on HTTPS.

### Backup and recovery

Before updates, use phpMyAdmin **Export** (custom SQL is ideal) for the database and download `uploads/` together with `img/services/`. Restore the SQL via phpMyAdmin **Import** and restore upload files with the same relative paths. The importer is safe to run again: it adds only missing original name/image pairs.

### Common deployment problems

- **403:** check cPanel file permissions and preserve the upload/app `.htaccess` files. `app/` returning 403 is expected.
- **404 API:** confirm `api/.htaccess` uploaded and Apache `mod_rewrite` enabled; direct `api/index.php` is a useful host check.
- **500:** read cPanel **Errors**; check PHP version/extensions and `app/config.local.php` syntax/ownership. Error responses intentionally hide database internals.
- **Database connection error:** verify the cPanel-prefixed database/user names, host (usually `localhost`), password, and privileges.
- **Image upload failure:** confirm PHP can write `uploads/`, `upload_max_filesize`/`post_max_size` are at least 5 MB, and use JPEG, PNG, or WebP.

## Manual acceptance checks

1. After import, `SELECT COUNT(*) FROM cars` returns 17 on a new database; their ordering and image paths match the existing page.
2. Open the home page and confirm published cars load from `api/cars`; change one car to draft in admin and refresh—the car is absent publicly.
3. Create, edit, publish/unpublish, and delete a test record; upload JPEG/PNG/WebP and replace it. Confirm a rejected PHP/text/oversize file does not upload.
4. In a private/incognito window, request an `api/admin/cars` URL and confirm `401`; send a state-changing request without the CSRF header and confirm `403`.
5. Confirm logout ends dashboard access and the changes remain after refresh.

`php -l` is suitable for a syntax check of all PHP files. Full browser/database integration tests require a configured MySQL server and were intentionally not run against production credentials.
