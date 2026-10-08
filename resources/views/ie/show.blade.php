@extends('layouts.app')

@section('title', 'Breakdown Process - ' . $styleOrder->code_style)

@section('content')
<div class="space-y-6">
    <div class="flex items-center gap-2 text-xs text-slate-500">
        <a href="{{ route('ie.breakdown.index') }}" class="hover:text-indigo-600 font-medium">Breakdown Process</a>
        <i class="fa-solid fa-chevron-right text-[10px]"></i>
        <span class="text-slate-800 font-bold">{{ $styleOrder->code_style }}</span>
    </div>

    <!-- Style Order Info Header -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6 pb-4 border-b border-slate-100">
            <div>
                <span class="text-xs font-bold text-indigo-600 bg-indigo-50 px-2.5 py-1 rounded-lg">IE Engineering Master</span>
                <h2 class="text-2xl font-black text-slate-800 mt-1">{{ $styleOrder->code_style }}</h2>
                <p class="text-xs text-slate-500">Buyer: {{ $styleOrder->buyer }} &bull; Line: {{ $styleOrder->productionLine ? $styleOrder->productionLine->name : 'Unassigned' }}</p>
            </div>
            <button onclick="openProcessModal()" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 px-4 rounded-xl shadow-sm transition">
                <i class="fa-solid fa-plus text-sm"></i>
                <span>Tambah Elemen Proses</span>
            </button>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="bg-slate-50 p-4 rounded-xl">
                <span class="text-xs font-semibold text-slate-400 block">Total Qty Target</span>
                <span class="text-xl font-bold text-slate-800">{{ number_format($styleOrder->total_qty_target) }} pcs</span>
            </div>
            <div class="bg-slate-50 p-4 rounded-xl">
                <span class="text-xs font-semibold text-slate-400 block">Deadline Produksi</span>
                <span class="text-xl font-bold text-slate-800">{{ $styleOrder->deadline ? $styleOrder->deadline->format('d M Y') : '-' }}</span>
            </div>
            <div class="bg-indigo-50 p-4 rounded-xl">
                <span class="text-xs font-semibold text-indigo-500 block">Total SMV (Kumulatif)</span>
                <span class="text-xl font-extrabold text-indigo-700" id="totalSmv">0.0000</span>
                <span class="text-[10px] text-indigo-400 block">menit / pcs</span>
            </div>
            <div class="bg-emerald-50 p-4 rounded-xl">
                <span class="text-xs font-semibold text-emerald-500 block">Jumlah Elemen Proses</span>
                <span class="text-xl font-extrabold text-emerald-700" id="totalProcesses">0</span>
                <span class="text-[10px] text-emerald-500 block">tahap pengerjaan</span>
            </div>
        </div>
    </div>

    <!-- Process Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden p-6">
        <div class="mb-4">
            <h3 class="text-base font-bold text-slate-800">Urutan Proses Teknis (Breakdown Workflow)</h3>
            <p class="text-xs text-slate-500">Parameter ini menentukan standar kecepatan produksi untuk line SPV.</p>
        </div>

        <div class="overflow-x-auto rounded-xl border border-slate-100">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-slate-600 font-semibold">
                    <tr>
                        <th class="px-5 py-3.5 text-center w-20">No. Urut</th>
                        <th class="px-5 py-3.5 text-left">Nama Proses Pengerjaan</th>
                        <th class="px-5 py-3.5 text-right">SMV (Standard Minute Value)</th>
                        <th class="px-5 py-3.5 text-right">Target Output / Jam</th>
                        <th class="px-5 py-3.5 text-center w-28">Aksi</th>
                    </tr>
                </thead>
                <tbody id="processTableBody" class="bg-white divide-y divide-slate-100">
                    <!-- Populated via AJAX -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add / Edit Process Modal -->
<div id="processModal" class="fixed inset-0 z-50 hidden overflow-y-auto" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen p-4 text-center">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" onclick="closeProcessModal()"></div>

        <div class="relative bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all w-full max-w-md p-6 z-10 border border-slate-100">
            <form id="processForm" class="space-y-4">
                <h3 class="text-lg font-bold text-slate-800" id="modalTitle">Tambah Elemen Proses</h3>

                <input type="hidden" id="process_id">

                <div>
                    <label for="sequence_number" class="block text-xs font-bold uppercase text-slate-600 mb-1">No. Urut Proses</label>
                    <input type="number" id="sequence_number" class="w-full py-2 px-3 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none" required min="1">
                </div>

                <div>
                    <label for="process_name" class="block text-xs font-bold uppercase text-slate-600 mb-1">Nama Proses</label>
                    <input type="text" id="process_name" placeholder="" class="w-full py-2 px-3 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none" required>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="smv" class="block text-xs font-bold uppercase text-slate-600 mb-1">SMV (Menit)</label>
                        <input type="number" id="smv" step="0.0001" placeholder="" class="w-full py-2 px-3 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none" required min="0.0001">
                    </div>

                    <div>
                        <label for="target_per_hour" class="block text-xs font-bold uppercase text-slate-600 mb-1">Target / Jam</label>
                        <input type="number" id="target_per_hour" placeholder="" class="w-full py-2 px-3 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none" required min="1">
                    </div>
                </div>

                <div class="flex justify-end gap-2.5 pt-4 border-t border-slate-100">
                    <button type="button" onclick="closeProcessModal()" class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 transition">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm transition">
                        Simpan Proses
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const styleOrderId = {{ $styleOrder->id }};

    function loadProcesses() {
        $('#processTableBody').html('<tr><td colspan="5" class="text-center py-6 text-slate-400"><i class="fa-solid fa-spinner fa-spin mr-2"></i>Memuat proses...</td></tr>');

        $.ajax({
            url: `/ie/api/style-orders/${styleOrderId}/processes`,
            type: 'GET',
            success: function(response) {
                let rows = '';
                let totalSmv = 0;
                let maxSequence = 0;

                if(response && response.length > 0) {
                    $.each(response, function(i, item) {
                        totalSmv += parseFloat(item.smv);
                        if(parseInt(item.sequence_number) > maxSequence) maxSequence = parseInt(item.sequence_number);

                        rows += `<tr class="hover:bg-slate-50 transition">
                            <td class="px-5 py-3.5 text-center font-bold text-slate-700">${item.sequence_number}</td>
                            <td class="px-5 py-3.5 font-medium text-slate-800">${item.process_name}</td>
                            <td class="px-5 py-3.5 text-right font-semibold text-indigo-600">${parseFloat(item.smv).toFixed(4)}</td>
                            <td class="px-5 py-3.5 text-right font-bold text-slate-700">${Number(item.target_per_hour).toLocaleString()} pcs</td>
                            <td class="px-5 py-3.5 text-center">
                                <div class="inline-flex gap-1.5">
                                    <button onclick='editProcess(${JSON.stringify(item)})' class="p-1.5 text-xs text-amber-600 hover:bg-amber-50 rounded-lg transition" title="Edit">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                    <button onclick="deleteProcess(${item.id})" class="p-1.5 text-xs text-rose-600 hover:bg-rose-50 rounded-lg transition" title="Hapus">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>`;
                    });
                } else {
                    rows = '<tr><td colspan="5" class="text-center py-6 text-slate-400">Belum ada breakdown proses yang diinput untuk style ini.</td></tr>';
                }

                $('#processTableBody').html(rows);
                $('#totalSmv').text(totalSmv.toFixed(4));
                $('#totalProcesses').text(response ? response.length : 0);
                $('#processModal').data('next-sequence', maxSequence + 1);
            },
            error: function(err) {
                $('#processTableBody').html('<tr><td colspan="5" class="text-center py-6 text-rose-500">Gagal memuat data breakdown proses.</td></tr>');
            }
        });
    }

    function openProcessModal() {
        $('#processForm')[0].reset();
        $('#process_id').val('');
        $('#modalTitle').text('Tambah Elemen Proses');
        $('#sequence_number').val($('#processModal').data('next-sequence') || 1);
        $('#processModal').removeClass('hidden');
    }

    function editProcess(item) {
        $('#process_id').val(item.id);
        $('#sequence_number').val(item.sequence_number);
        $('#process_name').val(item.process_name);
        $('#smv').val(item.smv);
        $('#target_per_hour').val(item.target_per_hour);
        $('#modalTitle').text('Edit Elemen Proses');
        $('#processModal').removeClass('hidden');
    }

    function closeProcessModal() {
        $('#processModal').addClass('hidden');
    }

    $('#processForm').on('submit', function(e) {
        e.preventDefault();
        let processId = $('#process_id').val();
        let isEdit = processId !== '';
        let url = isEdit ? `/ie/breakdown/process/${processId}` : `/ie/breakdown/${styleOrderId}/process`;
        let method = isEdit ? 'PUT' : 'POST';

        let data = {
            sequence_number: $('#sequence_number').val(),
            process_name: $('#process_name').val(),
            smv: $('#smv').val(),
            target_per_hour: $('#target_per_hour').val()
        };

        $.ajax({
            url: url,
            type: method,
            data: data,
            success: function(response) {
                closeProcessModal();
                showNotification('success', isEdit ? 'Proses berhasil diupdate!' : 'Proses baru berhasil ditambahkan!');
                loadProcesses();
            },
            error: function(err) {
                showNotification('error', 'Gagal menyimpan proses.');
            }
        });
    });

    function deleteProcess(id) {
        Swal.fire({
            title: 'Hapus Proses?',
            text: 'Data pengerjaan proses ini akan dihapus!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e11d48',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/ie/breakdown/process/${id}`,
                    type: 'DELETE',
                    success: function(response) {
                        showNotification('success', 'Proses berhasil dihapus.');
                        loadProcesses();
                    },
                    error: function(err) {
                        showNotification('error', 'Gagal menghapus proses.');
                    }
                });
            }
        });
    }

    $(document).ready(function() {
        loadProcesses();
    });
</script>
@endsection
