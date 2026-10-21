<?php

declare(strict_types=1);

// The CSV export of the customers. It uses the same filters as the listing,
// so what is exported is what the listing shows.

include_once __DIR__ . '/db_connection.php';
require_once __DIR__ . '/lib/CustomerRepository.php';
require_once __DIR__ . '/lib/CsvWriter.php';

$repository = new CustomerRepository($db);
$listing = $repository->listCustomers($_GET);

$writer = new CsvWriter();
$csv = $writer->write($listing['rows']);

$fileName = 'customers-' . date('Ymd-His') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('X-Content-Type-Options: nosniff');
header('Content-Length: ' . strlen($csv));

echo $csv;
