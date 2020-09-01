<?php

namespace Webike\Laravel\Fulltext\Tests;

use Mockery;
use Webike\Laravel\Fulltext\Indexer;
use Webike\Laravel\Fulltext\Tests\Fixtures\TestModel;

class IndexerTest extends AbstractTestCase
{
    public function test_index_model()
    {
        $indexer = new Indexer();
        $model = Mockery::mock(TestModel::class);
        $model->shouldReceive('indexRecord');
        $indexer->indexModel($model);
    }
}
