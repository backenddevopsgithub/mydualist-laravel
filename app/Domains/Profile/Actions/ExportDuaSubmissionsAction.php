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

        return [
            'filename' => $this->fileName($duaList),
            'callback' => fn (): mixed => $this->streamCsv($user, $duaList),
        ];
    }

    public function streamCsv(User $user, DuaList $duaList): void
    {
        $handle = fopen('php://output', 'w');
        $this->writeCsv($handle, $user, $duaList);
        fclose($handle);
    }

    /**
     * @param  resource  $handle
     */
    public function writeCsv($handle, User $user, DuaList $duaList): int
    {
        fputcsv($handle, ['Name', 'Email', 'Status', 'Dua', 'Note', 'Submitted At']);

        $query = DuaSubmission::query()->where('dua_list_id', $duaList->id);
        app(DuaSubmissionOrderingService::class)->applyOwnerListOrdering($query, $duaList, $user);

        $rowCount = 0;

        $query->chunk(200, function ($submissions) use ($handle, &$rowCount): void {
            foreach ($submissions as $submission) {
                $rowCount++;
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

        return $rowCount;
    }

    public function fileName(DuaList $duaList): string
    {
        return 'dua-submissions-'.$duaList->id.'.csv';
    }
}
