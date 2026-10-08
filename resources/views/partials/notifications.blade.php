<div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
    <h3 class="text-sm font-semibold text-gray-800">Notifikasi</h3>
    @if(isset($notifications) && $notifications->where('is_read', false)->count() > 0)
        <button type="button" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium mark-all-read-btn">Tandai semua dibaca</button>
    @endif
</div>

<div class="max-h-80 overflow-y-auto">
    @if(isset($notifications) && $notifications->count() > 0)
        <ul class="divide-y divide-gray-100">
            @foreach($notifications as $notification)
                <li class="px-4 py-3 hover:bg-gray-50 transition-colors {{ !$notification->is_read ? 'bg-indigo-50/50' : '' }}">
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center {{ !$notification->is_read ? 'bg-indigo-100 text-indigo-600' : 'bg-gray-100 text-gray-500' }}">
                            @if(str_contains(strtolower($notification->title), 'alert') || str_contains(strtolower($notification->title), 'tidak sesuai'))
                                <i class="fa-solid fa-triangle-exclamation text-amber-500 text-sm"></i>
                            @else
                                <i class="fa-solid fa-bell text-sm"></i>
                            @endif
                        </div>
                        
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold {{ !$notification->is_read ? 'text-indigo-950 font-bold' : 'text-gray-800' }} truncate">
                                {{ $notification->title }}
                            </p>
                            <p class="text-xs text-gray-600 mt-0.5 line-clamp-2">
                                {{ $notification->message }}
                            </p>
                            <p class="text-[11px] text-gray-400 mt-1">
                                {{ $notification->created_at->diffForHumans() }}
                            </p>
                        </div>
                        
                        @if(!$notification->is_read)
                            <div class="flex-shrink-0">
                                <button type="button" class="w-3 h-3 rounded-full bg-indigo-600 hover:bg-indigo-800 mark-single-read" title="Tandai dibaca" data-id="{{ $notification->id }}"></button>
                            </div>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    @else
        <div class="px-4 py-8 text-center">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-gray-100 mb-3 text-gray-400">
                <i class="fa-regular fa-bell-slash text-xl"></i>
            </div>
            <p class="text-sm text-gray-500">Tidak ada notifikasi baru</p>
        </div>
    @endif
</div>

<div class="p-3 border-t border-gray-100 text-center">
    <a href="{{ route('notifications.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800 transition-colors">
        Lihat Semua Notifikasi
    </a>
</div>
