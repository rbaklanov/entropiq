<?php

namespace App\Http\Resources;

use App\Models\AiAdvice;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AiAdvice */
class AiAdviceResource extends JsonResource
{
    private bool $teaser = false;

    private bool $locked = false;

    public function forViewer(bool $premium, ?int $openAdviceId): static
    {
        $this->teaser = ! $premium;
        $this->locked = ! $premium && $openAdviceId !== null && $openAdviceId !== $this->id;

        return $this;
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->locked ? null : $this->title,
            'body' => $this->teaser ? null : $this->body,
            'basis_data' => $this->teaser ? null : $this->basis_data,
            'rating' => $this->rating,
            'is_read' => $this->is_read,
            'locked' => $this->locked,
            'generated_at' => $this->generated_at->toIso8601String(),
            'created_at' => $this->created_at,
        ];
    }
}
