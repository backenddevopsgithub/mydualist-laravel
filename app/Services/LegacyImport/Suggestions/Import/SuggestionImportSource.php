<?php

namespace App\Services\LegacyImport\Suggestions\Import;

use App\Services\LegacyImport\Suggestions\WordPressSuggestionRecord;

interface SuggestionImportSource
{
    /**
     * @return iterable<int, WordPressSuggestionRecord>
     */
    public function records(): iterable;
}
