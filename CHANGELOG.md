# Changelog

Notable changes to IndieStats. Dates are in YYYY-MM-DD format.

## Unreleased

### Security

- Updated `laravel/framework` to 13.35.0 (GHSA-jh5r-qr3c-85q8, XSS in the debug page) and `league/commonmark` to 2.10.3 (GHSA-97jj-33gv-5xf9 and GHSA-3q6v-r5mr-hxv8). `composer audit` reports no advisories.

### Fixed

- **Timezones in analytics.** Range boundaries were compared with UTC timestamps without conversion, so for a user in Europe/Rome "today" started at 02:00 and daily charts were shifted. Ranges and daily buckets now follow each user's timezone.
- **Referrer sources.** Sources were matched by substring: `reddit.com`, `microsoft.com`, `dropbox.com` and `netflix.com` were counted as `twitter`, and lookalike domains such as `notgoogle.com` as `google`. Sources are now matched on whole domain labels.
- **Internal navigation as a source.** Pageviews sent `document.referrer`, so a site's own domain became one of its top sources. Pageviews now carry the session's entry referrer, like events and outbound clicks already did. Data recorded earlier keeps its original values.
- **Localization.** Average time on page was always shown in Italian, and the pages-per-visitor and outbound-rate ratios used Italian decimal separators in every language. Several messages (site and goal deletion, 2FA setup cancellation, allowed-domain validation, ASN scheduler hint) and the 429 error page in German, French and Spanish were untranslated, user management fell back to English in those languages, and code examples on the Events tab and in the snippet panel used Italian identifiers.
- **Users page.** Last login was shown in each listed user's timezone instead of the viewer's.
- **Two-factor setup.** Confirming the password on the Account page redirected to the dashboard instead of returning to the 2FA setup.
- **Crawlers** were stored with the browser name `Mozilla`; they now keep the crawler name (for example `Googlebot`).
- **Sidebar.** The collapse button had no icon or styling.
- **Backups.** Failure notifications defaulted to `admin@newsletter.test` and "Newsletter Backup", and `backup:monitor` checked a different backup name and disk than `backup:run` wrote to.

### Added

- `DemoDataSeeder`: five realistic demo sites with a year of traffic (about 230,000 pageviews), campaigns, traffic spikes, goals and funnels, crawlers, a team with roles and live activity. `DemoLiveTrafficSeeder` refreshes the real-time views.
- GitHub Actions workflow running Pint and the test suite on PHP 8.4 and 8.5.
- Tests for the tracker script, referrer classification, timezone-aware ranges, decimal formatting, the demo seeders and translation coverage across all five languages.
- New README with a full tour of the app, and new screenshots for the README and the website.

### Changed

- The minimum PHP version is now 8.4, which the locked dependencies (Symfony 8) already required.
- `composer.json` now describes IndieStats instead of the Laravel starter kit.
- The test suite runs without a `.env` file.
- The Vite dependency cache (`.vite/`) is no longer tracked by Git.

## 2026-06-25

### Analytics and interface

- Site analytics split into dedicated tabs: Summary, Real-time, Content, Traffic, UTM, Technology, Geography, Visitor and Events (replacing the single "Detail" view).
- Real-time tab with active visitors, pageviews in the last five minutes, a 30-minute chart and a live activity feed. Real-time counts on the dashboard site cards respect active filters.
- Geography tab with a choropleth country map and the country table.
- Visitor tab with visitor IDs and visitor type (human or bot).
- Technology tab with browser language, timezone, browser version, browser, OS, device and network (ASN).
- Top pages and top sources on the Summary tab.
- Filters apply everywhere: summary metrics, charts, real-time, every table (including AJAX requests) and Excel export.

### Filters

New searchable filters with options loaded from your data: source, search term, page, page title, query string, the five UTM tags, event, device, browser, browser version, OS, browser language, timezone, visitor, visitor type, country and network (ASN). Google Ads (`gclid`), Facebook (`fbclid`) and Microsoft Ads (`msclkid`) click IDs were removed from tracking and filters.

### Tracking and enrichment

- Session ID generated per tab in `sessionStorage` and stored with each pageview.
- New visitor context fields: page title, page query, browser language, timezone, search query and bot flag.
- ASN lookup with DB-IP ASN Lite, with an admin download in Settings and a monthly scheduled update.
- `CF-Connecting-IP` is used as the client IP behind Cloudflare.

### Export and localization

- Excel export includes visitor and visitor type sheets; sheet titles and headers follow the exporting user's language.
- More than 40 missing interface strings added in Italian, English, German, French and Spanish.
- Country names in tables and exports follow the user's language.

### Other

- Email verification is no longer required; mail is still used for password reset.
- `DatabaseSeeder` creates an admin demo user (`admin@users.test` / `password`); random sample data with `SEED_FAKE_DATA=true`.
- Branded error pages for HTTP 403, 404 and 429.
