<?php

namespace App\Http\Controllers\Api\V1;

use App\Contracts\SubscriptionServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Resources\AiAdviceResource;
use App\Models\AiAdvice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AiAdviceController extends Controller
{
    public function __construct(
        private readonly SubscriptionServiceInterface $subscriptionService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $advices = AiAdvice::where('user_id', $request->user()->id)
            ->orderByDesc('generated_at')
            ->paginate(20);

        return AiAdviceResource::collection($advices);
    }

    public function show(Request $request, AiAdvice $advice): AiAdviceResource|JsonResponse
    {
        abort_unless($advice->user_id === $request->user()->id, 403);

        if (! $this->subscriptionService->canViewAdvice($request->user(), $advice)) {
            return response()->json([
                'message' => __('subscription.advice_limit'),
                'upgrade_url' => route('settings.subscription'),
            ], 403);
        }

        $advice->markAsRead();

        return new AiAdviceResource($advice);
    }

    public function rate(Request $request, AiAdvice $advice): JsonResponse
    {
        abort_unless($advice->user_id === $request->user()->id, 403);

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'in:1,-1'],
        ]);

        $advice->update(['rating' => $validated['rating']]);

        return response()->json(['rating' => $advice->rating]);
    }
}
