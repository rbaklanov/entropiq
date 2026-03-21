<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRecurringRuleRequest;
use App\Http\Requests\UpdateRecurringRuleRequest;
use App\Http\Resources\RecurringRuleResource;
use App\Models\RecurringRule;
use App\Services\RecurringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RecurringRulesController extends Controller
{
    public function __construct(
        private readonly RecurringService $recurringService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $rules = $request->user()
            ->recurringRules()
            ->with('category')
            ->orderByDesc('created_at')
            ->paginate(20);

        return RecurringRuleResource::collection($rules);
    }

    public function store(StoreRecurringRuleRequest $request): RecurringRuleResource
    {
        $rule = $this->recurringService->createRule(
            $request->user()->id,
            $request->validated(),
        );

        $rule->load('category');

        return new RecurringRuleResource($rule);
    }

    public function show(Request $request, RecurringRule $recurringRule): RecurringRuleResource
    {
        abort_unless($recurringRule->user_id === $request->user()->id, 403);

        $recurringRule->load('category');

        return new RecurringRuleResource($recurringRule);
    }

    public function update(UpdateRecurringRuleRequest $request, RecurringRule $recurringRule): RecurringRuleResource
    {
        abort_unless($recurringRule->user_id === $request->user()->id, 403);

        $recurringRule->update($request->validated());
        $recurringRule->load('category');

        return new RecurringRuleResource($recurringRule);
    }

    public function destroy(Request $request, RecurringRule $recurringRule): JsonResponse
    {
        abort_unless($recurringRule->user_id === $request->user()->id, 403);

        $recurringRule->delete();

        return response()->json(['message' => __('recurring.deleted')]);
    }
}
