<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * موديل الأدوار (Role Model)
 * يحدد الأدوار المختلفة في النظام (مسؤول، طالب، صاحب سكن).
 */
class Role extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    /**
     * العلاقة مع المستخدمين المنتمين لهذا الدور.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
