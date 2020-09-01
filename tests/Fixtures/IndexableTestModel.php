<?php

namespace Webike\Laravel\Fulltext\Tests\Fixtures;

use Webike\Laravel\Fulltext\Indexable;

class IndexableTestModel extends TestModel
{
    use Indexable;

    public $indexRecord;
}
