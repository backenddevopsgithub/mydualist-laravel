<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Tests\Support\InteractsWithAuth;

abstract class TestCase extends BaseTestCase
{
    use InteractsWithAuth;

    protected function setUp(): void
    {
        parent::setUp();

        config(['sanctum.stateful' => []]);
        $this->withoutMiddleware(EnsureFrontendRequestsAreStateful::class);
    }
    protected function assertApiSuccess(TestResponse $response, int $status = 200): TestResponse
    {
        return $response
            ->assertStatus($status)
            ->assertJsonStructure(['message', 'data']);
    }

    /**
     * @param  array<string, mixed>  $structure
     */
    protected function assertApiError(
        TestResponse $response,
        int $status,
        ?array $structure = ['message'],
    ): TestResponse {
        return $response
            ->assertStatus($status)
            ->assertJsonStructure($structure);
    }
}
