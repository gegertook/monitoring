@extends('layouts.app')

@section('title', 'Plotting Karyawan ke Line (HRD)')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Plotting Karyawan ke Line Produksi</h2>
            <p class="text-sm text-slate-500">Alokasikan operator aktif ke line dan shift kerja sesuai kebutuhan harian.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <div>
                <label class="block text-[11px] font-bold uppercase text-slate-500 mb-1">Tanggal Tugas</label>
                <input type="date" id="plotDate" class="py-2 px-3 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none bg-white font-medium text-slate-700" value="{{ $date ?? date('Y-m-d') }}">
            </div>
            <div>
                <label class="block text-[11px] font-bold uppercase text-slate-500 mb-1">Shift Operasional</label>
                <div class="inline-flex items-center gap-2 py-2 px-3 text-sm border border-slate-200 rounded-xl bg-slate-50 font-semibold text-slate-700">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span>1 Shift Reguler (07:00 - 16:00)</span>
                </div>
                <input type="hidden" id="plotShift" value="shift_1">
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Left: Available Employees -->
        <div class="lg:col-span-4 bg-white rounded-2xl shadow-sm border border-slate-200 p-5 flex flex-col h-[650px]">
            <div class="mb-4 pb-3 border-b border-slate-100">
                <div class="flex justify-between items-center mb-2">
                    <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider">Karyawan Aktif</h3>
                    <span class="text-xs bg-indigo-50 text-indigo-700 font-bold px-2 py-0.5 rounded-full" id="empCount">0</span>
                </div>
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-3 text-slate-400 text-xs"></i>
                    <input type="text" id="searchEmp" placeholder="Cari NIK / Nama..." class="w-full pl-8 pr-3 py-1.5 text-xs border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>
            </div>

            <div class="flex-1 overflow-y-auto space-y-2 pr-1" id="employeeList">
                <!-- Loaded via AJAX -->
            </div>
        </div>

        <!-- Right: Production Lines with assignments -->
        <div class="lg:col-span-8 space-y-5">
            @foreach($lines as $line)
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
                    <div class="flex justify-between items-center mb-4 pb-3 border-b border-slate-100">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-indigo-600 text-white flex items-center justify-center font-bold text-sm">
                                <i class="fa-solid fa-layer-group"></i>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-800">{{ $line->name }}</h3>
                                <p class="text-xs text-slate-500">Kapasitas Maksimal: {{ $line->capacity }} Orang</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-slate-500 line-assigned-count-{{ $line->id }}">
                                Terisi: <b class="text-indigo-600 font-bold">{{ $line->employeeAssignments->count() }}</b> / {{ $line->capacity }}
                            </span>
                            <button onclick="promptAssignToLine({{ $line->id }}, '{{ $line->name }}')" class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-indigo-50 text-indigo-700 hover:bg-indigo-100 transition">
                                <i class="fa-solid fa-plus mr-1"></i> Plot Operator
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3" id="line-container-{{ $line->id }}">
                        @forelse($line->employeeAssignments as $assignment)
                            <div class="bg-slate-50 border border-slate-200 rounded-xl p-3 flex justify-between items-center group hover:bg-white hover:shadow-xs transition">
                                <div>
                                    <p class="text-xs font-bold text-slate-800">{{ $assignment->employee->name ?? '-' }}</p>
                                    <p class="text-[10px] text-slate-400 font-mono">{{ $assignment->employee->nik ?? '-' }} &bull; {{ strtoupper($assignment->shift) }}</p>
                                </div>
                                <button onclick="deleteAssignment({{ $assignment->id }})" class="text-slate-400 hover:text-rose-600 p-1 rounded-lg transition" title="Hapus plotting">
                                    <i class="fa-solid fa-xmark text-xs"></i>
                                </button>
                            </div>
                        @empty
                            <div class="col-span-full py-6 text-center text-xs text-slate-400 border border-dashed border-slate-200 rounded-xl">
                                Belum ada operator yang diplot ke line ini pada tanggal & shift yang dipilih.
                            </div>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    let cachedEmployees = [];

    function loadEmployees() {
        let search = $('#searchEmp').val().toLowerCase();
        
        $('#employeeList').html('<div class="text-center py-8 text-slate-400"><i class="fa-solid fa-spinner fa-spin mr-1"></i> Memuat...</div>');

        $.ajax({
            url: "{{ route('hrd.api.employees') }}",
            type: "GET",
            success: function(employees) {
                cachedEmployees = employees;
                renderEmployeeList(search);
            },
            error: function() {
                $('#employeeList').html('<div class="text-center py-6 text-rose-500 text-xs">Gagal memuat karyawan.</div>');
            }
        });
    }

    function renderEmployeeList(search = '') {
        let filtered = cachedEmployees.filter(emp => {
            if(!search) return true;
            return emp.name.toLowerCase().includes(search) || emp.nik.toLowerCase().includes(search);
        });

        $('#empCount').text(filtered.length);

        if(filtered.length === 0) {
            $('#employeeList').html('<div class="text-center py-6 text-slate-400 text-xs">Tidak ada operator cocok.</div>');
            return;
        }

        let html = '';
        filtered.forEach(emp => {
            let skillColor = {
                'senior': 'bg-emerald-100 text-emerald-800',
                'intermediate': 'bg-blue-100 text-blue-800',
                'junior': 'bg-amber-100 text-amber-800'
            }[emp.skill_level] || 'bg-slate-100 text-slate-800';

            html += `
            <div class="p-3 rounded-xl border border-slate-200 bg-white hover:border-indigo-400 transition flex justify-between items-center">
                <div>
                    <h4 class="text-xs font-bold text-slate-800">${emp.name}</h4>
                    <span class="text-[10px] text-slate-400 font-mono">${emp.nik}</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full ${skillColor}">${emp.skill_level.toUpperCase()}</span>
                    <button onclick="promptAssignEmployee(${emp.id}, '${emp.name}')" class="p-1.5 text-xs text-indigo-600 hover:bg-indigo-50 rounded-lg" title="Plot ke Line">
                        <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </div>
            </div>`;
        });

        $('#employeeList').html(html);
    }

    function promptAssignEmployee(empId, empName) {
        let date = $('#plotDate').val();
        let shift = $('#plotShift').val();

        let lineOptions = {};
        @foreach($lines as $line)
            lineOptions[{{ $line->id }}] = "{{ $line->name }}";
        @endforeach

        Swal.fire({
            title: `Plot ${empName}`,
            text: `Pilih line produksi tujuan untuk ${shift.replace('_', ' ').toUpperCase()} (${date}):`,
            input: 'select',
            inputOptions: lineOptions,
            inputPlaceholder: 'Pilih Line...',
            showCancelButton: true,
            confirmButtonText: 'Plot Sekarang',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#4f46e5',
            inputValidator: (value) => {
                if (!value) return 'Silakan pilih line produksi!';
            }
        }).then((result) => {
            if (result.isConfirmed) {
                submitPlotting(empId, result.value, shift, date);
            }
        });
    }

    function promptAssignToLine(lineId, lineName) {
        let date = $('#plotDate').val();
        let shift = $('#plotShift').val();

        let empOptions = {};
        cachedEmployees.forEach(e => {
            empOptions[e.id] = `${e.name} (${e.nik} - ${e.skill_level})`;
        });

        Swal.fire({
            title: `Plot ke ${lineName}`,
            text: `Pilih operator untuk shift ${shift.replace('_', ' ').toUpperCase()}:`,
            input: 'select',
            inputOptions: empOptions,
            inputPlaceholder: 'Pilih Operator...',
            showCancelButton: true,
            confirmButtonText: 'Plot Operator',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#4f46e5',
            inputValidator: (value) => {
                if (!value) return 'Silakan pilih operator!';
            }
        }).then((result) => {
            if (result.isConfirmed) {
                submitPlotting(result.value, lineId, shift, date);
            }
        });
    }

    function submitPlotting(empId, lineId, shift, date) {
        $.ajax({
            url: "{{ route('hrd.plotting.store') }}",
            type: "POST",
            data: {
                employee_id: empId,
                production_line_id: lineId,
                shift: shift,
                assigned_date: date
            },
            success: function(response) {
                showNotification('success', response.message || 'Operator berhasil diplot.');
                setTimeout(() => {
                    window.location.reload();
                }, 600);
            },
            error: function(err) {
                showNotification('error', err.responseJSON?.message || 'Gagal plotting operator.');
            }
        });
    }

    function deleteAssignment(id) {
        Swal.fire({
            title: 'Hapus Plotting?',
            text: 'Operator akan dikeluarkan dari line ini pada shift terkait.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e11d48',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/hrd/plotting/${id}`,
                    type: "DELETE",
                    success: function(res) {
                        showNotification('success', 'Plotting berhasil dihapus.');
                        setTimeout(() => {
                            window.location.reload();
                        }, 500);
                    },
                    error: function() {
                        showNotification('error', 'Gagal menghapus plotting.');
                    }
                });
            }
        });
    }

    $(document).ready(function() {
        loadEmployees();

        let timer;
        $('#searchEmp').on('keyup', function() {
            clearTimeout(timer);
            timer = setTimeout(() => {
                renderEmployeeList($(this).val().toLowerCase());
            }, 250);
        });

        $('#plotDate, #plotShift').on('change', function() {
            let date = $('#plotDate').val();
            window.location.href = `{{ route('hrd.plotting.index') }}?date=${date}`;
        });
    });
</script>
@endsection
