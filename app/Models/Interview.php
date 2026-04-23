<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * موديل المقابلة (Interview Model)
 * يسجل تفاصيل ونتائج المقابلة بين الطالب وصاحب السكن.
 */
class Interview extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'request_id',
        'student_id',
        'housing_id',
        'interview_date',
        'interview_result',
        'interview_status',
        'notes',
    ];

    protected $casts = [
        'interview_date' => 'datetime',
    ];

    /**
     * العلاقة مع طلب الحجز المرتبط بالمقابلة.
     */
    public function bookingRequest()
    {
        return $this->belongsTo(BookingRequest::class, 'request_id');
    }

    /**
     * العلاقة مع الطالب المعني بالمقابلة.
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * العلاقة مع السكن المعني بالمقابلة.
     */
    public function housing()
    {
        return $this->belongsTo(Housing::class);
    }
}
