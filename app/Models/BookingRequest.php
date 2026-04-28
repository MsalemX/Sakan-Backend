<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * موديل طلب الحجز (Booking Request Model)
 * يمثل الطلب الأولي الذي يقدمه الطالب للسكن قبل المقابلة.
 */
class BookingRequest extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'student_id',
        'housing_id',
        'start_date',
        'end_date',
        'status',
        'selected_services',
    ];

    protected $casts = [
        'selected_services' => 'array',
    ];

    /**
     * العلاقة مع الطالب مقدم الطلب.
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * العلاقة مع السكن المطلوب.
     */
    public function housing()
    {
        return $this->belongsTo(Housing::class);
    }

    /**
     * العلاقة مع المقابلة المرتبطة بهذا الطلب.
     */
    public function interview()
    {
        return $this->hasOne(Interview::class, 'request_id');
    }

    /**
     * العلاقة مع الخدمات المختارة.
     */
    public function selectedServices()
    {
        return Service::whereIn('id', $this->selected_services ?? [])->get();
    }
}
