<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * موديل المستخدم (User Model)
 * يمثل الكيان الأساسي للمستخدم في النظام، ويتم استخدامه للمصادقة.
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, \Laravel\Sanctum\HasApiTokens, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'profile_image',
        // حقول Social Login
        'google_id',  // معرّف المستخدم على Google
        'avatar',     // رابط صورة Google
        'provider',   // 'google' | null
        'fcm_token',  // توكن الإشعارات
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * العلاقة مع دور المستخدم (Role).
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * العلاقة مع الملف الشخصي للطالب (Student Profile).
     */
    public function student()
    {
        return $this->hasOne(Student::class);
    }

    /**
     * العلاقة مع الملف الشخصي لصاحب السكن (Housing Owner Profile).
     */
    public function housingOwner()
    {
        return $this->hasOne(HousingOwner::class);
    }

    /**
     * العلاقة مع الإشعارات الصادرة للمستخدم.
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
}
