# task_unilimes

A take-home PHP test task (see [`Task.md`](Task.md) for the original brief):
import a CSV of customer records into MySQL, then browse and filter that data
and export it back out as CSV.

Plain procedural PHP on the backend (`mysqli`, no framework, no third party
library), a Bootstrap page with a plain JavaScript refresh, and a MySQL
database. The architecture is a script per endpoint: `app/index.php` serves
the page, `app/restData.php` the JSON listing, `app/importData.php` the
upload and `app/exportData.php` the export. The logic that the endpoints
share lives in `app/lib/`.

## Stack

- **PHP 8.5** (`php:8.5-apache`), `mysqli` extension, no Composer and no
  third party PHP library.
- **MySQL 8.4**, schema and empty table via `build/mysql/dump.sql`.
- **Frontend:** Bootstrap 5.3.0-alpha3 and jQuery 3.6.4 from a CDN. The
  pagination is written in the page, so the vendored `pagination.js` copy of
  the jQuery plugin is no longer needed.
- **Docker Compose** wires the two services together (`php` on `:8000`,
  `mysql` on `:9306`).

## Running it

```shell
mkdir -p var
cp .env.example var/.env
docker compose build
docker compose up -d
```

Then open `http://localhost:8000/index.php`.

`APP_PORT` in `var/.env` moves the application to another host port. The
credentials of the database live in `var/.env` and are read by the container
from the environment, so the same image runs locally and in a deployment
without a change in the source.

Every bind mount of the stack lives under `var/`, so the repository holds no
state of its own: `var/.env` is the local environment file and `var/mysql` is
the data of the database. Named volumes are not used.

## Endpoints

| Method | Path | What it does |
|---|---|---|
| GET | `/index.php` | The listing. The rows are rendered by the server, so the page works without JavaScript. |
| GET | `/restData.php` | The JSON listing: the rows, the categories, the age range, the total and the window. |
| POST | `/importData.php` | The import of a CSV file. The field `importSubmit` has to be part of the request. |
| GET | `/exportData.php` | The export of the filtered rows as a CSV file. |

The request and the response of every endpoint are described in
[`docs/api.md`](docs/api.md).

### Filters

Both the listing and the export take the same filters:

| Parameter | Effect |
|---|---|
| `id`, `category`, `firstname`, `lastname`, `email`, `gender`, `birthDate`, `created_at` | The value is matched against the column with `LIKE '%value%'`. |
| `age` | The age of a customer, computed by the database. `age=30` asks for one age, `age=25~35` asks for a range. |
| `page` | The page of the listing, starting at 1. |
| `limit` | The number of rows of a page, at most 500. |

A column name that is not on the list above is ignored, and a value is never
part of a SQL text: it is always a bound parameter.

### Import

The first line of the file names the columns, and the values are read by those
names, so the order of the columns does not matter:

```csv
category,firstname,lastname,email,gender,birthDate
books,Ada,Lovelace,ada@example.com,female,1815-12-10
```

A row whose email is already known is updated, a new one is inserted. A row
without an email, with a gender outside `female`/`male` or with a birth date
that is not `YYYY-MM-DD` is rejected and counted. The number of the imported
and the rejected rows is shown after the upload.

## Tests

Both gates run inside the project's own Docker images. Nothing has to be
installed on the machine.

```shell
docker compose --profile test run --rm test
```

The suite runs on a small runner of the project itself — no framework and no
third party library — and covers the filter builder and the CSV writer. Two
of the tests are the regression tests of the SQL injection that the earlier
version of this code had: they state that a value with a quote or with a
statement terminator never reaches the SQL text, and that it is always a bound
parameter.

The suite needs neither a database nor a network.

## CI/CD

`.github/workflows/ci.yml` runs the same gates on every push and every pull
request:

| Step | Command | Gate |
|---|---|---|
| Validate the compose file | `docker compose config --quiet` | ✅ |
| Build the test image | `docker compose build test` | ✅ |
| Test suite | `docker compose --profile test run --rm test` | ✅ |
| Build the application image | `docker compose build php` | ✅ |
| Build the database image | `docker compose build mysql` | ✅ |

The runner needs a Docker daemon and nothing else — no PHP toolchain is
installed on it. The workflow reads the repository only, never prints a
secret and never publishes anything, and `actions/checkout` is pinned to a
commit hash so the tag cannot move under the pipeline.

Two permanent branches exist: `master` (the released state) and `test` (the
integration state). A change is delivered as a pull request from a
`feature/*` branch into `test`, and from `test` into `master`.

## Security notes

- **Every query is prepared and every value is bound.** The earlier version
  of this code put the values of the request and of the uploaded file into
  the SQL text, which was an unauthenticated injection: a request for
  `firstname=x%' OR 1=1 -- -` returned every row of the table instead of the
  rows that match. The check is now a regression test of the suite.
- **The output is escaped.** The rows of the page are written with
  `htmlspecialchars`, and the JavaScript fills the cells with text nodes, so
  a value of the database never becomes markup.
- **The upload is checked by its content.** The type a browser sends is
  chosen by the client, so the extension and the first line of the file
  decide, not the header.
- **The credentials are read from the environment**, so they are not part of
  the source and not part of an image.
- **A notice of PHP never reaches the body of a response.** The runtime image
  writes the notices to the log of the container, so an export or a redirect
  cannot be broken by one.

## Known limitations

- **The listing page needs a database.** The page renders the rows on the
  server, so it cannot be served without one.
- **The import writes one row at a time.** A file with a hundred thousand
  rows takes a while; the rows are not written in one transaction.
- **The data of the test is not in the repository.** `build/mysql/dump.sql`
  creates the empty table only. `AUTO_INCREMENT=15776` in that file is a
  leftover of the export the dump was taken from.
- **The brief links a CSV on Google Drive.** That file is a third party
  asset and is not part of the repository.
- **No license file.**
