@extends('layouts.app')

@section('title', 'Pusat Notifikasi')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 sm:p-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Semua Notifikasi & Alert</h2>
            <p class="text-xs text-slate-500 mt-1">Pemberitahuan peringatan kualitas, evaluasi manager, dan feedback loop produksi.</p>
        </div>
        
        <div class="flex items-center gap-3">
            <select id="notificationFilter" class="text-xs py-2 px-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none bg-white font-medium">
                <option value="all">Semua Notifikasi</option>
                <option value="unread">Belum Dibaca</option>
                <option value="read">Sudah Dibaca</option>
            </select>
            
            <button id="markAllReadBtn" class="px-4 py-2 bg-indigo-50 text-indigo-700 text-xs font-bold rounded-xl hover:bg-indigo-100 transition shadow-xs">
                Tandai Semua Dibaca
            </button>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        @if(isset($notifications) && $notifications->count() > 0)
            <ul class="divide-y divide-slate-100" id="notificationsList">
                @foreach($notifications as $notification)
                    @php
                        $isAlert = str_contains(strtolower($notification->title), 'alert') || str_contains(strtolower($notification->title), 'tidak sesuai');
                    @endphp
                    <li class="p-5 hover:bg-slate-50 transition-colors {{ !$notification->is_read ? ($isAlert ? 'bg-rose-50/40' : 'bg-indigo-50/40') : '' }}" data-status="{{ !$notification->is_read ? 'unread' : 'read' }}">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 mt-0.5">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center {{ $isAlert ? 'bg-rose-100 text-rose-600' : (!$notification->is_read ? 'bg-indigo-100 text-indigo-600' : 'bg-slate-100 text-slate-400') }}">
                                    @if($isAlert)
                                        <i class="fa-solid fa-triangle-exclamation text-lg"></i>
                                    @else
                                        <i class="fa-solid fa-bell text-lg"></i>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between mb-1">
                                    <h4 class="text-sm {{ !$notification->is_read ? 'font-bold text-slate-900' : 'font-semibold text-slate-700' }}">
                                        {{ $notification->title }}
                                    </h4>
                                    <span class="text-[11px] text-slate-400 whitespace-nowrap ml-2">
                                        {{ $notification->created_at->diffForHumans() }}
                                    </span>
                                </div>
                                
                                <p class="text-xs text-slate-600 leading-relaxed mb-2">
                                    {{ $notification->message }}
                                </p>
                            </div>
                            
                            @if(!$notification->is_read)
                                <div class="flex-shrink-0 ml-2">
                                    <button class="mark-read-btn text-xs font-semibold px-2.5 py-1 rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-600 hover:text-white transition" data-id="{{ $notification->id }}">
                                        Tandai Dibaca
                                    </button>
                                </div>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
            
            <div class="p-4 border-t border-slate-100 bg-slate-50">
                {{ $notifications->links() }}
            </div>
        @else
            <div class="p-12 text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-100 mb-3 text-slate-400">
                    <i class="fa-regular fa-bell-slash text-2xl"></i>
                </div>
                <h3 class="text-base font-bold text-slate-800">Tidak Ada Notifikasi</h3>
                <p class="text-xs text-slate-500 mt-1">Belum ada peringatan atau notifikasi masuk saat ini.</p>
            </div>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        $('#notificationFilter').change(function() {
            const filter = $(this).val();
            if (filter === 'all') {
                $('#notificationsList li').show();
            } else {
                $('#notificationsList li').hide();
                $(`#notificationsList li[data-status="${filter}"]`).show();
            }
        });

        $('.mark-read-btn').click(function() {
            const btn = $(this);
            const id = btn.data('id');
            const li = btn.closest('li');

            $.ajax({
                url: `/notifications/${id}/read`,
                type: 'POST',
                success: function(response) {
                    li.removeClass('bg-indigo-50/40 bg-rose-50/40').attr('data-status', 'read');
                    btn.parent().remove();
                    showNotification('success', 'Notifikasi ditandai dibaca.');
                },
                error: function() {
                    showNotification('error', 'Gagal memperbarui notifikasi.');
                }
            });
        });

        $('#markAllReadBtn').click(function() {
            $.post("{{ route('notifications.readAll') }}", function() {
                showNotification('success', 'Semua notifikasi telah ditandai dibaca.');
                setTimeout(() => location.reload(), 600);
            });
        });
    });
</script>
@endsection
