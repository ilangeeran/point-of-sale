<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $appends = ['image_url'];
    

    /**
     * Get the products image.
     *
     * @return string
     */
    public function getImageUrlAttribute()
    {
        if (! empty($this->image)) {
            $image_url = asset('/uploads/banners/'.rawurlencode($this->image));
        } else {
            $image_url = asset('/banners/default.png');
        }

        return $image_url;
    }

    /**
     * Get the products image path.
     *
     * @return string
     */
    public function getImagePathAttribute()
    {
        if (! empty($this->image)) {
            $image_path = public_path('uploads').'/banners/'.$this->image;
        } else {
            $image_path = null;
        }

        return $image_path;
    }
}
