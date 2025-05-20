<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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
        
        // Cache key yang unik berdasarkan halaman
        $cacheKey = 'accurate_stock_page_' . $page;
        // Tetapkan waktu cache (dalam menit) - dapat disesuaikan sesuai kebutuhan
        $cacheDuration = 60; // 1 jam
        
        // Jika ada parameter force_refresh, bypass cache
        if ($request->has('force_refresh')) {
            Cache::forget($cacheKey);
        }
        
        // Periksa apakah cache sudah ada - jika API error, gunakan data cache
        if (Cache::has($cacheKey)) {
            $cachedData = Cache::get($cacheKey);
            
            // Tambahkan informasi bahwa data berasal dari cache
            $cachedData['from_cache'] = true;
            $cachedData['cached_at'] = $cachedData['cached_at'] ?? now()->toDateTimeString();
            
            return response()->json([
                'success' => true,
                'data' => $cachedData,
            ]);
        }
        
        // Jika cache tidak ada, ambil dari API
        $authToken = config('services.accurate.auth_token');
        $sessionToken = config('services.accurate.session_token');
    
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $authToken,
            'X-Session-ID' => $sessionToken
        ])->get('https://public.accurate.id/accurate/api/item/list-stock.do', [
            'sp.page' => $page
        ]);
    
        if ($response->successful()) {
            // Jika API berhasil, simpan hasil ke cache
            $responseData = $response->json();
            $responseData['cached_at'] = now()->toDateTimeString();
            
            // Simpan ke cache
            Cache::put($cacheKey, $responseData, $cacheDuration * 60);
            
            return response()->json([
                'success' => true,
                'data' => $responseData,
            ]);
        } else {
            // Jika API error, coba ambil data dari cache jika ada
            $cachedData = Cache::get($cacheKey);
            
            if ($cachedData) {
                // Jika ada cache, gunakan cache tersebut
                $cachedData['from_cache'] = true;
                $cachedData['cached_at'] = $cachedData['cached_at'] ?? now()->toDateTimeString();
                
                return response()->json([
                    'success' => true,
                    'data' => $cachedData,
                    'message' => 'Data from cache because Accurate API failed'
                ]);
            }
            
            // Jika tidak ada cache, kembalikan error
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch stock from Accurate and no cached data available',
                'error' => $response->body()
            ], $response->status());
        }
    }
}
