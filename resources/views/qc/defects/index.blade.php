@extends('layouts.app')

@section('title', 'Input Defect Line Produksi - Quality Control')

@section('content')
<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Input Defect Line Produksi</h1>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">
                    <i class="fa-solid fa-microscope text-[10px]"></i> Quality Control (QC)
                </span>
            </div>
            <p class="text-sm text-slate-500 mt-1">Pencatatan data temuan cacat barang (defect) per line produksi dan per jam kerja operasional.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('qc.recap.index') }}" class="inline-flex items-center gap-2 px-3.5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg font-medium text-sm transition">
                <i class="fa-solid fa-chart-pie text-indigo-600"></i>
                <span>Lihat Rekap Keseluruhan</span>
            </a>
            <button type="button" onclick="openAddDefectModal()" class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-medium text-sm shadow-sm transition">
                <i class="fa-solid fa-plus"></i>
                <span>+ Catat Defect Baru</span>
            </button>
        </div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-lg bg-rose-50 border border-rose-100 flex items-center justify-center text-rose-600 text-xl font-bold">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Defect Hari Ini</p>
                <h3 class="text-2xl font-bold text-slate-900 mt-0.5">{{ number_format($stats['total_defect']) }} <span class="text-xs font-normal text-slate-500">pcs</span></h3>
            </div>
        </div>

        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-lg bg-amber-50 border border-amber-100 flex items-center justify-center text-amber-600 text-xl font-bold">
                <i class="fa-solid fa-wrench"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Dapat Diperbaiki (Repair)</p>
                <h3 class="text-2xl font-bold text-amber-600 mt-0.5">{{ number_format($stats['repaired']) }} <span class="text-xs font-normal text-slate-500">pcs</span></h3>
            </div>
        </div>

        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-lg bg-red-50 border border-red-100 flex items-center justify-center text-red-600 text-xl font-bold">
                <i class="fa-solid fa-ban"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Ditolak / Afkir (Reject)</p>
                <h3 class="text-2xl font-bold text-red-600 mt-0.5">{{ number_format($stats['rejected']) }} <span class="text-xs font-normal text-slate-500">pcs</span></h3>
            </div>
        </div>

        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-lg bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600 text-xl font-bold">
                <i class="fa-solid fa-industry"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Line Terinspeksi</p>
                <h3 class="text-2xl font-bold text-indigo-600 mt-0.5">{{ $stats['active_lines_inspected'] }} <span class="text-xs font-normal text-slate-500">Line</span></h3>
            </div>
        </div>
    </div>

    <!-- Filters & Table Card -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <!-- Filter Bar -->
        <div class="p-4 sm:p-5 border-b border-slate-200 bg-slate-50/50 flex flex-col md:flex-row gap-3 md:items-center md:justify-between">
            <form method="GET" action="{{ route('qc.defects.index') }}" class="flex flex-wrap items-center gap-3 flex-1">
                <div>
                    <label class="block text-[11px] font-bold uppercase text-slate-500 mb-1">Tanggal Inspeksi</label>
                    <input type="date" name="date" value="{{ $date }}" onchange="this.form.submit()"
                        class="py-2 px-3 text-sm bg-white border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:outline-hidden">
                </div>

                <div>
                    <label class="block text-[11px] font-bold uppercase text-slate-500 mb-1">Filter Line</label>
                    <select name="line_id" onchange="this.form.submit()" class="py-2 px-3 text-sm bg-white border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:outline-hidden">
                        <option value="">Semua Line Produksi</option>
                        @foreach($lines as $l)
                            <option value="{{ $l->id }}" {{ $lineId == $l->id ? 'selected' : '' }}>{{ $l->name }}</option>
                        @endforeach
                    </select>
                </div>

                @if($lineId || $date !== date('Y-m-d'))
                    <div class="pt-5">
                        <a href="{{ route('qc.defects.index') }}" class="text-xs text-rose-600 hover:text-rose-800 font-semibold inline-flex items-center gap-1">
                            <i class="fa-solid fa-rotate-left"></i> Reset Filter
                        </a>
                    </div>
                @endif
            </form>

            <div class="text-xs text-slate-500 font-medium">
                Menampilkan {{ $defects->total() }} Catatan Defect
            </div>
        </div>

        <!-- Table View -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                        <th class="py-3 px-4">Line & Style Order</th>
                        <th class="py-3 px-4">Jam Kerja</th>
                        <th class="py-3 px-4">Jenis Cacat (Defect)</th>
                        <th class="py-3 px-4 text-center">Jumlah</th>
                        <th class="py-3 px-4 text-center">Status / Tindakan</th>
                        <th class="py-3 px-4">Catatan Perbaikan</th>
                        <th class="py-3 px-4">Petugas QC</th>
                        <th class="py-3 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-sm">
                    @forelse($defects as $item)
                        <tr class="hover:bg-slate-50/80 transition" id="row-defect-{{ $item->id }}">
                            <td class="py-3.5 px-4 font-semibold text-slate-900">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold text-xs border border-emerald-100">
                                        {{ str_replace('Line ', 'L', $item->productionLine->name ?? 'L') }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-900">{{ $item->productionLine->name ?? '-' }}</div>
                                        <div class="text-xs text-indigo-600 font-medium">{{ $item->styleOrder->code_style ?? '-' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3.5 px-4 font-medium text-slate-700">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold bg-slate-100 text-slate-700">
                                    <i class="fa-regular fa-clock text-slate-400"></i> Jam {{ $item->hour_number }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 font-medium text-slate-800">
                                {{ $item->defect_type }}
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="font-bold text-rose-600 text-base">{{ $item->defect_qty }}</span> <span class="text-xs text-slate-400">pcs</span>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                @if($item->action_taken === 'repair')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 border border-amber-200">
                                        <i class="fa-solid fa-wrench text-[10px]"></i> Repair
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800 border border-red-200">
                                        <i class="fa-solid fa-ban text-[10px]"></i> Reject (BS)
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-xs text-slate-600 max-w-xs truncate">
                                {{ $item->notes ?: '-' }}
                            </td>
                            <td class="py-3.5 px-4 text-xs text-slate-500">
                                <i class="fa-regular fa-user text-slate-400 mr-1"></i>{{ $item->recorder->name ?? '-' }}
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <div class="inline-flex items-center gap-1.5">
                                    <!-- Edit Button -->
                                    <button type="button" onclick="openEditDefectModal({{ $item->id }}, {{ $item->production_line_id }}, {{ $item->style_order_id }}, '{{ $item->defect_date->format('Y-m-d') }}', {{ $item->hour_number }}, '{{ addslashes($item->defect_type) }}', {{ $item->defect_qty }}, '{{ $item->action_taken }}', '{{ addslashes($item->notes ?? '') }}')" 
                                        class="p-1.5 text-indigo-600 hover:bg-indigo-50 rounded-md transition" title="Edit Data Defect">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>

                                    <!-- Delete Button -->
                                    <button type="button" onclick="deleteDefect({{ $item->id }}, '{{ addslashes($item->defect_type) }}', '{{ $item->productionLine->name ?? 'Line' }}')" 
                                        class="p-1.5 text-rose-600 hover:bg-rose-50 rounded-md transition" title="Hapus Data Defect">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-slate-500">
                                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-emerald-50 text-emerald-600 mb-3">
                                    <i class="fa-solid fa-circle-check text-2xl"></i>
                                </div>
                                <h4 class="font-bold text-slate-800">Tidak Ada Data Defect</h4>
                                <p class="text-xs text-slate-400 mt-1">Belum ada temuan cacat yang tercatat pada kriteria filter tanggal dan line ini.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($defects->hasPages())
            <div class="p-4 border-t border-slate-200 bg-slate-50/50">
                {{ $defects->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Modal Tambah Defect -->
<div id="addDefectModal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full shadow-2xl border border-slate-100 overflow-hidden transform transition-all max-h-[90vh] flex flex-col">
        <div class="p-5 border-b border-slate-200 flex items-center justify-between bg-slate-50 shrink-0">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center text-sm font-bold">
                    <i class="fa-solid fa-plus"></i>
                </div>
                <h3 class="font-bold text-slate-900">Catat Temuan Defect QC</h3>
            </div>
            <button type="button" onclick="closeAddDefectModal()" class="text-slate-400 hover:text-slate-600 text-lg">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form id="addDefectForm" onsubmit="submitAddDefect(event)" class="p-5 space-y-4 overflow-y-auto">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Line Produksi</label>
                    <select name="production_line_id" id="add-line-id" required 
                        class="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:outline-hidden bg-white">
                        <option value="">-- Pilih Line --</option>
                        @foreach($lines as $l)
                            <option value="{{ $l->id }}">{{ $l->name }} (Kap: {{ $l->capacity }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Style Order</label>
                    <select name="style_order_id" id="add-style-id" required 
                        class="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:outline-hidden bg-white">
                        <option value="">-- Pilih Style Order --</option>
                        @foreach($styleOrders as $so)
                            <option value="{{ $so->id }}">{{ $so->code_style }} ({{ $so->buyer }})</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Tanggal</label>
                    <input type="date" name="defect_date" id="add-defect-date" value="{{ date('Y-m-d') }}" required 
                        class="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:outline-hidden">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Jam Kerja</label>
                    <select name="hour_number" id="add-hour-number" required 
                        class="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:outline-hidden bg-white">
                        @for($h = 1; $h <= 8; $h++)
                            <option value="{{ $h }}">Jam Ke-{{ $h }} ({{ 6 + $h }}:00 - {{ 7 + $h }}:00)</option>
                        @endfor
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Jenis Cacat (Defect Category)</label>
                <select name="defect_type_select" id="add-defect-type-select" onchange="handleDefectTypeSelect('add')" 
                    class="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:outline-hidden bg-white mb-2">
                    <option value="">-- Pilih Kategori Cacat --</option>
                    @foreach($defectTypes as $dt)
                        <option value="{{ $dt }}">{{ $dt }}</option>
                    @endforeach
                    <option value="custom">+ Tulis Kategori Lainnya...</option>
                </select>
                <input type="text" name="defect_type" id="add-defect-type" required placeholder="Nama jenis cacat..." 
                    class="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:outline-hidden">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Jumlah Cacat (pcs)</label>
                    <input type="number" name="defect_qty" id="add-defect-qty" min="1" value="1" required 
                        class="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:outline-hidden font-bold text-rose-600">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Tindakan / Status</label>
                    <select name="action_taken" id="add-action-taken" required 
                        class="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:outline-hidden bg-white font-medium">
                        <option value="repair">Dapat Diperbaiki (Repair)</option>
                        <option value="reject">Ditolak / Afkir (Reject / BS)</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Catatan / Area Kerusakan (Opsional)</label>
                <textarea name="notes" id="add-notes" rows="2" placeholder="Contoh: Jahitan bahu kiri mengkerut, perlu dibongkar..." 
                    class="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:outline-hidden"></textarea>
            </div>

            <div class="pt-4 flex items-center justify-end gap-2 border-t border-slate-100">
                <button type="button" onclick="closeAddDefectModal()" class="px-4 py-2 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100 transition">Batal</button>
                <button type="submit" id="btn-save-add-defect" class="px-4 py-2 rounded-lg text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 transition flex items-center gap-2">
                    <i class="fa-solid fa-check"></i>
                    <span>Simpan Data Defect</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Defect -->
<div id="editDefectModal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full shadow-2xl border border-slate-100 overflow-hidden transform transition-all max-h-[90vh] flex flex-col">
        <div class="p-5 border-b border-slate-200 flex items-center justify-between bg-slate-50 shrink-0">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center text-sm font-bold">
                    <i class="fa-solid fa-pen-to-square"></i>
                </div>
                <h3 class="font-bold text-slate-900">Edit Data Defect</h3>
            </div>
            <button type="button" onclick="closeEditDefectModal()" class="text-slate-400 hover:text-slate-600 text-lg">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form id="editDefectForm" onsubmit="submitEditDefect(event)" class="p-5 space-y-4 overflow-y-auto">
            <input type="hidden" id="edit-defect-id">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Line Produksi</label>
                    <select name="production_line_id" id="edit-line-id" required 
                        class="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:outline-hidden bg-white">
                        @foreach($lines as $l)
                            <option value="{{ $l->id }}">{{ $l->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Style Order</label>
                    <select name="style_order_id" id="edit-style-id" required 
                        class="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:outline-hidden bg-white">
                        @foreach($styleOrders as $so)
                            <option value="{{ $so->id }}">{{ $so->code_style }} ({{ $so->buyer }})</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Tanggal</label>
                    <input type="date" name="defect_date" id="edit-defect-date" required 
                        class="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:outline-hidden">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Jam Kerja</label>
                    <select name="hour_number" id="edit-hour-number" required 
                        class="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:outline-hidden bg-white">
                        @for($h = 1; $h <= 8; $h++)
                            <option value="{{ $h }}">Jam Ke-{{ $h }}</option>
                        @endfor
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Jenis Cacat (Defect)</label>
                <input type="text" name="defect_type" id="edit-defect-type" required 
                    class="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:outline-hidden">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Jumlah Cacat (pcs)</label>
                    <input type="number" name="defect_qty" id="edit-defect-qty" min="1" required 
                        class="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:outline-hidden font-bold text-rose-600">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Tindakan</label>
                    <select name="action_taken" id="edit-action-taken" required 
                        class="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:outline-hidden bg-white">
                        <option value="repair">Dapat Diperbaiki (Repair)</option>
                        <option value="reject">Ditolak / Afkir (Reject / BS)</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Catatan / Remark</label>
                <textarea name="notes" id="edit-notes" rows="2" 
                    class="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:outline-hidden"></textarea>
            </div>

            <div class="pt-4 flex items-center justify-end gap-2 border-t border-slate-100">
                <button type="button" onclick="closeEditDefectModal()" class="px-4 py-2 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100 transition">Batal</button>
                <button type="submit" id="btn-save-edit-defect" class="px-4 py-2 rounded-lg text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition flex items-center gap-2">
                    <i class="fa-solid fa-save"></i>
                    <span>Simpan Perubahan</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function handleDefectTypeSelect(mode) {
        const select = $(`#${mode}-defect-type-select`);
        const input = $(`#${mode}-defect-type`);
        if (select.val() === 'custom') {
            input.val('').focus();
        } else if (select.val()) {
            input.val(select.val());
        }
    }

    // Modal Add
    function openAddDefectModal() {
        $('#addDefectForm')[0].reset();
        $('#add-defect-date').val('{{ $date }}');
        $('#addDefectModal').removeClass('hidden');
    }
    function closeAddDefectModal() {
        $('#addDefectModal').addClass('hidden');
    }

    // Modal Edit
    function openEditDefectModal(id, lineId, styleId, date, hour, type, qty, action, notes) {
        $('#edit-defect-id').val(id);
        $('#edit-line-id').val(lineId);
        $('#edit-style-id').val(styleId);
        $('#edit-defect-date').val(date);
        $('#edit-hour-number').val(hour);
        $('#edit-defect-type').val(type);
        $('#edit-defect-qty').val(qty);
        $('#edit-action-taken').val(action);
        $('#edit-notes').val(notes);
        $('#editDefectModal').removeClass('hidden');
    }
    function closeEditDefectModal() {
        $('#editDefectModal').addClass('hidden');
    }

    // Submit Add via AJAX
    function submitAddDefect(e) {
        e.preventDefault();
        const btn = $('#btn-save-add-defect');
        btn.prop('disabled', true).html('<i class="fa-solid fa-circle-notch fa-spin"></i> Menyimpan...');

        const data = {
            production_line_id: $('#add-line-id').val(),
            style_order_id: $('#add-style-id').val(),
            defect_date: $('#add-defect-date').val(),
            hour_number: $('#add-hour-number').val(),
            defect_type: $('#add-defect-type').val(),
            defect_qty: $('#add-defect-qty').val(),
            action_taken: $('#add-action-taken').val(),
            notes: $('#add-notes').val(),
            _token: '{{ csrf_token() }}'
        };

        $.ajax({
            url: "{{ route('qc.defects.store') }}",
            type: "POST",
            data: data,
            success: function(res) {
                closeAddDefectModal();
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: res.message,
                    timer: 1400,
                    showConfirmButton: false
                }).then(() => {
                    location.reload();
                });
            },
            error: function(xhr) {
                btn.prop('disabled', false).html('<i class="fa-solid fa-check"></i> Simpan Data Defect');
                let msg = 'Gagal menyimpan data defect.';
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    msg = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                } else if (xhr.responseJSON && xhr.responseJSON.error) {
                    msg = xhr.responseJSON.error;
                }
                Swal.fire({ icon: 'error', title: 'Gagal', html: msg });
            }
        });
    }

    // Submit Edit via AJAX
    function submitEditDefect(e) {
        e.preventDefault();
        const id = $('#edit-defect-id').val();
        const btn = $('#btn-save-edit-defect');
        btn.prop('disabled', true).html('<i class="fa-solid fa-circle-notch fa-spin"></i> Menyimpan...');

        const data = {
            production_line_id: $('#edit-line-id').val(),
            style_order_id: $('#edit-style-id').val(),
            defect_date: $('#edit-defect-date').val(),
            hour_number: $('#edit-hour-number').val(),
            defect_type: $('#edit-defect-type').val(),
            defect_qty: $('#edit-defect-qty').val(),
            action_taken: $('#edit-action-taken').val(),
            notes: $('#edit-notes').val(),
            _token: '{{ csrf_token() }}',
            _method: 'PUT'
        };

        $.ajax({
            url: `/qc/defects/${id}`,
            type: "POST",
            data: data,
            success: function(res) {
                closeEditDefectModal();
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: res.message,
                    timer: 1400,
                    showConfirmButton: false
                }).then(() => {
                    location.reload();
                });
            },
            error: function(xhr) {
                btn.prop('disabled', false).html('<i class="fa-solid fa-save"></i> Simpan Perubahan');
                let msg = 'Gagal memperbarui data defect.';
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    msg = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                }
                Swal.fire({ icon: 'error', title: 'Gagal', html: msg });
            }
        });
    }

    // Delete Defect via AJAX
    function deleteDefect(id, type, lineName) {
        Swal.fire({
            title: `Hapus Defect?`,
            text: `Data defect "${type}" pada ${lineName} akan dihapus dan akumulasi jam kerja akan disinkronisasi ulang.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Hapus Data',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/qc/defects/${id}`,
                    type: "POST",
                    data: {
                        _token: '{{ csrf_token() }}',
                        _method: 'DELETE'
                    },
                    success: function(res) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Terhapus',
                            text: res.message,
                            timer: 1300,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    },
                    error: function(xhr) {
                        Swal.fire('Gagal', xhr.responseJSON?.error || 'Gagal menghapus defect', 'error');
                    }
                });
            }
        });
    }
</script>
@endsection
