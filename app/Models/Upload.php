<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Upload extends Model
{
    protected $fillable = ['filename', 'checksum', 'completed'];

    public function images()
    {
        return $this->hasMany(Image::class);
    }

}
