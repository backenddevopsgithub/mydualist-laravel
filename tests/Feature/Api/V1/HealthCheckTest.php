<?php

test('api v1 health endpoint returns ok', function () {
    $response = $this->getJson('/api/v1/health');

    $response->assertOk()
        ->assertJson([
            'status' => 'ok',
            'version' => 'v1',
            'service' => config('mydualist.name'),
        ]);
});

test('application health endpoint is available', function () {
    $this->get('/up')->assertOk();
});
