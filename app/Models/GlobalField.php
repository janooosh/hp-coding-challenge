<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GlobalField extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    /**
     * Relationships
     */

    public function brand() {
        return $this->belongsTo(Brand::class);
    }
}
