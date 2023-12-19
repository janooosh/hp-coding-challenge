<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * Relationships
     */
    public function products() {
        return $this->hasMany(Product::class);
    }

    public function users() {
        return $this->belongsToMany(User::class);
    }

    public function globalFields() {
        return $this->hasMany(GlobalField::class);
    }
}
