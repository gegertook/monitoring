<?php

namespace App\Http\Controllers;

use App\Models\StyleOrder;
use App\Models\ProductionLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PpicController extends Controller
{
    public function index(Request $request)
    {
        $styleOrders = StyleOrder::with('productionLine')->latest()->paginate(10);
        $lines = ProductionLine::where('is_active', true)->get();
        return view('ppic.index', compact('styleOrders', 'lines'));
    }

    public function create()
    {
        $lines = ProductionLine::where('is_active', true)->get();
        return view('ppic.create', compact('lines'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code_style' => 'required|string|unique:style_orders,code_style|max:255',
            'buyer' => 'required|string|max:255',
            'total_qty_target' => 'required|integer|min:1',
            'deadline' => 'required|date',
            'assigned_line_id' => 'nullable|exists:production_lines,id',
        ]);

        try {
            $validated['status'] = !empty($validated['assigned_line_id']) ? 'in_progress' : 'pending';
            $validated['created_by'] = auth()->id();
            
            $order = StyleOrder::create($validated);
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Style order created successfully.', 'data' => $order]);
            }
            
            return redirect()->route('ppic.style-orders.index')->with('success', 'Style order berhasil dibuat.');
        } catch (\Exception $e) {
            Log::error('Error creating style order: ' . $e->getMessage());
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['error' => 'Failed to create style order: ' . $e->getMessage()], 500);
            }
            
            return back()->withInput()->with('error', 'Gagal membuat style order.');
        }
    }

    public function edit(StyleOrder $styleOrder)
    {
        $lines = ProductionLine::where('is_active', true)->get();
        return view('ppic.edit', compact('styleOrder', 'lines'));
    }

    public function update(Request $request, StyleOrder $styleOrder)
    {
        $validated = $request->validate([
            'code_style' => 'required|string|max:255|unique:style_orders,code_style,' . $styleOrder->id,
            'buyer' => 'required|string|max:255',
            'total_qty_target' => 'required|integer|min:1',
            'deadline' => 'required|date',
            'assigned_line_id' => 'nullable|exists:production_lines,id',
            'status' => 'nullable|in:pending,in_progress,completed,cancelled',
        ]);

        try {
            if (!empty($validated['assigned_line_id']) && $styleOrder->status === 'pending') {
                $validated['status'] = 'in_progress';
            }
            
            $styleOrder->update($validated);
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Style order updated successfully.']);
            }
            
            return redirect()->route('ppic.style-orders.index')->with('success', 'Style order berhasil diupdate.');
        } catch (\Exception $e) {
            Log::error('Error updating style order: ' . $e->getMessage());
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['error' => 'Failed to update style order'], 500);
            }
            
            return back()->withInput()->with('error', 'Gagal memperbarui style order.');
        }
    }

    public function destroy(Request $request, StyleOrder $styleOrder)
    {
        try {
            $styleOrder->delete();
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Style order deleted successfully.']);
            }
            
            return redirect()->route('ppic.style-orders.index')->with('success', 'Style order berhasil dihapus.');
        } catch (\Exception $e) {
            Log::error('Error deleting style order: ' . $e->getMessage());
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['error' => 'Failed to delete style order'], 500);
            }
            
            return back()->with('error', 'Gagal menghapus style order.');
        }
    }

    public function assignLine(Request $request, StyleOrder $styleOrder)
    {
        $validated = $request->validate([
            'production_line_id' => 'required|exists:production_lines,id',
        ]);

        try {
            $styleOrder->update([
                'assigned_line_id' => $validated['production_line_id'],
                'status' => 'in_progress'
            ]);
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Line assigned successfully.']);
            }
            
            return redirect()->back()->with('success', 'Line produksi berhasil ditugaskan.');
        } catch (\Exception $e) {
            Log::error('Error assigning line: ' . $e->getMessage());
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['error' => 'Failed to assign line'], 500);
            }
            
            return back()->with('error', 'Gagal menugaskan line produksi.');
        }
    }

    public function getStyleOrders(Request $request)
    {
        try {
            $query = StyleOrder::with('productionLine');

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('code_style', 'like', "%{$search}%")
                      ->orWhere('buyer', 'like', "%{$search}%");
                });
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            $styleOrders = $query->latest()->paginate(10);
            return response()->json($styleOrders);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch style orders: ' . $e->getMessage()], 500);
        }
    }

    public function getProductionLines(Request $request)
    {
        try {
            $lines = ProductionLine::where('is_active', true)->get();
            return response()->json($lines);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch production lines'], 500);
        }
    }
}
