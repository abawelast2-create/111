<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\Admin;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * عرض الإشعارات
     */
    public function index(Request $request)
    {
        $adminId = session('admin_id');
        $notifications = NotificationService::getRecent(Admin::class, $adminId, 50);
        $unreadCount = NotificationService::getUnreadCount(Admin::class, $adminId);

        return view('admin.notifications', compact('notifications', 'unreadCount'));
    }

    /**
     * تحديد الكل كمقروء
     */
    public function markAllRead()
    {
        NotificationService::markAllRead(Admin::class, session('admin_id'));
        return back()->with('success', 'تم تحديد كل الإشعارات كمقروءة');
    }

    /**
     * تحديد إشعار كمقروء
     */
    public function markRead(Notification $notification)
    {
        $notification->markAsRead();
        return back();
    }

    /**
     * عدد الإشعارات غير المقروءة (AJAX)
     */
    public function unreadCount()
    {
        $count = NotificationService::getUnreadCount(Admin::class, session('admin_id'));
        return response()->json(['count' => $count]);
    }
}
