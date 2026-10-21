# The endpoints of csv-import-api

Every endpoint of the application, with its request, its response and the
rules that hold for it.

## The shared filter

The listing and the export take the same filters. A filter is a query
parameter:

| Parameter | Type | Rule |
|---|---|---|
| `id` | integer | Matched against the column with `LIKE '%value%'`. |
| `category` | string | Matched against the column with `LIKE '%value%'`. |
| `firstname` | string | Matched against the column with `LIKE '%value%'`. |
| `lastname` | string | Matched against the column with `LIKE '%value%'`. |
| `email` | string | Matched against the column with `LIKE '%value%'`. |
| `gender` | string | Matched against the column with `LIKE '%value%'`. The column itself is an enumeration of `female` and `male`. |
| `birthDate` | string | Matched against the column with `LIKE '%value%'`. The column itself is a date. |
| `created_at` | string | Matched against the column with `LIKE '%value%'`. |
| `age` | integer or range | The age is computed by the database: `age=30` asks for one age, `age=25~35` asks for a range. A range whose ends are the wrong way round is read as a single age. A value that is not a number is ignored. |
| `page` | integer | The page of the listing, starting at 1. A value below 1 is read as 1. |
| `limit` | integer | The number of rows of a page. The default is 100, the largest value is 500. |

Two rules hold for all of them:

1. A parameter that is not on this list is ignored. The name of a column
   never comes from the request.
2. A value is never part of a SQL text. It is always a bound parameter.

## `GET /index.php`

The listing page.

**Request:** the shared filter, plus `status`, `imported` and `rejected` for
the message after an import.

**Response:** `text/html`. The rows of the page are rendered by the server
from the same filters the JSON listing uses, so the page works without
JavaScript. With JavaScript the table is refreshed in place when a filter
changes, and the pagination and the export link follow the filters.

## `GET /restData.php`

The JSON listing.

**Request:** the shared filter.

**Response:** `application/json`

```json
{
  "category": ["books", "music"],
  "ageMinMax": { "minAge": 26, "maxAge": 210 },
  "data": [
    {
      "id": 15776,
      "file_id": null,
      "category": "books",
      "firstname": "Ada",
      "lastname": "Lovelace",
      "email": "ada@example.com",
      "gender": "female",
      "birthDate": "1815-12-10",
      "created_at": "2026-10-21 04:39:00",
      "updated_at": "2026-10-21 04:39:00",
      "age": 210
    }
  ],
  "total": 1,
  "page": 1,
  "limit": 100
}
```

| Field | Meaning |
|---|---|
| `category` | The distinct categories of the table. |
| `ageMinMax` | The youngest and the oldest customer of the table. `null` when the table is empty. |
| `data` | The rows of the requested page. The `age` of a row is computed by the database. |
| `total` | The number of rows that match the filter, not the number of rows of the page. |
| `page` | The page that was returned. |
| `limit` | The number of rows of a page. |

## `POST /importData.php`

The import of a CSV file.

**Request:** `multipart/form-data` with the field `file` and the field
`importSubmit`.

The first line of the file names the columns. The values are read by those
names, so the order of the columns does not matter, and the case of a name
does not matter either.

```csv
category,firstname,lastname,email,gender,birthDate
books,Ada,Lovelace,ada@example.com,female,1815-12-10
```

**Rules:**

- The file has to have the extension `csv` or `txt`. The type the browser
  sends is chosen by the client, so it does not decide.
- The file has to be smaller than 20 MB.
- A row without an email, with a gender outside `female` and `male`, or with
  a birth date that is not `YYYY-MM-DD` is rejected.
- A row whose email is already known is updated, a new one is inserted.

**Response:** `302` to `index.php` with `status`, `imported` and `rejected`.

| `status` | Meaning |
|---|---|
| `succ` | The file was read. `imported` and `rejected` hold the number of rows. |
| `invalid_file` | The file is not a CSV file with the expected columns. |
| `too_large` | The file is larger than the import accepts. |
| `err` | The upload itself failed. |

## `GET /exportData.php`

The export of the filtered rows as a CSV file.

**Request:** the shared filter. The `page` and the `limit` are ignored, the
export holds every row that matches the filter.

**Response:** `text/csv` with the header

```text
Content-Disposition: attachment; filename="customers-20261021-043900.csv"
```

The first line of the file holds the names of the columns:

```csv
category,firstname,lastname,email,gender,birthDate,age
```

## The health of the application

The `php` service of the compose stack has a health check that opens the
port. There is no endpoint of the application that reports the state of the
database; the health check of the `mysql` service does that with
`mysqladmin ping`.
