<?php

namespace App\Http\Resources;

use App\Models\RecurringRule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin RecurringRule */
class RecurringRuleResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'amount' => $this->amount,
            'currency_code' => $this->currency_code,
            'comment' => $this->comment,
            'interval' => $this->interval->value,
            'next_run_at' => $this->next_run_at->toDateString(),
            'is_active' => $this->is_active,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'created_at' => $this->created_at,
        ];
    }
}
