<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

abstract class ApiResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function collectionWithMeta(mixed $resource, array $meta = []): array
    {
        return [
            'data' => static::collection($resource),
            'meta' => $meta,
        ];
    }
}
