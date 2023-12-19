<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $guarded=['id'];

    /**
     * Relationships
     */
    public function brand() {
        return $this->belongsTo(Brand::class);
    }

    public function parentProduct() {
        return $this->belongsTo(Product::class,'parent_product_id');
    }

    /**
     * Returns the "correct" productVariants, depending on whether this is a parent product or not.
     * For a parentProduct, it returns all associated productVariants.
     * For a childProduct, it returns the "original" productVariants (before merging).
     */
    public function productVariants() {
        $foreignKey = 'product_id';
        if(!empty($this->parent_product_id)) {
            $foreignKey = 'default_product_id';
        }
        return $this->hasMany(ProductVariant::class,$foreignKey);
    }
}
