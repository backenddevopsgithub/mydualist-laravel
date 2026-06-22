<?php

namespace App\Filament\Resources\StripePaymentResource\Pages;

use App\Filament\Resources\BillingPurchaseResource;
use App\Filament\Resources\StripePaymentResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListStripePayments extends ListRecords
{
    protected static string $resource = StripePaymentResource::class;

    public function getSubheading(): ?string
    {
        return 'Historical legacy Stripe Checkout sessions only. For billing history after migration, use Purchases.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('viewPurchases')
                ->label('View purchases')
                ->icon('heroicon-o-shopping-cart')
                ->url(BillingPurchaseResource::getUrl('index'))
                ->color('primary'),
        ];
    }
}
