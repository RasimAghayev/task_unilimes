<?php

declare(strict_types=1);

require_once __DIR__ . '/FilterBuilder.php';

/**
 * Reads and writes the customer table.
 *
 * Every statement is prepared and every value is bound, so the data of a
 * request never becomes part of a SQL text.
 */
class CustomerRepository
{
    /** @var mysqli */
    private $db;

    /** @var FilterBuilder */
    private $filters;

    public function __construct(mysqli $db, ?FilterBuilder $filters = null)
    {
        $this->db = $db;
        $this->filters = $filters ?? new FilterBuilder();
    }

    /**
     * The rows of the listing that match the request.
     *
     * @param array<string,mixed> $query
     *
     * @return array{rows: array<int,array<string,mixed>>, total: int, page: int, limit: int}
     */
    public function listCustomers(array $query): array
    {
        $filter = $this->filters->build($query);

        $countStatement = $this->db->prepare(
            'SELECT COUNT(*) FROM ('
            . 'SELECT *, TIMESTAMPDIFF(YEAR, str_to_date(birthDate, "%Y-%m-%d"), CURRENT_DATE) AS age'
            . ' FROM customer'
            . ') customer_list' . $filter['where']
        );
        $this->bind($countStatement, $filter['params']);
        $countStatement->execute();
        $countRow = $countStatement->get_result()->fetch_assoc();
        $countStatement->close();
        $total = $countRow === null ? 0 : (int) $countRow['COUNT(*)'];

        $rows = [];
        if ($total > 0) {
            $statement = $this->db->prepare(
                'SELECT * FROM ('
                . 'SELECT *, TIMESTAMPDIFF(YEAR, str_to_date(birthDate, "%Y-%m-%d"), CURRENT_DATE) AS age'
                . ' FROM customer'
                . ') customer_list' . $filter['where']
                . ' ORDER BY id DESC LIMIT ? OFFSET ?'
            );
            $params = $filter['params'];
            $params[] = $filter['limit'];
            $params[] = $filter['offset'];
            $this->bind($statement, $params);
            $statement->execute();
            $result = $statement->get_result();
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
            $statement->close();
        }

        return [
            'rows' => $rows,
            'total' => $total,
            'page' => $filter['page'],
            'limit' => $filter['limit'],
        ];
    }

    /**
     * The distinct categories of the customers.
     *
     * @return array<int,string>
     */
    public function categories(): array
    {
        $result = $this->db->query('SELECT DISTINCT category FROM customer ORDER BY category');
        $categories = [];
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row['category'];
        }
        $result->free();

        return $categories;
    }

    /**
     * The youngest and the oldest customer of the table.
     *
     * @return array{minAge: int|null, maxAge: int|null}
     */
    public function ageRange(): array
    {
        $result = $this->db->query(
            'SELECT MIN(TIMESTAMPDIFF(YEAR, str_to_date(birthDate, "%Y-%m-%d"), CURRENT_DATE)) AS minAge,'
            . ' MAX(TIMESTAMPDIFF(YEAR, str_to_date(birthDate, "%Y-%m-%d"), CURRENT_DATE)) AS maxAge'
            . ' FROM customer'
        );
        $row = $result->fetch_assoc();
        $result->free();

        return [
            'minAge' => $row === null || $row['minAge'] === null ? null : (int) $row['minAge'],
            'maxAge' => $row === null || $row['maxAge'] === null ? null : (int) $row['maxAge'],
        ];
    }

    /**
     * Store one row of an imported file.
     *
     * A row whose email is already known is updated, a new one is inserted.
     *
     * @param array<string,mixed> $row
     *
     * @return bool True when the row was written.
     */
    public function upsertCustomer(array $row): bool
    {
        $email = (string) ($row['email'] ?? '');
        if ($email === '') {
            return false;
        }

        $existing = $this->db->prepare('SELECT id FROM customer WHERE email = ? LIMIT 1');
        $existing->bind_param('s', $email);
        $existing->execute();
        $found = $existing->get_result()->fetch_assoc();
        $existing->close();

        $category = (string) ($row['category'] ?? '');
        $firstname = (string) ($row['firstname'] ?? '');
        $lastname = (string) ($row['lastname'] ?? '');
        $gender = (string) ($row['gender'] ?? '');
        $birthDate = (string) ($row['birthDate'] ?? '');

        if ($found !== null) {
            $statement = $this->db->prepare(
                'UPDATE customer SET category = ?, firstname = ?, lastname = ?, email = ?,'
                . ' gender = ?, birthDate = ?, updated_at = NOW() WHERE email = ?'
            );
            $statement->bind_param('sssssss', $category, $firstname, $lastname, $email, $gender, $birthDate, $email);
        } else {
            $statement = $this->db->prepare(
                'INSERT INTO customer (category, firstname, lastname, email, gender, birthDate,'
                . ' created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())'
            );
            $statement->bind_param('ssssss', $category, $firstname, $lastname, $email, $gender, $birthDate);
        }

        $statement->execute();
        $ok = $statement->errno === 0;
        $statement->close();

        return $ok;
    }

    /**
     * Bind a list of values to a prepared statement.
     *
     * The values arrive as strings, as numbers or as null, so the types are
     * worked out here instead of at the call site. The list is bound in one
     * call, because mysqli binds the variables to the placeholders in the
     * order they are given.
     *
     * @param array<int,mixed> $params
     */
    private function bind(mysqli_stmt $statement, array $params): void
    {
        if (count($params) === 0) {
            return;
        }

        $types = '';
        $values = [];

        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
                $values[] = $param;
                continue;
            }
            if (is_float($param)) {
                $types .= 'd';
                $values[] = $param;
                continue;
            }
            if ($param === null) {
                $types .= 's';
                $values[] = null;
                continue;
            }
            $types .= 's';
            $values[] = (string) $param;
        }

        $references = [];
        foreach ($values as $key => $value) {
            $references[$key] = &$values[$key];
        }

        $statement->bind_param($types, ...$references);
    }
}
