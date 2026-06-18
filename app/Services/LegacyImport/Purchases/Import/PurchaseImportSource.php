<?php

namespace App\Services\LegacyImport\Purchases\Import;

use App\Services\LegacyImport\Purchases\WordPressOrderRecord;

interface PurchaseImportSource
{
    /**
     * @return iterable<int, WordPressOrderRecord>
     */
    public function records(): iterable;
}
