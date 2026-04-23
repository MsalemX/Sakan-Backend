<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * موديل التقييم (Rating Model)
 * يمثل تقييمات الطلاب للسكنات.
 */
class Rating extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'rate',
        'comment',
        'housing_id',
        'student_id',
    ];

    /**
     * العلاقة مع السكن المقيم.
     */
    public function housing()
    {
        return $this->belongsTo(Housing::class);
    }

    /**
     * العلاقة مع الطالب الذي قام بالتقييم.
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
