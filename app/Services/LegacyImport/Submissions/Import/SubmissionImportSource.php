<?php

namespace App\Services\LegacyImport\Submissions\Import;

use App\Services\LegacyImport\Submissions\WordPressSubmissionRecord;

interface SubmissionImportSource
{
    /**
     * @return iterable<int, WordPressSubmissionRecord>
     */
    public function records(): iterable;
}
