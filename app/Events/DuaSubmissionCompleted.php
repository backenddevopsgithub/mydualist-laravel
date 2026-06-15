<?php

namespace App\Events;

use App\Models\DuaSubmission;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DuaSubmissionCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly DuaSubmission $submission,
    ) {}
}
