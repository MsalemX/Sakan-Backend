<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'message',
        'type',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * إرسال إشعار FCM تلقائياً عند إنشاء إشعار في قاعدة البيانات.
     */
    protected static function booted()
    {
        static::created(function ($notification) {
            $user = $notification->user;
            if ($user && $user->fcm_token) {
                \App\Services\FCMService::sendNotification(
                    $user->fcm_token,
                    $notification->title,
                    $notification->message
                );
            }
        });
    }
}
