# Inventory Pricing System (PHP + Google Sheets)

Add, view, modify and delete products. Your Google Sheet is the database.

## How it works
Browser (HTML/CSS/JS) -> `api.php` (PHP) -> Google Apps Script web app -> your Google Sheet.
The secret token only lives on the server (config.php and Code.gs), never in the browser.

## Setup
### 1. Google Sheet + Apps Script
1. Create a new Google Sheet (any name).
2. Menu: Extensions > Apps Script. Delete the sample code and paste in `google-apps-script/Code.gs`.
3. Change `TOKEN` to a long random string (e.g. 40 random characters). Save.
4. Click Deploy > New deployment > gear icon > Web app.
   - Execute as: **Me**
   - Who has access: **Anyone**
   Click Deploy, approve the permissions, and copy the **Web app URL** (ends with `/exec`).
5. The first request creates a `Products` tab with the headers automatically.

### 2. PHP site
1. Requirements: PHP 7.4+ with the **cURL** extension (XAMPP/WAMP/Laragon include it).
2. Open `config.php` and set `SHEETS_URL` (the /exec URL) and `SHEETS_TOKEN` (same as TOKEN).
3. Put this folder in your web root (e.g. `htdocs/inventory`) and open `http://localhost/inventory/`,
   or run `php -S localhost:8000` inside the folder.

## Updating the Apps Script later
After editing Code.gs choose Deploy > Manage deployments > edit > Version: New version > Deploy.
The URL stays the same.

## Troubleshooting
- "Google did not return JSON": the deployment access must be "Anyone", and SHEETS_URL must end in /exec.
- "Token mismatch": TOKEN in Code.gs and SHEETS_TOKEN in config.php differ.
- "SSL certificate problem" on Windows/XAMPP: set `curl.cainfo` in php.ini to a cacert.pem file.
- Edit data directly in the Sheet if you like, but keep the header row and don't change the `id` column.

## Notes
- Google Sheets is slower than MySQL (about 1-2 seconds per request) and suits a few thousand rows.
- There is no login. Add authentication in front of index.php/api.php before using it publicly.
