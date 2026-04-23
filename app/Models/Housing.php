<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * موديل السكن (Housing Model)
 * يمثل السكنات المتاحة في النظام ويحتوي على تفاصيل الموقع، السعر، والخدمات.
 */
class Housing extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    // the database column is supposed to be `name`; legacy versions might have used
    // `title` so we keep both in fillable and provide accessors/mutators below in
    // case the table still contains the old name.  The controller always works with
    // `name` and the front‑end sends `name`, which is the public contract.
    protected $fillable = [
        'name',
        'housing_owner_id',
        'description',
        'conditions', // شروط السكن التي يدخلها المالك
        'base_price',
        'is_available',
        'is_approved',
        'latitude',
        'longitude',
        'features',
        'capacity',
        'remaining_capacity',
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'is_approved' => 'boolean',
        'features' => 'array',
    ];

    /**
     * العلاقة مع مالك السكن.
     */
    public function owner()
    {
        return $this->belongsTo(HousingOwner::class, 'housing_owner_id');
    }

    /**
     * العلاقة مع صور السكن.
     */
    public function images()
    {
        return $this->hasMany(HousingImage::class);
    }

    /**
     * العلاقة مع الخدمات المتوفرة في السكن.
     */
    public function services()
    {
        return $this->belongsToMany(Service::class, 'housing_service', 'housing_id', 'service_id')
            ->withTimestamps();
    }

    /**
     * نطاق (Scope) لجلب السكنات المعتمدة فقط.
     */
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    /**
     * العلاقة مع طلبات الحجز المقدمة على هذا السكن.
     */
    public function bookingRequests()
    {
        return $this->hasMany(BookingRequest::class);
    }

    /*
     * The application API always refers to the housing's "name" property.  If the
     * underlying table still uses a `title` column (left over from an earlier
     * migration) we transparently map between the two so that callers do not have
     * to change.  When the table is corrected the accessors become a no‑op.
     */
    public function setTitleAttribute($value)
    {
        // store into the `name` column regardless of which attribute was used
        $this->attributes['name'] = $value;
    }

    public function getTitleAttribute()
    {
        return $this->attributes['name'] ?? null;
    }

    // keep the normal name accessor/mutator for clarity (Eloquent handles it by
    // default, but we define it for symmetry).
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = $value;
    }

    public function getNameAttribute()
    {
        return $this->attributes['name'] ?? null;
    }

    /**
     * العلاقة مع المقابلات المرتبطة بالسكن.
     */
    public function interviews()
    {
        return $this->hasMany(Interview::class);
    }

    /**
     * العلاقة مع تقييمات الطلاب للسكن.
     */
    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }

    /**
     * العلاقة مع الحجوزات المؤكدة للسكن.
     */
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
}
