<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        switch ($user->role) {
            case 'ppic':
                return redirect()->route('ppic.style-orders.index');
            case 'ie':
                return redirect()->route('ie.breakdown.index');
            case 'hrd':
                return redirect()->route('hrd.employees.index');
            case 'spv':
                return redirect()->route('spv.hourly-output.index');
            case 'admin_produksi':
                return redirect()->route('admin.validation.index');
            case 'manager':
                return redirect()->route('manager.dashboard');
            case 'admin_qc':
                return redirect()->route('qc.defects.index');
            default:
                // Default fallback if role is unknown or missing
                return abort(403, 'Unauthorized access or role not recognized.');
        }
    }
}
