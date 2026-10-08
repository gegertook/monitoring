@extends('layouts.app')

@section('title', 'Validasi Data Produksi (Admin Produksi)')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Verifikasi & Validasi Output SPV</h2>
            <p class="text-sm text-slate-500">Admin produksi memeriksa kesesuaian data output dan defect yang diinput supervisor line.</p>
        </div>
        <div class="flex items-center gap-3">
            <button id="bulk_validate_btn" class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-2.5 px-4 rounded-xl shadow-sm shadow-emerald-600/20 transition text-sm">
                <i class="fa-solid fa-check-double"></i>
                <span>Validasi Data Terpilih</span>
            </button>
        </div>
    </div>

    <!-- KPI Summary Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl shadow-sm border-l-4 border-amber-500 p-4 border border-slate-200">
            <p class="text-xs text-slate-400 uppercase font-bold tracking-wider">Menunggu Validasi</p>
            <p class="text-2xl font-black text-amber-600 mt-1" id="stat_pending_count">{{ $totalPending }}</p>
            <span class="text-[10px] text-slate-400">jam pengerjaan pending</span>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border-l-4 border-emerald-500 p-4 border border-slate-200">
            <p class="text-xs text-slate-400 uppercase font-bold tracking-wider">Disetujui Hari Ini</p>
            <p class="text-2xl font-black text-emerald-600 mt-1">{{ $validatedToday }}</p>
            <span class="text-[10px] text-slate-400">rekaman terverifikasi</span>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border-l-4 border-indigo-500 p-4 border border-slate-200">
            <p class="text-xs text-slate-400 uppercase font-bold tracking-wider">Output Hari Ini</p>
            <p class="text-2xl font-black text-indigo-700 mt-1">{{ number_format($todayOutputSum) }}</p>
            <span class="text-[10px] text-slate-400">pcs barang bagus</span>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border-l-4 border-rose-500 p-4 border border-slate-200">
            <p class="text-xs text-slate-400 uppercase font-bold tracking-wider">Defect Rate Hari Ini</p>
            <p class="text-2xl font-black text-rose-600 mt-1">{{ $avgDefectRate }}%</p>
            <span class="text-[10px] text-slate-400">rata-rata per jam</span>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-2xl shadow-sm p-5 border border-slate-200">
        <form method="GET" action="{{ route('admin.validation.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
            <div>
                <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Tanggal Produksi</label>
                <input type="date" name="date" value="{{ request('date') }}" class="w-full py-2 px-3 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none bg-white">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Production Line</label>
                <select name="production_line_id" class="w-full py-2 px-3 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none bg-white">
                    <option value="">Semua Line</option>
                    @foreach($lines as $line)
                        <option value="{{ $line->id }}" {{ request('production_line_id') == $line->id ? 'selected' : '' }}>{{ $line->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Style Order</label>
                <select name="style_order_id" class="w-full py-2 px-3 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none bg-white">
                    <option value="">Semua Style Order</option>
                    @foreach($styleOrders as $so)
                        <option value="{{ $so->id }}" {{ request('style_order_id') == $so->id ? 'selected' : '' }}>{{ $so->code_style }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="flex-1 bg-slate-800 hover:bg-slate-900 text-white font-semibold py-2 px-4 rounded-xl text-sm transition">
                    <i class="fa-solid fa-filter mr-1"></i> Filter
                </button>
                <a href="{{ route('admin.validation.index') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-600 font-semibold py-2 px-3 rounded-xl text-sm transition" title="Reset">
                    <i class="fa-solid fa-rotate-left"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Validation Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-base font-bold text-slate-800">Antrian Data Output Pending</h3>
            <span class="text-xs text-slate-400">Baris dengan warna merah memiliki Defect Rate &gt; 5.0%</span>
        </div>

        <div class="overflow-x-auto rounded-xl border border-slate-100">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-slate-600 font-semibold">
                    <tr>
                        <th class="px-5 py-3.5 text-center w-12">
                            <input type="checkbox" id="select_all" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 w-4 h-4">
                        </th>
                        <th class="px-5 py-3.5 text-left">Tanggal</th>
                        <th class="px-5 py-3.5 text-left">Line & Style Order</th>
                        <th class="px-5 py-3.5 text-left">Jam Ke</th>
                        <th class="px-5 py-3.5 text-right">Output (Bagus)</th>
                        <th class="px-5 py-3.5 text-right">Defect (Cacat)</th>
                        <th class="px-5 py-3.5 text-right">Defect Rate</th>
                        <th class="px-5 py-3.5 text-left">Pencatat (SPV)</th>
                        <th class="px-5 py-3.5 text-center w-28">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white" id="validation_table_body">
                    @forelse($pendingOutputs as $output)
                        @php
                            $totalUnits = $output->output_qty + $output->defect_qty;
                            $rate = $totalUnits > 0 ? round(($output->defect_qty / $totalUnits) * 100, 2) : 0;
                            $isHighDefect = $rate > 5.0;
                        @endphp
                        <tr class="hover:bg-slate-50 transition {{ $isHighDefect ? 'bg-rose-50/60' : '' }}" id="row-{{ $output->id }}">
                            <td class="px-5 py-3.5 text-center">
                                <input type="checkbox" class="row-checkbox rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 w-4 h-4" value="{{ $output->id }}">
                            </td>
                            <td class="px-5 py-3.5 text-slate-600 whitespace-nowrap">{{ $output->production_date ? $output->production_date->format('d M Y') : '-' }}</td>
                            <td class="px-5 py-3.5">
                                <span class="font-bold text-slate-800 block">{{ $output->productionLine->name ?? 'Line ?' }}</span>
                                <span class="text-xs text-indigo-600 font-semibold">{{ $output->styleOrder->code_style ?? '-' }}</span>
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 font-bold text-xs">Jam {{ $output->hour_number }}</span>
                            </td>
                            <td class="px-5 py-3.5 text-right font-extrabold text-slate-800">{{ number_format($output->output_qty) }} pcs</td>
                            <td class="px-5 py-3.5 text-right font-extrabold text-rose-600">{{ number_format($output->defect_qty) }} pcs</td>
                            <td class="px-5 py-3.5 text-right font-mono font-bold {{ $isHighDefect ? 'text-rose-600' : 'text-slate-700' }}">
                                {{ $rate }}%
                            </td>
                            <td class="px-5 py-3.5 text-slate-600 text-xs">{{ $output->recorder->name ?? 'SPV' }}</td>
                            <td class="px-5 py-3.5 text-center">
                                <button type="button" onclick="validateSingleOutput({{ $output->id }})" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-600 hover:text-white transition">
                                    <i class="fa-solid fa-check mr-1"></i> Validasi
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-5 py-12 text-center text-slate-400">
                                <i class="fa-solid fa-clipboard-check text-3xl text-emerald-400 mb-2 block"></i>
                                Semua data output produksi telah divalidasi. Tidak ada data pending!
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $pendingOutputs->links() }}
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $('#select_all').change(function() {
        $('.row-checkbox').prop('checked', $(this).is(':checked'));
    });

    function validateSingleOutput(id) {
        Swal.fire({
            title: 'Validasi Output Ini?',
            text: 'Data akan diverifikasi dan masuk ke laporan resmi pabrik.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#059669',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Validasi',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/admin-produksi/validation/${id}/validate`,
                    type: "POST",
                    success: function(response) {
                        showNotification('success', response.message || 'Data berhasil divalidasi!');
                        $(`#row-${id}`).fadeOut(400, function() { $(this).remove(); });
                    },
                    error: function() {
                        showNotification('error', 'Gagal memvalidasi output.');
                    }
                });
            }
        });
    }

    $('#bulk_validate_btn').click(function() {
        let selectedIds = [];
        $('.row-checkbox:checked').each(function() {
            selectedIds.push($(this).val());
        });

        if (selectedIds.length === 0) {
            showNotification('warning', 'Pilih minimal satu data output untuk divalidasi!');
            return;
        }

        Swal.fire({
            title: `Validasi ${selectedIds.length} Data Output?`,
            text: 'Seluruh data terpilih akan disetujui (validated) secara massal.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#059669',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Validasi Semua',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ route('admin.validation.bulk') }}",
                    type: "POST",
                    data: { output_ids: selectedIds },
                    success: function(response) {
                        Swal.fire('Berhasil!', response.message || 'Data berhasil divalidasi.', 'success');
                        setTimeout(() => { window.location.reload(); }, 600);
                    },
                    error: function() {
                        showNotification('error', 'Gagal memvalidasi data terpilih.');
                    }
                });
            }
        });
    });
</script>
@endsection
