<?php

namespace App\Http\Controllers;

use App\Models\StyleOrder;
use App\Models\BreakdownProcess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class IeController extends Controller
{
    public function index(Request $request)
    {
        $query = StyleOrder::with(['productionLine', 'breakdownProcesses'])->whereIn('status', ['in_progress', 'pending']);
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('code_style', 'like', "%{$search}%")
                  ->orWhere('buyer', 'like', "%{$search}%");
            });
        }
        
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($query->paginate(12));
        }

        $styleOrders = $query->paginate(12);
        return view('ie.index', compact('styleOrders'));
    }

    public function show(StyleOrder $styleOrder)
    {
        $styleOrder->load('productionLine');
        $processes = $styleOrder->breakdownProcesses()->orderBy('sequence_number')->get();
        return view('ie.show', compact('styleOrder', 'processes'));
    }

    public function storeProcess(Request $request, StyleOrder $styleOrder)
    {
        $validated = $request->validate([
            'process_name' => 'required|string|max:255',
            'smv' => 'required|numeric|min:0.0001',
            'target_per_hour' => 'required|integer|min:1',
            'sequence_number' => 'required|integer|min:1',
        ]);

        try {
            $validated['style_order_id'] = $styleOrder->id;
            $validated['created_by'] = auth()->id();
            
            $process = BreakdownProcess::create($validated);
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Breakdown process berhasil ditambahkan.', 'data' => $process]);
            }
            
            return redirect()->back()->with('success', 'Breakdown process created successfully.');
        } catch (\Exception $e) {
            Log::error('Error creating breakdown process: ' . $e->getMessage());
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['error' => 'Gagal membuat breakdown process: ' . $e->getMessage()], 500);
            }
            
            return back()->with('error', 'Failed to create breakdown process.');
        }
    }

    public function updateProcess(Request $request, BreakdownProcess $process)
    {
        $validated = $request->validate([
            'process_name' => 'required|string|max:255',
            'smv' => 'required|numeric|min:0.0001',
            'target_per_hour' => 'required|integer|min:1',
            'sequence_number' => 'required|integer|min:1',
        ]);

        try {
            $process->update($validated);
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Breakdown process berhasil diperbarui.']);
            }
            
            return redirect()->back()->with('success', 'Breakdown process updated successfully.');
        } catch (\Exception $e) {
            Log::error('Error updating breakdown process: ' . $e->getMessage());
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['error' => 'Gagal memperbarui breakdown process'], 500);
            }
            
            return back()->with('error', 'Failed to update breakdown process.');
        }
    }

    public function destroyProcess(Request $request, BreakdownProcess $process)
    {
        try {
            $process->delete();
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Breakdown process berhasil dihapus.']);
            }
            
            return redirect()->back()->with('success', 'Breakdown process deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Error deleting breakdown process: ' . $e->getMessage());
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['error' => 'Gagal menghapus breakdown process'], 500);
            }
            
            return back()->with('error', 'Failed to delete breakdown process.');
        }
    }

    public function getProcesses(StyleOrder $styleOrder)
    {
        try {
            $processes = $styleOrder->breakdownProcesses()->orderBy('sequence_number')->get();
            return response()->json($processes);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch breakdown processes'], 500);
        }
    }
}
