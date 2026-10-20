<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Services\Payments\ClickService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Click SHOP API endpoints ("Prepare URL" and "Complete URL" in the Click merchant cabinet).
 * Click posts application/x-www-form-urlencoded and expects JSON (https://docs.click.uz/shop-api/requests).
 */
class ClickController extends Controller
{
    public function prepare(Request $request, ClickService $click): JsonResponse
    {
        return response()->json($click->prepare($request->all(), $request->ip()));
    }

    public function complete(Request $request, ClickService $click): JsonResponse
    {
        return response()->json($click->complete($request->all(), $request->ip()));
    }
}
