<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * These functions merge and unmerge products and take care of moving the product variants.
 */
class ProductMergingService {
    /**
     * Creates a new parent product and returns it
     * @param int $brandId
     * @param string $name
     *
     * @return Product ParentProduct
     */
    public static function createParentProduct(int $brandId, string $name):Product {
        $parentProduct = Product::create([
            'brand_id' => $brandId,
            'name' => $name
        ]);

        return $parentProduct;
    }

    /**
     * Make the products childProducts of the parentProduct
     *
     * @param Collection $childProductsToBe
     * @param Product $parentProduct
     */
    public static function mergeProducts(Collection &$childProductsToBe, Product $parentProduct):void {
        foreach($childProductsToBe as $childProduct) {
            $childProduct->update([
                'parent_product_id' => $parentProduct->id
            ]);
        }
    }

    /**
     * Call this function to update the productVariants.
     * It sets option values and moves the productVariants to the parentProduct.
     * $optionChanges is a collection with product_variant_id, global_field_id and value.
     *
     * Note that when value is set to null in optionChanges,
     * it essentially means to remove the optionValue
     *
     * @param Collection $products
     * @param Product $parentProduct
     * @param Collection $optionChanges
     */
    public static function mergeProductVariants(Collection $products, Product $parentProduct, Collection $optionChanges):void {
        foreach($products as $product) {
            foreach($product->productVariants as $productVariant) {
                // Set option values
                foreach($optionChanges as $optionChange) {
                    if($optionChange['product_variant_id'] == $productVariant->id) {
                        $productVariant->setOptionValue($optionChange['global_field_id'], $optionChange['value']);
                    }
                }

                // Move productVariant to parentProduct
                $productVariant->update([
                    'product_id' => $parentProduct->id,
                    'default_product_id' => $product->id
                ]);
            }
        }
    }
}
