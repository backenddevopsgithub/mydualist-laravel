<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Support\Actions\CreateSupportTicketAction;
use App\Http\Requests\Support\StoreSupportTicketRequest;
use App\Http\Resources\Api\V1\Support\SupportTicketResource;
use Illuminate\Http\JsonResponse;

class SupportController extends ApiController
{
    public function store(StoreSupportTicketRequest $request, CreateSupportTicketAction $action): JsonResponse
    {
        $ticket = ($action)(
            $request->user(),
            $request->safe()->except('image'),
            $request->file('image'),
        );

        return $this->success(
            (new SupportTicketResource($ticket))->resolve(),
            'Support request submitted successfully.',
            201,
        );
    }
}
