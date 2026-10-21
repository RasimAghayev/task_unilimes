<?php

declare(strict_types=1);

/**
 * Turns the query parameters of the listing into a safe SQL fragment.
 *
 * Two rules hold for everything this class returns:
 *
 * 1. A column name is only used when it is on the allow list. The name never
 *    comes from the request.
 * 2. A value is only ever returned as a bound parameter. The request never
 *    reaches the SQL text.
 *
 * The class needs no database, so its behaviour is covered by the test suite
 * on its own.
 */
class FilterBuilder
{
    /**
     * The columns the listing may be filtered by. The names are fixed here so
     * that a column name can never be taken from the request.
     */
    public const FILTERABLE_COLUMNS = [
        'id',
        'category',
        'firstname',
        'lastname',
        'email',
        'gender',
        'birthDate',
        'created_at',
    ];

    /** The number of rows the listing returns when the request does not say. */
    public const DEFAULT_LIMIT = 100;

    /** The largest number of rows a request may ask for. */
    public const MAX_LIMIT = 500;

    /**
     * Build the where clause, the bound parameters and the window of rows.
     *
     * @param array<string,mixed> $query The query parameters of the request.
     *
     * @return array{
     *     where: string,
     *     params: array<int,mixed>,
     *     limit: int,
     *     page: int,
     *     offset: int
     * }
     */
    public function build(array $query): array
    {
        $conditions = [];
        $params = [];

        foreach ($query as $key => $value) {
            if (! is_string($key) || ! is_scalar($value)) {
                continue;
            }

            $value = trim((string) $value);
            if ($value === '') {
                continue;
            }

            if (in_array($key, self::FILTERABLE_COLUMNS, true)) {
                $conditions[] = '`' . $key . '` LIKE ?';
                $params[] = '%' . $value . '%';

                continue;
            }

            if ($key === 'age') {
                $condition = $this->ageCondition($value, $params);
                if ($condition !== null) {
                    $conditions[] = $condition;
                }
            }
        }

        $limit = $this->limit($query);
        $page = $this->page($query);

        return [
            'where' => count($conditions) > 0 ? ' WHERE ' . implode(' AND ', $conditions) : '',
            'params' => $params,
            'limit' => $limit,
            'page' => $page,
            'offset' => ($page - 1) * $limit,
        ];
    }

    /**
     * The age of a customer is computed by the database, so the filter has to
     * compare against the computed column of the derived table.
     *
     * "30" asks for one age, "30~35" asks for a range. A range whose ends
     * are the wrong way round is read as a single age.
     *
     * @param array<int,mixed> $params
     */
    private function ageCondition(string $value, array &$params): ?string
    {
        if (preg_match('/^\d{1,3}$/', $value) === 1) {
            $params[] = (int) $value;

            return '`age` = ?';
        }

        if (preg_match('/^(\d{1,3})\s*~\s*(\d{1,3})$/', $value, $matches) !== 1) {
            return null;
        }

        $min = (int) $matches[1];
        $max = (int) $matches[2];

        if ($min > $max) {
            $params[] = $min;

            return '`age` = ?';
        }

        $params[] = $min;
        $params[] = $max;

        return '`age` BETWEEN ? AND ?';
    }

    /**
     * The number of rows the listing returns.
     *
     * @param array<string,mixed> $query
     */
    private function limit(array $query): int
    {
        if (! isset($query['limit']) || ! is_scalar($query['limit'])) {
            return self::DEFAULT_LIMIT;
        }

        $limit = (int) $query['limit'];

        if ($limit < 1) {
            return self::DEFAULT_LIMIT;
        }

        return min($limit, self::MAX_LIMIT);
    }

    /**
     * The page the listing starts at.
     *
     * @param array<string,mixed> $query
     */
    private function page(array $query): int
    {
        if (! isset($query['page']) || ! is_scalar($query['page'])) {
            return 1;
        }

        $page = (int) $query['page'];

        return $page < 1 ? 1 : $page;
    }
}
