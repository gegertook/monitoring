@extends('layouts.app')

@section('title', 'Kelola Line Produksi')

@section('content')
<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Kelola Line Produksi</h1>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-purple-100 text-purple-700 border border-purple-200">
                    <i class="fa-solid fa-user-shield text-[10px]"></i> Otoritas Manager
                </span>
            </div>
            <p class="text-sm text-slate-500 mt-1">Atur penambahan, kapasitas, serta aktivasi/nonaktivasi 30 line produksi di pabrik.</p>
        </div>
        <div>
            <button type="button" onclick="openAddModal()" class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium text-sm shadow-sm transition">
                <i class="fa-solid fa-plus"></i>
                <span>Tambah Line Baru</span>
            </button>
        </div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-lg bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600 text-xl font-bold">
                <i class="fa-solid fa-industry"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Line</p>
                <h3 class="text-2xl font-bold text-slate-900 mt-0.5" id="stat-total">{{ $stats['total'] }}</h3>
            </div>
        </div>

        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-lg bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-600 text-xl font-bold">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Line Aktif</p>
                <h3 class="text-2xl font-bold text-emerald-600 mt-0.5" id="stat-active">{{ $stats['active'] }}</h3>
            </div>
        </div>

        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-lg bg-rose-50 border border-rose-100 flex items-center justify-center text-rose-600 text-xl font-bold">
                <i class="fa-solid fa-power-off"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Line Non-Aktif</p>
                <h3 class="text-2xl font-bold text-rose-600 mt-0.5" id="stat-inactive">{{ $stats['inactive'] }}</h3>
            </div>
        </div>

        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-lg bg-amber-50 border border-amber-100 flex items-center justify-center text-amber-600 text-xl font-bold">
                <i class="fa-solid fa-gauge-high"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Kapasitas Aktif</p>
                <h3 class="text-2xl font-bold text-slate-900 mt-0.5" id="stat-capacity">{{ number_format($stats['total_capacity']) }} <span class="text-xs font-normal text-slate-500">pcs/jam</span></h3>
            </div>
        </div>
    </div>

    <!-- Filters & Table Card -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <!-- Filter Toolbar -->
        <div class="p-4 sm:p-5 border-b border-slate-200 bg-slate-50/50 flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between">
            <div class="flex flex-1 items-center gap-3">
                <div class="relative flex-1 max-w-sm">
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" id="filter-search" value="{{ request('search') }}" placeholder="Cari nama line..." 
                        class="w-full pl-9 pr-3 py-2 text-sm bg-white border border-slate-300 rounded-lg focus:outline-hidden focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <select id="filter-status" class="py-2 px-3 text-sm bg-white border border-slate-300 rounded-lg focus:outline-hidden focus:ring-2 focus:ring-indigo-500">
                    <option value="">Semua Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Line Aktif</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Line Non-Aktif</option>
                </select>
            </div>
            <div class="text-xs text-slate-500 font-medium">
                Menampilkan {{ $lines->total() }} Line Produksi
            </div>
        </div>

        <!-- Table View -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                        <th class="py-3 px-4">Nama Line</th>
                        <th class="py-3 px-4">Kapasitas (Target/Jam)</th>
                        <th class="py-3 px-4">Status Produksi</th>
                        <th class="py-3 px-4">Pembaruan Terakhir</th>
                        <th class="py-3 px-4 text-center">Aksi Cepat</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-sm" id="lines-table-body">
                    @forelse($lines as $line)
                        <tr class="hover:bg-slate-50/80 transition" id="row-line-{{ $line->id }}">
                            <td class="py-3.5 px-4 font-semibold text-slate-900">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-lg {{ $line->is_active ? 'bg-indigo-50 text-indigo-600' : 'bg-slate-100 text-slate-400' }} flex items-center justify-center font-bold text-xs">
                                        <i class="fa-solid fa-industry"></i>
                                    </div>
                                    <span class="line-name-text">{{ $line->name }}</span>
                                </div>
                            </td>
                            <td class="py-3.5 px-4 text-slate-600 font-medium">
                                <span class="line-capacity-text">{{ number_format($line->capacity) }}</span> <span class="text-xs text-slate-400">pcs/jam</span>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="status-badge inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $line->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $line->is_active ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                    <span>{{ $line->is_active ? 'Aktif' : 'Non-Aktif' }}</span>
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-xs text-slate-500">
                                {{ $line->updated_at ? $line->updated_at->format('d M Y, H:i') : '-' }}
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <div class="inline-flex items-center gap-1.5">
                                    <!-- Toggle Active Button -->
                                    <button type="button" onclick="toggleLineStatus({{ $line->id }}, '{{ $line->name }}', {{ $line->is_active ? 'true' : 'false' }})" 
                                        class="toggle-btn p-1.5 rounded-md text-xs font-semibold {{ $line->is_active ? 'text-amber-700 hover:bg-amber-50' : 'text-emerald-700 hover:bg-emerald-50' }} transition"
                                        title="{{ $line->is_active ? 'Nonaktifkan Line' : 'Aktifkan Line' }}">
                                        <i class="fa-solid {{ $line->is_active ? 'fa-toggle-on text-emerald-600 text-base' : 'fa-toggle-off text-slate-400 text-base' }}"></i>
                                    </button>

                                    <!-- Edit Button -->
                                    <button type="button" onclick="openEditModal({{ $line->id }}, '{{ $line->name }}', {{ $line->capacity }}, {{ $line->is_active ? 1 : 0 }})" 
                                        class="p-1.5 text-indigo-600 hover:bg-indigo-50 rounded-md transition" title="Edit Line">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>

                                    <!-- Delete Button -->
                                    <button type="button" onclick="deleteLine({{ $line->id }}, '{{ $line->name }}')" 
                                        class="p-1.5 text-rose-600 hover:bg-rose-50 rounded-md transition" title="Hapus Line">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-slate-500">
                                <i class="fa-solid fa-inbox text-3xl text-slate-300 mb-2 block"></i>
                                Tidak ada data line produksi yang sesuai kriteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($lines->hasPages())
            <div class="p-4 border-t border-slate-200 bg-slate-50/50">
                {{ $lines->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Modal Tambah Line -->
<div id="addModal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl border border-slate-100 overflow-hidden transform transition-all">
        <div class="p-5 border-b border-slate-200 flex items-center justify-between bg-slate-50">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-indigo-100 text-indigo-600 flex items-center justify-center text-sm font-bold">
                    <i class="fa-solid fa-plus"></i>
                </div>
                <h3 class="font-bold text-slate-900">Tambah Line Produksi</h3>
            </div>
            <button type="button" onclick="closeAddModal()" class="text-slate-400 hover:text-slate-600 text-lg">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="addForm" onsubmit="submitAddLine(event)" class="p-5 space-y-4">
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Nama Line</label>
                <input type="text" name="name" id="add-name" required placeholder="Contoh: Line 31" 
                    class="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-hidden">
                <p class="text-[11px] text-slate-400 mt-1">Gunakan penamaan standar seperti Line 1, Line 2, dst.</p>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Kapasitas Target (pcs/jam)</label>
                <input type="number" name="capacity" id="add-capacity" required min="1" max="500" value="30" 
                    class="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-hidden">
            </div>
            <div class="flex items-center gap-2 pt-1">
                <input type="checkbox" name="is_active" id="add-is-active" value="1" checked 
                    class="w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500">
                <label for="add-is-active" class="text-sm text-slate-700 font-medium">Langsung aktifkan line untuk produksi</label>
            </div>
            <div class="pt-4 flex items-center justify-end gap-2 border-t border-slate-100">
                <button type="button" onclick="closeAddModal()" class="px-4 py-2 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100 transition">Batal</button>
                <button type="submit" id="btn-save-add" class="px-4 py-2 rounded-lg text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition flex items-center gap-2">
                    <i class="fa-solid fa-check"></i>
                    <span>Simpan Line</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Line -->
<div id="editModal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl border border-slate-100 overflow-hidden transform transition-all">
        <div class="p-5 border-b border-slate-200 flex items-center justify-between bg-slate-50">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-indigo-100 text-indigo-600 flex items-center justify-center text-sm font-bold">
                    <i class="fa-solid fa-pen-to-square"></i>
                </div>
                <h3 class="font-bold text-slate-900">Edit Line Produksi</h3>
            </div>
            <button type="button" onclick="closeEditModal()" class="text-slate-400 hover:text-slate-600 text-lg">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="editForm" onsubmit="submitEditLine(event)" class="p-5 space-y-4">
            <input type="hidden" id="edit-id">
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Nama Line</label>
                <input type="text" name="name" id="edit-name" required 
                    class="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-hidden">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Kapasitas Target (pcs/jam)</label>
                <input type="number" name="capacity" id="edit-capacity" required min="1" max="500" 
                    class="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-hidden">
            </div>
            <div class="flex items-center gap-2 pt-1">
                <input type="checkbox" name="is_active" id="edit-is-active" value="1" 
                    class="w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500">
                <label for="edit-is-active" class="text-sm text-slate-700 font-medium">Status Aktif</label>
            </div>
            <div class="pt-4 flex items-center justify-end gap-2 border-t border-slate-100">
                <button type="button" onclick="closeEditModal()" class="px-4 py-2 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100 transition">Batal</button>
                <button type="submit" id="btn-save-edit" class="px-4 py-2 rounded-lg text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition flex items-center gap-2">
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
    // Filtering handler
    let searchTimer;
    $('#filter-search').on('input', function() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            applyFilters();
        }, 500);
    });

    $('#filter-status').on('change', function() {
        applyFilters();
    });

    function applyFilters() {
        const search = $('#filter-search').val();
        const status = $('#filter-status').val();
        const url = new URL(window.location.href);
        if (search) url.searchParams.set('search', search); else url.searchParams.delete('search');
        if (status) url.searchParams.set('status', status); else url.searchParams.delete('status');
        url.searchParams.delete('page');
        window.location.href = url.toString();
    }

    // Modal Add
    function openAddModal() {
        $('#addForm')[0].reset();
        $('#add-is-active').prop('checked', true);
        $('#addModal').removeClass('hidden');
    }
    function closeAddModal() {
        $('#addModal').addClass('hidden');
    }

    // Modal Edit
    function openEditModal(id, name, capacity, isActive) {
        $('#edit-id').val(id);
        $('#edit-name').val(name);
        $('#edit-capacity').val(capacity);
        $('#edit-is-active').prop('checked', isActive == 1);
        $('#editModal').removeClass('hidden');
    }
    function closeEditModal() {
        $('#editModal').addClass('hidden');
    }

    // Submit Add Line via AJAX
    function submitAddLine(e) {
        e.preventDefault();
        const btn = $('#btn-save-add');
        btn.prop('disabled', true).html('<i class="fa-solid fa-circle-notch fa-spin"></i> Menyimpan...');

        const data = {
            name: $('#add-name').val(),
            capacity: $('#add-capacity').val(),
            is_active: $('#add-is-active').is(':checked') ? 1 : 0,
            _token: '{{ csrf_token() }}'
        };

        $.ajax({
            url: "{{ route('manager.lines.store') }}",
            type: "POST",
            data: data,
            success: function(res) {
                closeAddModal();
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: res.message || 'Line berhasil ditambahkan.',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    location.reload();
                });
            },
            error: function(xhr) {
                btn.prop('disabled', false).html('<i class="fa-solid fa-check"></i> Simpan Line');
                let msg = 'Terjadi kesalahan saat menyimpan line.';
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    msg = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    html: msg
                });
            }
        });
    }

    // Submit Edit Line via AJAX
    function submitEditLine(e) {
        e.preventDefault();
        const id = $('#edit-id').val();
        const btn = $('#btn-save-edit');
        btn.prop('disabled', true).html('<i class="fa-solid fa-circle-notch fa-spin"></i> Menyimpan...');

        const data = {
            name: $('#edit-name').val(),
            capacity: $('#edit-capacity').val(),
            is_active: $('#edit-is-active').is(':checked') ? 1 : 0,
            _token: '{{ csrf_token() }}',
            _method: 'PUT'
        };

        $.ajax({
            url: `/manager/lines/${id}`,
            type: "POST",
            data: data,
            success: function(res) {
                closeEditModal();
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: res.message || 'Line berhasil diperbarui.',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    location.reload();
                });
            },
            error: function(xhr) {
                btn.prop('disabled', false).html('<i class="fa-solid fa-save"></i> Simpan Perubahan');
                let msg = 'Gagal memperbarui line.';
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    msg = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                }
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    html: msg
                });
            }
        });
    }

    // Toggle Line Status (Active / Inactive) via AJAX
    function toggleLineStatus(id, name, currentStatus) {
        const actionText = currentStatus ? 'menonaktifkan' : 'mengaktifkan';
        
        Swal.fire({
            title: `Konfirmasi Ubah Status`,
            text: `Apakah Anda yakin ingin ${actionText} ${name}?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: currentStatus ? '#e11d48' : '#10b981',
            cancelButtonColor: '#64748b',
            confirmButtonText: `Ya, ${actionText.charAt(0).toUpperCase() + actionText.slice(1)}`,
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/manager/lines/${id}/toggle`,
                    type: "POST",
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(res) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Status Diperbarui',
                            text: res.message,
                            timer: 1200,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    },
                    error: function(xhr) {
                        Swal.fire('Error', xhr.responseJSON?.error || 'Gagal mengubah status line', 'error');
                    }
                });
            }
        });
    }

    // Delete Line via AJAX
    function deleteLine(id, name) {
        Swal.fire({
            title: `Hapus ${name}?`,
            text: `Jika line ini memiliki riwayat order/output, statusnya akan dialihkan ke Non-Aktif untuk menjaga konsistensi database.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Hapus/Nonaktifkan',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/manager/lines/${id}`,
                    type: "POST",
                    data: {
                        _token: '{{ csrf_token() }}',
                        _method: 'DELETE'
                    },
                    success: function(res) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Selesai',
                            text: res.message,
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    },
                    error: function(xhr) {
                        Swal.fire('Gagal', xhr.responseJSON?.error || 'Gagal menghapus line', 'error');
                    }
                });
            }
        });
    }
</script>
@endsection
