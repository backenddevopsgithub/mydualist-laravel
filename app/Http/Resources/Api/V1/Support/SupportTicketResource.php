<?php

namespace App\Http\Resources\Api\V1\Support;

use App\Http\Resources\Api\V1\ApiResource;
use App\Models\SupportTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/** @mixin SupportTicket */
class SupportTicketResource extends ApiResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reason' => $this->reason,
            'email' => $this->email,
            'first_name' => $this->first_name,
            'surname' => $this->surname,
            'comments' => $this->comments,
            'image_url' => $this->image_path
                ? Storage::disk('public')->url($this->image_path)
                : null,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
