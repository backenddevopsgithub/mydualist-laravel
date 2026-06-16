<?php

namespace App\Http\Requests\Billing;

use App\Enums\BillingProductCode;
use App\Enums\BillingProductScope;
use App\Models\BillingProduct;
use App\Models\DuaList;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StartPurchaseCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'product_code' => ['required', 'string', Rule::in(BillingProductCode::values())],
            'dua_list_id' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $productCode = BillingProductCode::from($this->string('product_code')->toString());
            $product = BillingProduct::query()
                ->where('code', $productCode->value)
                ->where('active', true)
                ->first();

            if (! $product) {
                $validator->errors()->add('product_code', 'The selected product is not available.');

                return;
            }

            if ($product->scope === BillingProductScope::List && $this->input('dua_list_id') === null) {
                $validator->errors()->add('dua_list_id', 'Please select a list for this upgrade.');
            }

            $duaListId = $this->input('dua_list_id');

            if ($duaListId !== null) {
                $list = DuaList::query()->find($duaListId);

                if (! $list || $list->user_id !== Auth::id()) {
                    $validator->errors()->add('dua_list_id', 'You can only upgrade your own lists.');
                }
            }
        });
    }

    /**
     * @return array{product_code: string, dua_list_id?: int|null, metadata?: array<string, mixed>}
     */
    public function checkoutPayload(): array
    {
        return [
            'product_code' => $this->string('product_code')->toString(),
            'dua_list_id' => $this->input('dua_list_id'),
            'metadata' => [
                'upgrade_source' => 'dashboard_upgrade',
            ],
        ];
    }
}
