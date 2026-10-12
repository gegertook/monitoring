@extends('layouts.app')

@section('title', 'Master Mesin (Admin Mekanik)')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Master Mesin & Plotting Line</h2>
            <p class="text-sm text-slate-500">Input data mesin dan tempatkan mesin ke line. Satu mesin hanya berada di satu line dalam satu waktu.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('mekanik.dashboard') }}" class="inline-flex items-center gap-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold py-2 px-4 rounded-xl transition text-xs">
                <i class="fa-solid fa-gauge-high"></i> <span>Dashboard</span>
            </a>
            <button id="btn_add_machine" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-xl shadow-sm transition text-xs">
                <i class="fa-solid fa-plus"></i> <span>Tambah Mesin</span>
            </button>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1.5">Cari Mesin</label>
                <input type="text" id="filter_search" placeholder="Kode / nama / tipe mesin..." class="w-full py-2 px-3 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1.5">Filter Line</label>
                <select id="filter_line" class="w-full py-2 px-3 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none bg-white">
                    <option value="">Semua line</option>
                    <option value="pool">Belum di line (pool)</option>
                    @foreach($lines as $line)
                        <option value="{{ $line->id }}">{{ $line->name }}</option>
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
                        <th class="px-5 py-3.5 text-left">Kode Mesin</th>
                        <th class="px-5 py-3.5 text-left">Nama / Tipe</th>
                        <th class="px-5 py-3.5 text-left">Line</th>
                        <th class="px-5 py-3.5 text-left">Status</th>
                        <th class="px-5 py-3.5 text-left w-64">Plotting Line</th>
                        <th class="px-5 py-3.5 text-center w-32">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-100" id="machine_table_body">
                    @forelse($machines as $machine)
                        <tr class="hover:bg-slate-50 transition" data-code="{{ $machine->code }}" data-name="{{ $machine->name }}" data-type="{{ $machine->machine_type }}" data-description="{{ $machine->description }}">
                            <td class="px-5 py-3.5 font-mono font-bold text-slate-800">{{ $machine->code }}</td>
                            <td class="px-5 py-3.5">
                                <span class="font-bold text-slate-800 block">{{ $machine->name }}</span>
                                <span class="text-xs text-slate-400">{{ $machine->machine_type ?: '-' }}</span>
                            </td>
                            <td class="px-5 py-3.5 font-semibold text-slate-700">{{ $machine->currentLine->name ?? 'Pool' }}</td>
                            <td class="px-5 py-3.5">{!! \App\Http\Controllers\MekanikController::statusBadge($machine->status) !!}</td>
                            <td class="px-5 py-3.5">
                                <select class="line-select w-full py-1.5 px-2 text-xs border border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-indigo-500" data-machine-id="{{ $machine->id }}">
                                    <option value="">-- Pool (tidak di line) --</option>
                                    @foreach($lines as $line)
                                        <option value="{{ $line->id }}" {{ $machine->current_line_id === $line->id ? 'selected' : '' }}>{{ $line->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                <div class="inline-flex items-center gap-1">
                                    <button type="button" onclick="editMachine({{ $machine->id }})" class="px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-indigo-50 text-indigo-600 hover:bg-indigo-600 hover:text-white transition" title="Edit">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                    <button type="button" onclick="toggleMachine({{ $machine->id }})" class="px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-slate-100 text-slate-600 hover:bg-slate-600 hover:text-white transition" title="Aktif / Nonaktif">
                                        <i class="fa-solid fa-power-off"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-slate-400">Belum ada mesin terdaftar. Tekan "Tambah Mesin".</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah / Edit Mesin -->
<div id="machineModal" class="fixed inset-0 z-50 hidden overflow-y-auto" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="fixed inset-0 bg-slate-900 opacity-50" onclick="closeMachineModal()"></div>
        <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md p-6 space-y-4">
            <div class="flex justify-between items-center">
                <h3 class="text-base font-bold text-slate-800" id="machine_modal_title">Tambah Mesin</h3>
                <button onclick="closeMachineModal()" class="text-slate-400 hover:text-slate-700"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form id="machine_form" onsubmit="return submitMachine(event)">
                <input type="hidden" id="machine_id" value="">
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-600 mb-1.5">Kode Mesin *</label>
                        <input type="text" id="machine_code" required maxlength="50" placeholder="cth. MCH-001" class="w-full py-2 px-3 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-600 mb-1.5">Nama Mesin *</label>
                        <input type="text" id="machine_name" required maxlength="255" placeholder="cth. Overdeck Juki" class="w-full py-2 px-3 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-600 mb-1.5">Tipe Mesin</label>
                        <input type="text" id="machine_type" maxlength="100" placeholder="cth. Sewing / Iron / Cutting" class="w-full py-2 px-3 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-600 mb-1.5">Keterangan</label>
                        <textarea id="machine_description" maxlength="500" rows="2" class="w-full py-2 px-3 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none"></textarea>
                    </div>
                </div>
                <div class="flex justify-end gap-2 mt-5">
                    <button type="button" onclick="closeMachineModal()" class="py-2 px-4 rounded-xl text-sm font-semibold bg-slate-100 text-slate-600 hover:bg-slate-200 transition">Batal</button>
                    <button type="submit" class="py-2 px-4 rounded-xl text-sm font-semibold bg-indigo-600 text-white hover:bg-indigo-700 transition">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function openMachineModal() {
        $('#machineModal').removeClass('hidden');
    }

    function closeMachineModal() {
        $('#machineModal').addClass('hidden');
        $('#machine_form')[0].reset();
        $('#machine_id').val('');
        $('#machine_modal_title').text('Tambah Mesin');
    }

    function editMachine(id) {
        let row = $(`select.line-select[data-machine-id="${id}"]`).closest('tr');
        $('#machine_id').val(id);
        $('#machine_code').val(row.data('code'));
        $('#machine_name').val(row.data('name'));
        $('#machine_type').val(row.data('type') || '');
        $('#machine_description').val(row.data('description') || '');
        $('#machine_modal_title').text('Edit Mesin');
        openMachineModal();
    }

    function submitMachine(e) {
        e.preventDefault();

        let id = $('#machine_id').val();
        let payload = {
            code: $('#machine_code').val(),
            name: $('#machine_name').val(),
            machine_type: $('#machine_type').val(),
            description: $('#machine_description').val()
        };

        $.ajax({
            url: id ? `/mekanik/machines/${id}` : "{{ route('mekanik.machines.store') }}",
            type: id ? "PUT" : "POST",
            data: payload,
            success: function(res) {
                showNotification('success', res.message || 'Mesin tersimpan.');
                closeMachineModal();
                setTimeout(() => window.location.reload(), 500);
            },
            error: function(err) {
                showNotification('error', err.responseJSON?.errors ? Object.values(err.responseJSON.errors)[0][0] : (err.responseJSON?.error || 'Gagal menyimpan mesin.'));
            }
        });

        return false;
    }

    function toggleMachine(id) {
        $.ajax({
            url: `/mekanik/machines/${id}/toggle`,
            type: "POST",
            success: function(res) {
                showNotification('success', res.message);
                setTimeout(() => window.location.reload(), 500);
            },
            error: function(err) {
                showNotification('error', err.responseJSON?.error || 'Gagal mengubah status mesin.');
            }
        });
    }

    $(document).ready(function() {
        $('#btn_add_machine').click(openMachineModal);
        $('#btn_reload').click(() => window.location.reload());

        // Plotting mesin ke line
        $(document).on('change', 'select.line-select', function() {
            let machineId = $(this).data('machine-id');
            let lineId = $(this).val();

            $.ajax({
                url: `/mekanik/machines/${machineId}/assign-line`,
                type: "POST",
                data: { line_id: lineId || '' },
                success: function(res) {
                    showNotification('success', res.message || 'Mesin berhasil dipindahkan.');
                    setTimeout(() => window.location.reload(), 500);
                },
                error: function(err) {
                    showNotification('error', err.responseJSON?.error || 'Gagal memindahkan mesin.');
                    setTimeout(() => window.location.reload(), 800);
                }
            });
        });

        // Filter sederhana di sisi client
        function applyFilter() {
            let q = $('#filter_search').val().toLowerCase();
            let line = $('#filter_line').val();

            $('#machine_table_body tr').each(function() {
                let row = $(this);
                if (!row.data('code')) return;

                let text = (row.data('code') + ' ' + row.data('name') + ' ' + (row.data('type') || '')).toLowerCase();
                let matchText = !q || text.includes(q);

                let select = row.find('select.line-select');
                let currentLine = select.val();
                let matchLine = !line || (line === 'pool' ? !currentLine : currentLine === line);

                row.toggle(matchText && matchLine);
            });
        }

        $('#filter_search').on('keyup', applyFilter);
        $('#filter_line').on('change', applyFilter);
    });
</script>
@endsection
