<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\ProductionLine;
use App\Models\EmployeeLineAssignment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;

class HrdController extends Controller
{
    public function index(Request $request)
    {
        $query = Employee::query();
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nik', 'like', "%{$search}%");
            });
        }

        if ($request->filled('skill_level')) {
            $query->where('skill_level', $request->skill_level);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }
        
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($query->latest()->paginate(10));
        }

        $employees = $query->latest()->paginate(10);
        return view('hrd.index', compact('employees'));
    }

    public function create()
    {
        return view('hrd.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nik' => 'required|string|unique:employees,nik|max:50',
            'name' => 'required|string|max:255',
            'category' => 'nullable|in:operator,mekanik',
            'skill_level' => 'required_unless:category,mekanik|nullable|in:junior,intermediate,senior',
        ]);

        try {
            $validated['category'] = $validated['category'] ?? Employee::CATEGORY_OPERATOR;
            // kolom skill_level NOT NULL: mekanik boleh kosong di form, default junior
            $validated['skill_level'] = $validated['skill_level'] ?? 'junior';
            $validated['is_active'] = true;
            $validated['created_by'] = auth()->id();
            
            $employee = Employee::create($validated);
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Karyawan berhasil didaftarkan.', 'data' => $employee]);
            }
            
            return redirect()->route('hrd.employees.index')->with('success', 'Karyawan berhasil didaftarkan.');
        } catch (\Exception $e) {
            Log::error('Error creating employee: ' . $e->getMessage());
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['error' => 'Gagal mendaftarkan karyawan: ' . $e->getMessage()], 500);
            }
            
            return back()->withInput()->with('error', 'Gagal mendaftarkan karyawan.');
        }
    }

    public function show(Employee $employee)
    {
        $employee->load(['lineAssignments.productionLine', 'operatorTasks.breakdownProcess']);
        return view('hrd.show', compact('employee'));
    }

    public function edit(Employee $employee)
    {
        return view('hrd.edit', compact('employee'));
    }

    public function update(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'nik' => 'required|string|max:50|unique:employees,nik,' . $employee->id,
            'name' => 'required|string|max:255',
            'category' => 'nullable|in:operator,mekanik',
            'skill_level' => 'required_unless:category,mekanik|nullable|in:junior,intermediate,senior',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['category'] = $validated['category'] ?? $employee->category;
        // kolom skill_level NOT NULL: mekanik boleh kosong di form, default junior
        $validated['skill_level'] = $validated['skill_level'] ?? $employee->skill_level ?? 'junior';

        try {
            if ($request->has('is_active')) {
                $validated['is_active'] = $request->boolean('is_active');
            }
            
            $employee->update($validated);
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Data karyawan berhasil diperbarui.']);
            }
            
            return redirect()->route('hrd.employees.index')->with('success', 'Data karyawan berhasil diperbarui.');
        } catch (\Exception $e) {
            Log::error('Error updating employee: ' . $e->getMessage());
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['error' => 'Gagal memperbarui data karyawan'], 500);
            }
            
            return back()->withInput()->with('error', 'Gagal memperbarui data karyawan.');
        }
    }

    public function destroy(Request $request, Employee $employee)
    {
        try {
            $employee->update(['is_active' => !$employee->is_active]);
            $status = $employee->is_active ? 'diaktifkan' : 'dinonaktifkan';
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => "Status karyawan berhasil {$status}."]);
            }
            
            return redirect()->route('hrd.employees.index')->with('success', "Status karyawan berhasil {$status}.");
        } catch (\Exception $e) {
            Log::error('Error toggling employee status: ' . $e->getMessage());
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['error' => 'Gagal mengubah status karyawan'], 500);
            }
            
            return back()->with('error', 'Gagal mengubah status karyawan.');
        }
    }

    public function plotting(Request $request)
    {
        $date = $request->input('date', now()->toDateString());
        $lines = ProductionLine::where('is_active', true)->get();

        // Keanggotaan line disumberkan dari employees.current_line_id (satu-satunya
        // sumber kebenaran), bukan baris assignment per tanggal — count kartu line
        // tetap akurat walaupun baris assignment lama bertanggal lain (hasil backfill
        // atau plotting pada hari sebelumnya).
        $membersByLine = Employee::where('is_active', true)
            ->operators()
            ->whereNotNull('current_line_id')
            ->with('currentLine')
            ->orderBy('name')
            ->get()
            ->groupBy('current_line_id');

        // Untuk daftar kiri & modal: semua operator yang sudah menempel di line
        $assignedEmployees = $membersByLine->flatten()->values();

        // Pool = hanya operator aktif yang belum menempel di line mana pun
        // (kategori mekanik tidak ikut karena tidak bisa diplot ke line).
        $employees = Employee::where('is_active', true)
            ->operators()
            ->whereNull('current_line_id')
            ->orderBy('name')
            ->get();

        return view('hrd.plotting', compact('lines', 'employees', 'assignedEmployees', 'membersByLine', 'date'));
    }

    /**
     * Plotting massal: HRD bisa mencentang banyak karyawan sekaligus.
     * Karyawan hanya boleh berada di satu line (permanen sampai HRD memindahnya).
     */
    public function storePlotting(Request $request)
    {
        $validated = $request->validate([
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'integer|exists:employees,id',
            'production_line_id' => 'required|exists:production_lines,id',
            'shift' => 'nullable|string',
            'assigned_date' => 'required|date',
        ]);

        try {
            $shift = $validated['shift'] ?? 'shift_1';
            $lineId = $validated['production_line_id'];
            $plotted = [];
            $skipped = [];

            foreach ($validated['employee_ids'] as $employeeId) {
                $employee = Employee::with('currentLine')->find($employeeId);

                if (!$employee || !$employee->is_active || $employee->isMekanik()) {
                    $skipped[] = ($employee->name ?? 'ID ' . $employeeId) . ' bukan operator aktif';
                    continue;
                }

                if ($employee->current_line_id && (int) $employee->current_line_id !== (int) $lineId) {
                    $skipped[] = $employee->name . ' sudah menempel di ' . ($employee->currentLine->name ?? 'line lain') . ' (gunakan Pindah Line)';
                    continue;
                }

                EmployeeLineAssignment::updateOrCreate(
                    [
                        'employee_id' => $employeeId,
                        'assigned_date' => $validated['assigned_date'],
                        'shift' => $shift,
                    ],
                    [
                        'production_line_id' => $lineId,
                        'assigned_by' => auth()->id(),
                    ]
                );

                $employee->update(['current_line_id' => $lineId]);
                $plotted[] = $employee->name;
            }

            $line = ProductionLine::find($lineId);
            $message = count($plotted) . ' operator berhasil diplot ke ' . ($line->name ?? 'line') . '.';
            if ($skipped) {
                $message .= ' (' . count($skipped) . ' dilewati: ' . implode('; ', array_slice($skipped, 0, 3)) . (count($skipped) > 3 ? ', ...' : '') . ')';
            }

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => count($plotted) > 0,
                    'message' => $message,
                    'plotted' => $plotted,
                    'skipped' => $skipped,
                ]);
            }

            return redirect()->back()->with(count($plotted) ? 'success' : 'error', $message);
        } catch (\Exception $e) {
            Log::error('Error plotting employees: ' . $e->getMessage());

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['error' => 'Gagal plotting operator: ' . $e->getMessage()], 500);
            }

            return back()->with('error', 'Gagal plotting operator.');
        }
    }

    /**
     * Pindah Line: satu-satunya cara memindahkan karyawan yang sudah menempel di line lain.
     */
    public function moveEmployee(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'production_line_id' => 'required|exists:production_lines,id',
            'shift' => 'nullable|string',
            'assigned_date' => 'required|date',
        ]);

        try {
            $employee = Employee::with('currentLine')->find($validated['employee_id']);

            if (!$employee || !$employee->is_active || $employee->isMekanik()) {
                return response()->json(['error' => 'Karyawan bukan operator aktif.'], 422);
            }

            if ($employee->current_line_id && (int) $employee->current_line_id === (int) $validated['production_line_id']) {
                return response()->json(['error' => $employee->name . ' sudah berada di line tersebut.'], 422);
            }

            $fromLine = $employee->currentLine->name ?? 'line sebelumnya';

            // Lepas semua plotting lama, lalu tempelkan ke line baru
            EmployeeLineAssignment::where('employee_id', $employee->id)->delete();

            EmployeeLineAssignment::create([
                'employee_id' => $employee->id,
                'production_line_id' => $validated['production_line_id'],
                'shift' => $validated['shift'] ?? 'shift_1',
                'assigned_date' => $validated['assigned_date'],
                'assigned_by' => auth()->id(),
            ]);

            $employee->update(['current_line_id' => $validated['production_line_id']]);

            $toLine = ProductionLine::find($validated['production_line_id'])->name ?? 'line baru';
            $message = "{$employee->name} dipindah dari {$fromLine} ke {$toLine}.";

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $message]);
            }

            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            Log::error('Error moving employee: ' . $e->getMessage());

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['error' => 'Gagal memindahkan karyawan: ' . $e->getMessage()], 500);
            }

            return back()->with('error', 'Gagal memindahkan karyawan.');
        }
    }

    /**
     * Lepas operator dari line; kembali ke pool dan bisa diplot ulang.
     */
    public function unassignEmployee(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
        ]);

        try {
            $employee = Employee::find($validated['employee_id']);

            if (!$employee || $employee->isMekanik()) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['error' => 'Karyawan bukan operator.'], 422);
                }
                return back()->with('error', 'Karyawan bukan operator.');
            }

            $lineName = $employee->currentLine->name ?? 'line-nya';

            EmployeeLineAssignment::where('employee_id', $employee->id)->delete();
            $employee->update(['current_line_id' => null]);

            $message = "{$employee->name} dilepas dari {$lineName} dan kembali ke pool.";

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $message]);
            }

            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            Log::error('Error unassigning employee: ' . $e->getMessage());

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['error' => 'Gagal melepas operator dari line.'], 500);
            }

            return back()->with('error', 'Gagal melepas operator dari line.');
        }
    }

    public function destroyPlotting(Request $request, EmployeeLineAssignment $assignment)
    {
        try {
            $employee = Employee::find($assignment->employee_id);
            $assignment->delete();

            // Melepas plotting = karyawan kembali ke pool (bisa diplot ke line mana pun)
            if ($employee) {
                $stillAssigned = EmployeeLineAssignment::where('employee_id', $employee->id)->exists();
                if (!$stillAssigned) {
                    $employee->update(['current_line_id' => null]);
                }
            }
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Plotting operator berhasil dihapus.']);
            }
            
            return redirect()->back()->with('success', 'Plotting operator berhasil dihapus.');
        } catch (\Exception $e) {
            Log::error('Error removing assignment: ' . $e->getMessage());
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['error' => 'Gagal menghapus assignment'], 500);
            }
            
            return back()->with('error', 'Gagal menghapus assignment.');
        }
    }

    public function getEmployees(Request $request)
    {
        try {
            $query = Employee::where('is_active', true)->with('currentLine');

            // Halaman plotting hanya boleh melihat karyawan berkategori operator
            if ($request->boolean('operators_only', true)) {
                $query->operators();
            }
            
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('nik', 'like', "%{$search}%");
                });
            }
            
            return response()->json($query->orderBy('name')->get());
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch employees'], 500);
        }
    }

    public function getLineAssignments(ProductionLine $line)
    {
        try {
            $date = request()->input('date', now()->toDateString());
            $assignments = $line->employeeAssignments()
                ->whereDate('assigned_date', $date)
                ->with('employee')
                ->get();
            return response()->json($assignments);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch assignments'], 500);
        }
    }

    /**
     * Manajemen Akun Staff & Supervisor (Otoritas HRD)
     */
    public function usersIndex(Request $request)
    {
        $query = User::with('assignedLine');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nik', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $users = $query->latest()->paginate(15);

        $stats = [
            'total' => User::count(),
            'active' => User::where('is_active', true)->count(),
            'inactive' => User::where('is_active', false)->count(),
            'spv_count' => User::where('role', 'spv')->count(),
            'qc_count' => User::where('role', 'admin_qc')->count(),
            'office_count' => User::whereIn('role', ['ppic', 'ie', 'admin_produksi', 'manager', 'hrd', 'admin_qc', 'admin_mekanik'])->count(),
        ];

        $roles = [
            'ppic' => 'PPIC (Production Planning)',
            'ie' => 'Industrial Engineering (IE)',
            'hrd' => 'Human Resources (HRD)',
            'spv' => 'Supervisor Line (SPV)',
            'admin_qc' => 'Admin Quality Control (QC)',
            'admin_produksi' => 'Admin Produksi',
            'admin_mekanik' => 'Admin Mekanik (Mesin & Downtime)',
            'manager' => 'Executive Manager',
        ];

        // All production lines for SPV line assignment dropdown
        $productionLines = ProductionLine::orderBy('name')->get();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'users' => $users,
                'stats' => $stats
            ]);
        }

        return view('hrd.users', compact('users', 'stats', 'roles', 'productionLines'));
    }

    public function storeUser(Request $request)
    {
        $validated = $request->validate([
            'nik' => 'required|string|max:50|unique:users,nik',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'role' => 'required|in:ppic,ie,hrd,spv,admin_produksi,manager,admin_qc,admin_mekanik',
            'password' => 'required|string|min:6',
            'is_active' => 'nullable|boolean',
            'assigned_line_id' => 'nullable|exists:production_lines,id',
        ]);

        // Only SPV can have assigned_line_id; clear it for other roles
        if ($validated['role'] !== 'spv') {
            $validated['assigned_line_id'] = null;
        }

        try {
            $validated['password'] = Hash::make($validated['password']);
            $validated['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : true;

            $user = User::create($validated);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Akun {$user->name} ({$user->role}) berhasil dibuat.",
                    'data' => $user
                ]);
            }

            return redirect()->route('hrd.users.index')->with('success', "Akun {$user->name} berhasil dibuat.");
        } catch (\Exception $e) {
            Log::error('Error creating user: ' . $e->getMessage());
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['error' => 'Gagal membuat akun: ' . $e->getMessage()], 500);
            }
            return back()->with('error', 'Gagal membuat akun: ' . $e->getMessage());
        }
    }

    public function updateUser(Request $request, User $user)
    {
        $validated = $request->validate([
            'nik' => 'required|string|max:50|unique:users,nik,' . $user->id,
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'role' => 'required|in:ppic,ie,hrd,spv,admin_produksi,manager,admin_qc,admin_mekanik',
            'password' => 'nullable|string|min:6',
            'is_active' => 'nullable|boolean',
            'assigned_line_id' => 'nullable|exists:production_lines,id',
        ]);

        // Only SPV can have assigned_line_id; clear it for other roles
        if ($validated['role'] !== 'spv') {
            $validated['assigned_line_id'] = null;
        }

        try {
            if (!empty($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            } else {
                unset($validated['password']);
            }

            $validated['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : $user->is_active;

            $user->update($validated);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Akun {$user->name} berhasil diperbarui.",
                    'data' => $user
                ]);
            }

            return redirect()->route('hrd.users.index')->with('success', "Akun {$user->name} berhasil diperbarui.");
        } catch (\Exception $e) {
            Log::error('Error updating user: ' . $e->getMessage());
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['error' => 'Gagal memperbarui akun: ' . $e->getMessage()], 500);
            }
            return back()->with('error', 'Gagal memperbarui akun.');
        }
    }

    public function toggleUser(Request $request, User $user)
    {
        try {
            if ($user->id === auth()->id()) {
                return response()->json(['error' => 'Anda tidak dapat menonaktifkan akun Anda sendiri.'], 400);
            }

            $user->is_active = !$user->is_active;
            $user->save();

            $statusText = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';

            return response()->json([
                'success' => true,
                'is_active' => $user->is_active,
                'message' => "Status akun {$user->name} berhasil diubah menjadi {$statusText}."
            ]);
        } catch (\Exception $e) {
            Log::error('Error toggling user: ' . $e->getMessage());
            return response()->json(['error' => 'Gagal mengubah status akun.'], 500);
        }
    }

    public function destroyUser(Request $request, User $user)
    {
        try {
            if ($user->id === auth()->id()) {
                return response()->json(['error' => 'Anda tidak dapat menghapus akun Anda sendiri.'], 400);
            }

            // Check if user has associated production records
            $hasStyleOrders = $user->styleOrders()->exists();
            $hasEvaluations = $user->evaluations()->exists();

            if ($hasStyleOrders || $hasEvaluations) {
                $user->is_active = false;
                $user->save();

                return response()->json([
                    'success' => true,
                    'is_deactivated' => true,
                    'message' => "Akun {$user->name} memiliki riwayat transaksi/evaluasi. Status dialihkan menjadi Non-Aktif."
                ]);
            }

            $userName = $user->name;
            $user->delete();

            return response()->json([
                'success' => true,
                'message' => "Akun {$userName} berhasil dihapus dari sistem."
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting user: ' . $e->getMessage());
            return response()->json(['error' => 'Gagal menghapus akun: ' . $e->getMessage()], 500);
        }
    }
}

