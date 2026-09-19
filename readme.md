# task_unilimes

A take-home PHP test task (see [`Task.md`](Task.md) for the original brief):
import a CSV of customer records into MySQL, then browse/filter that data
and export it back out as CSV. Delivered as a Docker Compose stack — plain
procedural PHP (`mysqli`) on the backend, vanilla JS + jQuery + the
[paginationjs](https://github.com/superRaytin/paginationjs) plugin on the
frontend, Bootstrap for styling. No framework, no MVC layer — each `.php`
file in `app/` is a self-contained script handling one route directly; that
flat script-per-endpoint style is the actual architecture here, not a
DDD/CQRS/clean-architecture design.

## Stack

- **PHP 8.2** (`php:8.2-apache`), `mysqli` extension, no Composer/no
  third-party PHP libraries.
- **MySQL** (`mysql:latest`), schema + empty table via `build/mysql/dump.sql`.
- **Frontend:** vanilla JS, jQuery 3.6.4 (CDN), Bootstrap 5.3.0-alpha3 (CDN),
  paginationjs 2.0.5 (vendored in `app/pagination.js`).
- **Docker Compose** wires the two services together (`php` on `:8000`,
  `mysql` on `:9306`).

## Running it

Verified end-to-end by actually building and running the stack, not just
reading the config:

```
docker compose build
docker compose up -d
```

Then open `http://localhost:8000/index.php`. The `mysql`/`user`/`pass`
credentials in `docker-compose.yml`/`app/db_connection.php` are local to
this Compose network only (no external service behind them) — not a
credential-exposure finding, same treatment as this portfolio's other
Docker-only repos.

`build/mysql/dump.sql` only creates the (empty) `customer` table — no seed
rows are included, so a fresh `docker compose up` starts with an empty
listing (`AUTO_INCREMENT=15776` is a leftover from wherever this dump was
originally exported, not real seed data).

## Task.md requirements vs. what's actually implemented

Checked against the real source and a live run, not assumed from the brief:

1. **Public repo** — done (this repo).
2. **Runs in Docker** — done, verified via a real `docker compose build` +
   `up` + HTTP request cycle.
3. **README** — this file (previously 0 bytes).
4. **CSV dataset** — the brief links a Google Drive CSV; not fetched here
   (third-party asset, out of scope for this doc pass). The DB schema
   matches the brief's column list exactly (`category`, `firstname`,
   `lastname`, `email`, `gender`, `birthDate`), plus `id`/`file_id`/
   `created_at`/`updated_at`.
5. **Read CSV without third-party libraries** — done; `app/importData.php`
   uses PHP's built-in `fgetcsv()`, nothing else.
6. **Write data to the database** — verified with a real upload: rows
   land in `customer` via `INSERT`, with email-match rows going through
   `UPDATE` instead (dedup-by-email upsert).
7. **Display data with pagination (or a JSON API)** — the JSON API
   (`app/restData.php`) works and was verified live (filtering, age
   calculation via `TIMESTAMPDIFF`, category list, min/max age all
   correct). The server-rendered table in `index.php` is separate and only
   ever shows the last 10 rows, unfiltered — see the pagination-wiring bug
   below for why the two don't actually connect in the browser.
8. **Filters** (category, gender, date of birth, age, age range) — done in
   `restData.php`: any `$_GET` key matching a real column name becomes a
   `LIKE '%value%'` clause, and `age=25~35`-style range syntax is handled
   specially. Verified live with `gender=`, `category=`, and `age=25~35`
   filters, each returning the correct subset.
9. **CSV export filtered by the above** — **not implemented**. No
   export/download endpoint exists anywhere in this codebase.

## Findings (disclosed, not fixed — documentation-only pass)

- **SQL injection, both directions.** Neither `importData.php` nor
  `restData.php` escapes values or uses prepared statements — every query
  is raw string concatenation of `$_POST`/CSV-row/`$_GET` data straight
  into SQL. Verified live and safely (read-only probe, no data damage):
  `GET /restData.php?firstname=x%25%27%20OR%201=1%20--%20-` returned every
  row in the table instead of the (empty) set a literal `firstname LIKE
  '%x%'` match should give — a working, unauthenticated SQLi bypass of the
  filter logic. `importData.php`'s `htmlspecialchars()` on `firstname`/
  `lastname` guards against XSS on output; it does nothing for SQL
  injection on the same fields, and `category`/`email`/`gender`/
  `birthDate` get no treatment at all.
- **Pagination widget targets the wrong element.** `index.php`'s inline
  script does `container.prev().html(dataHtml)` where `container` is
  `#pagination-table`; that div's previous sibling is the *Import button*
  container, not the data table — so even though `restData.php` itself
  returns correct, filtered JSON, the AJAX-rendered rows would render into
  the wrong part of the page in a real browser, not into the visible
  table.
- **Copy-paste leftover in the table footer.** The footer's gender
  `<select>` still has Bootstrap-example placeholder values
  (`value="volvo"`/`value="saab"`) instead of `female`/`male` — inherited
  from whatever snippet this was copied from, never updated to match the
  matching `<thead>` select right above it.
- No license file; `.gitignore` is present but empty.
- One open PR: `renovate/php-8.x` → `php:8.5-apache` (opened 2023-11-27,
  still unmerged). GitHub's own "last pushed 2025-11-21" banner reflects
  that branch's most recent force-push, not `master` — `master`'s real
  last commit (2023-11-03) is the *earlier* Renovate PR (#1, merged,
  `php:8.2-apache`) landing. Same misleading-push-date pattern as the rest
  of this portfolio. No Dependabot/vulnerability alerts are reported for
  this repo, but that's because there's no `composer.json`/lockfile for
  GitHub's scanners to check against — it doesn't mean the code is clean
  (see the SQL injection finding above).
