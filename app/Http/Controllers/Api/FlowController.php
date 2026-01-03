<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\HomeyApiService;
use Illuminate\Http\JsonResponse;

class FlowController extends Controller
{
    public function __construct(protected HomeyApiService $homey) {}

    public function index(): JsonResponse
    {
        if (!$this->homey->isConfigured()) {
            return response()->json(['error' => 'Homey not configured'], 503);
        }

        return response()->json($this->homey->getFlows());
    }

    public function trigger(string $flowId): JsonResponse
    {
        $success = $this->homey->triggerFlow($flowId);

        return response()->json([
            'success' => $success,
            'flow_id' => $flowId,
        ]);
    }
}
