<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Scoped to the signed-in user's own notifications, so a guessed id
     * belonging to somebody else simply is not found.
     */
    public function markRead(Request $request, string $notification): RedirectResponse
    {
        $request->user()
            ->unreadNotifications()
            ->where('id', $notification)
            ->first()
            ?->markAsRead();

        return back();
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back();
    }
}
