<?php

namespace Webike\Searchable\Search;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Webike\Searchable\BaseSearchQuery;

/**
 * A search query resembling the behaviour in sublime file search (ctrl+p).
 */
class SublimeSearch extends BaseSearchQuery
{
    /**
     * Columns for sorting query.
     *
     * @var array
     */
    protected $columns = [];

    /**
     * Search string.
     * This is set everytime search() is called.
     *
     * @var string
     */
    protected $searchStr;

    /**
     * Construct.
     *
     * @param Builder $query
     * @param array $columns
     * @param bool $sort
     * @param string $searchOperator
     */
    public function __construct($query, $columns = [], $sort = true, $searchOperator = 'where')
    {
        $this->query = $query;
        $this->columns = $columns;
        $this->sort = $sort;
        $this->searchOperator = $searchOperator;
    }

    /**
     * Get the actual searchable column of the given column key.
     *
     * @param string $columnKey
     * @return string|mixed
     */
    public function getColumn($columnKey): string
    {
        return $this->findColumn($this->columnsToCompare(), $columnKey);
    }

    /**
     * Return the searchable columns to compare, actual columns for `where` operator and alias column names for `having` operator.
     *
     * @return array
     */
    public function columnsToCompare(): array
    {
        return $this->searchOperator === 'having' ? $this->columnKeys() : $this->columns();
    }

    /**
     * Get the keys of columns to be used in the query result.
     *
     * @return array
     */
    public function columnKeys(): array
    {
        $columnKeys = [];

        foreach ($this->columns() as $key => $column) {
            if (is_string($key)) {
                $columnKeys[] = $key;
            } elseif (str_contains($column, '.')) {
                list($table, $columnKey) = explode('.', $column);
                $columnKeys[] = $columnKey;
            } else {
                $columnKeys[] = $column;
            }
        }

        return $columnKeys;
    }

    /**
     * Apply search query.
     *
     * @param string|mixed $searchStr
     * @return Builder
     * @throws \Exception
     */
    public function search($searchStr): Builder
    {
        $columnsToCompare = $this->columnsToCompare();
        $conditions = [];
        $query = $this->query();

        if (count($columnsToCompare) === 0) {
            return $query;
        }

        $parsedStr = $this->parseSearchStr($this->searchStr = $searchStr);

        foreach ($columnsToCompare as $column) {
            $conditions[] = $column . ' like "' . $parsedStr . '"';
        }

        $method = $this->searchOperator . 'Raw';
        $query->{$method}('(' . join(' OR ', $conditions) . ')');

        if ($this->shouldSortByRelevance()) {
            $this->applySortByRelevance();
        }

        return $query;
    }

    /**
     * Parse string to search.
     *
     * @param string|mixed $searchStr
     * @return string
     */
    protected function parseSearchStr($searchStr): string
    {
        $searchStr = preg_replace('/[^A-Za-z0-9]/', '', $searchStr);

        return '%' . join('%', str_split($searchStr)) . '%';
    }

    /**
     * Return the columns for sorting query.
     *
     * @return array
     */
    public function columns(): array
    {
        return $this->columns;
    }
}
