<?php

namespace App\Support;

use App\Models\Notification;
use App\Models\User;

/**
 * Helper notifikasi antar-role (dipakai modul downtime mesin).
 */
class Notifier
{
    /**
     * Kirim notifikasi ke semua akun aktif dengan role tertentu.
     */
    public static function toRoles(array $roles, string $type, string $title, string $message, ?array $data = null): void
    {
        $users = User::whereIn('role', $roles)->where('is_active', true)->get();

        foreach ($users as $user) {
            Notification::create([
                'user_id' => $user->id,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'data' => $data,
            ]);
        }
    }

    /**
     * Kirim notifikasi ke satu akun.
     */
    public static function toUser(?int $userId, string $type, string $title, string $message, ?array $data = null): void
    {
        if (!$userId) {
            return;
        }

        Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ]);
    }
}
