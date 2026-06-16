<?php

namespace App\Domains\Community\Actions;

use App\Actions\Action;
use App\Domains\Billing\Services\StripeCheckoutService;
use App\Enums\CommunityDuaStatus;
use App\Enums\CommunityDuaType;
use App\Models\CommunityDua;
use App\Models\StripePayment;
use App\Models\User;

/**
 * @deprecated Use StartPaidCommunityDuaPurchaseAction and embedded checkout instead.
 */
class StartPaidCommunityDuaCheckoutAction extends Action
{
    public function __construct(
        private readonly StripeCheckoutService $stripeCheckoutService,
    ) {}

    /**
     * @param  array{first_name: string, last_name: string, email: string, gender: string, content: string}  $data
     * @return array{session: array{id: string, url: string, amount_total: int|null, currency: string|null}, payment: StripePayment, community_dua: CommunityDua}
     */
    public function handle(mixed ...$args): mixed
    {
        $data = $args[0];
        $user = $args[1] ?? null;

        $communityDua = CommunityDua::query()->create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'gender' => $data['gender'],
            'content' => $data['content'],
            'type' => CommunityDuaType::Paid,
            'status' => CommunityDuaStatus::PendingPayment,
            'required_completions' => CommunityDuaType::Paid->requiredCompletions(),
            'completion_count' => 0,
            'is_visible' => false,
        ]);

        $session = $this->stripeCheckoutService->createCommunityDuaCheckout(
            $communityDua,
            $user,
        );

        $payment = StripePayment::query()->updateOrCreate(
            ['stripe_checkout_session_id' => $session['id']],
            [
                'user_id' => $user?->id,
                'amount_total' => $session['amount_total'],
                'currency' => $session['currency'],
                'status' => StripePayment::STATUS_PENDING,
                'metadata' => [
                    'entitlement' => 'community_dua_paid',
                    'community_dua_id' => $communityDua->id,
                ],
            ],
        );

        return [
            'session' => $session,
            'payment' => $payment,
            'community_dua' => $communityDua,
        ];
    }
}
