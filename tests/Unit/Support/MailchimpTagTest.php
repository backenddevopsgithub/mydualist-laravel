<?php

use App\Support\MailchimpTag;

test('mailchimp tags mirror wordpress labels', function () {
    expect(MailchimpTag::DuaSubmitterReview->label())->toBe('Review - For Dua Submitters')
        ->and(MailchimpTag::ListCreatorsReview->label())->toBe('Review - For Dua List Creators')
        ->and(MailchimpTag::ListCreator->label())->toBe('Dua List Creator')
        ->and(MailchimpTag::FooterNewsletter->label())->toBe('MDL - Articles - Footer Sign Ups')
        ->and(MailchimpTag::FiveDuaSubmissions->label())->toBe('Dua Submission = 5')
        ->and(MailchimpTag::Submitter->label())->toBe('Dua Submitter')
        ->and(MailchimpTag::RamadanDuaTracking->label())->toBe('Ramadan Dua Track - Subscribers');
});

test('mailchimp tag payload uses active status', function () {
    expect(MailchimpTag::FooterNewsletter->payload())->toBe([
        [
            'name' => 'MDL - Articles - Footer Sign Ups',
            'status' => 'active',
        ],
    ]);
});
