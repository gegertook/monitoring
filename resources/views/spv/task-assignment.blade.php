@extends('layouts.app')

@section('title', 'Assignment Tugas Operator (SPV)')

@section('content')
<div class="space-y-6">
    <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-4 pb-3 border-b border-slate-100">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Distribusi Tugas Proses ke Operator Line</h2>
                <p class="text-xs text-slate-500">SPV Line membagikan tugas breakdown process kepada operator yang bertugas pada shift aktif.</p>
            </div>
            <span class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-50 border border-indigo-200 text-indigo-700 rounded-xl text-sm font-bold">
                <i class="fa-solid fa-industry"></i>
                {{ $line->name }}
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="style_order" class="block text-xs font-bold uppercase text-slate-600 mb-1.5">Pilih Style Order</label>
                <select id="style_order" class="w-full py-2.5 px-3 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none bg-white">
                    <option value="">-- Pilih Style Order --</option>
                    @foreach($styleOrders as $order)
                        <option value="{{ $order->id }}" {{ $loop->first ? 'selected' : '' }}>
                            {{ $order->code_style }} ({{ $order->buyer }} - Line: {{ $order->productionLine ? $order->productionLine->name : 'Unassigned' }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="assignment_date" class="block text-xs font-bold uppercase text-slate-600 mb-1.5">Tanggal Tugas</label>
                <input type="date" id="assignment_date" value="{{ $date ?? date('Y-m-d') }}" class="w-full py-2 px-3 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none bg-white font-medium">
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Form Assignment -->
        <div class="lg:col-span-5 bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h3 class="text-base font-bold text-slate-800 mb-4 pb-2 border-b border-slate-100">Tugaskan Operator</h3>
            
            <form id="assignment_form" class="space-y-4">
                <div>
                    <label for="process_id" class="block text-xs font-bold uppercase text-slate-600 mb-1.5">Elemen Breakdown Process</label>
                    <select id="process_id" name="breakdown_process_id" class="w-full py-2.5 px-3 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none bg-white" required>
                        <option value="">-- Memuat Proses... --</option>
                    </select>
                </div>

                <div>
                    <label for="operator_id" class="block text-xs font-bold uppercase text-slate-600 mb-1.5">Pilih Operator</label>
                    <select id="operator_id" name="employee_id" class="w-full py-2.5 px-3 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none bg-white" required>
                        <option value="">-- Pilih Operator --</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}">{{ $emp->name }} ({{ $emp->nik }} - {{ strtoupper($emp->skill_level) }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="pt-2">
                    <button type="submit" id="submit_assignment" class="w-full inline-flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 px-4 rounded-xl shadow-sm transition">
                        <i class="fa-solid fa-user-check text-sm"></i>
                        <span>Simpan Penugasan Tugas</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Breakdown Process Info Box -->
        <div class="lg:col-span-7 bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h3 class="text-base font-bold text-slate-800 mb-4 pb-2 border-b border-slate-100">Daftar Proses pada Style Ini</h3>
            <div id="process_info_container" class="space-y-2 max-h-[280px] overflow-y-auto pr-1">
                <div class="text-center py-8 text-slate-400 text-xs">Memuat proses...</div>
            </div>
        </div>
    </div>

    <!-- Active Assignments Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden p-6">
        <div class="mb-4">
            <h3 class="text-base font-bold text-slate-800">Daftar Penugasan Operator (Hari Ini)</h3>
            <p class="text-xs text-slate-500">Operator yang sudah diberikan tugas pengerjaan proses.</p>
        </div>

        <div class="overflow-x-auto rounded-xl border border-slate-100">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-slate-600 font-semibold">
                    <tr>
                        <th class="px-5 py-3.5 text-left">Nama Operator</th>
                        <th class="px-5 py-3.5 text-left">NIK</th>
                        <th class="px-5 py-3.5 text-left">Proses Pengerjaan</th>
                        <th class="px-5 py-3.5 text-left">Style Order</th>
                        <th class="px-5 py-3.5 text-left">Tanggal</th>
                    </tr>
                </thead>
                <tbody id="assignments_table_body" class="bg-white divide-y divide-slate-100">
                    @forelse($tasks as $task)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-5 py-3.5 font-bold text-slate-800">{{ $task->employee->name ?? '-' }}</td>
                            <td class="px-5 py-3.5 font-mono text-xs text-slate-500">{{ $task->employee->nik ?? '-' }}</td>
                            <td class="px-5 py-3.5 font-medium text-indigo-700">{{ $task->breakdownProcess->process_name ?? '-' }}</td>
                            <td class="px-5 py-3.5 font-semibold text-slate-700">{{ $task->styleOrder->code_style ?? '-' }}</td>
                            <td class="px-5 py-3.5 text-slate-500">{{ $task->assigned_date ? $task->assigned_date->format('d M Y') : '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-8 text-center text-slate-400">Belum ada penugasan proses pada tanggal ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function loadProcesses() {
        let styleId = $('#style_order').val();
        if(!styleId) return;

        $('#process_info_container').html('<div class="text-center py-6 text-slate-400 text-xs"><i class="fa-solid fa-spinner fa-spin mr-1"></i> Memuat proses...</div>');
        $('#process_id').html('<option value="">-- Memuat Proses... --</option>');

        $.ajax({
            url: `/ie/api/style-orders/${styleId}/processes`,
            type: "GET",
            success: function(processes) {
                let options = '<option value="">-- Pilih Proses --</option>';
                let listHtml = '';

                if(processes && processes.length > 0) {
                    processes.forEach(p => {
                        options += `<option value="${p.id}">[Seq ${p.sequence_number}] ${p.process_name} (Target: ${p.target_per_hour}/jam)</option>`;
                        listHtml += `
                        <div class="p-2.5 rounded-xl border border-slate-100 bg-slate-50 flex justify-between items-center text-xs">
                            <div>
                                <span class="font-bold text-slate-700 mr-2">#${p.sequence_number}</span>
                                <span class="font-medium text-slate-800">${p.process_name}</span>
                            </div>
                            <div class="text-right font-mono">
                                <span class="text-indigo-600 font-semibold">${p.smv} min</span> &bull; 
                                <span class="text-slate-500">${p.target_per_hour} pcs/jam</span>
                            </div>
                        </div>`;
                    });
                } else {
                    listHtml = '<div class="text-center py-6 text-slate-400 text-xs">Belum ada breakdown proses untuk style order ini.</div>';
                }

                $('#process_id').html(options);
                $('#process_info_container').html(listHtml);
            },
            error: function() {
                $('#process_info_container').html('<div class="text-center py-6 text-rose-500 text-xs">Gagal memuat breakdown proses.</div>');
            }
        });
    }

    $(document).ready(function() {
        loadProcesses();

        $('#style_order').change(function() {
            loadProcesses();
        });

        $('#assignment_form').submit(function(e) {
            e.preventDefault();

            let styleId = $('#style_order').val();
            let processId = $('#process_id').val();
            let employeeId = $('#operator_id').val();
            let date = $('#assignment_date').val();

            if(!styleId || !processId || !employeeId) {
                showNotification('warning', 'Lengkapi seluruh data penugasan!');
                return;
            }

            $.ajax({
                url: "{{ route('spv.task-assignment.store') }}",
                type: "POST",
                data: {
                    style_order_id: styleId,
                    breakdown_process_id: processId,
                    employee_id: employeeId,
                    assigned_date: date
                },
                success: function(res) {
                    showNotification('success', 'Tugas berhasil diberikan!');
                    setTimeout(() => {
                        window.location.reload();
                    }, 600);
                },
                error: function(err) {
                    showNotification('error', err.responseJSON?.error || 'Gagal menyimpan penugasan.');
                }
            });
        });
    });
</script>
@endsection
