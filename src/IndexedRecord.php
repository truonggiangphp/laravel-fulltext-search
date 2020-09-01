<?php

namespace Webike\Laravel\Fulltext;

use Illuminate\Database\Eloquent\Model;

class IndexedRecord extends Model
{
    protected $table = 'laravel_fulltext';

    /**
     * IndexedRecord constructor.
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->connection = config('laravel-fulltext.db_connection');

        parent::__construct($attributes);
    }

    /**
     * @return mixed
     */
    public function indexable()
    {
        return $this->morphTo();
    }


    public function updateIndex()
    {
        $this->setAttribute('indexed_title', $this->indexable->getIndexTitle());
        $this->setAttribute('indexed_content', $this->indexable->getIndexContent());
        $this->save();
    }
}
