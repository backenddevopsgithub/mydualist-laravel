<?php

namespace App\Services\Blog\Import;

use App\Services\Blog\WordPressPostRecord;

interface BlogImportSource
{
    /**
     * @return iterable<int, WordPressPostRecord>
     */
    public function records(): iterable;
}
