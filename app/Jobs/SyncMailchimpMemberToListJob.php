<?php

namespace App\Jobs;

use App\Data\MailchimpMemberData;
use App\Services\MailchimpService;
use App\Support\MailchimpTag;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncMailchimpMemberToListJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [60, 300];
    }

    /**
     * @param  array{
     *     email: string,
     *     first_name?: ?string,
     *     last_name?: ?string,
     *     category?: ?string,
     *     list_name?: ?string,
     *     start_date?: ?string,
     *     end_date?: ?string,
     *     submission_count?: ?int
     * }  $member
     */
    public function __construct(
        public array $member,
        public MailchimpTag $tag,
    ) {}

    public function handle(MailchimpService $mailchimp): void
    {
        if (! config('services.mailchimp.enabled')) {
            return;
        }

        $mailchimp->addMemberToList(
            new MailchimpMemberData(
                email: $this->member['email'],
                firstName: $this->member['first_name'] ?? null,
                lastName: $this->member['last_name'] ?? null,
                category: $this->member['category'] ?? null,
                listName: $this->member['list_name'] ?? null,
                startDate: $this->member['start_date'] ?? null,
                endDate: $this->member['end_date'] ?? null,
                submissionCount: $this->member['submission_count'] ?? null,
            ),
            $this->tag,
        );
    }
}
