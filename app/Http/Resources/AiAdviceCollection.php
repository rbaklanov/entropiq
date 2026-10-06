<?php

namespace App\Http\Resources;

use App\Contracts\SubscriptionServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class AiAdviceCollection extends ResourceCollection
{
    public $collects = AiAdviceResource::class;

    /** @return array<int, array<string, mixed>> */
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $subscriptionService = app(SubscriptionServiceInterface::class);

        $premium = $subscriptionService->isPremium($user);
        $openAdviceId = $subscriptionService->openAdviceId($user);

        return $this->collection
            ->map(fn (AiAdviceResource $advice) => $advice->forViewer($premium, $openAdviceId)->toArray($request))
            ->all();
    }
}
