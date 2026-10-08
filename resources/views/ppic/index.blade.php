@extends('layouts.app')

@section('title', 'Style Orders & Plotting Line')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Daftar Style Orders (PPIC)</h2>
            <p class="text-sm text-slate-500">Kelola order produksi dan tentukan line produksi tujuan.</p>
        </div>
        <a href="{{ route('ppic.style-orders.create') }}" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 px-4 rounded-xl shadow-sm shadow-indigo-600/20 transition-all">
            <i class="fa-solid fa-plus text-sm"></i>
            <span>Tambah Style Order</span>
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
        <div class="flex flex-col md:flex-row justify-between mb-6 gap-4">
            <div class="w-full md:w-1/3 relative">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3.5 text-slate-400 text-sm"></i>
                <input type="text" id="searchInput" placeholder="Cari Code Style atau Buyer..." class="w-full pl-10 pr-4 py-2 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
            </div>
            <div class="w-full md:w-1/4">
                <select id="statusFilter" class="w-full py-2 px-3 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent bg-white">
                    <option value="">Semua Status</option>
                    <option value="pending">Pending</option>
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
        </div>

        <div class="overflow-x-auto rounded-xl border border-slate-100">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-slate-600 font-semibold">
                    <tr>
                        <th class="px-5 py-3.5 text-left">Code Style</th>
                        <th class="px-5 py-3.5 text-left">Buyer</th>
                        <th class="px-5 py-3.5 text-left">Target Qty</th>
                        <th class="px-5 py-3.5 text-left">Deadline</th>
                        <th class="px-5 py-3.5 text-left">Status</th>
                        <th class="px-5 py-3.5 text-left">Production Line</th>
                        <th class="px-5 py-3.5 text-center">Aksi</th>
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

<!-- Assign Line Modal -->
<div id="assignLineModal" class="fixed inset-0 z-50 hidden overflow-y-auto" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen p-4 text-center">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" onclick="closeAssignModal()"></div>
        
        <div class="relative bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all w-full max-w-md p-6 z-10 border border-slate-100">
            <form id="assignLineForm">
                <h3 class="text-lg font-bold text-slate-800 mb-2">Penugasan Line Produksi</h3>
                <p class="text-xs text-slate-500 mb-4">Pilih line yang aktif untuk memproses style order ini.</p>
                
                <input type="hidden" id="assign_style_order_id">
                
                <div class="mb-5">
                    <label class="block text-xs font-bold uppercase text-slate-500 mb-2">Production Line</label>
                    <select id="line_id" class="w-full py-2.5 px-3 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none bg-white" required>
                        <option value="">Pilih Line...</option>
                        @foreach($lines as $line)
                            <option value="{{ $line->id }}">{{ $line->name }} (Kapasitas: {{ $line->capacity }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex justify-end gap-2.5">
                    <button type="button" onclick="closeAssignModal()" class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 transition">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm transition">
                        Tugaskan Line
                    </button>
                </div>
            </form>
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
        let status = $('#statusFilter').val();
        
        $('#dataTableBody').html('<tr><td colspan="7" class="text-center py-8"><i class="fa-solid fa-spinner fa-spin text-2xl text-indigo-600"></i><p class="text-xs text-slate-400 mt-2">Memuat data...</p></td></tr>');

        $.ajax({
            url: "{{ route('ppic.api.style-orders') }}",
            type: "GET",
            data: { page: page, search: search, status: status },
            success: function(response) {
                let rows = '';
                if(response.data && response.data.length > 0) {
                    $.each(response.data, function(index, item) {
                        let statusColor = {
                            'pending': 'bg-amber-100 text-amber-800',
                            'in_progress': 'bg-blue-100 text-blue-800',
                            'completed': 'bg-emerald-100 text-emerald-800',
                            'cancelled': 'bg-rose-100 text-rose-800'
                        }[item.status] || 'bg-slate-100 text-slate-800';

                        let lineName = item.production_line ? item.production_line.name : '<span class="text-slate-400 italic">Belum diplot</span>';

                        rows += `<tr class="hover:bg-slate-50 transition">
                            <td class="px-5 py-4 font-semibold text-slate-800">${item.code_style}</td>
                            <td class="px-5 py-4 text-slate-600">${item.buyer}</td>
                            <td class="px-5 py-4 font-medium text-slate-700">${Number(item.total_qty_target).toLocaleString()} pcs</td>
                            <td class="px-5 py-4 text-slate-500">${item.deadline ? item.deadline.substring(0, 10) : '-'}</td>
                            <td class="px-5 py-4">
                                <span class="px-2.5 py-1 text-xs font-bold rounded-full ${statusColor}">${item.status.replace('_', ' ').toUpperCase()}</span>
                            </td>
                            <td class="px-5 py-4 font-medium">${lineName}</td>
                            <td class="px-5 py-4 text-center">
                                <div class="inline-flex items-center gap-1.5">
                                    <button onclick="openAssignModal(${item.id}, ${item.assigned_line_id || 'null'})" class="p-1.5 text-xs text-indigo-600 hover:bg-indigo-50 rounded-lg transition" title="Plotting Line">
                                        <i class="fa-solid fa-layer-group"></i>
                                    </button>
                                    <a href="/ppic/style-orders/${item.id}/edit" class="p-1.5 text-xs text-amber-600 hover:bg-amber-50 rounded-lg transition" title="Edit">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                    <button onclick="deleteOrder(${item.id})" class="p-1.5 text-xs text-rose-600 hover:bg-rose-50 rounded-lg transition" title="Hapus">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>`;
                    });
                } else {
                    rows = '<tr><td colspan="7" class="text-center py-8 text-slate-400">Tidak ada data style order ditemukan.</td></tr>';
                }
                
                $('#dataTableBody').html(rows);
                renderPagination(response);
            },
            error: function(err) {
                $('#dataTableBody').html('<tr><td colspan="7" class="text-center py-8 text-rose-500">Gagal memuat data style orders.</td></tr>');
            }
        });
    }

    function renderPagination(data) {
        if(!data || !data.total) return;
        
        let html = `<div>Menampilkan ${data.from || 0} - ${data.to || 0} dari ${data.total} item</div>`;
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

    function deleteOrder(id) {
        Swal.fire({
            title: 'Hapus Style Order?',
            text: 'Data yang dihapus tidak dapat dikembalikan!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e11d48',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/ppic/style-orders/${id}`,
                    type: 'DELETE',
                    success: function(response) {
                        showNotification('success', 'Style order berhasil dihapus.');
                        loadData(currentPage);
                    },
                    error: function(err) {
                        showNotification('error', 'Gagal menghapus style order.');
                    }
                });
            }
        });
    }

    function openAssignModal(id, currentLineId) {
        $('#assign_style_order_id').val(id);
        if(currentLineId) {
            $('#line_id').val(currentLineId);
        } else {
            $('#line_id').val('');
        }
        $('#assignLineModal').removeClass('hidden');
    }

    function closeAssignModal() {
        $('#assignLineModal').addClass('hidden');
    }

    $('#assignLineForm').on('submit', function(e) {
        e.preventDefault();
        let id = $('#assign_style_order_id').val();
        let lineId = $('#line_id').val();
        
        $.ajax({
            url: `/ppic/style-orders/${id}/assign-line`,
            type: 'POST',
            data: { production_line_id: lineId },
            success: function(response) {
                closeAssignModal();
                showNotification('success', 'Production line berhasil ditugaskan.');
                loadData(currentPage);
            },
            error: function(err) {
                showNotification('error', 'Gagal menugaskan production line.');
            }
        });
    });

    $(document).ready(function() {
        loadData();
        let timer;
        $('#searchInput').on('keyup', function() {
            clearTimeout(timer);
            timer = setTimeout(() => { loadData(1); }, 350);
        });
        $('#statusFilter').on('change', function() { loadData(1); });
    });
</script>
@endsection
