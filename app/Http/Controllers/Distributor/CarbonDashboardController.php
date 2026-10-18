<?php

namespace App\Http\Controllers\Distributor;

use App\Http\Controllers\Controller;
use App\Services\CarbonDashboard;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CarbonDashboardController extends Controller
{
    /**
     * Dashboard carbone du distributeur connecté, filtré par période
     */
    public function __invoke(Request $request, CarbonDashboard $dashboard): View
    {
        $period = $request->query('period', '12m');

        return view('pro.distributor.dashboard', [
            'stats' => $dashboard->forUser($request->user(), is_string($period) ? $period : '12m'),
            'periods' => CarbonDashboard::PERIODS,
        ]);
    }
}
