<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Notification bell: mark announcements and notifications as read (one by one, or all at once). */
class NotificationController extends Controller
{
    /** Clicking one announcement in the bell marks only that one as read. */
    public function read(Request $request, Announcement $announcement)
    {
        $this->markRead($request->user()->id, [$announcement->id]);

        return response()->json(['ok' => true]);
    }

    /** Clicking one notification (recommendation or class update) marks it as read. Only the owner's own. */
    public function readNotice(Request $request, string $id)
    {
        $request->user()->notifications()->whereKey($id)->firstOrFail()->markAsRead();

        return response()->json(['ok' => true]);
    }

    /** "Mark all as read" marks every announcement the user can see and all of the user's notifications. */
    public function readAll(Request $request)
    {
        $user = $request->user();
        $ids = ($user->isLibrarian() ? Announcement::current() : Announcement::visibleTo($user))->pluck('id')->all();
        $this->markRead($request->user()->id, $ids);
        $user->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }

    private function markRead(int $userId, array $ids): void
    {
        $rows = array_map(fn ($id) => ['announcement_id' => $id, 'user_id' => $userId, 'read_at' => now()], $ids);
        DB::table('announcement_reads')->insertOrIgnore($rows);
    }
}
