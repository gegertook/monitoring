@extends('layouts.app')

@section('title', 'Data Akun Staff & Supervisor')

@section('content')
<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Data Akun Staff & Supervisor</h1>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-700 border border-blue-200">
                    <i class="fa-solid fa-users-gear text-[10px]"></i> Otoritas HRD
                </span>
            </div>
            <p class="text-sm text-slate-500 mt-1">HRD berwenang mengelola akun login pengguna sistem untuk Supervisor (SPV), PPIC, IE, Admin Produksi, dan Manager.</p>
        </div>
        <div>
            <button type="button" onclick="openAddUserModal()" class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium text-sm shadow-sm transition">
                <i class="fa-solid fa-user-plus"></i>
                <span>Tambah Akun Staff</span>
            </button>
        </div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-lg bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600 text-xl font-bold">
                <i class="fa-solid fa-users-viewfinder"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Akun</p>
                <h3 class="text-2xl font-bold text-slate-900 mt-0.5">{{ $stats['total'] }}</h3>
            </div>
        </div>

        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-lg bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-600 text-xl font-bold">
                <i class="fa-solid fa-user-check"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Akun Aktif</p>
                <h3 class="text-2xl font-bold text-emerald-600 mt-0.5">{{ $stats['active'] }}</h3>
            </div>
        </div>

        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-lg bg-amber-50 border border-amber-100 flex items-center justify-center text-amber-600 text-xl font-bold">
                <i class="fa-solid fa-clipboard-user"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Supervisor (SPV)</p>
                <h3 class="text-2xl font-bold text-amber-600 mt-0.5">{{ $stats['spv_count'] }}</h3>
            </div>
        </div>

        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-lg bg-sky-50 border border-sky-100 flex items-center justify-center text-sky-600 text-xl font-bold">
                <i class="fa-solid fa-briefcase"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Office & Manajemen</p>
                <h3 class="text-2xl font-bold text-slate-900 mt-0.5">{{ $stats['office_count'] }}</h3>
            </div>
        </div>
    </div>

    <!-- Filters & Table Card -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <!-- Filter Toolbar -->
        <div class="p-4 sm:p-5 border-b border-slate-200 bg-slate-50/50 flex flex-col lg:flex-row gap-3 lg:items-center lg:justify-between">
            <div class="flex flex-1 flex-col sm:flex-row items-stretch sm:items-center gap-3">
                <div class="relative flex-1 max-w-sm">
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" id="filter-search" value="{{ request('search') }}" placeholder="Cari NIK, nama, atau email..." 
                        class="w-full pl-9 pr-3 py-2 text-sm bg-white border border-slate-300 rounded-lg focus:outline-hidden focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <select id="filter-role" class="py-2 px-3 text-sm bg-white border border-slate-300 rounded-lg focus:outline-hidden focus:ring-2 focus:ring-indigo-500">
                    <option value="">Semua Peran (Role)</option>
                    @foreach($roles as $key => $label)
                        <option value="{{ $key }}" {{ request('role') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <select id="filter-status" class="py-2 px-3 text-sm bg-white border border-slate-300 rounded-lg focus:outline-hidden focus:ring-2 focus:ring-indigo-500">
                    <option value="">Semua Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Non-Aktif</option>
                </select>
            </div>
            <div class="text-xs text-slate-500 font-medium">
                Menampilkan {{ $users->total() }} Akun Terdaftar
            </div>
        </div>

        <!-- Table View -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                        <th class="py-3 px-4">Nama & NIK</th>
                        <th class="py-3 px-4">Email Login</th>
                        <th class="py-3 px-4">Hak Akses / Peran</th>
                        <th class="py-3 px-4">Status Akun</th>
                        <th class="py-3 px-4">Terdaftar</th>
                        <th class="py-3 px-4 text-center">Aksi Cepat</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-sm">
                    @forelse($users as $user)
                        <tr class="hover:bg-slate-50/80 transition" id="row-user-{{ $user->id }}">
                            <td class="py-3.5 px-4 font-semibold text-slate-900">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center font-bold text-slate-700 text-xs">
                                        {{ substr($user->name, 0, 1) }}
                                    </div>
                                    <div>
                                        <div class="text-slate-900 font-semibold">{{ $user->name }}</div>
                                        <div class="text-xs text-slate-400 font-mono">{{ $user->nik }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3.5 px-4 text-slate-600 font-mono text-xs">
                                {{ $user->email }}
                            </td>
                            <td class="py-3.5 px-4">
                                @php
                                    $roleColor = match($user->role) {
                                        'manager' => 'bg-purple-100 text-purple-700 border-purple-200',
                                        'admin_qc' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                                        'admin_produksi' => 'bg-indigo-100 text-indigo-700 border-indigo-200',
                                        'spv' => 'bg-amber-100 text-amber-700 border-amber-200',
                                        'ppic' => 'bg-blue-100 text-blue-700 border-blue-200',
                                        'ie' => 'bg-teal-100 text-teal-700 border-teal-200',
                                        'hrd' => 'bg-rose-100 text-rose-700 border-rose-200',
                                        default => 'bg-slate-100 text-slate-700 border-slate-200',
                                    };
                                    $roleIcon = match($user->role) {
                                        'manager' => 'fa-chart-line',
                                        'admin_qc' => 'fa-microscope',
                                        'admin_produksi' => 'fa-check-double',
                                        'spv' => 'fa-clock',
                                        'ppic' => 'fa-boxes-packing',
                                        'ie' => 'fa-diagram-project',
                                        'hrd' => 'fa-users',
                                        default => 'fa-user',
                                    };
                                @endphp
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border {{ $roleColor }}">
                                    <i class="fa-solid {{ $roleIcon }} text-[10px]"></i>
                                    <span>{{ strtoupper(str_replace('_', ' ', $user->role)) }}</span>
                                </span>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $user->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $user->is_active ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                    <span>{{ $user->is_active ? 'Aktif' : 'Non-Aktif' }}</span>
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-xs text-slate-500">
                                {{ $user->created_at ? $user->created_at->format('d M Y') : '-' }}
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <div class="inline-flex items-center gap-1.5">
                                    @if($user->id !== auth()->id())
                                        <!-- Toggle Status Button -->
                                        <button type="button" onclick="toggleUserStatus({{ $user->id }}, '{{ $user->name }}', {{ $user->is_active ? 'true' : 'false' }})" 
                                            class="p-1.5 rounded-md text-xs font-semibold {{ $user->is_active ? 'text-amber-700 hover:bg-amber-50' : 'text-emerald-700 hover:bg-emerald-50' }} transition"
                                            title="{{ $user->is_active ? 'Nonaktifkan Akun' : 'Aktifkan Akun' }}">
                                            <i class="fa-solid {{ $user->is_active ? 'fa-toggle-on text-emerald-600 text-base' : 'fa-toggle-off text-slate-400 text-base' }}"></i>
                                        </button>
                                    @endif

                                    <!-- Edit Button -->
                                    <button type="button" onclick="openEditUserModal({{ $user->id }}, '{{ $user->nik }}', '{{ $user->name }}', '{{ $user->email }}', '{{ $user->role }}', {{ $user->is_active ? 1 : 0 }})" 
                                        class="p-1.5 text-indigo-600 hover:bg-indigo-50 rounded-md transition" title="Edit Akun">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>

                                    @if($user->id !== auth()->id())
                                        <!-- Delete Button -->
                                        <button type="button" onclick="deleteUser({{ $user->id }}, '{{ $user->name }}')" 
                                            class="p-1.5 text-rose-600 hover:bg-rose-50 rounded-md transition" title="Hapus Akun">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-500">
                                <i class="fa-solid fa-users-slash text-3xl text-slate-300 mb-2 block"></i>
                                Tidak ada akun staff yang cocok dengan filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($users->hasPages())
            <div class="p-4 border-t border-slate-200 bg-slate-50/50">
                {{ $users->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Modal Tambah Akun -->
<div id="addUserModal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full shadow-2xl border border-slate-100 overflow-hidden transform transition-all">
        <div class="p-5 border-b border-slate-200 flex items-center justify-between bg-slate-50">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-indigo-100 text-indigo-600 flex items-center justify-center text-sm font-bold">
                    <i class="fa-solid fa-user-plus"></i>
                </div>
                <h3 class="font-bold text-slate-900">Tambah Akun Staff / Supervisor</h3>
            </div>
            <button type="button" onclick="closeAddUserModal()" class="text-slate-400 hover:text-slate-600 text-lg">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="addUserForm" onsubmit="submitAddUser(event)" class="p-5 space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">NIK Karyawan/Staff</label>
                    <input type="text" name="nik" id="add-nik" required placeholder="Contoh: SPV002" 
                        class="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-hidden">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Nama Lengkap</label>
                    <input type="text" name="name" id="add-name" required placeholder="Nama personil..." 
                        class="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-hidden">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Email Login</label>
                    <input type="email" name="email" id="add-email" required placeholder="staff@mlmps.com" 
                        class="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-hidden">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Peran / Hak Akses</label>
                    <select name="role" id="add-role" required 
                        class="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-hidden bg-white">
                        <option value="">-- Pilih Peran --</option>
                        @foreach($roles as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Password Awal</label>
                <input type="password" name="password" id="add-password" required minlength="6" placeholder="Minimal 6 karakter" 
                    class="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-hidden">
            </div>

            <div class="flex items-center gap-2 pt-1">
                <input type="checkbox" name="is_active" id="add-is-active" value="1" checked 
                    class="w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500">
                <label for="add-is-active" class="text-sm text-slate-700 font-medium">Langsung aktifkan akun ini untuk login</label>
            </div>

            <div class="pt-4 flex items-center justify-end gap-2 border-t border-slate-100">
                <button type="button" onclick="closeAddUserModal()" class="px-4 py-2 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100 transition">Batal</button>
                <button type="submit" id="btn-save-add-user" class="px-4 py-2 rounded-lg text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition flex items-center gap-2">
                    <i class="fa-solid fa-check"></i>
                    <span>Simpan Akun</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Akun -->
<div id="editUserModal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full shadow-2xl border border-slate-100 overflow-hidden transform transition-all">
        <div class="p-5 border-b border-slate-200 flex items-center justify-between bg-slate-50">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-indigo-100 text-indigo-600 flex items-center justify-center text-sm font-bold">
                    <i class="fa-solid fa-user-pen"></i>
                </div>
                <h3 class="font-bold text-slate-900">Edit Akun Staff</h3>
            </div>
            <button type="button" onclick="closeEditUserModal()" class="text-slate-400 hover:text-slate-600 text-lg">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="editUserForm" onsubmit="submitEditUser(event)" class="p-5 space-y-4">
            <input type="hidden" id="edit-user-id">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">NIK</label>
                    <input type="text" name="nik" id="edit-nik" required 
                        class="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-hidden">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Nama Lengkap</label>
                    <input type="text" name="name" id="edit-name" required 
                        class="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-hidden">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Email Login</label>
                    <input type="email" name="email" id="edit-email" required 
                        class="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-hidden">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Peran / Hak Akses</label>
                    <select name="role" id="edit-role" required 
                        class="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-hidden bg-white">
                        @foreach($roles as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Password Baru (Opsional)</label>
                <input type="password" name="password" id="edit-password" minlength="6" placeholder="Kosongkan bila tidak ingin mengubah password" 
                    class="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-hidden">
            </div>

            <div class="flex items-center gap-2 pt-1">
                <input type="checkbox" name="is_active" id="edit-is-active" value="1" 
                    class="w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500">
                <label for="edit-is-active" class="text-sm text-slate-700 font-medium">Status Akun Aktif</label>
            </div>

            <div class="pt-4 flex items-center justify-end gap-2 border-t border-slate-100">
                <button type="button" onclick="closeEditUserModal()" class="px-4 py-2 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100 transition">Batal</button>
                <button type="submit" id="btn-save-edit-user" class="px-4 py-2 rounded-lg text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition flex items-center gap-2">
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
    // Filter controls
    let searchTimer;
    $('#filter-search').on('input', function() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(applyUserFilters, 500);
    });

    $('#filter-role, #filter-status').on('change', function() {
        applyUserFilters();
    });

    function applyUserFilters() {
        const search = $('#filter-search').val();
        const role = $('#filter-role').val();
        const status = $('#filter-status').val();
        const url = new URL(window.location.href);

        if (search) url.searchParams.set('search', search); else url.searchParams.delete('search');
        if (role) url.searchParams.set('role', role); else url.searchParams.delete('role');
        if (status) url.searchParams.set('status', status); else url.searchParams.delete('status');
        url.searchParams.delete('page');

        window.location.href = url.toString();
    }

    // Modal controls
    function openAddUserModal() {
        $('#addUserForm')[0].reset();
        $('#add-is-active').prop('checked', true);
        $('#addUserModal').removeClass('hidden');
    }
    function closeAddUserModal() {
        $('#addUserModal').addClass('hidden');
    }

    function openEditUserModal(id, nik, name, email, role, isActive) {
        $('#edit-user-id').val(id);
        $('#edit-nik').val(nik);
        $('#edit-name').val(name);
        $('#edit-email').val(email);
        $('#edit-role').val(role);
        $('#edit-password').val('');
        $('#edit-is-active').prop('checked', isActive == 1);
        $('#editUserModal').removeClass('hidden');
    }
    function closeEditUserModal() {
        $('#editUserModal').addClass('hidden');
    }

    // Submit Add User via AJAX
    function submitAddUser(e) {
        e.preventDefault();
        const btn = $('#btn-save-add-user');
        btn.prop('disabled', true).html('<i class="fa-solid fa-circle-notch fa-spin"></i> Menyimpan...');

        const data = {
            nik: $('#add-nik').val(),
            name: $('#add-name').val(),
            email: $('#add-email').val(),
            role: $('#add-role').val(),
            password: $('#add-password').val(),
            is_active: $('#add-is-active').is(':checked') ? 1 : 0,
            _token: '{{ csrf_token() }}'
        };

        $.ajax({
            url: "{{ route('hrd.users.store') }}",
            type: "POST",
            data: data,
            success: function(res) {
                closeAddUserModal();
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: res.message || 'Akun staff berhasil dibuat.',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    location.reload();
                });
            },
            error: function(xhr) {
                btn.prop('disabled', false).html('<i class="fa-solid fa-check"></i> Simpan Akun');
                let msg = 'Gagal menyimpan akun staff.';
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    msg = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                } else if (xhr.responseJSON && xhr.responseJSON.error) {
                    msg = xhr.responseJSON.error;
                }
                Swal.fire({ icon: 'error', title: 'Gagal', html: msg });
            }
        });
    }

    // Submit Edit User via AJAX
    function submitEditUser(e) {
        e.preventDefault();
        const id = $('#edit-user-id').val();
        const btn = $('#btn-save-edit-user');
        btn.prop('disabled', true).html('<i class="fa-solid fa-circle-notch fa-spin"></i> Menyimpan...');

        const data = {
            nik: $('#edit-nik').val(),
            name: $('#edit-name').val(),
            email: $('#edit-email').val(),
            role: $('#edit-role').val(),
            password: $('#edit-password').val() || null,
            is_active: $('#edit-is-active').is(':checked') ? 1 : 0,
            _token: '{{ csrf_token() }}',
            _method: 'PUT'
        };

        $.ajax({
            url: `/hrd/users/${id}`,
            type: "POST",
            data: data,
            success: function(res) {
                closeEditUserModal();
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: res.message || 'Akun berhasil diperbarui.',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    location.reload();
                });
            },
            error: function(xhr) {
                btn.prop('disabled', false).html('<i class="fa-solid fa-save"></i> Simpan Perubahan');
                let msg = 'Gagal memperbarui akun.';
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    msg = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                }
                Swal.fire({ icon: 'error', title: 'Gagal', html: msg });
            }
        });
    }

    // Toggle User Status via AJAX
    function toggleUserStatus(id, name, currentStatus) {
        const actionText = currentStatus ? 'menonaktifkan' : 'mengaktifkan';

        Swal.fire({
            title: `Konfirmasi Ubah Status`,
            text: `Apakah Anda yakin ingin ${actionText} akun ${name}?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: currentStatus ? '#e11d48' : '#10b981',
            cancelButtonColor: '#64748b',
            confirmButtonText: `Ya, ${actionText.charAt(0).toUpperCase() + actionText.slice(1)}`,
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/hrd/users/${id}/toggle`,
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
                        Swal.fire('Error', xhr.responseJSON?.error || 'Gagal mengubah status akun', 'error');
                    }
                });
            }
        });
    }

    // Delete User via AJAX
    function deleteUser(id, name) {
        Swal.fire({
            title: `Hapus Akun ${name}?`,
            text: `Jika akun ini memiliki riwayat transaksi data produksi, akun akan dialihkan ke status Non-Aktif secara otomatis.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Hapus/Nonaktifkan',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/hrd/users/${id}`,
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
                        Swal.fire('Gagal', xhr.responseJSON?.error || 'Gagal menghapus akun', 'error');
                    }
                });
            }
        });
    }
</script>
@endsection
