<?php

namespace App\Services\LegacyImport\Users\Import;

use App\Services\LegacyImport\Users\WordPressUserRecord;

interface UserImportSource
{
    /**
     * @return iterable<int, WordPressUserRecord>
     */
    public function records(): iterable;
}
