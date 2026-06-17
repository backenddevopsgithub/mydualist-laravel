<?php

namespace App\Domains\Submissions\Actions;

use App\Actions\Action;
use App\Enums\DuaSubmissionStatus;
use App\Events\DuaSubmissionsCreated;
use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Models\User;
use App\Services\WhatsAppOtpService;
use App\Support\SubmissionGenders;
use App\Support\WhatsAppPhone;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateDuaSubmissionAction extends Action
{
    private const MAX_PER_EMAIL_PER_LIST = 35;

    public function __construct(
        private readonly WhatsAppOtpService $whatsappOtp,
    ) {}

    /**
     * @param  array{first_name?: string|null, last_name?: string|null, email?: string|null, gender?: string|null, content?: string, duas?: array<int, string>, note?: string|null, is_anonymous?: bool|null, whatsapp_notifications?: bool|null, whatsapp_country_code?: string|null, whatsapp_phone?: string|null, whatsapp_verification_token?: string|null}  $data
     */
    public function handle(mixed ...$args): mixed
    {
        /** @var DuaList $duaList */
        $duaList = $args[0];
        $data = $args[1];
        /** @var User|null $user */
        $user = $args[2] ?? null;

        return DB::transaction(function () use ($duaList, $data, $user): Collection {
            /** @var DuaList $lockedList */
            $lockedList = DuaList::query()
                ->whereKey($duaList->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless($lockedList->acceptsSubmissions(), 403, $lockedList->closedReason() ?? 'This list is not accepting submissions.');

            $email = isset($data['email']) ? mb_strtolower((string) $data['email']) : null;
            $contents = $this->contents($data);
            $whatsappFields = $this->resolveWhatsAppFields($data);

            if ($email) {
                $limit = $lockedList->dua_limit_per_person ?: self::MAX_PER_EMAIL_PER_LIST;
                $submittedCount = DuaSubmission::query()
                    ->where('dua_list_id', $lockedList->id)
                    ->where('email', $email)
                    ->count();

                if ($submittedCount + count($contents) > $limit) {
                    throw ValidationException::withMessages([
                        'email' => 'You have reached the submission limit for this list.',
                    ]);
                }
            }

            $nonPersonalCountBefore = DuaSubmission::query()
                ->where('dua_list_id', $lockedList->id)
                ->where('is_personal_dua', false)
                ->count();

            $submissions = collect($contents)
                ->map(fn (string $content): DuaSubmission => DuaSubmission::query()->create([
                    'dua_list_id' => $lockedList->id,
                    'user_id' => $user?->id,
                    'first_name' => $data['first_name'] ?? null,
                    'last_name' => $data['last_name'] ?? null,
                    'email' => $email,
                    'gender' => SubmissionGenders::normalize($data['gender'] ?? null),
                    'is_anonymous' => (bool) ($data['is_anonymous'] ?? false),
                    'whatsapp_country_code' => $whatsappFields['whatsapp_country_code'],
                    'whatsapp_phone' => $whatsappFields['whatsapp_phone'],
                    'whatsapp_verified_at' => $whatsappFields['whatsapp_verified_at'],
                    'content' => $content,
                    'note' => null,
                    'status' => DuaSubmissionStatus::Pending,
                ]));

            event(new DuaSubmissionsCreated($lockedList, $submissions, $nonPersonalCountBefore));

            return $submissions;
        });
    }

    /**
     * @param  array{content?: string, duas?: array<int, string>}  $data
     * @return list<string>
     */
    private function contents(array $data): array
    {
        $contents = $data['duas'] ?? [$data['content'] ?? ''];

        return collect($contents)
            ->map(fn (mixed $content): string => trim((string) $content))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{whatsapp_country_code: ?string, whatsapp_phone: ?string, whatsapp_verified_at: ?Carbon}
     */
    private function resolveWhatsAppFields(array $data): array
    {
        $wantsNotifications = (bool) ($data['whatsapp_notifications'] ?? false);

        if (! $wantsNotifications) {
            return [
                'whatsapp_country_code' => null,
                'whatsapp_phone' => null,
                'whatsapp_verified_at' => null,
            ];
        }

        $countryCode = (string) ($data['whatsapp_country_code'] ?? '');
        $phone = (string) ($data['whatsapp_phone'] ?? '');
        $token = (string) ($data['whatsapp_verification_token'] ?? '');

        if ($token === '') {
            throw ValidationException::withMessages([
                'whatsapp_verification_token' => 'Please verify your WhatsApp number before submitting.',
            ]);
        }

        $verified = $this->whatsappOtp->consumeVerificationToken($token);

        $submittedPhone = WhatsAppPhone::normalize($countryCode, $phone);

        if ($verified['normalized_phone'] !== $submittedPhone) {
            throw ValidationException::withMessages([
                'whatsapp_phone' => 'Verified phone number does not match the submitted number.',
            ]);
        }

        return [
            'whatsapp_country_code' => $countryCode,
            'whatsapp_phone' => $phone,
            'whatsapp_verified_at' => now(),
        ];
    }
}
