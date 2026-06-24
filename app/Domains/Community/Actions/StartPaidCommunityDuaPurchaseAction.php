<?php

namespace App\Domains\Community\Actions;

use App\Actions\Action;
use App\Domains\Billing\Services\PurchaseService;
use App\Domains\Billing\Support\PurchaseCheckoutRedirectResolver;
use App\Enums\BillingProductCode;
use App\Enums\CommunityDuaStatus;
use App\Enums\CommunityDuaType;
use App\Models\BillingPurchase;
use App\Models\CommunityDua;
use App\Models\User;
use App\Support\WhatsAppNotificationFieldsResolver;
use Illuminate\Support\Str;

class StartPaidCommunityDuaPurchaseAction extends Action
{
    public function __construct(
        private readonly PurchaseService $purchases,
        private readonly PurchaseCheckoutRedirectResolver $redirects,
        private readonly WhatsAppNotificationFieldsResolver $whatsappFields,
    ) {}

    /**
     * @param  array{first_name: string, last_name: string, email: string, gender: string, content: string, whatsapp_notifications?: bool|null, whatsapp_country_code?: string|null, whatsapp_phone?: string|null, whatsapp_verification_token?: string|null}  $data
     * @return array{purchase: BillingPurchase, community_dua: CommunityDua}
     */
    public function handle(mixed ...$args): mixed
    {
        $data = $args[0];
        /** @var User|null $user */
        $user = $args[1] ?? null;
        $whatsapp = $this->whatsappFields->resolve($data);

        $communityDua = CommunityDua::query()->create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'gender' => $data['gender'],
            'whatsapp_country_code' => $whatsapp['whatsapp_country_code'],
            'whatsapp_phone' => $whatsapp['whatsapp_phone'],
            'whatsapp_verified_at' => $whatsapp['whatsapp_verified_at'],
            'content' => $data['content'],
            'type' => CommunityDuaType::Paid,
            'status' => CommunityDuaStatus::PendingPayment,
            'required_completions' => CommunityDuaType::Paid->requiredCompletions(),
            'completion_count' => 0,
            'is_visible' => false,
        ]);

        $purchasePayload = [
            'product_code' => BillingProductCode::CommunityDuaPaid->value,
            'idempotency_key' => (string) Str::uuid(),
            'community_dua_id' => $communityDua->id,
            'metadata' => [
                'checkout_source' => 'community_dua_form',
                'community_dua_email' => $communityDua->email,
            ],
        ];

        $result = $this->purchases->create($purchasePayload, $user);

        /** @var BillingPurchase $purchase */
        $purchase = $result['purchase']->fresh(['product']);

        $purchase->forceFill([
            'metadata' => array_merge($purchase->metadata ?? [], [
                'success_url' => $this->redirects->successUrl($purchase),
                'failure_url' => $this->redirects->failureUrl($purchase),
            ]),
        ])->save();

        return [
            'purchase' => $purchase->fresh(['product']),
            'community_dua' => $communityDua,
        ];
    }
}
