# IndieStats

Privacy-friendly, self-hosted web analytics built with [Laravel 13](https://laravel.com/docs/13.x). Each user manages **multiple sites**, each with a dedicated lightweight tracking snippet. No cookies, no consent banners required.

## Features

- **Pageview tracking** with localStorage-based visitor identification (no cookies)
- **Session-scoped ID** stored per browser tab (`sessionStorage`) for internal analytics — not exposed in the UI
- **Rich page context** on each pageview: `document.title`, full query string, browser language, IANA timezone, and on-site search terms (`q`, `query`, `s` URL params)
- **Bot detection** via User-Agent parsing (`is_bot` flag)
- **Time on page** measurement (duration sent on tab hide / page unload)
- **Outbound click tracking** on external links
- **Custom event tracking** with up to 20 key-value properties per event
- **UTM parameter support** (source, medium, campaign, term, content)
- **Referrer & search query detection** with automatic source classification (Google, Bing, social, etc.)
- **Device, browser, OS and country detection** (GeoIP via MaxMind)
- **Network (ASN) detection** via DB-IP ASN Lite — ISP / hosting provider per visit
- **Real-time analytics** on the dashboard and per-site (active visitors, live activity feed, charts)
- **Interactive country map** on the Geography tab
- **Noscript fallback** using a 1x1 tracking pixel
- **Goal management** to monitor specific custom events per site
- **Dashboard** with daily charts, date range selectors and advanced filters
- **Advanced analytics filters** (Tom Select with live search) applied consistently across summary, realtime, all detail tabs, DataTables AJAX, and Excel export
- **Multi-tab site analytics**: Summary, Real-time, Content, Traffic, UTM, Technology, Geography, Visitor, Events
- **Data export** to Excel (XLSX) in the user's language via background jobs
- **Automatic data pruning** with configurable retention period (default: 375 days)
- **Multi-site support** with domain allowlisting per site
- **Cloudflare-aware client IP** resolution for accurate GeoIP/ASN behind `CF-Connecting-IP`
- **Localized UI** (IT, EN, DE, FR, ES): labels, DataTables, country names, and export sheets follow the signed-in user's locale
- **Branded error pages** for HTTP 403, 404, and 429, consistent with the app design

## Screenshots

<p align="center">
  <img src="screenshots/is-dashboard.png" alt="IndieStats — dashboard with site overview and sparklines" width="920">
</p>
<p align="center"><em>Dashboard — traffic overview per site and date range</em></p>

<p align="center">
  <img src="screenshots/is-site.png" alt="IndieStats — site analytics summary" width="920">
</p>
<p align="center"><em>Site analytics — summary metrics and charts</em></p>

<p align="center">
  <img src="screenshots/is-site2.png" alt="IndieStats — site analytics detail" width="920">
</p>
<p align="center"><em>Site analytics — additional breakdowns</em></p>

<p align="center">
  <img src="screenshots/is-filters.png" alt="IndieStats — analytics filters" width="920">
</p>
<p align="center"><em>Filters — source, path, page title, UTM, device, country, ASN, visitor, and more</em></p>

<p align="center">
  <img src="screenshots/is-tables.png" alt="IndieStats — DataTables analytics" width="920">
</p>
<p align="center"><em>Detail tables — server-side paginated records with sorting</em></p>

<p align="center">
  <img src="screenshots/is-users.png" alt="IndieStats — user management" width="920">
</p>
<p align="center"><em>Users (admin) — manage accounts and roles</em></p>

<p align="center">
  <img src="screenshots/is-2fa.png" alt="IndieStats — two-factor authentication" width="920">
</p>
<p align="center"><em>Account — password and two-factor authentication</em></p>

## Recent updates

_Changes from the last 24 hours (June 2026)._

### Analytics & UI

- **Site detail redesign** — analytics split into dedicated tabs: **Summary**, **Real-time**, **Content**, **Traffic**, **UTM**, **Technology**, **Geography**, **Visitor**, and **Events** (replacing the old single “Detail” view).
- **Real-time tab** — active visitors, pageviews in the last 5 minutes, sparkline for the last 30 minutes, and a live activity feed with human-readable relative timestamps. Real-time counts on the **Dashboard** site cards respect active filters.
- **Geography tab** — choropleth **country map** plus the country breakdown table.
- **Visitor tab** — DataTables for **visitor ID** and **visitor type** (human vs bot/crawler).
- **Technology tab** — browser language, timezone, browser version, browser, OS, device, and **network (ASN)** tables.
- **Summary highlights** — top pages and top sources on the Summary tab; full breakdowns live in the other tabs.
- **Filters apply everywhere** — active filters affect summary metrics, charts, realtime, all DataTables (including AJAX POST), and Excel export (query string + request body merged server-side).

### Filters

New searchable filters (Tom Select, 3-column layout, dynamic options from your data):

| Filter | Description |
|--------|-------------|
| Source | Referrer / engine |
| Search term | On-site search query (`search_query`) |
| Page | Path |
| Page title | `document.title` at visit time |
| Query string | Full landing-page query string |
| UTM (5) | source, medium, campaign, term, content |
| Event | Custom event name (narrows visitors) |
| Device / Browser / Browser version / OS | From User-Agent |
| Browser language / Timezone | From the tracker script |
| Visitor | Persistent visitor ID |
| Visitor type | Human vs bot |
| Country | GeoIP country code |
| Network (ASN) | Autonomous System (ISP / host) |

**Removed from tracking and filters:** Google Ads (`gclid`), Facebook (`fbclid`), and Microsoft Ads (`msclkid`) click IDs.

### Tracking & enrichment

- **Session ID** — generated in `sessionStorage` per tab and stored on each pageview (for future analysis; not shown as a dashboard filter).
- **Visitor context fields** — page title, page query, browser language, timezone, search query, bot flag.
- **ASN lookup** — DB-IP ASN Lite (`.mmdb`), optional admin download in Settings, monthly scheduler.
- **Cloudflare** — when `CF-Connecting-IP` is present, it is used as the client IP for GeoIP and ASN.

### Export & i18n

- Excel export includes **visitor** and **visitor type** sheets; sheet titles and column headers follow the exporting user's locale.
- **40+ missing UI strings** added across IT, EN, DE, FR, ES (e.g. “Page title” / “Page titles” no longer leak Italian in English mode).
- Country labels in tables and export use the user's locale.

### Other

- **Authentication**: email verification is not required (Fortify flow disabled). `MAIL_*` is still used for password reset.
- **Demo seeding**: `DatabaseSeeder` creates an **admin** demo user (`admin@users.test` / `password`). Optional fake analytics with `SEED_FAKE_DATA=true`.
- **Branded error pages** for HTTP 403, 404, and 429.

## Requirements

| Software | Version |
|----------|---------|
| PHP | >= 8.3 |
| Composer | 2.x |
| Node.js | 20+ (recommended) |
| npm | 9+ |

Standard Laravel PHP extensions: `openssl`, `pdo`, `mbstring`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`.

Database: **SQLite** (default), MySQL or PostgreSQL.

## Installation

### Quick Setup

```bash
git clone https://github.com/your-username/indiestats.git
cd indiestats
composer run setup
```

The `setup` script runs: `composer install`, `npm install`, `npm run build`, creates `.env` from `.env.example`, generates the app key, and runs migrations.

### Manual Installation

```bash
composer install
npm install
npm run build
cp .env.example .env
php artisan key:generate
touch database/database.sqlite   # only if using SQLite and file doesn't exist
php artisan migrate
```

### Seed Demo Data (Optional)

Add `SEED_FAKE_DATA=true` to your `.env`, then:

```bash
php artisan db:seed
```

This creates a demo **admin** user (`admin@users.test`, password `password`) and, when `SEED_FAKE_DATA=true`, populates the database with sample sites, pageviews, outbound clicks, events and goals. If you run `FakeDataSeeder` alone (e.g. in tests), an admin user with that email is created when missing.

## Configuration

### Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `APP_URL` | **Required in production.** Public URL of your instance (e.g. `https://stats.example.com`). The tracker script uses this to load `/i/{uuid}.js` and call `/collect/...` endpoints. | `http://localhost` |
| `APP_ENV` / `APP_DEBUG` | Set to `production` / `false` in production | `local` / `true` |
| `DB_CONNECTION` | Database driver (`sqlite`, `mysql`, `pgsql`) | `sqlite` |
| `QUEUE_CONNECTION` | Queue driver for background jobs (exports) | `database` |
| `GEOIP_DATABASE` | Optional override: absolute path to GeoLite2-Country.mmdb (skips the default storage path) | _(auto)_ |
| `GEOIP_MAXMIND_LICENSE_KEY` | Optional MaxMind license key for `php artisan geoip:update` / scheduler (overrides the key stored in **Settings** when set) | _(empty)_ |
| `GEOIP_ASN_DATABASE` | Optional override: absolute path to DB-IP ASN Lite `.mmdb` | _(auto)_ |
| `ANALYTICS_RETENTION_DAYS` | Days to keep raw analytics data before pruning | `375` |
| `TRACKING_EXTRA_ALLOWED_HOSTS` | Comma-separated extra hosts allowed for tracking (useful for local dev) | `localhost,127.0.0.1` (local env) |
| `MAIL_*` | Mail configuration (password reset, etc.) | `log` (local) |

### GeoIP (Country Detection)

Country resolution uses the **GeoLite2-Country** database (`.mmdb`). IndieStats can download and install it automatically.

**Recommended (admin UI):**

1. Sign in as an **admin** user and open **Impostazioni** (Settings).
2. In the **GeoIP (country)** card, follow the short instructions: create a free [MaxMind](https://www.maxmind.com/en/geolite2/signup) account, generate a **license key**, paste it, save, then click **Download or update database**.
3. The file is stored at `storage/app/geoip/GeoLite2-Country.mmdb`. The scheduler runs `php artisan geoip:update` weekly (Mondays 04:15) if cron is configured.

**Optional environment overrides:**

- `GEOIP_MAXMIND_LICENSE_KEY` — use this key for downloads instead of the key saved in Settings (useful in production).
- `GEOIP_DATABASE` — point to a specific `.mmdb` file on disk (e.g. a manual install). When set and the file is readable, that path is used instead of the auto-download location.

If no database is available, country stats show as unknown — everything else works normally. The download step requires the `tar` command (standard on Linux and macOS).

### ASN (Network Detection)

Network resolution uses the free **DB-IP ASN Lite** database (`.mmdb`, Creative Commons Attribution). No API key is required.

**Recommended (admin UI):**

1. Sign in as an **admin** and open **Settings**.
2. In the **ASN (network)** card, click **Download or update ASN database**.
3. The file is stored at `storage/app/geoip/dbip-asn-lite.mmdb`. The scheduler runs `php artisan dbip-asn:update` monthly (3rd of each month at 04:30) if cron is configured.

**Optional:** set `GEOIP_ASN_DATABASE` in `.env` to point to a custom `.mmdb` file.

If ASN data is unavailable, network stats are omitted — pageviews and other metrics still work.

### Cloudflare & client IP

When requests include the `CF-Connecting-IP` header (typical behind Cloudflare), IndieStats uses it for GeoIP and ASN instead of the proxy IP. For other reverse proxies, configure Laravel **trusted proxies** so `request()->ip()` returns the real visitor address.

## Cron & Scheduled Commands

IndieStats uses Laravel's task scheduler for automated maintenance. Add this single cron entry to your server:

```cron
* * * * * cd /path-to-indiestats && php artisan schedule:run >> /dev/null 2>&1
```

### Scheduled Tasks

| Command | Schedule | Description |
|---------|----------|-------------|
| `analytics:prune` | Daily at 02:00 | Removes pageviews, tracking events, and outbound clicks older than the configured retention period (default: 375 days, ~1 year + 10 days margin) |
| `geoip:update` | Weekly (Mondays 04:15) | Downloads GeoLite2-Country from MaxMind when a license key is configured (Settings or `GEOIP_MAXMIND_LICENSE_KEY`) |
| `dbip-asn:update` | Monthly (3rd at 04:30) | Downloads DB-IP ASN Lite for network (ASN) resolution |

You can also run these manually:

```bash
php artisan analytics:prune
php artisan geoip:update
php artisan dbip-asn:update
```

Optional: `php artisan geoip:update --key=your_license_key` for a one-off download with a specific key.

**Note:** Goal definitions are never pruned — only raw pageview, event and outbound click records are removed.

## Queue Workers (Jobs)

IndieStats uses background jobs for generating Excel exports. You need a queue worker running to process them.

### Development

The dev server handles queue processing automatically:

```bash
composer run dev
```

### Production

Run a persistent queue worker:

```bash
php artisan queue:work --sleep=3 --tries=3
```

For reliability, use a process manager like **Supervisor**:

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

## Managing Sites

### Adding a Site

1. Log in to your IndieStats instance
2. Navigate to **Sites** (`/sites`)
3. Enter a **name** for your site (e.g., "My Blog") — this is just a label for you
4. Enter the **allowed domains** (comma-separated, e.g., `example.com, www.example.com`)
   - Only pages served from these domains can send tracking data (validated via `Origin` / `Referer` header)
   - In production, always set allowed domains to prevent unauthorized use of your site key
5. Click **Add Site**

### Installing the Tracking Script

After creating a site, copy the provided embed code and paste it before the closing `</body>` tag on your website:

```html
<script async src="https://your-indiestats-url/i/xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx.js"></script>
<noscript><img src="https://your-indiestats-url/collect/pixel.gif?k=xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx&p=/" width="1" height="1" /></noscript>
```

Replace `your-indiestats-url` with your actual `APP_URL`. The `.js` file is dynamically generated and contains the site's public key.

The script automatically tracks:
- **Pageviews** on every page load
- **Time on page** when the visitor leaves or switches tab
- **Outbound clicks** on links to external domains
- **UTM parameters** and **search queries** from the URL (`q`, `query`, `s` params)
- **Page context**: `document.title`, full query string, `navigator.language`, IANA timezone
- **Session ID** (per browser tab, in `sessionStorage` — stored server-side, not used as a dashboard filter)

Collected per pageview server-side from the User-Agent and client IP: **browser**, **browser version**, **OS**, **device type**, **country** (GeoIP), **ASN**, **bot flag**.

### Custom Event Tracking

From your website's JavaScript, you can send custom events:

```javascript
// Basic event
indiestats.track('signup');

// Event with properties (up to 20 key-value pairs)
indiestats.track('purchase', {
    plan: 'pro',
    price: '29.99',
    currency: 'USD'
});
```

Property values can be strings, numbers or booleans. They are normalized to strings server-side.

### Deleting a Site

Navigate to **Sites**, open the site detail, and use the delete option. This removes the site and all associated analytics data.

## Using the Dashboard

### Overview Dashboard

The main **Dashboard** (`/dashboard`) shows all your sites at a glance:

- Unique visitors and total pageviews per site for the selected period
- Sparkline charts showing daily traffic trends
- **Live visitor counts** on each site card (respects dashboard filters when set)
- Date range selector: today, 7 days, 30 days, 3 months, 6 months, 1 year

### Site Detail View

Click on any site to access detailed analytics. The view has a **filter panel** (accordion) and these tabs:

#### Summary
- Key metrics: unique visitors, pageviews, average time on page, outbound clicks, pages/visitor, outbound rate
- Daily (or hourly for “today”) pageview chart
- Top pages and top referrer sources

#### Real-time
- Active visitors now and pageviews in the last 5 minutes
- 30-minute activity chart
- Live feed of recent pageviews and events (auto-refreshes; respects filters)

#### Content
- Page titles, paths, and on-site search queries (server-side DataTables)

#### Traffic
- Referrer sources and outbound link destinations

#### UTM
- Breakdown tables for utm_source, utm_medium, utm_campaign, utm_term, utm_content

#### Technology
- Browser language, timezone, browser version, browser, OS, device, and **network (ASN)**

#### Geography
- **Country map** (choropleth) and country table

#### Visitor
- Tables by **visitor ID** and **visitor type** (human vs bot)

#### Events
- Configured goals, event name aggregates, and paginated event detail (payload JSON)
- Goal management: link a label to an event name and track counts over the period

All detail tables use server-side DataTables (sorting, search, pagination). UI language follows your account locale.

### Filtering

Open the **Filters** accordion on any site view. Filters use Tom Select with type-ahead search against your actual data. Once applied, they narrow **all** tabs — summary KPIs, charts, realtime, every DataTable, and Excel export.

Available dimensions are listed in [Recent updates → Filters](#recent-updates) above.

### Exporting Data

From the site detail view, click **Export** to generate an Excel (XLSX) file for the selected period and active filters. Sheets include pages, page titles, UTM dimensions, search queries, sources, technology breakdowns, countries, **visitors**, **visitor types**, outbound links, events, and goals. Headers and sheet names are translated to your account language. The export runs in the background — download when ready.

## Public Endpoints (Reference)

| Method | Path | Description |
|--------|------|-------------|
| `GET` | `/i/{uuid}.js` | Tracker script (dynamically generated) |
| `POST` | `/collect/pageview` | Record a pageview |
| `POST` | `/collect/duration` | Update page duration |
| `POST` | `/collect/outbound` | Record an outbound click |
| `POST` | `/collect/event` | Record a custom event |
| `GET` | `/collect/pixel.gif` | Noscript fallback (limited tracking) |

All `/collect/*` POST routes are CSRF-exempt with open CORS to allow cross-origin requests from tracked sites. Rate limiting is applied (300 requests/minute for collect endpoints, 600/minute for the tracker script).

## Production Checklist

1. Set `APP_URL` to your final HTTPS URL
2. Set `APP_ENV=production` and `APP_DEBUG=false`
3. **Build frontend assets** so `public/build/manifest.json` exists: from the project root run `npm ci && npm run build` (or copy a pre-built `public/build/` from CI). This is **required on every deploy** for any release that uses `@vite` in Blade — not only when JS/CSS sources change. Deploy scripts that only run `composer install` will **not** create this file and the app will error with `Vite manifest not found`.
4. Set `allowed_domains` for every site to prevent key misuse
5. Configure the **cron** for `schedule:run` (see Cron section above)
6. Start a **queue worker** with Supervisor (see Queue Workers section above)
7. Run `php artisan config:cache` and `php artisan route:cache` after deploy
8. If behind a proxy/load balancer, configure Laravel's **trusted proxies** so `request()->ip()` returns the real visitor IP (needed for GeoIP)
9. Configure `MAIL_*` for password reset and other mail

### Troubleshooting: `Vite manifest not found`

If logs show `ViteManifestNotFoundException` / `Vite manifest not found at .../public/build/manifest.json`, the current release directory has no Vite build output. Fix by running `npm ci && npm run build` **inside that release** (after `composer install`), or by uploading `public/build/` from a machine or CI job that ran the build. Until this file exists, any page that calls `@vite(...)` will return 500 for logged-in users.

## Deploying with Cipi

[Cipi](https://cipi.sh/) is an open-source CLI for Ubuntu VPS: LEMP stack, isolated apps, zero-downtime deploys, Let's Encrypt SSL, Supervisor workers and cron.

### Setup

```bash
# Install Cipi on your VPS (one-time)
wget -O - https://cipi.sh/setup.sh | bash

# Create an app
cipi app create
# Provide domain, git repo, branch, PHP version (>= 8.3)

# Deploy
cipi deploy myapp
```

### Frontend Build with Cipi

Cipi's default deploy pipeline does not include Node.js. You have two options:

**Option A — Install Node on the server** and add a custom task to `.deployer/deploy.php`:

```php
task('npm:build', function () {
    run('cd {{release_path}} && npm ci && npm run build');
});
after('deploy:vendors', 'npm:build');
```

**Option B — Build locally or in CI**, then copy `public/build/` to the server after deploy.

### SSL and Cron

```bash
cipi ssl install myapp
```

Add the Laravel scheduler cron for the app user as described in the Cron section above.

For more details, see the [Cipi documentation](https://cipi.sh/).

## Development

```bash
# Start development server (PHP server + queue worker + Vite)
composer run dev

# Code formatting
composer run lint

# Run tests
php artisan test --compact

# Build frontend
npm run build
```

## Tech Stack

- **Backend**: Laravel 13, PHP 8.3+
- **Frontend**: Bootstrap 5, Chart.js, DataTables, Tom Select, Font Awesome
- **Authentication**: Laravel Fortify (login, registration, password reset, optional two-factor authentication; email verification not enforced)
- **Database**: SQLite (default), MySQL/PostgreSQL supported
- **Queue**: Database driver (default), Redis supported
- **GeoIP**: MaxMind GeoLite2-Country (optional)
- **ASN**: DB-IP ASN Lite (optional)
- **Export**: PhpSpreadsheet (XLSX)

## License

IndieStats is released under the [MIT License](LICENSE).
