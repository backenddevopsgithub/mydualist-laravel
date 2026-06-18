<?php

namespace App\Support;

/**
 * Mailchimp audience tags mirrored from WordPress Integration\Mailchimp::get_tag().
 */
enum MailchimpTag: string
{
    case DuaSubmitterReview = 'dua_submitter';
    case ListCreatorsReview = 'list_creators';
    case ListCreator = 'list_creator';
    case FooterNewsletter = 'footer_newsletter';
    case FiveDuaSubmissions = '5_dua_submissions';
    case Submitter = 'submitter';
    case RamadanDuaTracking = 'ramadan_dua_tracking';

    public function label(): string
    {
        return match ($this) {
            self::DuaSubmitterReview => 'Review - For Dua Submitters',
            self::ListCreatorsReview => 'Review - For Dua List Creators',
            self::ListCreator => 'Dua List Creator',
            self::FooterNewsletter => 'MDL - Articles - Footer Sign Ups',
            self::FiveDuaSubmissions => 'Dua Submission = 5',
            self::Submitter => 'Dua Submitter',
            self::RamadanDuaTracking => 'Ramadan Dua Track - Subscribers',
        };
    }

    /**
     * @return list<array{name: string, status: string}>
     */
    public function payload(): array
    {
        return [
            [
                'name' => $this->label(),
                'status' => 'active',
            ],
        ];
    }
}
