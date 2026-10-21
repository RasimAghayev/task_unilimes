<?php

declare(strict_types=1);

// The import of a CSV file. The rows are written with prepared statements,
// so a value of the file never becomes part of a SQL text.

include_once __DIR__ . '/db_connection.php';
require_once __DIR__ . '/lib/CustomerRepository.php';

/** The largest file the import accepts. */
const MAX_UPLOAD_BYTES = 20 * 1024 * 1024;

/** The columns of a file, in the order the brief names them. */
const COLUMNS = ['category', 'firstname', 'lastname', 'email', 'gender', 'birthDate'];

$status = 'err';
$imported = 0;
$rejected = 0;

if (isset($_POST['importSubmit'])) {
    $upload = $_FILES['file'] ?? null;

    if (! is_array($upload) || ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $status = 'invalid_file';
    } elseif (($upload['size'] ?? 0) > MAX_UPLOAD_BYTES) {
        $status = 'too_large';
    } else {
        $name = (string) ($upload['name'] ?? '');
        $extension = strtolower((string) pathinfo($name, PATHINFO_EXTENSION));

        // The type the browser sends is chosen by the client, so the
        // extension and the content of the file decide, not the header.
        if (! in_array($extension, ['csv', 'txt'], true)) {
            $status = 'invalid_file';
        } elseif (! is_uploaded_file((string) $upload['tmp_name'])) {
            $status = 'invalid_file';
        } else {
            $handle = fopen((string) $upload['tmp_name'], 'r');
            if ($handle === false) {
                $status = 'invalid_file';
            } else {
                // The escape parameter is given on purpose: PHP 8.5 warns
                // when it is left out, and the warning would be printed
                // before the headers of the redirect. The second parameter is
                // the length of a line, and null means "as long as the line".
                $header = fgetcsv($handle, null, ',', '"', '\\');
                $header = $header === false ? [] : array_map(
                    static fn ($value): string => strtolower(trim((string) $value)),
                    $header
                );

                if (! headerHasExpectedColumns($header)) {
                    $status = 'invalid_file';
                } else {
                    $repository = new CustomerRepository($db);
                    $positions = columnPositions($header);

                    while (($line = fgetcsv($handle, null, ',', '"', '\\')) !== false) {
                        if ($line === [null] || count($line) === 0) {
                            continue;
                        }

                        $row = [];
                        foreach (COLUMNS as $column) {
                            $index = $positions[$column];
                            $row[$column] = $index === null ? '' : (string) ($line[$index] ?? '');
                        }

                        if (! isValidRow($row)) {
                            $rejected++;
                            continue;
                        }

                        if ($repository->upsertCustomer($row)) {
                            $imported++;
                        } else {
                            $rejected++;
                        }
                    }

                    fclose($handle);
                    $status = 'succ';
                }
            }
        }
    }
}

// The status is a fixed value of this file, so it is safe to put into the
// query string of the redirect.
header('Location: index.php?status=' . urlencode($status) . '&imported=' . $imported . '&rejected=' . $rejected);

/**
 * Whether the first line of the file names the expected columns.
 *
 * The comparison ignores the case, because the name of a column of the file
 * does not have to be written the way the database writes it.
 *
 * @param array<int,string> $header The lower case names of the first line.
 */
function headerHasExpectedColumns(array $header): bool
{
    foreach (COLUMNS as $column) {
        if (! in_array(strtolower($column), $header, true)) {
            return false;
        }
    }

    return true;
}

/**
 * The position of every expected column in the first line of the file.
 *
 * The values are read by the name of the column, so a file whose columns are
 * in another order still lands in the right field.
 *
 * @param array<int,string> $header The lower case names of the first line.
 *
 * @return array<string,int|null>
 */
function columnPositions(array $header): array
{
    $positions = [];
    foreach (COLUMNS as $column) {
        $index = array_search(strtolower($column), $header, true);
        $positions[$column] = $index === false ? null : (int) $index;
    }

    return $positions;
}

/**
 * Whether a row of the file holds usable data.
 *
 * The gender is an enumeration in the database and the birth date is a date,
 * so both are checked before the row is written.
 *
 * @param array<string,mixed> $row
 */
function isValidRow(array $row): bool
{
    if (trim((string) $row['email']) === '') {
        return false;
    }

    $gender = strtolower(trim((string) $row['gender']));
    if ($gender !== '' && ! in_array($gender, ['female', 'male'], true)) {
        return false;
    }

    $birthDate = trim((string) $row['birthDate']);
    if ($birthDate !== '') {
        $parsed = DateTimeImmutable::createFromFormat('Y-m-d', $birthDate);
        if ($parsed === false || $parsed->format('Y-m-d') !== $birthDate) {
            return false;
        }
    }

    return true;
}
