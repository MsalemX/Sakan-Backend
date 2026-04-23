<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * موديل صور السكن (Housing Image Model)
 * يخزن روابط الصور الخاصة بكل سكن.
 */
class HousingImage extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = ['housing_id', 'image_url'];

    /**
     * العلاقة مع السكن الذي تتبعه الصورة.
     */
    public function housing()
    {
        return $this->belongsTo(Housing::class);
    }
}
