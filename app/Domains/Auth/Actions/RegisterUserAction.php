<?php

namespace App\Domains\Auth\Actions;

use App\Actions\Action;
use App\Domains\Auth\DTOs\AuthTokenData;
use App\Domains\Auth\Services\AuthTokenService;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;

class RegisterUserAction extends Action
{
    public function __construct(
        private readonly AuthTokenService $authTokenService,
    ) {
    }

    /**
     * @param  array{name?: string, first_name?: string, last_name?: string, email: string, password: string, device_name?: string|null}  $data
     */
    public function handle(mixed ...$args): mixed
    {
        $data = $args[0];

        return DB::transaction(function () use ($data) {
            $user = User::query()->create([
                'name' => $data['name'] ?? trim(($data['first_name'] ?? '').' '.($data['last_name'] ?? '')),
                'first_name' => $data['first_name'] ?? null,
                'last_name' => $data['last_name'] ?? null,
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => UserRole::User,
                'status' => UserStatus::Active,
            ]);

            event(new Registered($user));

            return $this->authTokenService->issue(
                $user,
                $data['device_name'] ?? 'api-token',
            );
        });
    }
}
