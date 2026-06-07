<?php

namespace App\Filament\Resources\StripePaymentResource\Pages;

use App\Filament\Resources\StripePaymentResource;
use Filament\Resources\Pages\ListRecords;

class ListStripePayments extends ListRecords
{
    protected static string $resource = StripePaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
