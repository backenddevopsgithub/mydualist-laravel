<?php

namespace App\Http\Controllers\Api\V1\Billing;

use App\Domains\Billing\Exceptions\IdempotencyConflictException;
use App\Domains\Billing\Services\PurchaseService;
use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\Billing\StorePurchaseRequest;
use App\Http\Resources\Api\V1\Billing\PurchaseCheckoutResource;
use App\Http\Resources\Api\V1\Billing\PurchaseClientSecretResource;
use App\Http\Resources\Api\V1\Billing\PurchasePaymentStatusResource;
use App\Http\Resources\Api\V1\Billing\PurchaseResource;
use App\Models\BillingPurchase;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class PurchaseController extends ApiController
{
    public function store(StorePurchaseRequest $request, PurchaseService $purchases): JsonResponse
    {
        try {
            $result = $purchases->create($request->payload(), $request->user());
        } catch (AuthenticationException $exception) {
            return $this->error($exception->getMessage(), 401, 'authentication_required');
        } catch (IdempotencyConflictException $exception) {
            return $this->error($exception->getMessage(), 409, 'idempotency_conflict');
        } catch (RuntimeException $exception) {
            return $this->error($exception->getMessage(), 503, 'stripe_unavailable');
        }

        /** @var BillingPurchase $purchase */
        $purchase = $result['purchase'];
        $created = (bool) $result['created'];

        $resource = (new PurchaseResource($purchase))->resolve();
        $resource['client_secret'] = $result['client_secret'];

        return $this->success(
            $resource,
            $created ? 'Purchase created.' : 'Purchase already exists for idempotency key.',
            $created ? 201 : 200,
        );
    }

    public function show(BillingPurchase $purchase, PurchaseService $purchases): JsonResponse
    {
        try {
            $accessible = $purchases->findAccessible($purchase, request()->user());
        } catch (AuthenticationException $exception) {
            return $this->error($exception->getMessage(), 401, 'authentication_required');
        } catch (AuthorizationException $exception) {
            return $this->error($exception->getMessage(), 403, 'purchase_access_denied');
        }

        return $this->success(
            (new PurchaseCheckoutResource($accessible))->resolve(),
            'Purchase retrieved.',
        );
    }

    public function clientSecret(BillingPurchase $purchase, PurchaseService $purchases): JsonResponse
    {
        try {
            $secret = $purchases->clientSecretFor($purchase, request()->user());
        } catch (AuthenticationException $exception) {
            return $this->error($exception->getMessage(), 401, 'authentication_required');
        } catch (AuthorizationException $exception) {
            return $this->error($exception->getMessage(), 403, 'purchase_access_denied');
        } catch (RuntimeException $exception) {
            return $this->error($exception->getMessage(), 409, 'purchase_not_payable');
        }

        return $this->success(
            (new PurchaseClientSecretResource($secret))->resolve(),
            'Client secret retrieved.',
        );
    }

    public function paymentStatus(BillingPurchase $purchase, PurchaseService $purchases): JsonResponse
    {
        try {
            $status = $purchases->paymentStatusFor($purchase, request()->user());
        } catch (AuthenticationException $exception) {
            return $this->error($exception->getMessage(), 401, 'authentication_required');
        } catch (AuthorizationException $exception) {
            return $this->error($exception->getMessage(), 403, 'purchase_access_denied');
        }

        return $this->success(
            (new PurchasePaymentStatusResource($status))->resolve(),
            'Payment status retrieved.',
        );
    }
}
