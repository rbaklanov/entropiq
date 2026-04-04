<?php

namespace App\Http\Resources;

use App\Models\AiAdvice;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AiAdvice */
class AiAdviceResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'body' => $this->body,
            'basis_data' => $this->basis_data,
            'rating' => $this->rating,
            'is_read' => $this->is_read,
            'generated_at' => $this->generated_at->toIso8601String(),
            'created_at' => $this->created_at,
        ];
    }
}
