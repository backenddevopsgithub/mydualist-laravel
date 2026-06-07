<?php

namespace App\Domains\Submissions\Actions;

use App\Actions\Action;
use App\Models\DuaSubmission;

class DeleteDuaSubmissionAction extends Action
{
    public function handle(mixed ...$args): mixed
    {
        /** @var DuaSubmission $submission */
        $submission = $args[0];

        return $submission->delete();
    }
}
