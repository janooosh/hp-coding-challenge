<?php

namespace App\Services;

use App\Models\Product;
use Exception;
use Illuminate\Support\Collection;
use InvalidArgumentException;

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
        $brandIds = $products->pluck('brand_id')->unique();

        if ($brandIds->isEmpty()) {
            throw new InvalidArgumentException("Products don't have brand ids");
        }

        if ($brandIds->count() > 1) {
            return false;
        }

        return $brandId ? $brandIds->first() == $brandId : true;
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
        $parentProductIds = $products->pluck('parent_product_id')->filter()->unique();

        if ($parentProductIds->isEmpty()) {
            return true;
        }

        return $parentProduct
            && $parentProductIds->count() === 1
            && $parentProductIds->first() === $parentProduct->id;
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
        // (1) There is at least one option (global_field_id) for each product variant (no nulls)
        $emptyVariants = $optionValues->firstWhere('global_field_id', null);
        if($emptyVariants) {
            return false;
        }

        // (2) Each product_variant_id has the same number of global_field_ids with a non-empty value and
        // (3) All variants share the same global_field_ids
        // (4) Each variant has a unique combination of global_field_id / value
        $productVariantIds = $optionValues->pluck('product_variant_id')->unique();
        $globalFieldIds = $optionValues->pluck('global_field_id')->unique();

        $uniqueCombinations = [];

        foreach($productVariantIds as $productVariantId) {

            $uniqueCombination = $optionValues->where('product_variant_id', $productVariantId)
            ->sortBy('global_field_id')
            ->unique(function($item) {
                return $item['global_field_id'].$item['value'];
            });
            $uniqueCombination = $uniqueCombination->map(function($item) {
                return $item['global_field_id'].$item['value'];
            })->implode(',');

            // Is uniqueCombination already in uniqueCombinations?
            if(in_array($uniqueCombination, $uniqueCombinations)) {
                return false;
            }
            else {
                $uniqueCombinations[] = $uniqueCombination;
            }

            $optionValuesForProductVariant = $optionValues->where('product_variant_id', $productVariantId)
            ->where('value', '!=', null);

            // (2) Each product_variant_id has the same number of global_field_ids with a non-empty value
            if($globalFieldIds->count() != $optionValuesForProductVariant->count()) {
                return false;
            }

            // (3) All variants share the same global_field_ids
            $globalFieldIdsForProductVariant = $optionValuesForProductVariant->pluck('global_field_id')->unique();
            if($globalFieldIdsForProductVariant->count() != $globalFieldIds->count()) {
                return false;
            }
        }

        return true;
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
