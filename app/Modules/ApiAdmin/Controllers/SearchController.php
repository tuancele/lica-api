<?php

namespace App\Modules\ApiAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Search\Models\Search;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SearchController extends Controller
{
    public function logs(Request $request): JsonResponse
    {
        try {
            $filters = [];
            if ($request->has('status') && $request->status !== '') $filters['status'] = $request->status;
            if ($request->has('keyword') && $request->keyword !== '') $filters['keyword'] = $request->keyword;
            
            $perPage = (int) $request->get('limit', 10);
            $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 10;
            
            $query = Search::query();
            if (isset($filters['status'])) $query->where('status', $filters['status']);
            if (isset($filters['keyword'])) $query->where('name', 'like', '%' . $filters['keyword'] . '%');
            $query->orderBy('sort', 'asc');
            
            $logs = $query->paginate($perPage);
            
            return response()->json([
                'success' => true,
                'data' => $logs->items(),
                'pagination' => [
                    'current_page' => $logs->currentPage(),
                    'per_page' => $logs->perPage(),
                    'total' => $logs->total(),
                    'last_page' => $logs->lastPage(),
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Get search logs failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to get search logs'], 500);
        }
    }

    public function analytics(Request $request): JsonResponse
    {
        try {
            $dateFrom = $request->get('date_from', date('Y-m-d', strtotime('-30 days')));
            $dateTo = $request->get('date_to', date('Y-m-d'));
            
            $popularSearches = Search::whereDate('created_at', '>=', $dateFrom)
                ->whereDate('created_at', '<=', $dateTo)
                ->select('name', DB::raw('COUNT(*) as count'))
                ->groupBy('name')
                ->orderBy('count', 'desc')
                ->limit(20)
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'popular_searches' => $popularSearches,
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Get search analytics failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to get search analytics'], 500);
        }
    }
}

