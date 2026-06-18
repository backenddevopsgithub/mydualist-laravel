<?php

namespace App\Support;

use App\Models\DuaList;

class TrackableDonationLink
{
    public static function forList(DuaList $duaList, string $donationLink): string
    {
        return route('fundraising.redirect', [
            'redirecting' => $donationLink,
            'list_id' => $duaList->wp_post_id ?: $duaList->id,
            'tracking' => 'native',
            'bypass' => 'false',
        ]);
    }
}
