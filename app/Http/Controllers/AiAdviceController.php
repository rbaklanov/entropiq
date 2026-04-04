<?php

namespace App\Http\Controllers;

use App\Models\AiAdvice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiAdviceController extends Controller
{
    public function show(Request $request, AiAdvice $advice): JsonResponse
    {
        $this->authorizeAdvice($request, $advice);

        $advice->markAsRead();

        return response()->json([
            'id' => $advice->id,
            'title' => $advice->title,
            'body' => $advice->body,
            'basis_data' => $advice->basis_data,
            'rating' => $advice->rating,
            'is_read' => $advice->is_read,
            'generated_at' => $advice->generated_at->toIso8601String(),
        ]);
    }

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
