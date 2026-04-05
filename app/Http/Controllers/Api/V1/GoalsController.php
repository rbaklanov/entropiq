<?php

namespace App\Http\Controllers\Api\V1;

use App\Contracts\SubscriptionServiceInterface;
use App\Enums\GoalStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ContributeRequest;
use App\Http\Requests\StoreGoalRequest;
use App\Http\Requests\UpdateGoalRequest;
use App\Http\Resources\GoalContributionResource;
use App\Http\Resources\GoalResource;
use App\Models\Goal;
use App\Services\GoalCalculationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class GoalsController extends Controller
{
    public function __construct(
        private readonly GoalCalculationService $calculationService,
        private readonly SubscriptionServiceInterface $subscriptionService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $goals = $request->user()
            ->goals()
            ->orderByDesc('created_at')
            ->get();

        return GoalResource::collection($goals);
    }

    public function store(StoreGoalRequest $request): GoalResource|JsonResponse
    {
        if (! $this->subscriptionService->canCreateGoal($request->user())) {
            return response()->json([
                'message' => __('goals.limit_reached'),
                'upgrade_url' => route('settings.subscription'),
            ], 403);
        }

        $data = $request->safe()->except('initial_amount');
        $data['started_at'] = now()->toDateString();
        $data['status'] = GoalStatus::Active;
        $data['current_amount'] = $request->validated('initial_amount', 0);

        $goal = $request->user()->goals()->create($data);

        if ($goal->current_amount > 0) {
            $goal->contributions()->create([
                'amount' => $goal->current_amount,
                'date' => now()->toDateString(),
            ]);
        }

        return new GoalResource($goal);
    }

    public function show(Request $request, Goal $goal): GoalResource
    {
        abort_unless($goal->user_id === $request->user()->id, 403);

        $goal->load('contributions');

        return new GoalResource($goal);
    }

    public function update(UpdateGoalRequest $request, Goal $goal): GoalResource
    {
        abort_unless($goal->user_id === $request->user()->id, 403);

        $goal->update($request->validated());

        return new GoalResource($goal);
    }

    public function destroy(Request $request, Goal $goal): JsonResponse
    {
        abort_unless($goal->user_id === $request->user()->id, 403);

        $goal->delete();

        return response()->json(['message' => __('goals.deleted')]);
    }

    public function contribute(ContributeRequest $request, Goal $goal): GoalContributionResource
    {
        abort_unless($goal->user_id === $request->user()->id, 403);
        abort_if($goal->isAchieved(), 422, __('goals.already_achieved'));

        $amount = $request->validated('amount');

        $contribution = $goal->contributions()->create([
            'amount' => $amount,
            'date' => now()->toDateString(),
        ]);

        $goal->increment('current_amount', $amount);

        if ($goal->fresh()->current_amount >= $goal->target_amount) {
            $goal->update(['status' => GoalStatus::Achieved]);
        }

        return new GoalContributionResource($contribution);
    }

    public function scenarios(Request $request, Goal $goal): JsonResponse
    {
        abort_unless($goal->user_id === $request->user()->id, 403);

        return response()->json($this->calculationService->buildScenarios($goal));
    }

    public function whatIf(Request $request, Goal $goal): JsonResponse
    {
        abort_unless($goal->user_id === $request->user()->id, 403);

        $additional = (int) $request->input('additional_monthly', 0);

        return response()->json($this->calculationService->whatIf($goal, $additional));
    }
}
