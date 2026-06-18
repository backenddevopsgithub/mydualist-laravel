<?php

namespace App\Console\Commands;

use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;

class SeedLoadTestDataCommand extends Command
{
    public const USER_EMAIL = 'loadtest@mydualist.local';

    public const USER_PASSWORD = 'loadtest-password';

    public const LIST_SLUG = 'arafah-load-test';

    protected $signature = 'load-test:seed
                            {--submissions=500 : Number of regular submissions on the load-test list}
                            {--fresh : Delete and recreate load-test submissions}';

    protected $description = 'Seed deterministic load-test data and write load-tests/fixtures/manifest.json for k6';

    public function handle(): int
    {
        if (! in_array(config('app.env'), ['local', 'staging'], true)) {
            $this->components->warn('Load-test seeding is intended for local/staging environments only.');
        }

        $submissionCount = max(0, (int) $this->option('submissions'));

        $user = User::query()->updateOrCreate(
            ['email' => self::USER_EMAIL],
            [
                'name' => 'Load Test Owner',
                'password' => Hash::make(self::USER_PASSWORD),
                'email_verified_at' => now(),
            ],
        );

        $list = DuaList::query()->updateOrCreate(
            ['slug' => self::LIST_SLUG],
            [
                'user_id' => $user->id,
                'title' => 'Arafah Load Test List',
                'occasion' => 'hajj',
                'start_date' => now()->toDateString(),
                'end_date' => now()->addMonth()->toDateString(),
                'status' => DuaList::STATUS_ACTIVE,
                'published_at' => now()->subMinute(),
            ],
        );

        if ($this->option('fresh')) {
            DuaSubmission::query()
                ->where('dua_list_id', $list->id)
                ->delete();

            $this->components->info('Removed existing submissions for the load-test list.');
        }

        $existingCount = DuaSubmission::query()
            ->where('dua_list_id', $list->id)
            ->where('is_personal_dua', false)
            ->count();

        $toCreate = max(0, $submissionCount - $existingCount);

        if ($toCreate > 0) {
            $bar = $this->output->createProgressBar($toCreate);
            $bar->start();

            for ($index = 0; $index < $toCreate; $index++) {
                DuaSubmission::factory()->create([
                    'dua_list_id' => $list->id,
                    'user_id' => null,
                    'content' => 'Load test submission '.($existingCount + $index + 1).' for Arafah performance testing.',
                    'email' => 'visitor'.($existingCount + $index + 1).'@loadtest.local',
                ]);

                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
        }

        $manifest = [
            'base_url' => rtrim((string) config('app.url'), '/'),
            'owner' => [
                'email' => self::USER_EMAIL,
                'password' => self::USER_PASSWORD,
            ],
            'list' => [
                'id' => $list->id,
                'slug' => $list->slug,
                'public_path' => '/'.$list->slug,
                'owner_path' => '/dashboard/lists/'.$list->slug,
                'submission_count' => DuaSubmission::query()
                    ->where('dua_list_id', $list->id)
                    ->where('is_personal_dua', false)
                    ->count(),
            ],
            'generated_at' => now()->toIso8601String(),
        ];

        $manifestPath = base_path('load-tests/fixtures/manifest.json');
        File::ensureDirectoryExists(dirname($manifestPath));
        File::put($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);

        $this->components->info('Load-test manifest written to load-tests/fixtures/manifest.json');
        $this->line('Public list URL: '.$manifest['base_url'].$manifest['list']['public_path']);
        $this->line('Owner dashboard: '.$manifest['base_url'].$manifest['list']['owner_path']);
        $this->line('Owner login: '.self::USER_EMAIL.' / '.self::USER_PASSWORD);

        return self::SUCCESS;
    }
}
