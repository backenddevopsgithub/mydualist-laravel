<?php

namespace App\Filament\Resources\BillingPurchaseResource\Concerns;

use App\Domains\Billing\Actions\MarkBillingPurchaseFulfilledAction;
use App\Domains\Billing\Actions\MarkBillingPurchaseRefundedAction;
use App\Domains\Billing\Actions\RefundBillingPurchaseViaStripeAction;
use App\Domains\Billing\Actions\RetryBillingPurchaseFulfillmentAction;
use App\Models\BillingPurchase;
use App\Models\User;
use App\Support\Impersonation;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ViewAction;
use RuntimeException;

trait InteractsWithBillingPurchaseActions
{
  /**
   * @return array<int, Action>
   */
    protected static function billingPurchaseTableActions(): array
    {
        return [
            ViewAction::make(),
            static::retryFulfillmentTableAction(),
            static::markFulfilledTableAction(),
            static::refundViaStripeTableAction(),
            static::markRefundedTableAction(),
        ];
    }

    protected static function retryFulfillmentTableAction(): Action
    {
        return Action::make('retryFulfillment')
            ->label('Retry fulfillment')
            ->icon('heroicon-o-arrow-path')
            ->color('warning')
            ->visible(fn (BillingPurchase $record): bool => static::canRetryFulfillment($record) && ! Impersonation::isActive())
            ->authorize('update')
            ->requiresConfirmation()
            ->action(function (BillingPurchase $record): void {
                static::runBillingPurchaseAction(
                    fn () => app(RetryBillingPurchaseFulfillmentAction::class)($record),
                    'Fulfillment retried successfully.',
                );
            });
    }

    protected static function markFulfilledTableAction(): Action
    {
        return Action::make('markFulfilled')
            ->label('Mark fulfilled')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->visible(fn (BillingPurchase $record): bool => static::canMarkFulfilled($record) && ! Impersonation::isActive())
            ->authorize('update')
            ->requiresConfirmation()
            ->action(function (BillingPurchase $record): void {
                /** @var User $actor */
                $actor = auth()->user();

                static::runBillingPurchaseAction(
                    fn () => app(MarkBillingPurchaseFulfilledAction::class)($record, $actor),
                    'Purchase marked as fulfilled.',
                );
            });
    }

    protected static function refundViaStripeTableAction(): Action
    {
        return Action::make('refundViaStripe')
            ->label('Refund in Stripe')
            ->icon('heroicon-o-credit-card')
            ->color('danger')
            ->visible(fn (BillingPurchase $record): bool => static::canRefundViaStripe($record) && ! Impersonation::isActive())
            ->authorize('update')
            ->requiresConfirmation()
            ->modalHeading('Refund in Stripe')
            ->modalDescription('Creates a full refund in Stripe for this payment intent and records it locally.')
            ->action(function (BillingPurchase $record): void {
                /** @var User $actor */
                $actor = auth()->user();

                static::runBillingPurchaseAction(
                    fn () => app(RefundBillingPurchaseViaStripeAction::class)($record, $actor),
                    'Stripe refund created successfully.',
                );
            });
    }

    protected static function markRefundedTableAction(): Action
    {
        return Action::make('markRefunded')
            ->label('Mark refunded (local only)')
            ->icon('heroicon-o-receipt-refund')
            ->color('gray')
            ->visible(fn (BillingPurchase $record): bool => static::canMarkRefunded($record) && ! Impersonation::isActive())
            ->authorize('update')
            ->requiresConfirmation()
            ->modalHeading('Mark refunded (local only)')
            ->modalDescription('Records a refund in Laravel only. Does not call Stripe or WooCommerce. Use for WooCommerce imports or manual reconciliation.')
            ->action(function (BillingPurchase $record): void {
                /** @var User $actor */
                $actor = auth()->user();

                static::runBillingPurchaseAction(
                    fn () => app(MarkBillingPurchaseRefundedAction::class)($record, $actor),
                    'Purchase marked as refunded locally.',
                );
            });
    }

    protected static function canRetryFulfillment(BillingPurchase $record): bool
    {
        return $record->isUnfulfilled() && ! $record->isRefunded();
    }

    protected static function canMarkFulfilled(BillingPurchase $record): bool
    {
        return $record->isCompleted() && ! $record->isFulfilled();
    }

    protected static function canMarkRefunded(BillingPurchase $record): bool
    {
        return ! $record->isRefunded();
    }

    protected static function canRefundViaStripe(BillingPurchase $record): bool
    {
        return ! $record->isRefunded()
            && $record->isCompleted()
            && filled($record->payment_intent_id);
    }

    /**
     * @param  callable(): BillingPurchase  $callback
     */
    protected static function runBillingPurchaseAction(callable $callback, string $successMessage): void
    {
        try {
            $callback();

            Notification::make()
                ->title($successMessage)
                ->success()
                ->send();
        } catch (RuntimeException $exception) {
            Notification::make()
                ->title('Action failed')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }
}
