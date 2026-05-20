<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AIChatRequest;
use App\Services\AIService;
use Illuminate\Http\JsonResponse;

class AIChatController extends Controller
{
    public function __construct(private AIService $ai) {}

    public function chat(AIChatRequest $request): JsonResponse
    {
        try {
            $result = $this->ai->chat(
                $request->input('message'),
                $request->input('history', []),
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 502);
        }
    }
}
