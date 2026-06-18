<?php

namespace App\Services;

use App\Domains\Notifications\Notifications\SubmissionExportReadyNotification;
use App\Domains\Profile\Actions\ExportDuaSubmissionsAction;
use App\Enums\AdminExportStatus;
use App\Enums\AdminExportType;
use App\Exceptions\AdminExportDuplicateException;
use App\Exceptions\AdminExportQueueException;
use App\Exceptions\AdminExportRateLimitException;
use App\Jobs\GenerateAdminExportJob;
use App\Models\AdminExport;
use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Models\User;
use App\Support\DuaListOccasions;
use App\Support\ExceptionSanitizer;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminExportService extends Service
{
    public function __construct(
        private readonly AnalyticsQueryService $analytics,
        private readonly KeywordAnalyticsService $keywords,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function queue(User $user, AdminExportType $type, array $filters = []): AdminExport
    {
        if (app()->isProduction() && config('queue.default') === 'sync') {
            throw AdminExportQueueException::syncConnectionNotAllowedInProduction();
        }

        if ($this->hasPendingExport($user)) {
            throw AdminExportDuplicateException::pendingExportExists();
        }

        $rateLimitKey = 'admin-exports:'.$user->id;
        $maxAttempts = (int) config('mydualist.admin_exports.rate_limit_per_hour', 10);

        if (! RateLimiter::attempt($rateLimitKey, $maxAttempts, fn (): bool => true, 3600)) {
            throw AdminExportRateLimitException::exceeded();
        }

        $export = AdminExport::query()->create([
            'user_id' => $user->id,
            'type' => $type,
            'status' => AdminExportStatus::Pending,
            'filters' => $filters,
            'file_name' => $this->fileName($type),
        ]);

        GenerateAdminExportJob::dispatch($export);

        return $export;
    }

    public function queueUserListSubmissions(User $user, int $duaListId): AdminExport
    {
        if (app()->isProduction() && config('queue.default') === 'sync') {
            throw AdminExportQueueException::syncConnectionNotAllowedInProduction();
        }

        $duaList = DuaList::query()
            ->whereKey($duaListId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if ($this->hasPendingExport($user)) {
            throw AdminExportDuplicateException::pendingExportExists();
        }

        $rateLimitKey = 'user-exports:'.$user->id;
        $maxAttempts = (int) config('mydualist.user_exports.rate_limit_per_hour', 5);

        if (! RateLimiter::attempt($rateLimitKey, $maxAttempts, fn (): bool => true, 3600)) {
            throw AdminExportRateLimitException::exceeded();
        }

        $export = AdminExport::query()->create([
            'user_id' => $user->id,
            'type' => AdminExportType::UserListSubmissions,
            'status' => AdminExportStatus::Pending,
            'filters' => [
                'dua_list_id' => $duaList->id,
                'dua_list_title' => $duaList->title,
            ],
            'file_name' => app(ExportDuaSubmissionsAction::class)->fileName($duaList),
        ]);

        GenerateAdminExportJob::dispatch($export);

        return $export;
    }

    public function generate(AdminExport $export): void
    {
        $export = $export->fresh();

        if ($export === null || $export->status !== AdminExportStatus::Pending) {
            return;
        }

        $claimed = AdminExport::query()
            ->whereKey($export->id)
            ->where('status', AdminExportStatus::Pending)
            ->update(['status' => AdminExportStatus::Processing]);

        if ($claimed === 0) {
            return;
        }

        $export->refresh();
        $export->loadMissing('user');
        $path = null;

        try {
            $path = 'exports/'.Str::uuid().'.csv';
            $fullPath = Storage::disk('local')->path($path);

            if (! is_dir(dirname($fullPath))) {
                mkdir(dirname($fullPath), 0755, true);
            }

            $handle = fopen($fullPath, 'w');
            $rowCount = $this->writeExport($export, $handle);
            fclose($handle);

            $export->update([
                'status' => AdminExportStatus::Completed,
                'file_path' => $path,
                'row_count' => $rowCount,
                'completed_at' => now(),
            ]);

            $this->notifyUser($export);
        } catch (\Throwable $exception) {
            if ($path !== null && Storage::disk('local')->exists($path)) {
                Storage::disk('local')->delete($path);
            }

            $this->recordFailure($export, $exception);

            throw $exception;
        }
    }

    public function recordFailure(AdminExport $export, \Throwable $exception, bool $notify = true): void
    {
        $export->update([
            'status' => AdminExportStatus::Failed,
            'error_message' => ExceptionSanitizer::forStorage($exception),
        ]);

        if ($notify) {
            $this->notifyExportFailed($export, ExceptionSanitizer::forUser($exception));
        }
    }

    public function notifyExportFailed(AdminExport $export, ?string $message = null): void
    {
        $user = $export->user;

        if ($user === null) {
            return;
        }

        Notification::make()
            ->title('Export failed')
            ->body($message ?? 'Your export could not be completed. Please try again later.')
            ->danger()
            ->sendToDatabase($user);
    }

    public function hasPendingExport(User $user): bool
    {
        return AdminExport::query()
            ->where('user_id', $user->id)
            ->whereIn('status', [AdminExportStatus::Pending, AdminExportStatus::Processing])
            ->exists();
    }

    /**
     * @param  resource  $handle
     */
    private function writeExport(AdminExport $export, $handle): int
    {
        $filters = $export->filters ?? [];
        $rowCount = 0;

        match ($export->type) {
            AdminExportType::DuaListAnalytics => $rowCount = $this->writeDuaListAnalytics($handle, $filters),
            AdminExportType::UserAnalytics => $rowCount = $this->writeUserAnalytics($handle, $filters),
            AdminExportType::UniqueUsers => $rowCount = $this->writeUniqueUsers($handle, $filters),
            AdminExportType::CategoryAnalytics => $rowCount = $this->writeCategoryAnalytics($handle, $filters),
            AdminExportType::SubmissionAnalytics => $rowCount = $this->writeSubmissionAnalytics($handle, $filters),
            AdminExportType::KeywordAnalytics => $rowCount = $this->writeKeywordAnalytics($handle, $filters),
            AdminExportType::UserListSubmissions => $rowCount = $this->writeUserListSubmissions($export, $handle, $filters),
        };

        return $rowCount;
    }

    /**
     * @param  resource  $handle
     * @param  array<string, mixed>  $filters
     */
    private function writeDuaListAnalytics($handle, array $filters): int
    {
        fputcsv($handle, ['S.No', 'List Name', 'Creator Name', 'Creator Email', 'Category', 'Total Submissions', 'Completed Submissions', 'Created Date']);

        $serial = 0;

        $this->analytics->duaListAnalyticsQuery($filters)->chunk(200, function ($lists) use ($handle, &$serial): void {
            foreach ($lists as $list) {
                /** @var DuaList $list */
                $serial++;
                fputcsv($handle, [
                    $serial,
                    $list->title,
                    $list->user?->name,
                    $list->user?->email,
                    DuaListOccasions::label((string) $list->occasion),
                    $list->submissions_count,
                    $list->completed_submissions_count,
                    optional($list->created_at)->toDateTimeString(),
                ]);
            }
        });

        return $serial;
    }

    /**
     * @param  resource  $handle
     * @param  array<string, mixed>  $filters
     */
    private function writeUserAnalytics($handle, array $filters): int
    {
        fputcsv($handle, ['S.No', 'Username', 'User Email', 'List Names', 'Number of Dua Lists', 'Total Dua Submissions', 'Registration Date']);

        $serial = 0;

        $this->analytics->userAnalyticsQuery($filters)->chunk(200, function ($users) use ($handle, &$serial): void {
            $titlesByUserId = DuaList::query()
                ->whereIn('user_id', $users->pluck('id'))
                ->orderBy('title')
                ->get(['user_id', 'title'])
                ->groupBy('user_id')
                ->map(fn ($lists) => $lists->pluck('title')->implode('; '));

            foreach ($users as $user) {
                /** @var User $user */
                $serial++;
                fputcsv($handle, [
                    $serial,
                    $user->name,
                    $user->email,
                    $titlesByUserId->get($user->id, ''),
                    $user->dua_lists_count,
                    $user->dua_submissions_count,
                    optional($user->created_at)->toDateTimeString(),
                ]);
            }
        });

        return $serial;
    }

    /**
     * @param  resource  $handle
     * @param  array<string, mixed>  $filters
     */
    private function writeUniqueUsers($handle, array $filters): int
    {
        fputcsv($handle, ['S.No', 'Username', 'Name', 'User Email', 'Verified Status', 'Registration Date']);

        $serial = 0;

        $this->analytics->uniqueUsersQuery($filters)->chunk(200, function ($users) use ($handle, &$serial): void {
            foreach ($users as $user) {
                /** @var User $user */
                $serial++;
                fputcsv($handle, [
                    $serial,
                    $user->name,
                    $user->name,
                    $user->email,
                    $user->email_verified_at ? 'Verified' : 'Unverified',
                    optional($user->created_at)->toDateTimeString(),
                ]);
            }
        });

        return $serial;
    }

    /**
     * @param  resource  $handle
     * @param  array<string, mixed>  $filters
     */
    private function writeCategoryAnalytics($handle, array $filters): int
    {
        fputcsv($handle, ['Category Name', 'Total Lists', 'Percentage of Total']);

        $rowCount = 0;

        foreach ($this->analytics->categoryAnalyticsRows($filters) as $row) {
            $rowCount++;
            fputcsv($handle, [$row->label, $row->list_count, $row->percentage.'%']);
        }

        return $rowCount;
    }

    /**
     * @param  resource  $handle
     * @param  array<string, mixed>  $filters
     */
    private function writeSubmissionAnalytics($handle, array $filters): int
    {
        fputcsv($handle, ['Submission Title', 'User Email', 'Gender', 'Dua List Name', 'Phone Number', 'Submitted Date']);

        $rowCount = 0;

        $this->analytics->submissionAnalyticsQuery($filters)->chunk(200, function ($submissions) use ($handle, &$rowCount): void {
            foreach ($submissions as $submission) {
                /** @var DuaSubmission $submission */
                $rowCount++;
                $phone = trim(implode(' ', array_filter([
                    $submission->whatsapp_country_code,
                    $submission->whatsapp_phone,
                ])));

                fputcsv($handle, [
                    Str::limit((string) $submission->content, 120),
                    $submission->email,
                    $submission->gender,
                    $submission->duaList?->title,
                    $phone,
                    optional($submission->created_at)->toDateTimeString(),
                ]);
            }
        });

        return $rowCount;
    }

    /**
     * @param  resource  $handle
     * @param  array<string, mixed>  $filters
     */
    private function writeKeywordAnalytics($handle, array $filters): int
    {
        fputcsv($handle, ['S.No', 'Keyword', 'Occurrences']);

        $serial = 0;

        foreach ($this->keywords->aggregate($filters) as $row) {
            $serial++;
            fputcsv($handle, [$serial, $row->keyword, $row->occurrences]);
        }

        return $serial;
    }

    /**
     * @param  resource  $handle
     * @param  array<string, mixed>  $filters
     */
    private function writeUserListSubmissions(AdminExport $export, $handle, array $filters): int
    {
        $user = $export->user;
        $duaListId = (int) ($filters['dua_list_id'] ?? 0);

        abort_if($user === null || $duaListId <= 0, 422, 'Export filters are invalid.');

        $duaList = DuaList::query()
            ->whereKey($duaListId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        return app(ExportDuaSubmissionsAction::class)->writeCsv($handle, $user, $duaList);
    }

    private function notifyUser(AdminExport $export): void
    {
        $user = $export->user;

        if ($user === null) {
            return;
        }

        if ($export->type->isUserFacing()) {
            $user->notify(new SubmissionExportReadyNotification($export->fresh()));

            return;
        }

        Notification::make()
            ->title('Export ready')
            ->body($export->type->label().' CSV is ready to download ('.number_format((int) $export->row_count).' rows).')
            ->success()
            ->actions([
                \Filament\Notifications\Actions\Action::make('download')
                    ->label('Download')
                    ->url($export->downloadUrl(), shouldOpenInNewTab: true),
            ])
            ->sendToDatabase($user);
    }

    private function fileName(AdminExportType $type): string
    {
        return Str::slug($type->label()).'-'.now()->format('Y-m-d-His').'.csv';
    }
}
