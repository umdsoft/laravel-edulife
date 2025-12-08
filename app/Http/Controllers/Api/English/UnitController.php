<?php

namespace App\Http\Controllers\Api\English;

use App\Http\Controllers\Controller;
use App\Models\English\EnglishUnit;
use App\Services\English\UnitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function __construct(
        private UnitService $unitService
    ) {
    }

    /**
     * Get units for a topic
     */
    public function index(Request $request, string $topicId): JsonResponse
    {
        $units = $this->unitService->getUnitsForTopic($topicId, $request->user());

        return response()->json([
            'success' => true,
            'data' => $units,
        ]);
    }

    /**
     * Get single unit
     */
    public function show(Request $request, string $unitId): JsonResponse
    {
        $unit = $this->unitService->getUnitWithDetails($unitId, $request->user());

        if (!$unit) {
            return response()->json(['success' => false, 'message' => 'Unit not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $unit,
        ]);
    }

    /**
     * Complete unit test
     */
    public function completeTest(Request $request, string $unitId): JsonResponse
    {
        $validated = $request->validate([
            'score' => 'required|integer|min:0|max:100',
        ]);

        $unit = EnglishUnit::findOrFail($unitId);
        $result = $this->unitService->completeUnitTest($unit, $request->user(), $validated['score']);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }
}
