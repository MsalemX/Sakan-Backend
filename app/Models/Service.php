<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * موديل الخدمات (Service Model)
 * يمثل الخدمات الإضافية التي يمكن أن يوفرها السكن (مثل الإنترنت أو الوجبات).
 */
class Service extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = ['name', 'extra_price', 'status'];

    protected $casts = [
        'status' => 'boolean',
    ];

    /**
     * العلاقة مع السكنات التي توفر هذه الخدمة.
     */
    public function housings()
    {
        return $this->belongsToMany(Housing::class, 'housing_service', 'service_id', 'housing_id')
                    ->withTimestamps();
    }
}
