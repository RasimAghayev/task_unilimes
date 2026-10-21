<?php

declare(strict_types=1);

/**
 * Writes rows of customers as CSV.
 *
 * The writer is pure: it turns a list of rows into a text, so the test suite
 * can cover it without a database and without a request.
 */
class CsvWriter
{
    /**
     * The columns of an export, in the order they are written.
     */
    public const COLUMNS = [
        'category',
        'firstname',
        'lastname',
        'email',
        'gender',
        'birthDate',
        'age',
    ];

    /**
     * The rows as a CSV text.
     *
     * The first line holds the names of the columns, so the file can be read
     * back by a spreadsheet without a manual step.
     *
     * @param array<int,array<string,mixed>> $rows
     */
    public function write(array $rows): string
    {
        $handle = fopen('php://temp', 'r+');
        if ($handle === false) {
            return '';
        }

        // The escape parameter is given on purpose: PHP 8.5 warns when it is
        // left out, and the warning would be printed before the headers of
        // the export.
        fputcsv($handle, self::COLUMNS, ',', '"', '\\');

        foreach ($rows as $row) {
            $line = [];
            foreach (self::COLUMNS as $column) {
                $value = $row[$column] ?? '';
                $line[] = $value === null ? '' : (string) $value;
            }
            fputcsv($handle, $line, ',', '"', '\\');
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv === false ? '' : $csv;
    }
}
