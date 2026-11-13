<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MakeMeParticipant;
use App\Models\MakeMeProduct;

class MakeMeAdminDashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'participants' => MakeMeParticipant::count(),
            'active_participants' => MakeMeParticipant::where('status', 'active')->count(),
            'products' => MakeMeProduct::count(),
            'active_products' => MakeMeProduct::where('is_active', true)->count(),
            'stock_units' => MakeMeProduct::sum('stock_quantity'),
        ];
        $recentProducts = MakeMeProduct::latest('id')->limit(5)->get();
        return view('admin.makeme.dashboard.index', compact('stats', 'recentProducts'));
    }
}
