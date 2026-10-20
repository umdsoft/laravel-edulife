<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Services\Payments\PaymeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Payme Business Merchant API endpoint ("Endpoint URL" in the cashbox settings).
 * Every answer is HTTP 200 with a JSON-RPC body: any other status is treated by Payme as -32400
 * (https://developer.help.paycom.uz/protokol-merchant-api/).
 */
class PaymeController extends Controller
{
    public function __invoke(Request $request, PaymeService $payme): JsonResponse
    {
        return response()->json($payme->handle($request), 200, [], JSON_UNESCAPED_UNICODE);
    }
}
