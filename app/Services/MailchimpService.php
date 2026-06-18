<?php

namespace App\Services;

use App\Data\MailchimpMemberData;
use App\Support\MailchimpTag;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class MailchimpService extends Service
{
    public function __construct(
        private readonly MailchimpRestrictionStore $restrictions,
    ) {}

    public function addMemberToTag(MailchimpMemberData $member, MailchimpTag $tag, bool $needsValidationOnEmail = false): bool
    {
        if (! config('services.mailchimp.enabled')) {
            return false;
        }

        if (! $this->isConfigured()) {
            return false;
        }

        $email = $member->normalizedEmail();

        if ($email === '') {
            return false;
        }

        if ($needsValidationOnEmail && $this->restrictions->contains($email)) {
            return false;
        }

        $subscriberHash = $this->ensureMemberExists($member, $member->baseMergeFields());

        if ($subscriberHash === null) {
            return false;
        }

        if (! $this->applyTags($subscriberHash, $tag)) {
            return false;
        }

        if ($needsValidationOnEmail) {
            $this->restrictions->remember($email);
        }

        return true;
    }

    public function addMemberToList(MailchimpMemberData $member, MailchimpTag $tag): bool
    {
        if (! config('services.mailchimp.enabled')) {
            return false;
        }

        if (! $this->isConfigured()) {
            return false;
        }

        $email = $member->normalizedEmail();

        if ($email === '') {
            return false;
        }

        $mergeFields = $member->listCreatorMergeFields();
        $existingHash = $this->findSubscriberHash($email);

        if ($existingHash !== null) {
            if (! $this->updateMember($existingHash, $mergeFields)) {
                return false;
            }

            $subscriberHash = $existingHash;
        } else {
            $subscriberHash = $this->createMember($email, $mergeFields);

            if ($subscriberHash === null) {
                return false;
            }
        }

        return $this->applyTags($subscriberHash, $tag);
    }

    public function isConfigured(): bool
    {
        return filled(config('services.mailchimp.api_key'))
            && filled(config('services.mailchimp.server_prefix'))
            && filled(config('services.mailchimp.audience_id'));
    }

    /**
     * @param  array<string, mixed>  $mergeFields
     */
    private function ensureMemberExists(MailchimpMemberData $member, array $mergeFields): ?string
    {
        $email = $member->normalizedEmail();
        $existingHash = $this->findSubscriberHash($email);

        if ($existingHash !== null) {
            return $existingHash;
        }

        return $this->createMember($email, $mergeFields);
    }

    private function findSubscriberHash(string $email): ?string
    {
        $hash = md5(mb_strtolower($email));

        try {
            $this->client()->get($this->memberPath($hash))->throw();

            return $hash;
        } catch (RequestException $exception) {
            if ($exception->response?->status() === 404) {
                return null;
            }

            $this->logApiFailure('mailchimp.member_lookup_failed', $hash, $exception);

            return null;
        } catch (Throwable $exception) {
            $this->logApiFailure('mailchimp.member_lookup_failed', $hash, $exception);

            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $mergeFields
     */
    private function createMember(string $email, array $mergeFields): ?string
    {
        try {
            $response = $this->client()->post($this->membersPath(), [
                'email_address' => $email,
                'status' => 'subscribed',
                'merge_fields' => $mergeFields,
            ])->throw();

            return (string) ($response->json('id') ?? md5($email));
        } catch (RequestException $exception) {
            if ($exception->response?->status() === 400 && $this->memberExistsError($exception)) {
                return md5($email);
            }

            $this->logApiFailure('mailchimp.member_create_failed', md5($email), $exception);

            return null;
        } catch (Throwable $exception) {
            $this->logApiFailure('mailchimp.member_create_failed', md5($email), $exception);

            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $mergeFields
     */
    private function updateMember(string $subscriberHash, array $mergeFields): bool
    {
        try {
            $this->client()->patch($this->memberPath($subscriberHash), [
                'merge_fields' => $mergeFields,
            ])->throw();

            return true;
        } catch (Throwable $exception) {
            $this->logApiFailure('mailchimp.member_update_failed', $subscriberHash, $exception);

            return false;
        }
    }

    private function applyTags(string $subscriberHash, MailchimpTag $tag): bool
    {
        try {
            $this->client()->post($this->memberPath($subscriberHash).'/tags', [
                'tags' => $tag->payload(),
            ])->throw();

            return true;
        } catch (Throwable $exception) {
            $this->logApiFailure('mailchimp.tag_update_failed', $subscriberHash, $exception);

            return false;
        }
    }

    private function memberExistsError(RequestException $exception): bool
    {
        $title = (string) $exception->response?->json('title', '');

        return str_contains(mb_strtolower($title), 'member exists');
    }

    private function membersPath(): string
    {
        return '/lists/'.config('services.mailchimp.audience_id').'/members';
    }

    private function memberPath(string $subscriberHash): string
    {
        return $this->membersPath().'/'.$subscriberHash;
    }

    private function client()
    {
        $server = (string) config('services.mailchimp.server_prefix');

        return Http::withBasicAuth('mailchimp', (string) config('services.mailchimp.api_key'))
            ->acceptJson()
            ->baseUrl("https://{$server}.api.mailchimp.com/3.0")
            ->timeout(15);
    }

    private function logApiFailure(string $event, string $subscriberHash, Throwable $exception): void
    {
        Log::error($event, [
            'subscriber_hash' => $subscriberHash,
            'message' => $exception->getMessage(),
        ]);
    }
}
