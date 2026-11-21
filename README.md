# KABC Hotel Menu

A PHP and MySQL application for the KABC Hotel public menu and its admin tools.

## Features

- Public landing page and searchable menu, grouped by category and subcategory.
- Admin sign-in and first-admin setup, menu editing, menu-image uploads, login history, password changes, and password reset by email.
- PDF menu export with a browser print-to-PDF fallback.
- Branded 403, 404, 500, 502, and 503 error pages.
- Locally hosted Font Awesome Free icons.

## Requirements

- PHP 7.4 or newer with `pdo_mysql`, `fileinfo`, and `mbstring` enabled.
- MySQL or MariaDB.
- Apache with `mod_rewrite` enabled and `.htaccess` overrides allowed (`AllowOverride All`).
- Composer, for installing PHPMailer.

## Local setup (XAMPP)


1. Place the project in the Apache document root, for example `C:\xampp\htdocs\kabc_menu`.
2. Create a MySQL database and import the KABC menu schema. The schema should include the existing `menu`, `category`, and `subcategory` tables; this repository does not include their database dump.
3. Set the database host, port, database name, username, and password in `config/database.php`.
4. From the project directory, run:

   ```sh
   composer install
   ```

5. Make sure PHP can write to the `uploads/` directory.
6. Open `http://localhost/kabc_menu/` in a browser. The public menu is at `/kabc_menu/full_menu`.
7. Open `/kabc_menu/admin/`. If there are no admin accounts yet, the setup form lets you create the first one.

## Configuration

### Database

Edit `config/database.php` for each environment. Keep production credentials private and use a database account with only the permissions this application needs.

The admin account and login-history tables are created by the admin sign-in/setup page when it is first opened. Menu content tables must already exist and be populated separately.
Database configuration accepts both `DB_NAME` (used by Wasmer) and `DB_DATABASE` (used by local/shared-hosting `.env` files).

### Email delivery

For local or shared hosting with external SMTP, set the SMTP host, port, encryption mode, username, password, and sender details in the private `.env` file. The app uses `config/smtp.php` when present and falls back to the tracked, environment-based `config/smtp.example.php`, so production does not need a copied secret config file. The public room-enquiry form and admin password-reset email both require a working email configuration. Room enquiries are sent to `reservations@kabchotel.rw`.

Wasmer's built-in email uses `SMTP_TRANSPORT=sendmail` and does not require SMTP login credentials. It does require `enable_email: true` in `app.yaml` and a paid plan. Set a valid `SMTP_FROM_EMAIL`, `SMTP_FROM_NAME`, `SMTP_SUPPORT_EMAIL`, and `SMTP_SUPPORT_PHONE` in the Wasmer app environment; the sender address should belong to a domain you control. The current public KABC contact details are prefilled in `app.yaml`.

To test external SMTP configuration, a signed-in admin can open `/admin/test_email` and send one test message to the address shown on that page. It requires `SMTP_TRANSPORT=smtp`; detailed failures are written to the PHP/server error log. A successful result means the SMTP server accepted the message, not that it reached the inbox.

### Uploads

Uploaded menu images are saved under `uploads/` by default. Set `UPLOAD_DIR` and `UPLOAD_URL_PATH` for persistent storage. On Wasmer, `UPLOAD_DIR=/data/uploads` with the `uploads` volume provides persistent image storage. Uploads are limited to 5 MB and accept JPG, PNG, GIF, and WEBP.

## Apache and deployment

The project `.htaccess` currently assumes it is served from `/kabc_menu/` and maps 403, 404, 500, 502, and 503 responses to the corresponding PHP pages. If the application is installed at a different URL path, update `RewriteBase` and the `ErrorDocument` paths to match.

On Apache/shared hosting, `.htaccess` rewrites extensionless URLs, redirects direct `.php` page requests to extensionless URLs, and blocks private configuration directories. For Wasmer or any server without Apache rewrites, `router.php` provides equivalent PHP routing and private-path protection. Enable HTTPS in production, and ensure the production server has the required PHP extensions and writable upload directory.

## Wasmer deployment

The project includes `router.php`, `app.yaml`, `wasmer.toml`, and a persistent-session migration. It does not require a GitHub Actions token for Wasmer deployments.

1. In Wasmer, import this GitHub repository and connect it to an app.
2. Let Wasmer generate or update the app owner and app name in `app.yaml`.
3. Create the Wasmer MySQL database capability and deploy once, or enable it in `app.yaml`.
4. Import the existing menu/category data into the managed database.
5. For built-in email, keep `SMTP_TRANSPORT=sendmail` and configure the sender/support details in `app.yaml` or Wasmer app environment. SMTP username/password credentials are not used by this transport.
6. Push to the repository branch configured in Wasmer to trigger redeployment.
7. Admin sessions are stored in MySQL through `app_sessions`; the schema is at `database/session.sql`. The application creates this table automatically when it starts.

## External assets

Font Awesome CSS and its webfont are stored locally in `assets/fontawesome/`, so those icons work without a CDN. The project also references Google Fonts, Chart.js, and jsPDF from external CDNs; the dashboard charts and direct PDF download therefore need network access. The PDF page falls back to the browser's print dialog if its PDF libraries are unavailable.

## Font Awesome attribution

This project uses Font Awesome Free 7.3.1 by Fonticons, Inc. under the Free license. The locally bundled package license is at `assets/fontawesome/LICENSE.txt`.

- Project: <https://fontawesome.com>
- License: <https://fontawesome.com/license/free>

## Main pages

- `/` - public landing page
- `/full_menu` - searchable public menu
- `/menu_table.php` - menu export and PDF page
- `/admin/` - admin sign-in and initial account setup
