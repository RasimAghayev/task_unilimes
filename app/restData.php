<?php

declare(strict_types=1);

// The JSON listing of the customers. Every value of the request is bound to
// a prepared statement, so the request never becomes part of a SQL text.

include_once __DIR__ . '/db_connection.php';
require_once __DIR__ . '/lib/CustomerRepository.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

$repository = new CustomerRepository($db);
$listing = $repository->listCustomers($_GET);

echo json_encode(
    [
        'category' => $repository->categories(),
        'ageMinMax' => $repository->ageRange(),
        'data' => $listing['rows'],
        'total' => $listing['total'],
        'page' => $listing['page'],
        'limit' => $listing['limit'],
    ],
    JSON_UNESCAPED_UNICODE
);
