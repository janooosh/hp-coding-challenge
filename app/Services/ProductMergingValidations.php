<?php

namespace App\Services;

use App\Models\Product;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Functions to facilitate the validation of merging products together.
 * These functions have a specific cause and typically return bool whether they meet that cause or not.
 */
class ProductMergingValidations {

    /**
     * validates if products share the same brandId.
     * If provided with a brandId, checks if it is the same as the products.
     * Otherwise, just checks if the products share the same brandId.
     *
     * @param Collection $products
     * @param ?int $brandId = null
     * @return bool
     */
    public static function checkBrandIdMatch(Collection $products, ?int $brandId = null):bool {
        // TO DO
        return false;
    }

    /**
     * Checks if the products are not merged yet
     * Accepts an (optional) parentProduct, if passed this is the only product
     * that the products are allowed to be merged into
     *
     * @param Collection $products
     * @param ?Product $parentProduct=null
     * @return bool
     */
    public static function checkProductsNotMerged(Collection $products, ?Product $parentProduct=null):bool {
        // TO DO (you can disregard the parentProduct)
        return false;
    }

    /**
     * Checks if the option values are valid to merge
     * Structure of the optionValues collection:
     * - product_variant_id
     * - global_field_id
     * - value
     *
     * @param Collection $optionValues (product_variant_id, global_field_id, value)
     */
    public static function checkConsistentOptionValues(Collection $optionValues):bool {
        // TO DO
        return false;
    }


    /**
     * Extract the (existing) option values from the products
     * (Note we let ProductMergingController append the changes from frontend on the optionValues)
     * Structure of the array:
     * - product_variant_id
     * - global_field_id
     * - value
     *
     * If a productVariant has no option, it is still included in the array with null values.
     *
     * The productVariants should be pre-loaded to avoid N+1.
     *
     * @param Collection $products
     * @return Collection
     */
    public static function getOptionValuesFromProducts(Collection $products):Collection {
        $optionValues = collect();
        foreach($products as $product) {

            // Each child product must have productVariants for grouping.
            if(is_null($product->parent_product_id) && $product->productVariants->count() == 0) {
                throw new Exception("Product ".$product->id." has no productVariants");
            }

            foreach($product->productVariants as $productVariant) {
                $atLeastOneOptionFound = false;
                // Iterate over the options
                for($i = 1; $i<=3; $i++) {
                    $optionFieldKey = "option_".$i."_global_field_id";
                    $optionValueKey = "option_".$i."_value";
                    if(!empty($productVariant->{$optionFieldKey})) {
                        $optionValues->push([
                            'product_variant_id' => $productVariant->id,
                            'global_field_id' => $productVariant->{$optionFieldKey},
                            'value' => $productVariant->{$optionValueKey}
                        ]);
                        $atLeastOneOptionFound = true;
                    }
                }

                if($atLeastOneOptionFound == false) {
                    $optionValues->push([
                        'product_variant_id' => $productVariant->id,
                        'global_field_id' => null,
                        'value' => null
                    ]);
                }
            }
        }

        return $optionValues;
    }
}
