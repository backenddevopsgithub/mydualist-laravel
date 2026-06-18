<?php

namespace App\Services\LegacyImport\Lists\Import;

use App\Services\LegacyImport\Lists\WordPressListRecord;

interface ListImportSource
{
    /**
     * @return iterable<int, WordPressListRecord>
     */
    public function records(): iterable;
}
