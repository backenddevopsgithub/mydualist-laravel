<?php

namespace App\Filament\Resources\BillingPurchaseResource\Pages;

use App\Domains\Billing\Actions\MarkBillingPurchaseFulfilledAction;
use App\Domains\Billing\Actions\MarkBillingPurchaseRefundedAction;
use App\Domains\Billing\Actions\RetryBillingPurchaseFulfillmentAction;
use App\Filament\Resources\BillingPurchaseResource;
use App\Filament\Resources\BillingPurchaseResource\Concerns\InteractsWithBillingPurchaseActions;
use App\Filament\Resources\StripePaymentResource;
use App\Models\BillingPurchase;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewBillingPurchase extends ViewRecord
{
    use InteractsWithBillingPurchaseActions;

    protected static string $resource = BillingPurchaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->retryFulfillmentHeaderAction(),
            $this->markFulfilledHeaderAction(),
            $this->markRefundedHeaderAction(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->record($this->record->load(['product', 'user', 'stripePayment', 'entitlementGrants']))
            ->schema([
                Section::make('Purchase')
                    ->schema([
                        TextEntry::make('id'),
                        TextEntry::make('user.email')->label('User')->placeholder('Guest'),
                        TextEntry::make('product.name')->label('Product'),
                        TextEntry::make('provider')
                            ->badge()
                            ->state(fn (BillingPurchase $record): string => $record->provider())
                            ->formatStateUsing(fn (string $state): string => str($state)->headline()->toString()),
                        TextEntry::make('amount_minor')
                            ->label('Amount')
                            ->formatStateUsing(fn (BillingPurchase $record): string => number_format($record->amount_minor / 100, 2).' '.strtoupper($record->currency)),
                        TextEntry::make('status')
                            ->badge()
                            ->formatStateUsing(fn ($state): string => str($state->value)->headline()->toString()),
                        TextEntry::make('fulfillment_status')
                            ->label('Fulfillment status')
                            ->badge()
                            ->state(fn (BillingPurchase $record): string => $record->fulfillmentStatus())
                            ->formatStateUsing(fn (string $state): string => str($state)->headline()->toString()),
                        TextEntry::make('payment_intent_id')->placeholder('—'),
                        TextEntry::make('wp_order_id')->label('WooCommerce order ID')->placeholder('—'),
                        TextEntry::make('fulfilled_at')->dateTime()->placeholder('—'),
                        TextEntry::make('refunded_at')->dateTime()->placeholder('—'),
                        TextEntry::make('disputed_at')->dateTime()->placeholder('—'),
                        TextEntry::make('failure_reason')->placeholder('—')->columnSpanFull(),
                        TextEntry::make('created_at')->dateTime(),
                    ])
                    ->columns(2),
                Section::make('Related records')
                    ->schema([
                        TextEntry::make('stripe_payment_link')
                            ->label('Stripe payment')
                            ->state(function (BillingPurchase $record): string {
                                $payment = $record->stripePayment;

                                if ($payment === null) {
                                    return 'No linked Stripe payment record';
                                }

                                return 'Payment #'.$payment->id;
                            })
                            ->url(function (BillingPurchase $record): ?string {
                                $payment = $record->stripePayment;

                                return $payment === null
                                    ? null
                                    : StripePaymentResource::getUrl('edit', ['record' => $payment]);
                            })
                            ->openUrlInNewTab()
                            ->color(fn (BillingPurchase $record): ?string => $record->stripePayment === null ? 'gray' : 'primary'),
                        TextEntry::make('entitlement_grants_count')
                            ->label('Entitlement grants')
                            ->state(fn (BillingPurchase $record): string => (string) $record->entitlementGrants->count().' grant(s) — see relation table below'),
                    ])
                    ->columns(1),
            ]);
    }

    private function retryFulfillmentHeaderAction(): Action
    {
        return Action::make('retryFulfillment')
            ->label('Retry fulfillment')
            ->icon('heroicon-o-arrow-path')
            ->color('warning')
            ->visible(fn (): bool => static::canRetryFulfillment($this->record))
            ->authorize('update')
            ->requiresConfirmation()
            ->action(function (): void {
                static::runBillingPurchaseAction(
                    fn () => app(RetryBillingPurchaseFulfillmentAction::class)($this->record),
                    'Fulfillment retried successfully.',
                );

                $this->refreshFormData(['fulfilled_at', 'status']);
            });
    }

    private function markFulfilledHeaderAction(): Action
    {
        return Action::make('markFulfilled')
            ->label('Mark fulfilled')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->visible(fn (): bool => static::canMarkFulfilled($this->record))
            ->authorize('update')
            ->requiresConfirmation()
            ->action(function (): void {
                /** @var User $actor */
                $actor = auth()->user();

                static::runBillingPurchaseAction(
                    fn () => app(MarkBillingPurchaseFulfilledAction::class)($this->record, $actor),
                    'Purchase marked as fulfilled.',
                );

                $this->refreshFormData(['fulfilled_at']);
            });
    }

    private function markRefundedHeaderAction(): Action
    {
        return Action::make('markRefunded')
            ->label('Mark refunded')
            ->icon('heroicon-o-receipt-refund')
            ->color('danger')
            ->visible(fn (): bool => static::canMarkRefunded($this->record))
            ->authorize('update')
            ->requiresConfirmation()
            ->action(function (): void {
                /** @var User $actor */
                $actor = auth()->user();

                static::runBillingPurchaseAction(
                    fn () => app(MarkBillingPurchaseRefundedAction::class)($this->record, $actor),
                    'Purchase marked as refunded.',
                );

                $this->refreshFormData(['refunded_at']);
            });
    }
}
