<?php

namespace App\Services\LegacyImport\CommunityDuas\Import;

use App\Services\LegacyImport\CommunityDuas\WordPressCommunityDuaRecord;
use App\Services\LegacyImport\CommunityDuas\WordPressCommunityQueueRecord;

interface CommunityDuaImportSource
{
    /**
     * @return iterable<int, WordPressCommunityDuaRecord>
     */
    public function duaRecords(): iterable;

    /**
     * @return iterable<int, WordPressCommunityQueueRecord>
     */
    public function queueRecords(): iterable;
}
