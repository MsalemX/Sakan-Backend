<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * موديل الطالب (Student Model)
 * يحتوي على المعلومات التفصيلية والوثائق الخاصة بالطلاب.
 */
class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'full_name',
        'personal_id_image',
        'father_id_image',
        'university_name',
        'major',
        'university_card_image',
        'academic_level',
        'image',
        'phone_number',
        'address',
        'nationality',
        'proof_of_enrollment',
    ];

    protected $appends = [
        'personal_id_image_url',
        'father_id_image_url',
        'university_card_image_url',
        'image_url',
        'proof_of_enrollment_url',
    ];

    /**
     * العلاقة مع حساب المستخدم الأساسي.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * العلاقة مع طلبات الحجز الخاصة بالطالب.
     */
    public function bookingRequests()
    {
        return $this->hasMany(BookingRequest::class);
    }

    /**
     * العلاقة مع المقابلات التي أجراها الطالب.
     */
    public function interviews()
    {
        return $this->hasMany(Interview::class);
    }

    /**
     * العلاقة مع التقييمات التي قدمها الطالب.
     */
    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }

    /**
     * العلاقة مع الحجوزات المؤكدة للطالب.
     */
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Accessor للحصول على رابط الصورة الشخصية الكامل.
     */
    public function getPersonalIdImageUrlAttribute()
    {
        return $this->personal_id_image ? asset('storage/'.$this->personal_id_image) : null;
    }

    /**
     * Accessor للحصول على رابط صورة بطاقة الأب الكامل.
     */
    public function getFatherIdImageUrlAttribute()
    {
        return $this->father_id_image ? asset('storage/'.$this->father_id_image) : null;
    }

    /**
     * Accessor للحصول على رابط صورة البطاقة الجامعية الكامل.
     */
    public function getUniversityCardImageUrlAttribute()
    {
        return $this->university_card_image ? asset('storage/'.$this->university_card_image) : null;
    }

    /**
     * Accessor للحصول على رابط الصورة الشخصية الكامل.
     */
    public function getImageUrlAttribute()
    {
        return $this->image ? asset('storage/'.$this->image) : null;
    }

    /**
     * Accessor للحصول على رابط إثبات القيد الكامل.
     */
    public function getProofOfEnrollmentUrlAttribute()
    {
        return $this->proof_of_enrollment ? asset('storage/'.$this->proof_of_enrollment) : null;
    }
}
