<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AccurateController extends Controller
{
    /**
     * Get Accurate Stock
     */
    public function GetAllStockAccurate(Request $request)
    {
        // Ambil halaman dari query string (?page=X) atau dari parameter URL segment
        $page = $request->input('page', 1); // Default ke halaman 1 jika tidak disediakan

        $authToken = config('services.accurate.auth_token');
        $sessionToken = config('services.accurate.session_token');

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $authToken,
            'X-Session-ID' => $sessionToken
        ])->get('https://public.accurate.id/accurate/api/item/list-stock.do', [
            'sp.page' => $page
        ]);

        if ($response->successful()) {
            return response()->json([
                'success' => true,
                'data' => $response->json(),
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch stock from Accurate',
                'error' => $response->body()
            ], $response->status());
        }
    }
}
