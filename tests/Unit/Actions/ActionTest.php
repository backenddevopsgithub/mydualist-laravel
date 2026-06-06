<?php

use App\Actions\Action;
use App\Exceptions\DomainException;

test('domain exception returns api friendly payload', function () {
    $exception = new DomainException('Invalid quota', 'quota_exceeded');

    $response = $exception->render(request());

    expect($response->getStatusCode())->toBe(422)
        ->and($response->getData(true))->toMatchArray([
            'message' => 'Invalid quota',
            'error_code' => 'quota_exceeded',
            'errors' => null,
        ]);
});

test('action base class supports invoke pattern', function () {
    $action = new class extends Action
    {
        public function handle(mixed ...$args): mixed
        {
            return $args[0] ?? null;
        }
    };

    expect($action('test'))->toBe('test')
        ->and($action())->toBeNull();
});
