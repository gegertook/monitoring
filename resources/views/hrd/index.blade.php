@extends('layouts.app')

@section('title', 'Master Data Karyawan (HRD)')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Master Data Karyawan Operator</h2>
            <p class="text-sm text-slate-500">Kelola database profil operator, skill level, dan status keaktifan kerja.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('hrd.plotting.index') }}" class="inline-flex items-center gap-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold py-2.5 px-4 rounded-xl transition">
                <i class="fa-solid fa-user-tag text-sm"></i>
                <span>Plotting Line</span>
            </a>
            <a href="{{ route('hrd.employees.create') }}" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 px-4 rounded-xl shadow-sm shadow-indigo-600/20 transition">
                <i class="fa-solid fa-user-plus text-sm"></i>
                <span>Tambah Karyawan</span>
            </a>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
        <div class="flex flex-col md:flex-row justify-between mb-6 gap-4">
            <div class="w-full md:w-1/3 relative">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3.5 text-slate-400 text-sm"></i>
                <input type="text" id="searchInput" placeholder="Cari NIK atau Nama Operator..." class="w-full pl-10 pr-4 py-2 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div class="flex gap-3 w-full md:w-1/2">
                <select id="skillFilter" class="w-full py-2 px-3 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white">
                    <option value="">Semua Skill Level</option>
                    <option value="junior">Junior</option>
                    <option value="intermediate">Intermediate</option>
                    <option value="senior">Senior</option>
                </select>
                <select id="statusFilter" class="w-full py-2 px-3 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white">
                    <option value="">Semua Status</option>
                    <option value="1">Aktif</option>
                    <option value="0">Non-Aktif</option>
                </select>
            </div>
        </div>

        <div class="overflow-x-auto rounded-xl border border-slate-100">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-slate-600 font-semibold">
                    <tr>
                        <th class="px-5 py-3.5 text-left">NIK</th>
                        <th class="px-5 py-3.5 text-left">Nama Lengkap</th>
                        <th class="px-5 py-3.5 text-left">Skill Level</th>
                        <th class="px-5 py-3.5 text-left">Status Keaktifan</th>
                        <th class="px-5 py-3.5 text-center w-32">Aksi</th>
                    </tr>
                </thead>
                <tbody id="dataTableBody" class="bg-white divide-y divide-slate-100">
                    <!-- Populated via AJAX -->
                </tbody>
            </table>
        </div>
        
        <div id="pagination" class="mt-5 flex items-center justify-between text-sm text-slate-500">
            <!-- Pagination Controls -->
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    let currentPage = 1;

    function loadData(page = 1) {
        currentPage = page;
        let search = $('#searchInput').val();
        let skill = $('#skillFilter').val();
        let status = $('#statusFilter').val();
        
        $('#dataTableBody').html('<tr><td colspan="5" class="text-center py-8"><i class="fa-solid fa-spinner fa-spin text-2xl text-indigo-600"></i><p class="text-xs text-slate-400 mt-2">Memuat karyawan...</p></td></tr>');

        $.ajax({
            url: "{{ route('hrd.employees.index') }}",
            type: "GET",
            data: { page: page, search: search, skill_level: skill, is_active: status },
            success: function(response) {
                let rows = '';
                if(response.data && response.data.length > 0) {
                    $.each(response.data, function(index, item) {
                        let skillBadge = {
                            'senior': 'bg-emerald-100 text-emerald-800 border-emerald-200',
                            'intermediate': 'bg-blue-100 text-blue-800 border-blue-200',
                            'junior': 'bg-amber-100 text-amber-800 border-amber-200'
                        }[item.skill_level] || 'bg-slate-100 text-slate-800';

                        let statusBadge = item.is_active 
                            ? '<span class="px-2.5 py-1 text-xs font-bold rounded-full bg-emerald-100 text-emerald-800"><i class="fa-solid fa-circle-check text-[10px] mr-1"></i>Aktif</span>'
                            : '<span class="px-2.5 py-1 text-xs font-bold rounded-full bg-slate-100 text-slate-600"><i class="fa-solid fa-circle-xmark text-[10px] mr-1"></i>Non-Aktif</span>';

                        rows += `<tr class="hover:bg-slate-50 transition">
                            <td class="px-5 py-4 font-mono font-bold text-slate-800">${item.nik}</td>
                            <td class="px-5 py-4 font-medium text-slate-800">${item.name}</td>
                            <td class="px-5 py-4">
                                <span class="px-2.5 py-1 text-xs font-semibold rounded-full border ${skillBadge}">${(item.skill_level || '-').toUpperCase()}</span>
                                ${item.category === 'mekanik'
                                    ? '<span class="ml-1.5 inline-flex items-center gap-1 px-2.5 py-1 text-xs font-bold rounded-full bg-purple-100 text-purple-800 border border-purple-200"><i class="fa-solid fa-wrench text-[10px]"></i>Mekanik</span>'
                                    : ''}
                            </td>
                            <td class="px-5 py-4">${statusBadge}</td>
                            <td class="px-5 py-4 text-center">
                                <div class="inline-flex items-center gap-1.5">
                                    <a href="/hrd/employees/${item.id}/edit" class="p-1.5 text-xs text-amber-600 hover:bg-amber-50 rounded-lg transition" title="Edit">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                    <button onclick="toggleStatus(${item.id})" class="p-1.5 text-xs ${item.is_active ? 'text-rose-600 hover:bg-rose-50' : 'text-emerald-600 hover:bg-emerald-50'} rounded-lg transition" title="${item.is_active ? 'Nonaktifkan' : 'Aktifkan'}">
                                        <i class="fa-solid ${item.is_active ? 'fa-user-slash' : 'fa-user-check'}"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>`;
                    });
                } else {
                    rows = '<tr><td colspan="5" class="text-center py-8 text-slate-400">Tidak ada data karyawan ditemukan.</td></tr>';
                }
                
                $('#dataTableBody').html(rows);
                renderPagination(response);
            },
            error: function(err) {
                $('#dataTableBody').html('<tr><td colspan="5" class="text-center py-8 text-rose-500">Gagal memuat data karyawan.</td></tr>');
            }
        });
    }

    function renderPagination(data) {
        if(!data || !data.total) return;
        
        let html = `<div>Menampilkan ${data.from || 0} - ${data.to || 0} dari ${data.total} operator</div>`;
        html += '<div class="inline-flex gap-1">';
        
        if(data.prev_page_url) {
            html += `<button onclick="loadData(${currentPage - 1})" class="px-3 py-1.5 text-xs rounded-lg border border-slate-200 bg-white hover:bg-slate-50 font-medium">Prev</button>`;
        }
        if(data.next_page_url) {
            html += `<button onclick="loadData(${currentPage + 1})" class="px-3 py-1.5 text-xs rounded-lg border border-slate-200 bg-white hover:bg-slate-50 font-medium">Next</button>`;
        }
        
        html += '</div>';
        $('#pagination').html(html);
    }

    function toggleStatus(id) {
        $.ajax({
            url: `/hrd/employees/${id}`,
            type: 'DELETE',
            success: function(response) {
                showNotification('success', response.message || 'Status karyawan diperbarui.');
                loadData(currentPage);
            },
            error: function(err) {
                showNotification('error', 'Gagal memperbarui status karyawan.');
            }
        });
    }

    $(document).ready(function() {
        loadData();
        let timer;
        $('#searchInput').on('keyup', function() {
            clearTimeout(timer);
            timer = setTimeout(() => { loadData(1); }, 350);
        });
        $('#skillFilter, #statusFilter').on('change', function() { loadData(1); });
    });
</script>
@endsection
