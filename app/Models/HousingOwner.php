<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * موديل صاحب السكن (Housing Owner Model)
 * يحتوي على المعلومات الخاصة بأصحاب السكنات وحالة اعتمادهم.
 */
class HousingOwner extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'id_number',
        'phone_number',
        'commercial_register',
        'is_approved',
    ];

    /**
     * العلاقة مع حساب المستخدم الأساسي.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * العلاقة مع السكنات المملوكة لهذا الشحص.
     */
    public function housings()
    {
        return $this->hasMany(Housing::class);
    }
}
