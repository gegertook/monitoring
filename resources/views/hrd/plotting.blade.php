@extends('layouts.app')

@section('title', 'Plotting Karyawan ke Line (HRD)')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Plotting Karyawan ke Line Produksi</h2>
            <p class="text-sm text-slate-500">Centang banyak operator sekaligus untuk plot cepat. Karyawan yang sudah menempel di satu line hanya bisa dipindah lewat tombol <b>Pindah Line</b>.</p>
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
        <!-- Left: Employee roster with line status -->
        <div class="lg:col-span-4 bg-white rounded-2xl shadow-sm border border-slate-200 p-5 flex flex-col h-[650px]">
            <div class="mb-4 pb-3 border-b border-slate-100">
                <div class="flex justify-between items-center mb-2">
                    <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider">Daftar Operator</h3>
                    <span class="text-xs bg-indigo-50 text-indigo-700 font-bold px-2 py-0.5 rounded-full" id="empCount">0</span>
                </div>
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-3 text-slate-400 text-xs"></i>
                    <input type="text" id="searchEmp" placeholder="Cari NIK / Nama..." class="w-full pl-8 pr-3 py-1.5 text-xs border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>
                <p class="text-[11px] text-slate-400 mt-2">Karyawan berkategori <b>mekanik</b> tidak muncul di sini karena tidak bisa diplot ke line.</p>
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
                                Terisi: <b class="text-indigo-600 font-bold">{{ ($membersByLine->get($line->id) ?? collect())->count() }}</b> / {{ $line->capacity }}
                            </span>
                            <button onclick="openPlotModal({{ $line->id }}, '{{ $line->name }}', {{ $line->capacity }})" class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-indigo-50 text-indigo-700 hover:bg-indigo-100 transition">
                                <i class="fa-solid fa-plus mr-1"></i> Plot Operator
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3" id="line-container-{{ $line->id }}">
                        @php $members = $membersByLine->get($line->id) ?? collect(); @endphp
                        @forelse($members as $member)
                            <div class="bg-slate-50 border border-slate-200 rounded-xl p-3 flex justify-between items-center group hover:bg-white hover:shadow-xs transition">
                                <div class="min-w-0">
                                    <p class="text-xs font-bold text-slate-800 truncate">{{ $member->name }}</p>
                                    <p class="text-[10px] text-slate-400 font-mono">{{ $member->nik }}</p>
                                </div>
                                <div class="flex items-center gap-1 shrink-0">
                                    <button onclick="promptMoveEmployee({{ $member->id }}, {{ json_encode($member->name) }}, {{ json_encode($member->currentLine->name ?? $line->name) }})" class="text-slate-400 hover:text-amber-600 p-1 rounded-lg transition" title="Pindah Line">
                                        <i class="fa-solid fa-right-left text-xs"></i>
                                    </button>
                                    <button onclick="unassignEmployee({{ $member->id }}, {{ json_encode($member->name) }})" class="text-slate-400 hover:text-rose-600 p-1 rounded-lg transition" title="Lepas dari line (kembali ke pool)">
                                        <i class="fa-solid fa-user-minus text-xs"></i>
                                    </button>
                                </div>
                            </div>
                        @empty
                            <div class="col-span-full py-6 text-center text-xs text-slate-400 border border-dashed border-slate-200 rounded-xl">
                                Belum ada operator yang menempel di line ini. Centang operator lewat tombol <b>Plot Operator</b>.
                            </div>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<!-- Modal Plot Massal per Line -->
<div id="plotModal" class="fixed inset-0 z-50 hidden overflow-y-auto" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="fixed inset-0 bg-slate-900 opacity-50" onclick="closePlotModal()"></div>
        <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-lg p-6">
            <div class="flex justify-between items-center mb-1">
                <h3 class="text-base font-bold text-slate-800" id="plot_modal_title">Plot Operator</h3>
                <button onclick="closePlotModal()" class="text-slate-400 hover:text-slate-700"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <p class="text-xs text-slate-500 mb-4">Centang satu atau banyak operator, lalu tekan tombol simpan di bawah.</p>

            <div class="flex items-center justify-between gap-3 mb-3 pb-3 border-b border-slate-100">
                <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-600 cursor-pointer">
                    <input type="checkbox" id="plot_select_all" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    <span>Pilih semua operator</span>
                </label>
                <input type="text" id="plot_search" placeholder="Cari..." class="py-1.5 px-3 text-xs border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none w-40">
            </div>

            <div id="plot_modal_list" class="space-y-2 max-h-[380px] overflow-y-auto pr-1"></div>

            <div class="flex items-center justify-between gap-3 mt-5">
                <span class="text-xs font-semibold text-slate-500"><b id="plot_selected_count">0</b> operator dipilih</span>
                <div class="flex gap-2">
                    <button type="button" onclick="closePlotModal()" class="py-2 px-4 rounded-xl text-sm font-semibold bg-slate-100 text-slate-600 hover:bg-slate-200 transition">Batal</button>
                    <button type="button" onclick="submitBulkPlot()" class="py-2 px-4 rounded-xl text-sm font-semibold bg-indigo-600 text-white hover:bg-indigo-700 transition">
                        <i class="fa-solid fa-user-check mr-1"></i> Simpan Plotting
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
@php
    // Disiapkan di @php agar directive @json tidak terpotong oleh kurung pada closure
    $assignedJson = $assignedEmployees->map(fn ($e) => [
        'id' => $e->id,
        'name' => $e->name,
        'nik' => $e->nik,
        'skill_level' => $e->skill_level,
        'current_line_id' => $e->current_line_id,
        'line' => $e->currentLine->name ?? '-',
    ]);
@endphp
<script>
    let cachedEmployees = [];
    let cachedAssigned = @json($assignedJson);
    let activePlotLineId = null;
    let activePlotLineName = '';
    let activePlotCapacity = 0;

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

    function skillBadge(skill) {
        let color = {
            'senior': 'bg-emerald-100 text-emerald-800',
            'intermediate': 'bg-blue-100 text-blue-800',
            'junior': 'bg-amber-100 text-amber-800'
        }[skill] || 'bg-slate-100 text-slate-800';

        return `<span class="text-[10px] font-bold px-2 py-0.5 rounded-full ${color}">${(skill || '-').toUpperCase()}</span>`;
    }

    function renderEmployeeList(search = '') {
        let pool = cachedEmployees.filter(emp => !emp.current_line_id);
        let attached = cachedAssigned;

        let filtered = pool.concat(attached).filter(emp => {
            if (!search) return true;
            return emp.name.toLowerCase().includes(search) || emp.nik.toLowerCase().includes(search);
        });

        $('#empCount').text(filtered.length);

        if (filtered.length === 0) {
            $('#employeeList').html('<div class="text-center py-6 text-slate-400 text-xs">Tidak ada operator cocok.</div>');
            return;
        }

        let html = '';
        filtered.forEach(emp => {
            let assigned = !!emp.current_line_id || !!emp.line;
            let lineName = emp.line || '';

            html += `
            <div class="p-3 rounded-xl border ${assigned ? 'border-slate-200 bg-slate-50' : 'border-slate-200 bg-white hover:border-indigo-400'} transition flex justify-between items-center gap-2">
                <div class="min-w-0">
                    <h4 class="text-xs font-bold text-slate-800 truncate">${emp.name}</h4>
                    <span class="text-[10px] text-slate-400 font-mono">${emp.nik}</span>
                    <div class="mt-1">
                        ${assigned
                            ? `<span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700"><i class="fa-solid fa-industry"></i> ${lineName}</span>`
                            : `<span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700"><i class="fa-solid fa-circle-check"></i> Pool (belum di line)</span>`}
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    ${skillBadge(emp.skill_level)}
                    ${assigned
                        ? `<button onclick="promptMoveEmployee(${emp.id}, '${emp.name.replace(/'/g, "\\'")}', '${lineName}')" class="px-2 py-1.5 text-[10px] font-bold text-amber-700 bg-amber-50 hover:bg-amber-100 rounded-lg" title="Pindah Line"><i class="fa-solid fa-right-left mr-1"></i>Pindah</button>`
                        : ''}
                </div>
            </div>`;
        });

        $('#employeeList').html(html);
    }

    // -------------------------------------------------------------------------
    // Plot massal per line (multi checkbox)
    // -------------------------------------------------------------------------
    function openPlotModal(lineId, lineName, capacity) {
        activePlotLineId = lineId;
        activePlotLineName = lineName;
        activePlotCapacity = capacity;

        $('#plot_modal_title').text(`Plot Operator ke ${lineName}`);
        $('#plot_search').val('');
        $('#plot_select_all').prop('checked', false);
        renderPlotModalList();
        $('#plotModal').removeClass('hidden');
    }

    function closePlotModal() {
        activePlotLineId = null;
        $('#plotModal').addClass('hidden');
        $('#plot_modal_list').empty();
        $('#plot_selected_count').text('0');
    }

    function renderPlotModalList() {
        let search = ($('#plot_search').val() || '').toLowerCase();
        let pool = cachedEmployees.filter(emp => !emp.current_line_id && !emp.is_mekanik);

        let filtered = pool.filter(emp => !search || emp.name.toLowerCase().includes(search) || emp.nik.toLowerCase().includes(search));

        if (filtered.length === 0) {
            $('#plot_modal_list').html('<div class="text-center py-6 text-slate-400 text-xs border border-dashed border-slate-200 rounded-xl">Tidak ada operator pool tersedia. Operator yang sudah di line lain harus dipindah lewat daftar di kiri.</div>');
            return;
        }

        let html = '';
        filtered.forEach(emp => {
            html += `
            <label class="flex items-center gap-3 p-2.5 rounded-xl border border-slate-200 hover:border-indigo-400 cursor-pointer transition">
                <input type="checkbox" class="plot-check rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" value="${emp.id}" data-name="${emp.name}">
                <span class="flex-1 min-w-0">
                    <span class="block text-xs font-bold text-slate-800 truncate">${emp.name}</span>
                    <span class="block text-[10px] text-slate-400 font-mono">${emp.nik}</span>
                </span>
                ${skillBadge(emp.skill_level)}
            </label>`;
        });

        $('#plot_modal_list').html(html);
        updatePlotCount();
    }

    function updatePlotCount() {
        $('#plot_selected_count').text($('.plot-check:checked').length);
    }

    function submitBulkPlot() {
        let ids = $('.plot-check:checked').map(function() { return $(this).val(); }).get();

        if (ids.length === 0) {
            showNotification('warning', 'Centang minimal satu operator terlebih dahulu.');
            return;
        }

        $.ajax({
            url: "{{ route('hrd.plotting.store') }}",
            type: "POST",
            data: {
                employee_ids: ids,
                production_line_id: activePlotLineId,
                shift: $('#plotShift').val(),
                assigned_date: $('#plotDate').val()
            },
            success: function(res) {
                if (res.success) {
                    showNotification('success', res.message);
                    setTimeout(() => window.location.reload(), 800);
                } else {
                    showNotification('warning', res.message);
                }
            },
            error: function(err) {
                showNotification('error', err.responseJSON?.error || 'Gagal plotting operator.');
            }
        });
    }

    // -------------------------------------------------------------------------
    // Pindah Line
    // -------------------------------------------------------------------------
    function promptMoveEmployee(empId, empName, currentLine) {
        let lineOptions = {};
        @foreach($lines as $line)
            lineOptions[{{ $line->id }}] = "{{ $line->name }}";
        @endforeach

        Swal.fire({
            title: `Pindahkan ${empName}`,
            text: `Saat ini menempel di ${currentLine}. Pilih line tujuan:`,
            input: 'select',
            inputOptions: lineOptions,
            inputPlaceholder: 'Pilih Line...',
            showCancelButton: true,
            confirmButtonText: 'Pindahkan',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#d97706',
            inputValidator: (value) => {
                if (!value) return 'Silakan pilih line tujuan!';
            }
        }).then((result) => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: "{{ route('hrd.plotting.move') }}",
                type: "POST",
                data: {
                    employee_id: empId,
                    production_line_id: result.value,
                    shift: $('#plotShift').val(),
                    assigned_date: $('#plotDate').val()
                },
                success: function(res) {
                    showNotification('success', res.message || 'Karyawan dipindah.');
                    setTimeout(() => window.location.reload(), 800);
                },
                error: function(err) {
                    showNotification('error', err.responseJSON?.error || 'Gagal memindahkan karyawan.');
                }
            });
        });
    }

    function unassignEmployee(empId, empName) {
        Swal.fire({
            title: 'Lepas dari Line?',
            text: `${empName} akan dikeluarkan dari line dan kembali ke pool (bisa diplot ulang).`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e11d48',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Lepas',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: "{{ route('hrd.plotting.unassign') }}",
                type: "POST",
                data: { employee_id: empId },
                success: function(res) {
                    showNotification('success', res.message || 'Operator dilepas dari line.');
                    setTimeout(() => window.location.reload(), 500);
                },
                error: function(err) {
                    showNotification('error', err.responseJSON?.error || 'Gagal melepas operator.');
                }
            });
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

        $('#plot_search').on('keyup', renderPlotModalList);

        $(document).on('change', '.plot-check', updatePlotCount);

        $('#plot_select_all').change(function() {
            $('.plot-check').prop('checked', $(this).is(':checked'));
            updatePlotCount();
        });

        $('#plotDate').on('change', function() {
            window.location.href = `{{ route('hrd.plotting.index') }}?date=${$(this).val()}`;
        });
    });
</script>
@endsection
