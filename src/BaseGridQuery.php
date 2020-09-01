<?php

namespace Webike\Searchable;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\DB;

abstract class BaseGridQuery
{
    /**
     * Initialized query.
     *
     * @var Builder
     */
    protected $query;

    /**
     * Return the initialized specific query. This contains the joins logic and condition that make the query specific.
     *
     * @return Builder
     * @throws \Exception
     */
    public function query(): Builder
    {
        return $this->query ?? $this->query = $this->initQuery();
    }

    /**
     * Return the final query of this gridQuery.
     * By default context, we can call selectColumns() to return the query with its selected columns
     * to treat them as the final query.
     *
     * @return Builder
     */
    public function makeQuery(): Builder
    {
        return $this->selectColumns();
    }

    /**
     * Return the query from the query() method with its select statement from the columns() method.
     *
     * @return Builder
     */
    public function selectColumns(): Builder
    {
        return $this->query()->select($this->makeSelect($this->columns()));
    }

    /**
     * Create an array of select parameters that can be passed in $query->select().
     * String indexed columns will be transformed to have an alias like "column_key as as actual_column".
     *
     * @param array|null $columns
     * @return array
     */
    public function makeSelect(array $columns = null): array
    {
        $columns = $columns ?: $this->columns();
        $selects = [];

        foreach ($columns as $key => $select) {
            if (is_int($key)) {
                $selects[] = $select;
            } else {
                $selects[] = DB::raw($select . ' as ' . $key);
            }
        }

        return $selects;
    }

    /**
     * Set the query.
     *
     * @param Builder $query
     * @return  $this
     */
    public function setQuery($query): self
    {
        $this->query = $query;

        return $this;
    }

    /**
     * Set the columns of this gridQuery instance of the grid to the given query's select clause.
     *
     * @param Builder $query
     * @return Builder
     */
    public function setSelectQuery($query): Builder
    {
        return $query->select($this->makeSelect($this->columns()));
    }

    /**
     * Get the actual column of the given column key.
     *
     * @param string $columnKey
     * @return string|mixed
     */
    public function getColumn($columnKey): string
    {
        return $this->findColumn($this->columns(), $columnKey);
    }

    /**
     * Find the column from columns.
     *
     * @param string $columns
     * @param string $columnKey
     * @return string
     */
    public static function findColumn($columns, $columnKey): string
    {
        if (array_key_exists($columnKey, $columns)) {
            return $columns[$columnKey];
        }

        foreach ($columns as $column) {
            if ($column === $columnKey || ends_with($column, ".{$columnKey}")) {
                return $column;
            }
        }
    }

    /**
     * Get the actual columns of the given column keys.
     *
     * @param array $columnKeys
     * @return array
     */
    public function getColumns(array $columnKeys): array
    {
        $columns = [];

        foreach ($columnKeys as $columnKey) {
            $columns[] = $this->getColumn($columnKey);
        }

        return $columns;
    }

    /**
     * Getter for column.
     *
     * @param string $columnKey
     * @return string|mixed
     */
    public function __get($columnKey): string
    {
        return $this->getColumn($columnKey);
    }

    /**
     * Handle dynamic calls on query.
     *
     * @param string $method
     * @param array $parameters
     * @return $this
     * @throws \Exception
     */
    public function __call($method, $parameters): self
    {
        if (!$this->query) {
            throw new \Exception("Property \$query is not set. Cannot call method {$method} on object of " . static::class . '.');
        }

        call_user_func_array([$this->query, $method], $parameters);

        return $this;
    }

    /**
     * Initialize query.
     *
     * @return Builder
     * @throws \Exception
     */
    public function initQuery()
    {
        throw new \Exception('Please create self initQuery() method on ' . get_class($this) . '.');
    }

    /**
     * Columns declaration of the report grid.
     *
     * @return array
     */
    abstract public function columns();

    /**
     * Return new instance.
     *
     * @return static
     */
    public static function make(): self
    {
        return new static;
    }
}
