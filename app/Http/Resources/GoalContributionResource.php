<?php

namespace App\Http\Resources;

use App\Models\GoalContribution;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin GoalContribution */
class GoalContributionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'goal_id' => $this->goal_id,
            'transaction_id' => $this->transaction_id,
            'amount' => $this->amount,
            'date' => $this->date->toDateString(),
            'created_at' => $this->created_at,
        ];
    }
}
