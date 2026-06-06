<?php

use App\Enums\UserRole;

test('user role enum exposes expected values', function () {
    expect(UserRole::values())->toBe(['user', 'admin']);
});

test('user role enum contains check works', function () {
    expect(UserRole::contains('admin'))->toBeTrue()
        ->and(UserRole::contains('invalid'))->toBeFalse();
});
