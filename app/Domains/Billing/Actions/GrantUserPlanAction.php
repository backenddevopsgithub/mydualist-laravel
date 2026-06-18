<?php

namespace App\Domains\Billing\Actions;

use App\Domains\Billing\Services\EntitlementGrantManagementService;
use App\Domains\Billing\Services\PurchaseFulfillmentService;
use App\Enums\BillingPurchaseStatus;
use App\Enums\EntitlementProductType;
use App\Models\BillingProduct;
use App\Models\BillingPurchase;
use App\Models\EntitlementGrant;
use App\Models\User;
use App\Support\Impersonation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class GrantUserPlanAction
{
    public function __construct(
        private readonly EntitlementGrantManagementService $grantManagement,
        private readonly PurchaseFulfillmentService $fulfillment,
    ) {}

    /**
     * @return array{purchase: BillingPurchase, grant: EntitlementGrant}
     */
    public function __invoke(
        User $user,
        EntitlementProductType $product,
        User $grantedBy,
        ?int $duaListId = null,
    ): array {
        Impersonation::ensureSensitiveActionAllowed();

        $this->grantManagement->assertGrantable($user, $product, $duaListId);

        $billingProduct = BillingProduct::query()
            ->where('code', $product->billingProductCode()->value)
            ->where('active', true)
            ->first();

        if ($billingProduct === null) {
            throw new RuntimeException('The selected billing product is not available.');
        }

        return DB::transaction(function () use ($user, $product, $grantedBy, $duaListId, $billingProduct): array {
            $purchase = BillingPurchase::query()->create([
                'billing_product_id' => $billingProduct->id,
                'user_id' => $user->id,
                'dua_list_id' => $duaListId,
                'status' => BillingPurchaseStatus::Succeeded,
                'amount_minor' => $billingProduct->amount_minor,
                'currency' => $billingProduct->currency ?? (string) config('billing.currency', 'gbp'),
                'idempotency_key' => 'admin:grant-plan:'.Str::uuid(),
                'metadata' => [
                    'source' => 'admin',
                    'admin_action' => 'grant_plan',
                    'product_type' => $product->value,
                    'granted_by' => $grantedBy->id,
                    'granted_by_email' => $grantedBy->email,
                ],
            ]);

            $this->fulfillment->fulfill($purchase->fresh(['product', 'user']));

            $purchase->refresh();

            if ($purchase->fulfilled_at === null) {
                throw new RuntimeException('Plan grant fulfillment did not complete.');
            }

            $grant = EntitlementGrant::query()
                ->where('source_purchase_id', $purchase->id)
                ->first();

            if ($grant === null) {
                throw new RuntimeException('Entitlement grant was not created for this plan.');
            }

            return [
                'purchase' => $purchase,
                'grant' => $grant,
            ];
        });
    }
}
