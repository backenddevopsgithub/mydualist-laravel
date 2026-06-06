<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Testing\TestResponse;

abstract class TestCase extends BaseTestCase
{
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
