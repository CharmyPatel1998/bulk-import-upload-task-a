<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    protected $fillable = ['upload_id', 'path', 'variants'];

    protected $casts = [
        'variants' => 'array'
    ];

    public function upload()
    {
        return $this->belongsTo(Upload::class);
    }
}

