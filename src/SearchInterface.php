<?php

namespace Webike\Laravel\Fulltext;

interface SearchInterface
{
    /**
     * @param $search
     * @return mixed
     */
    public function run($search);

    /**
     * @param $search
     * @param $class
     * @return mixed
     */
    public function runForClass($search, $class);

    /**
     * @param $search
     * @return mixed
     */
    public function searchQuery($search);
}
