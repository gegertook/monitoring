<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PpicController;
use App\Http\Controllers\IeController;
use App\Http\Controllers\HrdController;
use App\Http\Controllers\SpvController;
use App\Http\Controllers\AdminProduksiController;
use App\Http\Controllers\ManagerController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\QcController;

// Public routes
Route::get('/', function () { 
    return redirect()->route('login'); 
});
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.process');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Authenticated routes
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Notifications (all authenticated users)
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unreadCount');
    Route::get('/notifications/dropdown', [NotificationController::class, 'dropdown'])->name('notifications.dropdown');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.readAll');
    
    // PPIC Routes
    Route::middleware(['role:ppic'])->prefix('ppic')->name('ppic.')->group(function () {
        Route::get('/style-orders', [PpicController::class, 'index'])->name('style-orders.index');
        Route::get('/style-orders/create', [PpicController::class, 'create'])->name('style-orders.create');
        Route::post('/style-orders', [PpicController::class, 'store'])->name('style-orders.store');
        Route::get('/style-orders/{styleOrder}/edit', [PpicController::class, 'edit'])->name('style-orders.edit');
        Route::put('/style-orders/{styleOrder}', [PpicController::class, 'update'])->name('style-orders.update');
        Route::delete('/style-orders/{styleOrder}', [PpicController::class, 'destroy'])->name('style-orders.destroy');
        Route::post('/style-orders/{styleOrder}/assign-line', [PpicController::class, 'assignLine'])->name('style-orders.assign-line');
        // AJAX endpoints
        Route::get('/api/style-orders', [PpicController::class, 'getStyleOrders'])->name('api.style-orders');
        Route::get('/api/production-lines', [PpicController::class, 'getProductionLines'])->name('api.production-lines');
    });
    
    // IE Routes
    Route::middleware(['role:ie'])->prefix('ie')->name('ie.')->group(function () {
        Route::get('/breakdown', [IeController::class, 'index'])->name('breakdown.index');
        Route::get('/breakdown/{styleOrder}', [IeController::class, 'show'])->name('breakdown.show');
        Route::post('/breakdown/{styleOrder}/process', [IeController::class, 'storeProcess'])->name('breakdown.store');
        Route::put('/breakdown/process/{process}', [IeController::class, 'updateProcess'])->name('breakdown.update');
        Route::delete('/breakdown/process/{process}', [IeController::class, 'destroyProcess'])->name('breakdown.destroy');
        // AJAX
        Route::get('/api/style-orders/{styleOrder}/processes', [IeController::class, 'getProcesses'])->name('api.processes');
    });
    
    // HRD Routes
    Route::middleware(['role:hrd'])->prefix('hrd')->name('hrd.')->group(function () {
        // Master Operator
        Route::resource('employees', HrdController::class);
        Route::get('/plotting', [HrdController::class, 'plotting'])->name('plotting.index');
        Route::post('/plotting', [HrdController::class, 'storePlotting'])->name('plotting.store');
        Route::delete('/plotting/{assignment}', [HrdController::class, 'destroyPlotting'])->name('plotting.destroy');
        
        // HRD: Manajemen Akun Staff / Supervisor / PPIC / IE / Admin / Manager
        Route::get('/users', [HrdController::class, 'usersIndex'])->name('users.index');
        Route::post('/users', [HrdController::class, 'storeUser'])->name('users.store');
        Route::put('/users/{user}', [HrdController::class, 'updateUser'])->name('users.update');
        Route::post('/users/{user}/toggle', [HrdController::class, 'toggleUser'])->name('users.toggle');
        Route::delete('/users/{user}', [HrdController::class, 'destroyUser'])->name('users.destroy');
        
        // AJAX
        Route::get('/api/employees', [HrdController::class, 'getEmployees'])->name('api.employees');
        Route::get('/api/line-assignments/{line}', [HrdController::class, 'getLineAssignments'])->name('api.line-assignments');
    });
    
    // SPV Routes
    Route::middleware(['role:spv'])->prefix('spv')->name('spv.')->group(function () {
        Route::get('/task-assignment', [SpvController::class, 'taskAssignment'])->name('task-assignment.index');
        Route::post('/task-assignment', [SpvController::class, 'storeTask'])->name('task-assignment.store');
        Route::get('/hourly-output', [SpvController::class, 'hourlyOutput'])->name('hourly-output.index');
        Route::post('/hourly-output', [SpvController::class, 'storeHourlyOutput'])->name('hourly-output.store');
        Route::get('/operator-output', [SpvController::class, 'operatorOutput'])->name('operator-output.index');
        Route::post('/operator-output', [SpvController::class, 'storeOperatorOutput'])->name('operator-output.store');
        // AJAX
        Route::get('/api/line-operators', [SpvController::class, 'getLineOperators'])->name('api.line-operators');
        Route::get('/api/hourly-data', [SpvController::class, 'getHourlyData'])->name('api.hourly-data');
        Route::get('/api/operator-data', [SpvController::class, 'getOperatorData'])->name('api.operator-data');
    });
    
    // Admin Produksi Routes
    Route::middleware(['role:admin_produksi'])->prefix('admin-produksi')->name('admin.')->group(function () {
        Route::get('/validation', [AdminProduksiController::class, 'index'])->name('validation.index');
        Route::post('/validation/{hourlyOutput}/validate', [AdminProduksiController::class, 'validateOutput'])->name('validation.validate');
        Route::post('/validation/bulk-validate', [AdminProduksiController::class, 'bulkValidate'])->name('validation.bulk');
        Route::get('/reports', [AdminProduksiController::class, 'reports'])->name('reports.index');
        Route::get('/reports/export', [AdminProduksiController::class, 'export'])->name('reports.export');
        // Rekap Defect QC (Viewable by Admin Produksi)
        Route::get('/defect-recap', [QcController::class, 'recap'])->name('defects.recap');
        Route::get('/defect-export', [QcController::class, 'exportRecap'])->name('defects.export');

        // AJAX
        Route::get('/api/pending-validations', [AdminProduksiController::class, 'getPendingValidations'])->name('api.pending');
        Route::get('/api/report-data', [AdminProduksiController::class, 'getReportData'])->name('api.report-data');
    });
    
    // Manager Routes
    Route::middleware(['role:manager'])->prefix('manager')->name('manager.')->group(function () {
        Route::get('/dashboard', [ManagerController::class, 'dashboard'])->name('dashboard');
        Route::get('/evaluation', [ManagerController::class, 'evaluation'])->name('evaluation.index');
        Route::post('/evaluation', [ManagerController::class, 'storeEvaluation'])->name('evaluation.store');
        
        // Rekap Defect QC (Viewable by Manager)
        Route::get('/defect-recap', [QcController::class, 'recap'])->name('defects.recap');
        Route::get('/defect-export', [QcController::class, 'exportRecap'])->name('defects.export');

        // Manager: Manajemen Production Lines (Tambah, Edit, Aktifkan/Nonaktifkan Line)
        Route::get('/lines', [ManagerController::class, 'lines'])->name('lines.index');
        Route::post('/lines', [ManagerController::class, 'storeLine'])->name('lines.store');
        Route::put('/lines/{line}', [ManagerController::class, 'updateLine'])->name('lines.update');
        Route::post('/lines/{line}/toggle', [ManagerController::class, 'toggleLine'])->name('lines.toggle');
        Route::delete('/lines/{line}', [ManagerController::class, 'destroyLine'])->name('lines.destroy');
        
        // AJAX
        Route::get('/api/realtime-kpi', [ManagerController::class, 'getRealtimeKpi'])->name('api.realtime-kpi');
        Route::get('/api/line-performance', [ManagerController::class, 'getLinePerformance'])->name('api.line-performance');
        Route::get('/api/defect-analysis', [ManagerController::class, 'getDefectAnalysis'])->name('api.defect-analysis');
    });

    // Admin QC Routes (Input Defect Per Line & Rekapitulasi Keseluruhan)
    Route::middleware(['role:admin_qc'])->prefix('qc')->name('qc.')->group(function () {
        Route::get('/defects', [QcController::class, 'index'])->name('defects.index');
        Route::post('/defects', [QcController::class, 'store'])->name('defects.store');
        Route::put('/defects/{defect}', [QcController::class, 'update'])->name('defects.update');
        Route::delete('/defects/{defect}', [QcController::class, 'destroy'])->name('defects.destroy');
        Route::get('/recap', [QcController::class, 'recap'])->name('recap.index');
        Route::get('/export', [QcController::class, 'exportRecap'])->name('export');
        Route::get('/api/recap-data', [QcController::class, 'getRecapData'])->name('api.recap-data');
    });
});
