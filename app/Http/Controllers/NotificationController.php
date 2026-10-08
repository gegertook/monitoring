<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = Auth::user()->notifications()->latest()->paginate(15);
        return view('notifications.index', compact('notifications'));
    }

    public function unreadCount()
    {
        $count = Auth::user()->notifications()->where('is_read', false)->count();
        return response()->json(['count' => $count]);
    }

    public function dropdown()
    {
        $notifications = Auth::user()->notifications()->latest()->take(6)->get();
        return view('partials.notifications', compact('notifications'));
    }

    public function markAsRead(Notification $notification)
    {
        try {
            if ($notification->user_id !== Auth::id()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $notification->update(['is_read' => true]);
            
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['success' => true]);
            }
            
            return redirect()->back()->with('success', 'Notification marked as read.');
        } catch (\Exception $e) {
            Log::error('Error marking notification as read: ' . $e->getMessage());
            
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['error' => 'Failed to mark as read'], 500);
            }
            
            return back()->with('error', 'Failed to mark notification as read.');
        }
    }

    public function markAllAsRead()
    {
        try {
            Auth::user()->notifications()->where('is_read', false)->update(['is_read' => true]);
            
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['success' => true]);
            }
            
            return redirect()->back()->with('success', 'All notifications marked as read.');
        } catch (\Exception $e) {
            Log::error('Error marking all notifications as read: ' . $e->getMessage());
            
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['error' => 'Failed to mark all as read'], 500);
            }
            
            return back()->with('error', 'Failed to mark notifications as read.');
        }
    }
}
