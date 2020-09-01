<?php

namespace Webike\Searchable;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Webike\Searchable\Search\SublimeSearch;

trait Searchable
{
    /** @var array */
    protected static $allSearchableColumns = [];

    /** @var bool */
    protected $searchableEnabled = true;

    /** @var bool */
    protected $sortByRelevance = true;

    /** @var SublimeSearch */
    protected $searchQuery;

    /**
     * Return the searchable columns for this model's table.
     *
     * @return array
     */
    public function searchableColumns(): array
    {
        if (property_exists($this, 'searchableColumns')) {
            return $this->searchableColumns;
        }

        if (property_exists($this, 'searchable') && array_key_exists('columns', $this->searchable)) {
            return $this->searchable['columns'];
        }

        return static::getTableColumns($this->getTable());
    }

    /**
     * Return the sortable columns for this model's table.
     *
     * @return array
     */
    public function sortableColumns(): array
    {
        if (property_exists($this, 'sortableColumns')) {
            return $this->sortableColumns;
        }

        if (property_exists($this, 'searchable') && array_key_exists('sortable_columns', $this->searchable)) {
            return $this->searchable['sortable_columns'];
        }

        return static::getTableColumns($this->getTable());
    }

    /**
     * Get table columns.
     *
     * @param $table | null
     * @return array
     */
    public static function getTableColumns($table = null): array
    {
        $table = $table ?? (new static)->getTable();

        if (!Arr::has(static::$allSearchableColumns, $table)) {
            static::$allSearchableColumns[$table] = Schema::getColumnListing($table);
        }

        return static::$allSearchableColumns[$table];
    }

    /**
     * Identifies if the column is a valid column, either a regular table column or derived column.
     * Useful for checking valid columns to avoid sql injection especially in orderBy query.
     *
     * @param string $column
     * @return boolean
     */
    public static function isColumnValid($column): bool
    {
        $model = new static;
        $allColumns = array_merge($model->searchableColumns(), $model->sortableColumns());

        // Derived columns are a key in allColumns.
        if (array_key_exists($column, $allColumns)) {
            return true;
        }

        // Regular table column can be included in the allColumns.
        if (in_array($column, $allColumns)) {
            return true;
        }

        // Regular table column from the table
        return in_array($column, static::getTableColumns($model->getTable()));
    }

    /**
     * Get the actual sortable column.
     *
     * @param string $column
     * @return string|mixed
     */
    public static function getSortableColumn($column): string
    {
        $model = new static;
        $allColumns = array_merge($model->searchableColumns(), $model->sortableColumns());

        return BaseGridQuery::findColumn($allColumns, $column);
    }

    /**
     * Return the searchable joins for the search query.
     *
     * @return array
     */
    public function searchableJoins(): array
    {
        if (property_exists($this, 'searchableJoins')) {
            return $this->searchableJoins;
        }

        if (property_exists($this, 'searchable') && array_key_exists('joins', $this->searchable)) {
            return $this->searchable['joins'];
        }

        return [];
    }

    /**
     * Apply searchable joins for the search query.
     *
     * @param  $query
     * @return void
     */
    protected function applySearchableJoins($query)
    {
        foreach ($this->searchableJoins() as $table => $join) {
            $joinMethod = $join[2] ?? 'leftJoin';
            $query->{$joinMethod}($table, $join[0], '=', $join[1]);
        }
    }

    /**
     * Return the search query.
     *
     * @return mixed|SublimeSearch
     */
    public function searchQuery(): SublimeSearch
    {
        if ($this->searchQuery) {
            return $this->searchQuery;
        }

        if (method_exists($this, 'defaultSearchQuery')) {
            return $this->searchQuery = $this->defaultSearchQuery();
        }

        return $this->searchQuery = new SublimeSearch($this, $this->searchableColumns(), $this->sortByRelevance, 'where');
    }

    /**
     * Set the model's search query.
     *
     * @param BaseSearchQuery $searchQuery
     * @return Searchable
     */
    public function setSearchQuery($searchQuery): self
    {
        $this->searchQuery = $searchQuery;

        return $this;
    }

    /**
     * Disable searchable in the model.
     *
     * @return $this
     */
    public function disableSearchable(): self
    {
        $this->searchableEnabled = false;
        return $this;
    }

    /**
     * Enable searchable in the model.
     *
     * @return $this
     */
    public function enableSearchable(): self
    {
        $this->searchableEnabled = true;
        return $this;
    }

    /**
     * Apply search in the query.
     *
     * @param query $query
     * @param string $search
     *
     * @return void
     * @throws \Exception
     */
    public function scopeSearch($query, $search)
    {
        if (!$this->searchableEnabled) {
            return;
        }

        $this->applySearchableJoins($query);

        if (empty($query->getQuery()->columns)) {
            $query->select([$query->getQuery()->from . '.*']);
        }

        $this->searchQuery()->setQuery($query)->search($search);
    }

    /**
     * Scope query to set $sortByRelevance.
     * @param Builder $query
     * @param boolean $sortByRelevance
     * @return void
     */
    public function scopeSortByRelevance($query, $sortByRelevance = true)
    {
        $query->getModel()->searchableSortByRelevance($sortByRelevance);
    }

    /**
     * Set model's $sortByRelevance for searchable query.
     *
     * @param boolean $sortByRelevance
     * @return $this
     */
    public function searchableSortByRelevance($sortByRelevance = true): self
    {
        $this->sortByRelevance = $sortByRelevance;

        $this->searchQuery()->sortByRelevance($sortByRelevance);

        return $this;
    }

    /**
     * If model should sort by relevance.
     *
     * @return bool
     */
    public function shouldSortByRelevance(): bool
    {
        return $this->sortByRelevance;
    }

    /**
     * Set $searchable.
     *
     * @param array $config
     * @return  $this
     */
    public function setSearchable($config): self
    {
        $this->setSearchableColumns(array_get($config, 'columns'));
        $this->setSearchableJoins(array_get($config, 'joins'));
        $this->setSortableColumns(array_get($config, 'sortable_columns'));

        return $this;
    }

    /**
     * Set searchable columns.
     *
     * @param array $columns
     * @return  $this
     */
    public function setSearchableColumns($columns): self
    {
        if (property_exists($this, 'searchableColumns')) {
            $this->searchableColumns = $columns ?? [];
        }

        if (property_exists($this, 'searchable')) {
            $this->searchable['columns'] = $columns ?? [];
        }

        return $this;
    }

    /**
     * Set searchable joins.
     *
     * @param array $joins
     * @return  $this
     */
    public function setSearchableJoins($joins): self
    {
        if (property_exists($this, 'searchableJoins')) {
            $this->searchableJoins = $joins ?? [];
        }

        if (property_exists($this, 'searchable')) {
            $this->searchable['joins'] = $joins ?? [];
        }

        return $this;
    }

    /**
     * Set sortable columns.
     *
     * @param array $columns
     * @return  $this
     */
    public function setSortableColumns($columns): self
    {
        if (property_exists($this, 'sortableColumns')) {
            $this->sortableColumns = $columns ?? [];
        }

        if (property_exists($this, 'searchable')) {
            $this->searchable['sortable_columns'] = $columns ?? [];
        }

        return $this;
    }

    /**
     * Add searchable.
     *
     * @param array $config
     * @return  $this
     */
    public function addSearchable($config): self
    {
        if ($columns = array_get($config, 'columns')) {
            $this->addSearchableColumns($columns);
        }

        if ($joins = array_get($config, 'joins')) {
            $this->addSearchableJoins($joins);
        }

        if ($columns = array_get($config, 'sortable_columns')) {
            $this->addSortableColumns($columns);
        }

        return $this;
    }

    /**
     * Add searchable columns.
     *
     * @param array $columns
     * @return  $this
     */
    public function addSearchableColumns($columns): self
    {
        if (property_exists($this, 'searchableColumns')) {
            $this->searchableColumns = array_merge($this->searchableColumns, $columns);
        }

        if (property_exists($this, 'searchable')) {
            $this->searchable['columns'] = array_merge($this->searchable['columns'], $columns);
        }

        return $this;
    }

    /**
     * Add searchable joins.
     *
     * @param array $joins
     * @return  $this
     */
    public function addSearchableJoins($joins): self
    {
        if (property_exists($this, 'searchableJoins')) {
            $this->searchableJoins = array_merge($this->searchableJoins, $joins);
        }

        if (property_exists($this, 'searchable')) {
            $this->searchable['joins'] = array_merge($this->searchable['joins'], $joins);
        }

        return $this;
    }

    /**
     * Add sortable columns.
     *
     * @param array $columns
     * @return  $this
     */
    public function addSortableColumns($columns): self
    {
        if (property_exists($this, 'sortableColumns')) {
            $this->sortableColumns = array_merge($this->sortableColumns, $columns);
        }

        if (property_exists($this, 'searchable')) {
            $this->searchable['sortable_columns'] = array_merge($this->searchable['sortable_columns'], $columns);
        }

        return $this;
    }
}
