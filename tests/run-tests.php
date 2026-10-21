<?php

declare(strict_types=1);

/**
 * The test suite of task_unilimes.
 *
 * The suite runs on the PHP test runner of the project itself: no framework
 * and no third party library. Every test states what it checks, and the
 * runner prints the result of every one of them.
 *
 * The classes under test need no database, so the suite runs without one and
 * without a network.
 */

require_once __DIR__ . '/../app/lib/FilterBuilder.php';
require_once __DIR__ . '/../app/lib/CsvWriter.php';

$failures = [];
$passed = 0;

/**
 * Run one test and report the result.
 */
function test(string $name, callable $body): void
{
    global $failures, $passed;

    try {
        $body();
        $passed++;
        echo "ok   - {$name}\n";
    } catch (Throwable $exception) {
        $failures[] = $name . ': ' . $exception->getMessage();
        echo "not ok - {$name}\n";
        echo '  ' . $exception->getMessage() . "\n";
    }
}

/**
 * Fail the running test when the condition does not hold.
 */
function assertTrue(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
}

function assertSame($expected, $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException(
            $message . ' (expected ' . var_export($expected, true)
            . ', got ' . var_export($actual, true) . ')'
        );
    }
}

// --- the filter builder -------------------------------------------------

$filters = new FilterBuilder();

test('a known column becomes a bound like comparison', function () use ($filters): void {
    $filter = $filters->build(['firstname' => 'Ada']);

    assertSame(' WHERE `firstname` LIKE ?', $filter['where'], 'The where clause is wrong.');
    assertSame(['%Ada%'], $filter['params'], 'The bound value is wrong.');
});

test('an unknown column is ignored', function () use ($filters): void {
    $filter = $filters->build(['id; DROP TABLE customer' => 'x', 'password' => 'y']);

    assertSame('', $filter['where'], 'An unknown column reached the where clause.');
    assertSame([], $filter['params'], 'An unknown column reached the parameters.');
});

test('a value with a quote never reaches the sql text', function () use ($filters): void {
    $payload = "x%' OR 1=1 -- -";
    $filter = $filters->build(['firstname' => $payload]);

    assertTrue(
        strpos($filter['where'], $payload) === false,
        'The value of the request reached the SQL text.'
    );
    assertSame(['%' . $payload . '%'], $filter['params'], 'The value is not bound.');
    assertTrue(
        substr_count($filter['where'], '?') === 1,
        'The number of placeholders does not match the number of values.'
    );
});

test('a value with a semicolon never reaches the sql text', function () use ($filters): void {
    $payload = "'; DELETE FROM customer; --";
    $filter = $filters->build(['email' => $payload]);

    assertTrue(
        strpos($filter['where'], 'DELETE') === false,
        'The value of the request reached the SQL text.'
    );
    assertSame(['%' . $payload . '%'], $filter['params'], 'The value is not bound.');
});

test('a single age becomes an equality', function () use ($filters): void {
    $filter = $filters->build(['age' => '30']);

    assertSame(' WHERE `age` = ?', $filter['where'], 'The where clause is wrong.');
    assertSame([30], $filter['params'], 'The bound value is wrong.');
});

test('an age range becomes a between', function () use ($filters): void {
    $filter = $filters->build(['age' => '25~30']);

    assertSame(' WHERE `age` BETWEEN ? AND ?', $filter['where'], 'The where clause is wrong.');
    assertSame([25, 30], $filter['params'], 'The bound values are wrong.');
});

test('a reversed age range becomes a single age', function () use ($filters): void {
    $filter = $filters->build(['age' => '30~25']);

    assertSame(' WHERE `age` = ?', $filter['where'], 'The where clause is wrong.');
    assertSame([30], $filter['params'], 'The bound value is wrong.');
});

test('an age that is not a number is ignored', function () use ($filters): void {
    $filter = $filters->build(['age' => "30' OR 1=1 --"]);

    assertSame('', $filter['where'], 'A broken age reached the where clause.');
    assertSame([], $filter['params'], 'A broken age reached the parameters.');
});

test('several filters are combined', function () use ($filters): void {
    $filter = $filters->build(['gender' => 'female', 'age' => '20~30']);

    assertSame(
        ' WHERE `gender` LIKE ? AND `age` BETWEEN ? AND ?',
        $filter['where'],
        'The where clause is wrong.'
    );
    assertSame(['%female%', 20, 30], $filter['params'], 'The bound values are wrong.');
});

test('an empty value is ignored', function () use ($filters): void {
    $filter = $filters->build(['firstname' => '', 'gender' => '   ']);

    assertSame('', $filter['where'], 'An empty value reached the where clause.');
});

test('the number of rows falls back to the default', function () use ($filters): void {
    assertSame(FilterBuilder::DEFAULT_LIMIT, $filters->build([])['limit'], 'The default is wrong.');
    assertSame(FilterBuilder::DEFAULT_LIMIT, $filters->build(['limit' => '0'])['limit'], 'Zero is not handled.');
    assertSame(FilterBuilder::DEFAULT_LIMIT, $filters->build(['limit' => 'abc'])['limit'], 'Text is not handled.');
    assertSame(FilterBuilder::MAX_LIMIT, $filters->build(['limit' => '10000'])['limit'], 'The upper bound is not held.');
    assertSame(25, $filters->build(['limit' => '25'])['limit'], 'A valid value is not taken.');
});

test('the page and the offset follow the limit', function () use ($filters): void {
    $filter = $filters->build(['limit' => '20', 'page' => '3']);

    assertSame(3, $filter['page'], 'The page is wrong.');
    assertSame(40, $filter['offset'], 'The offset is wrong.');

    $first = $filters->build(['limit' => '20', 'page' => '0']);
    assertSame(1, $first['page'], 'A page below one is not handled.');
    assertSame(0, $first['offset'], 'The offset of the first page is wrong.');
});

// --- the csv writer -----------------------------------------------------

$writer = new CsvWriter();

test('the export starts with the names of the columns', function () use ($writer): void {
    $csv = $writer->write([]);

    assertSame(
        'category,firstname,lastname,email,gender,birthDate,age' . "\n",
        $csv,
        'The header of the file is wrong.'
    );
});

test('a row is written in the order of the columns', function () use ($writer): void {
    $csv = $writer->write([
        [
            'category' => 'books',
            'firstname' => 'Ada',
            'lastname' => 'Lovelace',
            'email' => 'ada@example.com',
            'gender' => 'female',
            'birthDate' => '1815-12-10',
            'age' => 210,
        ],
    ]);

    $lines = explode("\n", trim($csv));
    assertSame(2, count($lines), 'The file does not hold the header and the row.');
    assertSame(
        'books,Ada,Lovelace,ada@example.com,female,1815-12-10,210',
        $lines[1],
        'The row is wrong.'
    );
});

test('a value with a comma is quoted', function () use ($writer): void {
    $csv = $writer->write([['firstname' => 'Ada, Countess of Lovelace']]);
    $lines = explode("\n", trim($csv));

    assertTrue(
        strpos($lines[1], '"Ada, Countess of Lovelace"') !== false,
        'The value was not quoted.'
    );
});

test('a value with a quote is quoted', function () use ($writer): void {
    $csv = $writer->write([['firstname' => 'Ada "Countess" Lovelace']]);
    $lines = explode("\n", trim($csv));

    assertTrue(
        strpos($lines[1], '"Ada ""Countess"" Lovelace"') !== false,
        'The value was not quoted.'
    );
});

test('a missing value becomes an empty field', function () use ($writer): void {
    $csv = $writer->write([['category' => 'books']]);
    $lines = explode("\n", trim($csv));

    assertSame(
        'books,,,,,,',
        $lines[1],
        'The empty fields are wrong.'
    );
});

// --- the result ---------------------------------------------------------

echo "\n";
echo 'tests: ' . ($passed + count($failures)) . ', pass: ' . $passed . ', fail: ' . count($failures) . "\n";

if (count($failures) > 0) {
    echo "\n";
    foreach ($failures as $failure) {
        echo 'FAILED: ' . $failure . "\n";
    }
    exit(1);
}

exit(0);
