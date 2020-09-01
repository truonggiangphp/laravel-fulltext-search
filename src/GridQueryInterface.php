<?php

namespace Webike\Searchable;

use Illuminate\Database\Eloquent\Builder;

interface GridQueryInterface
{
    /**
     * Create the query for the report grid.
     *
     * @return Builder
     */
    public function makeQuery();

    /**
     * Columns declaration of the report grid.
     *
     * @return array
     */
    public function columns();
}
