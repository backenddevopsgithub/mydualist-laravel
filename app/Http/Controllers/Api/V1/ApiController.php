<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\AbstractPaginator;

abstract class ApiController extends Controller
{
    /**
     * @param  array<string, mixed>|null  $data
     */
    protected function success(?array $data = null, string $message = 'Success', int $status = 200): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    /**
     * @param  class-string<JsonResource>  $resourceClass
     */
    protected function paginated(AbstractPaginator $paginator, string $resourceClass, string $message = 'Success'): JsonResponse
    {
        $resolved = $resourceClass::collection($paginator)->response()->getData(true);

        return response()->json([
            'message' => $message,
            'data' => $resolved['data'] ?? [],
            'meta' => $resolved['meta'] ?? null,
            'links' => $resolved['links'] ?? null,
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $errors
     */
    protected function error(string $message, int $status = 400, ?string $errorCode = null, ?array $errors = null): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'error_code' => $errorCode,
            'errors' => $errors,
        ], $status);
    }
}
