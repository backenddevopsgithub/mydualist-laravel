<?php

namespace App\Domains\Profile\Actions;

use App\Actions\Action;
use App\Domains\Submissions\Services\DuaSubmissionOrderingService;
use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Models\User;

class ExportDuaSubmissionsAction extends Action
{
    /**
     * @return array{filename: string, callback: callable(): void}
     */
    public function handle(mixed ...$args): mixed
    {
        /** @var User $user */
        $user = $args[0];
        $duaListId = $args[1];

        $duaList = DuaList::query()
            ->whereKey($duaListId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $ordering = app(DuaSubmissionOrderingService::class);

        return [
            'filename' => 'dua-submissions-'.$duaList->id.'.csv',
            'callback' => function () use ($duaList, $user, $ordering): void {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, ['Name', 'Email', 'Status', 'Dua', 'Note', 'Submitted At']);

                $query = DuaSubmission::query()->where('dua_list_id', $duaList->id);
                $ordering->applyOwnerListOrdering($query, $duaList, $user);

                $query->chunk(200, function ($submissions) use ($handle): void {
                    foreach ($submissions as $submission) {
                        fputcsv($handle, [
                            $submission->displayName(),
                            $submission->email,
                            $submission->status->value,
                            $submission->content,
                            $submission->note,
                            optional($submission->created_at)->toDateTimeString(),
                        ]);
                    }
                });

                fclose($handle);
            },
        ];
    }
}
