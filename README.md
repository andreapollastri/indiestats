<h1 align="center">IndieStats</h1>

<p align="center">
  <strong>Privacy-friendly, self-hosted web analytics built with Laravel.</strong><br>
  Multiple sites, real-time dashboards, advanced filters and localized Excel exports — no cookies, no third parties.
</p>

<p align="center">
  <a href="https://github.com/andreapollastri/indiestats/actions/workflows/tests.yml"><img src="https://github.com/andreapollastri/indiestats/actions/workflows/tests.yml/badge.svg" alt="Tests"></a>
  <a href="LICENSE"><img src="https://img.shields.io/badge/license-MIT-0d9488" alt="MIT License"></a>
  <img src="https://img.shields.io/badge/PHP-8.4%2B-777BB4?logo=php&logoColor=white" alt="PHP 8.4+">
  <img src="https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white" alt="Laravel 13">
</p>

<p align="center">
  <a href="https://indiestats.web.ap.it">Website</a> ·
  <a href="#quick-start">Quick start</a> ·
  <a href="#try-the-demo">Try the demo</a> ·
  <a href="#production-checklist">Deploy</a> ·
  <a href="CHANGELOG.md">Changelog</a>
</p>

<p align="center">
  <img src="screenshots/dashboard.png" alt="IndieStats dashboard: real-time activity across all sites and a 30-day trend for each site" width="960">
</p>

> All screenshots in this README come from the built-in demo (`DemoDataSeeder`): five fictional sites with a year of generated traffic. See [Try the demo](#try-the-demo).

## Contents

- [Features](#features)
- [A tour of the app](#a-tour-of-the-app)
- [Quick start](#quick-start)
- [Try the demo](#try-the-demo)
- [Tracking your sites](#tracking-your-sites)
- [What is collected](#what-is-collected)
- [Using the dashboard](#using-the-dashboard)
- [Users and roles](#users-and-roles)
- [Configuration](#configuration)
- [Scheduler, queue and backups](#scheduler-queue-and-backups)
- [Production checklist](#production-checklist)
- [Deploying with Cipi](#deploying-with-cipi)
- [Public endpoints](#public-endpoints)
- [Development](#development)
- [Tech stack](#tech-stack)
- [License](#license)

## Features

**Tracking**

- Lightweight async snippet with a `noscript` pixel fallback — no cookies; visitors are identified by a random ID in `localStorage`
- Pageviews, time on page, outbound clicks, UTM tags, on-site search terms, page title, query string, browser language and timezone
- Custom events with up to 20 properties, and goals to follow conversions
- Every hit is attributed to the session's entry referrer, so internal navigation never shows up as a traffic source
- Device, browser, OS and crawler detection; country (MaxMind GeoLite2) and network/ISP (DB-IP ASN Lite)
- Per-site domain allowlist, rate limiting and Cloudflare-aware client IP resolution

**Analytics**

- Multi-site dashboard with live visitor counts and per-site trends
- Nine tabs per site: Summary, Real-time, Content, Traffic, UTM, Technology, Geography (choropleth map), Visitor and Events
- 21 filters with type-ahead search, applied consistently to every chart, table, real-time view and export
- Ranges from today (hourly) to one year, computed in each user's own timezone
- Background Excel (XLSX) export in the user's language

**Team and operations**

- Admin and base roles; base users only see the sites assigned to them
- Two-factor authentication (TOTP) with recovery codes
- Interface in English, Italian, German, French and Spanish
- Scheduled data retention, GeoIP/ASN database updates and backups
- A realistic demo dataset and a test suite that runs in CI

## A tour of the app

### Site summary

Headline metrics, top pages, top sources and the daily trend for the selected range.

<img src="screenshots/site-summary.png" alt="Site summary tab with visitors, pageviews, average time on page, outbound clicks, top pages, top sources and a trend chart" width="960">

### Real-time

Active visitors, pageviews in the last five minutes, a 30-minute chart and a live activity feed. The dashboard shows the same view across all sites.

<img src="screenshots/site-realtime.png" alt="Real-time tab with active visitors, last five minutes of pageviews, a 30-minute chart and the latest activity" width="960">

### Filters

Every dimension can be filtered with type-ahead search. Filters narrow every tab, chart, real-time count, table and export.

<img src="screenshots/site-filters.png" alt="Filter panel with source set to instagram and device set to mobile, and the summary narrowed accordingly" width="960">

### Geography

A choropleth map and the country breakdown, with country names in the user's language.

<img src="screenshots/site-geography.png" alt="Geography tab with a world map shaded by visits and a country table" width="960">

### Events and goals

Goals link a label to a custom event name. The event detail table shows each occurrence with its properties.

<img src="screenshots/site-events.png" alt="Events tab with configured goals, event totals and event details with properties" width="960">

### More analytics tabs

| Content | Traffic |
| --- | --- |
| <img src="screenshots/site-content.png" alt="Content tab with page titles, paths and on-site search terms" width="470"> | <img src="screenshots/site-traffic.png" alt="Traffic tab with sources and outbound links" width="470"> |
| Page titles, paths and on-site search terms. | Sources and outbound links, attributed to the session's entry referrer. |
| **UTM** | **Technology** |
| <img src="screenshots/site-utm.png" alt="UTM tab with source, medium, campaign, term and content tables" width="470"> | <img src="screenshots/site-technology.png" alt="Technology tab with browser language, timezone, browser version and browser tables" width="470"> |
| Source, medium, campaign, term and content. | Language, timezone, browser, version, OS, device and network (ASN). |
| **Visitor** | **Sites** |
| <img src="screenshots/site-visitors.png" alt="Visitor tab with visitor IDs and human versus bot traffic" width="470"> | <img src="screenshots/sites.png" alt="Sites list with allowed domains and actions" width="470"> |
| Visitor IDs and human vs. bot traffic. | Every site with its allowed domains. |

### Adding a site

<table>
  <tr>
    <td width="50%"><img src="screenshots/site-create.png" alt="New site dialog with name and allowed domains"></td>
    <td width="50%"><img src="screenshots/site-snippet.png" alt="Confirmation with the tracking snippet ready to copy"></td>
  </tr>
  <tr>
    <td>Name the site and list the domains allowed to send data.</td>
    <td>Copy the snippet and paste it before <code>&lt;/body&gt;</code>.</td>
  </tr>
</table>

### Users, settings and security

<table>
  <tr>
    <td width="33%"><img src="screenshots/users.png" alt="User management with admin and base roles and last login"></td>
    <td width="33%"><img src="screenshots/settings.png" alt="Settings with GeoIP and ASN database management and language and timezone preferences"></td>
    <td width="33%"><img src="screenshots/account.png" alt="Account page enabling two-factor authentication with a QR code"></td>
  </tr>
  <tr>
    <td>Users, roles and last login.</td>
    <td>GeoIP/ASN databases, language and timezone.</td>
    <td>Profile, password and two-factor authentication.</td>
  </tr>
</table>

### On mobile

<p>
  <img src="screenshots/mobile-dashboard.png" alt="Dashboard on a phone" width="300">
  &nbsp;&nbsp;
  <img src="screenshots/mobile-site.png" alt="Site summary on a phone" width="300">
</p>

## Quick start

**Requirements:** PHP 8.4+ with the standard Laravel extensions (`openssl`, `pdo`, `mbstring`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`), Composer 2, Node.js 20+ and npm, and SQLite (default), MySQL/MariaDB or PostgreSQL. Downloading the GeoIP database also needs `tar`.

```bash
git clone https://github.com/andreapollastri/indiestats.git
cd indiestats
composer run setup
```

`composer run setup` installs PHP and Node dependencies, builds the frontend, creates `.env`, generates the app key and runs the migrations.

Create the first admin account (public sign-up is disabled — admins create users from **Users**):

```bash
php artisan db:seed   # admin@users.test / password — change it after signing in
```

Then serve the app (`php artisan serve`, or Laravel Herd/Valet) and sign in.

<details>
<summary>Manual installation</summary>

```bash
composer install
npm install
npm run build
cp .env.example .env
php artisan key:generate
touch database/database.sqlite   # SQLite only
php artisan migrate
php artisan db:seed
```

</details>

## Try the demo

The demo seeder builds a realistic, self-contained dataset so you can explore every screen without installing the snippet anywhere:

```bash
php artisan migrate:fresh --seed --seeder=DemoDataSeeder
```

Sign in as **admin@users.test** / **password**. You get:

- **Five fictional sites** with different audiences: a developer blog (Pixel Notes), open-source docs (Trailhead Docs), a European coffee shop (Northwind Coffee), a SaaS landing page (Fieldnote) and a design studio (Lumen Studio).
- **About 230,000 pageviews over the last year** with growth, weekly and seasonal patterns, local time of day, returning visitors and multi-page sessions.
- **Coherent visitors:** country, language, timezone, ISP, device, OS and browser match each other, and browser versions age realistically over the year.
- **Real campaigns and spikes:** newsletter and paid-social UTM campaigns, Google Ads terms, a Hacker News front page, a Reddit release post and a Product Hunt launch.
- **Goals and funnels:** add to cart → checkout → purchase, signups, downloads and contact forms, with event properties.
- **Crawlers** (Googlebot, Bingbot, Applebot…), on-site searches and outbound clicks.
- **A team** of four extra users with admin and base roles, languages and assigned sites.
- **Live activity** in the last 30 minutes, so the real-time views are populated.

Seeding takes about 10 seconds on SQLite. The data is deterministic, uses `.example` domains and documentation IP ranges, and never points at real websites or people. Re-running the seeder replaces only the demo sites.

Real-time views show the last few minutes, so they empty out after a while. Refresh them before a demo:

```bash
php artisan db:seed --class=DemoLiveTrafficSeeder
```

Both demo seeders refuse to run when `APP_ENV=production`.

<details>
<summary>Random bulk data (load testing)</summary>

`FakeDataSeeder` creates five random sites with 3,000 pageviews each, spread over 18 months. It runs as part of `php artisan db:seed` when `SEED_FAKE_DATA=true` is set in `.env`.

</details>

## Tracking your sites

### 1. Add a site

Open **Sites → New site**, enter a name and the domains allowed to send data (for example `example.com, www.example.com`). Subdomains of an allowed domain are accepted. Requests from any other origin are rejected, so always set allowed domains in production.

### 2. Install the snippet

Paste the generated code before the closing `</body>` tag of every page (or in your shared layout):

```html
<script async src="https://stats.example.com/i/xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx.js"></script>
<noscript><img src="https://stats.example.com/collect/pixel.gif?k=xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx&p=/" width="1" height="1" /></noscript>
```

The base URL is your `APP_URL`; the UUID is the site's public key.

### 3. Send custom events (optional)

```javascript
indiestats.track('signup');

indiestats.track('purchase', {
    plan: 'pro',
    price: 29.99,
    currency: 'EUR',
});
```

Events accept up to 20 properties (strings, numbers or booleans, stored as strings). Add a goal on the site's **Events** tab to follow an event over time.

### Local development

When `APP_ENV=local`, `localhost` and `127.0.0.1` are accepted as extra origins. Override them with `TRACKING_EXTRA_ALLOWED_HOSTS` (an empty value disables the default). If your pages are served over HTTPS, `APP_URL` must be HTTPS too, or the browser blocks the script as mixed content.

## What is collected

| Data | Source | Notes |
| --- | --- | --- |
| Visitor ID | Random UUID in `localStorage` | Per site and browser; no cookies |
| Session ID | Random UUID in `sessionStorage` | Per tab; stored for analysis, not shown as a filter |
| Path, page title, query string | The page | Search terms are read from the `q`, `query` or `s` parameters |
| Referrer and source | `document.referrer` at session start | Classified as `direct`, a known source (`google`, `reddit`, `twitter`…) or the referring host |
| UTM tags | Landing page URL | `utm_source`, `utm_medium`, `utm_campaign`, `utm_term`, `utm_content` |
| Browser language, timezone | The browser | `navigator.language` and the IANA timezone |
| Browser, version, OS, device, bot flag | User-Agent | Crawlers are stored with their name, e.g. `Googlebot` |
| Country | GeoLite2 lookup of the client IP | Optional; requires the GeoIP database |
| Network (ASN and organization) | DB-IP ASN Lite lookup of the client IP | Optional; requires the ASN database |
| IP address | The request | Stored with the pageview for GeoIP/ASN enrichment and removed with it by the retention job |
| Time on page | Sent when the tab is hidden or closed | Seconds, up to 24 hours |
| Outbound clicks | Clicks on links to other hosts | Target URL and page |

Raw pageviews, events and outbound clicks are deleted after `ANALYTICS_RETENTION_DAYS` (375 by default). Goals are kept.

## Using the dashboard

- **Dashboard** — all your sites with pageviews, unique visitors, a trend line and live visitor counts, plus a real-time panel across sites.
- **Ranges** — today (hourly), 7 days, 30 days, 3 months, 6 months or 1 year. Ranges and daily buckets follow the timezone in your **Settings**.
- **Site tabs** — Summary, Real-time, Content, Traffic, UTM, Technology, Geography, Visitor and Events. Tables are paginated, sortable and searchable on the server.
- **Filters** — open the **Filters** panel on any site. Every filter narrows all tabs and the export.

| Filter | Description |
| --- | --- |
| Source | Referrer source or host |
| Search term | On-site search query |
| Page / Page title / Query string | Path, `document.title` and landing query string |
| UTM source, medium, campaign, term, content | Campaign tags |
| Event | Visitors who triggered a custom event |
| Device / Browser / Browser version / Operating system | From the User-Agent |
| Browser language / Time zone | From the tracker |
| Visitor / Visitor type | A single visitor ID, or humans vs. bots |
| Country / Network | GeoIP country and ASN |

- **Export** — the **Export** button builds an XLSX file in the background for the selected range and filters, with one sheet per breakdown and headers in your language. A queue worker must be running; downloads expire after 7 days.

## Users and roles

| Role | Can do |
| --- | --- |
| **Admin** | See and manage every site, manage users, configure GeoIP/ASN databases |
| **Base** | See only the sites an admin assigns to them |

Every user picks their own language and timezone in **Settings**, and can enable two-factor authentication in **Account**. Guest pages such as the login follow the browser's language.

## Configuration

### Environment variables

| Variable | Description | Default |
| --- | --- | --- |
| `APP_URL` | **Required in production.** Public URL used by the tracker snippet | `http://localhost` |
| `APP_ENV` / `APP_DEBUG` | Use `production` / `false` in production | `local` / `true` |
| `DB_CONNECTION` | `sqlite`, `mysql`, `mariadb` or `pgsql` | `sqlite` |
| `QUEUE_CONNECTION` | Queue for Excel exports | `database` |
| `MAIL_*` | Mail transport for password reset and backup notifications | `log` |
| `ANALYTICS_RETENTION_DAYS` | Days of raw analytics to keep | `375` |
| `TRACKING_EXTRA_ALLOWED_HOSTS` | Extra origins accepted for every site (comma-separated) | `localhost,127.0.0.1` when `APP_ENV=local` |
| `GEOIP_MAXMIND_LICENSE_KEY` | MaxMind key for GeoLite2 downloads (overrides the key saved in Settings) | _empty_ |
| `GEOIP_DATABASE` | Absolute path to an existing `GeoLite2-Country.mmdb` | _auto_ |
| `GEOIP_ASN_DATABASE` | Absolute path to an existing DB-IP ASN Lite `.mmdb` | _auto_ |
| `BACKUP_DISK` | Filesystem disk for backups (`local`, `s3`…) | `s3` |
| `BACKUP_APP_NAME` | Folder name for backups | `indiestats-backup` |
| `BACKUP_MAIL_TO` | Recipient of backup failure notifications | `MAIL_FROM_ADDRESS` |
| `BACKUP_ARCHIVE_PASSWORD` | Optional password for backup archives | _empty_ |
| `BOOGLE_KEY` / `BOOGLE_PROJECT_KEY` | Optional exception reporting to [Boogle](https://boogle.app) (production only) | _empty_ |
| `SEED_FAKE_DATA` | Let `db:seed` create random bulk data | `false` |

### GeoIP (countries)

Countries come from MaxMind **GeoLite2-Country**, which needs a free license key.

1. Sign in as an admin and open **Settings**.
2. Create a free [MaxMind account](https://www.maxmind.com/en/geolite2/signup), generate a license key, paste it and save.
3. Click **Download or update database**. The file is saved to `storage/app/geoip/GeoLite2-Country.mmdb` and refreshed weekly by the scheduler.

Without the database, countries show as unknown and everything else keeps working. To fill in countries and networks for pageviews recorded before the databases were installed, run `php artisan analytics:enrich-geodata` (`--site=`, `--dry-run`).

### ASN (networks)

Networks come from the free **DB-IP ASN Lite** database (Creative Commons Attribution, no key needed). Click **Download or update ASN database** in **Settings**; the file is saved to `storage/app/geoip/dbip-asn-lite.mmdb` and refreshed monthly.

### Proxies and Cloudflare

When the `CF-Connecting-IP` header is present, IndieStats uses it as the client IP. Behind other reverse proxies or load balancers, configure Laravel's [trusted proxies](https://laravel.com/docs/13.x/requests#configuring-trusted-proxies) so `request()->ip()` returns the visitor's address.

## Scheduler, queue and backups

Add the Laravel scheduler to cron:

```cron
* * * * * cd /path-to-indiestats && php artisan schedule:run >> /dev/null 2>&1
```

| Command | Schedule | Purpose |
| --- | --- | --- |
| `backup:run` | Daily 00:00 | Back up the database, `.env` and `storage/app` to `BACKUP_DISK` |
| `backup:clean` | Daily 01:00 | Remove old backups |
| `analytics:prune` | Daily 02:00 | Delete raw analytics older than the retention period |
| `geoip:update` | Mondays 04:15 | Download GeoLite2-Country (when a license key is set) |
| `dbip-asn:update` | 3rd of the month, 04:30 | Download DB-IP ASN Lite |

All of them can be run by hand; `php artisan geoip:update --key=YOUR_KEY` downloads with a one-off key.

Backups use [spatie/laravel-backup](https://spatie.be/docs/laravel-backup). The default disk is `s3`: either configure the `AWS_*` variables or set `BACKUP_DISK=local`. Check them with `php artisan backup:list`.

Excel exports run on the queue. In production keep a worker running, for example with Supervisor:

```ini
[program:indiestats-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path-to-indiestats/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
numprocs=1
user=www-data
redirect_stderr=true
stdout_logfile=/path-to-indiestats/storage/logs/worker.log
```

## Production checklist

1. Set `APP_URL` to the final HTTPS URL, `APP_ENV=production` and `APP_DEBUG=false`.
2. Make sure `public/build/manifest.json` exists. Built assets are committed to the repository; if you change the frontend, run `npm ci && npm run build` on every deploy (or ship `public/build/` from CI).
3. Run `php artisan migrate --force`, then `php artisan config:cache`, `route:cache` and `view:cache`.
4. Configure the cron entry and a queue worker (see above).
5. Configure `MAIL_*` and the backup disk.
6. Set allowed domains on every site.
7. Behind a proxy, configure trusted proxies so GeoIP and ASN see the real visitor IP.
8. Optionally run `php artisan checkpoint:scan` for a security audit of the installation.

<details>
<summary>Troubleshooting: <code>Vite manifest not found</code></summary>

The release has no `public/build/manifest.json`. Run `npm ci && npm run build` inside that release after `composer install`, or upload `public/build/` from a machine or CI job that ran the build. Until the file exists, every page using `@vite` returns a 500 error.

</details>

## Deploying with Cipi

[Cipi](https://cipi.sh) is an open-source CLI for Ubuntu servers: LEMP stack, isolated apps, zero-downtime deploys, Let's Encrypt and Supervisor workers. IndieStats ships with the Cipi agent for webhook deploys and health checks.

```bash
# On the server (one time)
wget -O - https://cipi.sh/setup.sh | bash

# Create the app: domain, Git repository, branch and PHP version (8.4 or newer)
cipi app create

# Deploy and enable HTTPS
cipi deploy myapp
cipi ssl install myapp
```

Cipi's default pipeline does not include Node.js. Built assets are committed, so a plain deploy works; if you change the frontend, either build in CI and upload `public/build/`, or install Node on the server and add a task to `.deployer/deploy.php`:

```php
task('npm:build', function () {
    run('cd {{release_path}} && npm ci && npm run build');
});
after('deploy:vendors', 'npm:build');
```

Then add the scheduler cron for the app user. See the [Cipi documentation](https://cipi.sh) for details.

## Public endpoints

| Method | Path | Description | Rate limit |
| --- | --- | --- | --- |
| `GET` | `/i/{uuid}.js` | Tracker script for a site | 600/min |
| `POST` | `/collect/pageview` | Record a pageview | 300/min |
| `POST` | `/collect/duration` | Update time on page | 300/min |
| `POST` | `/collect/outbound` | Record an outbound click | 300/min |
| `POST` | `/collect/event` | Record a custom event | 300/min |
| `GET` | `/collect/pixel.gif` | `noscript` fallback (no JavaScript context such as title or timezone) | 300/min |
| `GET` | `/up` | Health check | — |

The `/collect/*` endpoints are CSRF-exempt and answer CORS requests from any origin; the site's allowed domains decide whether data is stored.

## Development

```bash
php artisan serve          # or Laravel Herd / Valet
php artisan queue:listen   # processes Excel exports
npm run dev                # Vite with hot reload
```

`composer run dev` starts only the PHP server.

```bash
php artisan test --compact   # test suite (PHPUnit)
composer run lint            # format with Laravel Pint
composer run test            # Pint check + tests, as in CI
```

GitHub Actions runs Pint and the test suite on PHP 8.4 and 8.5 for every push to `main` and every pull request.

**Translations.** Interface strings use Italian or descriptive keys with `__()`. App strings live in `lang/extensions/{locale}.json`, framework strings in `lang/{locale}.json` and `lang/{locale}/*.php`. `TranslationCoverageTest` fails when a key used in the code is missing for any of the five languages, so add every new string to all of them.

**Demo data and screenshots.** The screenshots in this README were taken from a sandbox seeded with `DemoDataSeeder` and refreshed with `DemoLiveTrafficSeeder` just before capture, with a headless browser at 1440 px (2× density) and 390 px for mobile.

## Tech stack

- **Backend:** Laravel 13, PHP 8.4+, Laravel Fortify (login, password reset, two-factor authentication)
- **Frontend:** Bootstrap 5, Chart.js, DataTables, Tom Select, jsVectorMap, Font Awesome, built with Vite
- **Data:** SQLite, MySQL/MariaDB or PostgreSQL; database queue by default
- **Enrichment:** MaxMind GeoLite2-Country and DB-IP ASN Lite (both optional)
- **Export:** PhpSpreadsheet (XLSX)
- **Operations:** spatie/laravel-backup, Cipi agent, Checkpoint security scanner

## License

IndieStats is open-source software released under the [MIT License](LICENSE).
