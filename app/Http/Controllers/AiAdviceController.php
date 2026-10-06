<?php

namespace App\Http\Controllers;

use App\Models\AiAdvice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiAdviceController extends Controller
{
    public function rate(Request $request, AiAdvice $advice): JsonResponse
    {
        $this->authorizeAdvice($request, $advice);

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'in:1,-1'],
        ]);

        $advice->update(['rating' => $validated['rating']]);

        return response()->json(['rating' => $advice->rating]);
    }

    private function authorizeAdvice(Request $request, AiAdvice $advice): void
    {
        abort_unless($advice->user_id === $request->user()->id, 403);
    }
}
