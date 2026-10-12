@extends('layouts.app')

@section('title', 'Papan Downtime Mesin (Admin Mekanik)')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Papan Downtime Mesin</h2>
            <p class="text-sm text-slate-500">Laporan mesin rusak dari SPV: selesaikan dengan perbaikan atau ganti mesin.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('mekanik.dashboard') }}" class="inline-flex items-center gap-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold py-2 px-4 rounded-xl transition text-xs">
                <i class="fa-solid fa-gauge-high"></i> <span>Dashboard</span>
            </a>
            <a href="{{ route('mekanik.machines.index') }}" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-xl shadow-sm transition text-xs">
                <i class="fa-solid fa-gears"></i> <span>Master Mesin</span>
            </a>
        </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="bg-white rounded-2xl shadow-sm border-l-4 border-rose-500 border border-slate-200 p-4">
            <p class="text-xs text-slate-400 uppercase font-bold tracking-wider">Total Aktif</p>
            <p class="text-2xl font-black text-rose-600 mt-1">{{ $stats['open'] }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border-l-4 border-amber-500 border border-slate-200 p-4">
            <p class="text-xs text-slate-400 uppercase font-bold tracking-wider">Belum Dikirim</p>
            <p class="text-2xl font-black text-amber-600 mt-1">{{ $stats['waiting_mechanic'] }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border-l-4 border-blue-500 border border-slate-200 p-4">
            <p class="text-xs text-slate-400 uppercase font-bold tracking-wider">Mekanik di Lapangan</p>
            <p class="text-2xl font-black text-blue-600 mt-1">{{ $stats['in_progress'] }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border-l-4 border-purple-500 border border-slate-200 p-4">
            <p class="text-xs text-slate-400 uppercase font-bold tracking-wider">Minta Ganti</p>
            <p class="text-2xl font-black text-purple-600 mt-1">{{ $stats['replacement_requested'] }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border-l-4 border-emerald-500 border border-slate-200 p-4">
            <p class="text-xs text-slate-400 uppercase font-bold tracking-wider">Selesai Hari Ini</p>
            <p class="text-2xl font-black text-emerald-600 mt-1">{{ $stats['resolved_today'] }}</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1.5">Status</label>
                <select id="filter_status" class="w-full py-2 px-3 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none bg-white">
                    <option value="">Semua status</option>
                    <option value="open" {{ request('status') === 'open' ? 'selected' : '' }}>Aktif (belum selesai)</option>
                    <option value="reported" {{ request('status') === 'reported' ? 'selected' : '' }}>Dilaporkan</option>
                    <option value="mechanic_sent" {{ request('status') === 'mechanic_sent' ? 'selected' : '' }}>Mekanik Dikirim</option>
                    <option value="replacement_requested" {{ request('status') === 'replacement_requested' ? 'selected' : '' }}>Minta Ganti Mesin</option>
                    <option value="repaired" {{ request('status') === 'repaired' ? 'selected' : '' }}>Diperbaiki</option>
                    <option value="replaced" {{ request('status') === 'replaced' ? 'selected' : '' }}>Diganti</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1.5">Line</label>
                <select id="filter_line" class="w-full py-2 px-3 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none bg-white">
                    <option value="">Semua line</option>
                    @foreach($lines as $line)
                        <option value="{{ $line->id }}" {{ request('line_id') == $line->id ? 'selected' : '' }}>{{ $line->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <button id="btn_reload" class="w-full inline-flex items-center justify-center gap-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold py-2 px-4 rounded-xl transition text-xs">
                    <i class="fa-solid fa-arrows-rotate"></i> <span>Muat Ulang</span>
                </button>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-slate-600 font-semibold">
                    <tr>
                        <th class="px-5 py-3.5 text-left">Waktu Lapor</th>
                        <th class="px-5 py-3.5 text-left">Mesin</th>
                        <th class="px-5 py-3.5 text-left">Line</th>
                        <th class="px-5 py-3.5 text-left">Keluhan</th>
                        <th class="px-5 py-3.5 text-left">Status</th>
                        <th class="px-5 py-3.5 text-left">Mekanik</th>
                        <th class="px-5 py-3.5 text-center w-52">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-100">
                    @forelse($breakdowns as $b)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-5 py-3.5 text-xs text-slate-500">{{ $b->reported_at->format('d M Y H:i') }}</td>
                            <td class="px-5 py-3.5">
                                <span class="font-bold text-slate-800 block">{{ $b->machine->code ?? '-' }}</span>
                                <span class="text-xs text-slate-400">{{ $b->machine->name ?? '-' }}</span>
                            </td>
                            <td class="px-5 py-3.5 font-semibold text-slate-700">{{ $b->productionLine->name ?? '-' }}</td>
                            <td class="px-5 py-3.5 text-xs text-slate-500">{{ $b->notes ?: '-' }}</td>
                            <td class="px-5 py-3.5">{!! \App\Http\Controllers\MekanikController::statusBadge($b->status) !!}</td>
                            <td class="px-5 py-3.5 text-xs text-slate-600">{{ $b->mechanic->name ?? '-' }}</td>
                            <td class="px-5 py-3.5 text-center">
                                @if($b->isOpen())
                                    <div class="inline-flex items-center gap-1.5">
                                        <button type="button" onclick="resolveBreakdown({{ $b->id }}, 'repaired')" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-600 hover:text-white transition">
                                            <i class="fa-solid fa-wrench mr-1"></i> Selesai Diperbaiki
                                        </button>
                                        <button type="button" onclick="openReplaceModal({{ $b->id }}, '{{ $b->machine->code ?? '-' }}', '{{ $b->productionLine->name ?? '-' }}')" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-purple-50 text-purple-700 hover:bg-purple-600 hover:text-white transition">
                                            <i class="fa-solid fa-right-left mr-1"></i> Ganti Mesin
                                        </button>
                                    </div>
                                @else
                                    <span class="text-xs text-slate-400">{{ $b->resolved_at?->format('d M H:i') ?: '-' }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-10 text-center text-slate-400">Tidak ada laporan downtime untuk filter ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Ganti Mesin -->
<div id="replaceModal" class="fixed inset-0 z-50 hidden overflow-y-auto" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="fixed inset-0 bg-slate-900 opacity-50" onclick="closeReplaceModal()"></div>
        <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md p-6 space-y-4">
            <div class="flex justify-between items-center">
                <h3 class="text-base font-bold text-slate-800">Ganti Mesin</h3>
                <button onclick="closeReplaceModal()" class="text-slate-400 hover:text-slate-700"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <p class="text-xs text-slate-500" id="replace_info"></p>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1.5">Mesin Pengganti (dari pool, siap pakai)</label>
                <select id="replacement_machine_id" class="w-full py-2 px-3 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none bg-white">
                    <option value="">-- Pilih mesin pengganti --</option>
                    @foreach($replacementCandidates as $m)
                        <option value="{{ $m->id }}">{{ $m->code }} - {{ $m->name }}{{ $m->machine_type ? ' ('.$m->machine_type.')' : '' }}</option>
                    @endforeach
                </select>
                @if($replacementCandidates->isEmpty())
                    <p class="text-[11px] text-rose-500 mt-1">Tidak ada mesin siap pakai di pool. Lepas dulu mesin dari line lain atau tambah mesin baru.</p>
                @endif
            </div>
            <div class="flex justify-end gap-2 mt-4">
                <button type="button" onclick="closeReplaceModal()" class="py-2 px-4 rounded-xl text-sm font-semibold bg-slate-100 text-slate-600 hover:bg-slate-200 transition">Batal</button>
                <button type="button" onclick="confirmReplace()" class="py-2 px-4 rounded-xl text-sm font-semibold bg-purple-600 text-white hover:bg-purple-700 transition">Konfirmasi Ganti</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    let activeBreakdownId = null;

    function openReplaceModal(id, machineCode, lineName) {
        activeBreakdownId = id;
        $('#replace_info').text(`Mesin ${machineCode} yang rusak di ${lineName} akan dilepas, mesin pengganti masuk ke line tersebut.`);
        $('#replaceModal').removeClass('hidden');
    }

    function closeReplaceModal() {
        activeBreakdownId = null;
        $('#replaceModal').addClass('hidden');
        $('#replacement_machine_id').val('');
    }

    function confirmReplace() {
        let replacementId = $('#replacement_machine_id').val();
        if (!replacementId) {
            showNotification('warning', 'Pilih mesin pengganti terlebih dahulu.');
            return;
        }
        resolveBreakdown(activeBreakdownId, 'replaced', replacementId);
        closeReplaceModal();
    }

    function resolveBreakdown(id, resolution, replacementId = null) {
        $.ajax({
            url: `/mekanik/downtime/${id}/resolve`,
            type: "POST",
            data: {
                resolution: resolution,
                replacement_machine_id: replacementId || ''
            },
            success: function(res) {
                showNotification('success', res.message || 'Downtime diselesaikan.');
                setTimeout(() => window.location.reload(), 700);
            },
            error: function(err) {
                showNotification('error', err.responseJSON?.error || 'Gagal menyelesaikan downtime.');
            }
        });
    }

    $(document).ready(function() {
        $('#btn_reload').click(() => window.location.reload());

        function applyFilters() {
            let params = new URLSearchParams();
            if ($('#filter_status').val()) params.set('status', $('#filter_status').val());
            if ($('#filter_line').val()) params.set('line_id', $('#filter_line').val());
            let qs = params.toString();
            window.location.href = "{{ route('mekanik.downtime.index') }}" + (qs ? '?' + qs : '');
        }

        $('#filter_status').change(applyFilters);
        $('#filter_line').change(applyFilters);
    });
</script>
@endsection
