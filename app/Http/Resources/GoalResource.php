<?php

namespace App\Http\Resources;

use App\Models\Goal;
use App\Services\GoalCalculationService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Goal */
class GoalResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $calc = app(GoalCalculationService::class);
        $monthsLeft = $calc->getMonthsLeft($this->resource);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type->value,
            'status' => $this->status->value,
            'icon' => $this->icon,
            'target_amount' => $this->target_amount,
            'current_amount' => $this->current_amount,
            'currency_code' => $this->currency_code,
            'started_at' => $this->started_at->toDateString(),
            'target_date' => $this->target_date?->toDateString(),
            'progress_percent' => $this->progressPercent(),
            'remaining_amount' => $this->remainingAmount(),
            'monthly_payment' => $calc->requiredMonthlyPayment(
                $this->target_amount,
                $this->current_amount,
                $monthsLeft,
            ),
            'monthly_payment_with_inflation' => $calc->requiredMonthlyPaymentWithInflation(
                $this->target_amount,
                $this->current_amount,
                $monthsLeft,
            ),
            'contributions' => GoalContributionResource::collection($this->whenLoaded('contributions')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
