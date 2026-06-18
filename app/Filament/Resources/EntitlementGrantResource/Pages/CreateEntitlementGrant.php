<?php

namespace App\Filament\Resources\EntitlementGrantResource\Pages;

use App\Domains\Billing\Services\EntitlementGrantManagementService;
use App\Enums\EntitlementProductType;
use App\Filament\Resources\EntitlementGrantResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class CreateEntitlementGrant extends CreateRecord
{
    protected static string $resource = EntitlementGrantResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        /** @var User $admin */
        $admin = auth()->user();
        $user = User::query()->findOrFail($data['user_id']);
        $product = EntitlementProductType::from($data['product']);

        return app(EntitlementGrantManagementService::class)->createGrant(
            $user,
            $product,
            $admin,
            $data['dua_list_id'] ?? null,
            isset($data['expires_at']) ? Carbon::parse($data['expires_at']) : null,
        );
    }

    protected function getRedirectUrl(): string
    {
        return EntitlementGrantResource::getUrl('index');
    }
}
