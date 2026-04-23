<?php

namespace App\Http\Controllers\Api;

use App\Services\DashboardDataService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardDataService $dashboardDataService)
    {
        $userId = $request->integer('user');

        return response()->json($dashboardDataService->build($userId));
    }
}
