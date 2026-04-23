<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * موديل الحجز (Booking Model)
 * يمثل الحجز المؤكد للسكن بعد انتهاء المقابلة بنجاح.
 */
class Booking extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'student_id',
        'housing_id',
        'interview_id',
        'booking_date',
        'end_date',
        'status',
        'selected_services',
    ];

    protected $casts = [
        'booking_date' => 'date',
        'end_date' => 'date',
        'selected_services' => 'array',
    ];

    /**
     * العلاقة مع الطالب صاحب الحجز.
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * العلاقة مع السكن المحجوز.
     */
    public function housing()
    {
        return $this->belongsTo(Housing::class);
    }

    /**
     * العلاقة مع المقابلة التي أدت لهذا الحجز.
     */
    public function interview()
    {
        return $this->belongsTo(Interview::class);
    }

    /**
     * العلاقة مع الخدمات المختارة.
     */
    public function selectedServices()
    {
        return Service::whereIn('id', $this->selected_services ?? [])->get();
    }
}
