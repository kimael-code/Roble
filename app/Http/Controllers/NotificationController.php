<?php

namespace App\Http\Controllers;

use App\InertiaProps\NotificationIndexProps;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Inertia\Inertia;

class NotificationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(NotificationIndexProps $props)
    {
        return Inertia::render('notifications/Index', $props->toArray(auth()->user()));
    }

    /**
     * Mark a notification as read and redirect to its URL.
     */
    public function markAsRead(DatabaseNotification $notification)
    {
        $notification->markAsRead();

        return redirect($notification->data['url']);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return redirect()->back();
    }

    /**
     * Mark a notification as read without redirecting.
     */
    public function markAsReadOnly(DatabaseNotification $notification)
    {
        $notification->markAsRead();

        return redirect()->back();
    }

    /**
     * Delete a notification.
     */
    public function destroy(DatabaseNotification $notification)
    {
        $notification->delete();

        return redirect()->back();
    }

    /**
     * Delete all notifications for the authenticated user.
     */
    public function destroyAll(Request $request)
    {
        $request->user()->notifications()->delete();

        return redirect()->back();
    }
}
