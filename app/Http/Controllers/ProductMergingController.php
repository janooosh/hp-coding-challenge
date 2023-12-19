<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\GlobalField;
use App\Models\Product;
use App\Services\ProductMergingService;
use App\Services\ProductMergingValidations;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ProductMergingController extends Controller
{
    /**
     * Checks if the current user is authorized to edit products for the brand.
     * Throws 403 if not.
     *
     * @param Brand $brand
     * @return void
     */
    public function checkUserAuth(Brand $brand) {
        $user = Auth::user();

        if($user->cannot('editProducts', $brand)) {
            abort(403);
        }
    }

    /**
     * Returns the data needed to determine if the products can be grouped
     * and to let users add global fields.
     * Data for list in the component
     */
    public function index(Brand $brand, Request $request) {

        // Check authorization
        $this->checkUserAuth($brand);

        $request->validate([
            // product_ids is string because of url param / get request
            'product_ids' => ['required','string'],
            'parent_product_id' => ['nullable','integer']
        ]);


        // transform product_ids
        $productIds = explode(",", $request->input('product_ids'));
        $parentProductId = $request->input('parent_product_id');

        // if no parent productId is passed, we need at least two products
        $minProductIds = $parentProductId? 1 : 2;
        if(count($productIds) < $minProductIds) {
            return response()->json(['message' => 'You have to select at least two products for merging.'], 400);
        }

        // Load Products
        if($parentProductId) {
            $productIds[] = $parentProductId;
        }
        $products = Product::query()
            ->whereIn('id', $productIds)
            ->with([
                'productVariants',
            ])
            ->orderByDesc('updated_at')
            ->get();

        $parentProduct = null;
        if($parentProductId) {
            $parentProduct = $products->firstWhere('id', $parentProductId);
        }

        // Are all products found?
        if($products->count() != count($productIds)) {
            return response()->json(['message' => 'Some products do not exist'], 400);
        }

        // Do the brands match?
        if(!ProductMergingValidations::checkBrandIdMatch($products, $brand->id)) {
            return response()->json(['message' => 'The products need to belong to the same brand.'], 400);
        }

        // Are the products already merged?
        if(!ProductMergingValidations::checkProductsNotMerged($products, $parentProduct)) {
            return response()->json(['message' => 'The products are already merged.'], 400);
        }

        // Get & validate the variant option values
        $variantOptionValues = ProductMergingValidations::getOptionValuesFromProducts($products);
        $canBeGrouped = ProductMergingValidations::checkConsistentOptionValues($variantOptionValues);

        // Prepare suggested data
        $suggestedName = null;
        if($parentProductId) {
            $parentProduct = $products->firstWhere('id', $parentProductId);
            $suggestedName = $parentProduct->name;
        }

        // Return response
        $response = [
            'can_be_grouped' => $canBeGrouped,
            'product_variants' => $this->prepareProductVariantArray($products),
            'suggested_name' => $suggestedName,
        ];

        return response()->json($response);
    }

    /**
     * Merges Products together
     */
    public function create(Brand $brand, Request $request) {
        $this->checkUserAuth($brand);

        // Validate request
        $rules = [
            // parent_product_id is optional. If passed, products will be merged into the existing parentProduct. Else, a new parentProduct will be created.
            'parent_product_id' => 'integer|nullable',
            // The name and dfor the new merged product
            'parent_product_name' => 'required_without:parent_product_id',
            // The ids of the products to be merged
            'product_ids' => 'required|array',
            // An array containing changes to the productVariants
            'product_variant_option_changes' => 'array',
            // all entries of product_variant_option_changes are in this format:
            'product_variant_option_changes.*.product_variant_id' => 'required|integer',
            'product_variant_option_changes.*.global_field_id' => 'required|integer',
            'product_variant_option_changes.*.value' => 'required|string',

        ];


        $this->validate($request, $rules, [
            'parent_product_name.required_without' => 'You need to provide a name for the product',
            'product_variant_option_changes.*.product_variant_id.required' => 'There is an issue with the variants',
            'product_variant_option_changes.*.global_field_id.required' => 'There is an issue with the fields',
            'product_variant_option_changes.*.value.required' => 'All values need to be filled out',
        ]);

        $productIds = $request->input('product_ids');
        $parentProductId = $request->input('parent_product_id');
        $productVariantOptionChanges = $request->input('product_variant_option_changes', []);

        // if no parent productId is passed, we need at least two products
        $minProductIds = $parentProductId? 1 : 2;
        if(count($productIds) < $minProductIds) {
            return response()->json(['message' => 'You have to select at least two products for merging.'], 400);
        }

        // Load Products (including parentProduct)
        if($parentProductId) {
            $productIds[] = $parentProductId;
        }
        $products = Product::query()
            ->whereIn('id', $productIds)
            ->with([
                'productVariants',
            ])
            ->orderByDesc('updated_at')
            ->get();

        $parentProduct = null;
        if(!empty($parentProductId)) {
            $parentProduct = $products->firstWhere('id', $parentProductId);

            // Update name if passed
            if($request->parent_product_name) {
                $parentProduct->update([
                    'name' => $request->parent_product_name,
                ]);
            }
        }

        // Do the brands match?
        if(!ProductMergingValidations::checkBrandIdMatch($products, $brand->id)) {
            return response()->json(['message' => 'The products need to belong to the same brand.'], 400);
        }

        // Are the products already merged?
        if(!ProductMergingValidations::checkProductsNotMerged($products, $parentProduct)) {
            return response()->json(['message' => 'The products are already merged.'], 400);
        }

        // Get & validate the variant option values
        $variantOptionValues = ProductMergingValidations::getOptionValuesFromProducts($products);

        // Merge the option changes
        foreach($productVariantOptionChanges as $optionChange) {

            // If product_variant_id is not in variantOptionValues, throw error
            if(!$variantOptionValues->firstWhere('product_variant_id', $optionChange['product_variant_id'])) {
                return response()->json(['message' => 'There is an issue with the variants'], 400);
            }

            // Find existing optionValue by global_field_id and product_variant_id
            $existingOptionValue = $variantOptionValues->where('global_field_id', $optionChange['global_field_id'])
            ->where('product_variant_id', $optionChange['product_variant_id'])
            ->first();

            if($existingOptionValue) {
                // If value is null, remove the optionValue
                if($optionChange['value'] == null) {
                    $variantOptionValues = $variantOptionValues->reject(function($optionValue) use ($optionChange) {
                        return $optionValue['global_field_id'] == $optionChange['global_field_id'] && $optionValue['product_variant_id'] == $optionChange['product_variant_id'];
                    });
                }
                // Otherwise update the value
                else {
                    $variantOptionValues = $variantOptionValues->map(function ($item) use ($optionChange) {
                        if ($item['global_field_id'] == $optionChange['global_field_id'] && $item['product_variant_id'] == $optionChange['product_variant_id']) {
                            $item['value'] = $optionChange['value'];
                        }
                        return $item;
                    });
                }
            }
            // If no existing optionValue is found, create a new one
            else {
                $variantOptionValues->push([
                    'product_variant_id' => $optionChange['product_variant_id'],
                    'global_field_id' => $optionChange['global_field_id'],
                    'value' => $optionChange['value']
                ]);

                /**
                 * Reject the variantOptionValues where global_field_id is null
                 * This is important, because a null value for global_field_id only occurs from the getOptionValuesFromProducts (see above)
                 * when the variant does not have any options.
                 * So, we reject the variantOptionValues where global_field_id is null.
                 */
                $variantOptionValues = $variantOptionValues->reject(function($ov) use ($optionChange) {
                    return $ov['product_variant_id'] == $optionChange['product_variant_id'] && $ov['global_field_id'] == null;
                });
            }
        }

        // Validate consistency on variantOptionValues
        if(!ProductMergingValidations::checkConsistentOptionValues($variantOptionValues)) {
            return response()->json(['message' => 'The option values are not consistent'], 400);
        }

        if(!$parentProduct) {
            // Create parent product
            $parentProduct = ProductMergingService::createParentProduct($brand->id, $request->input('parent_product_name'));
        }

        // Merge Products
        $childProducts = $products->where('id', '!=', $parentProduct->id);
        ProductMergingService::mergeProducts($childProducts, $parentProduct);
        ProductMergingService::mergeProductVariants($childProducts, $parentProduct, $variantOptionValues);

        return response()->json(['message' => 'Products grouped successfully'],201);
    }

    /**
     * NOT RELEVANT FOR CHALLENGE - ONLY FOR VIEWING (but might help you understand the data structure)
     * Data to view the merged products
     */
    public function merged(Request $request, $brand_id, $product_id) {

        $this->checkUserAuth($brand_id);

        $product = Product::where('id',$product_id)
        ->withCount('childProducts')
        ->with('parentProduct')
        ->first();

        if(!$product) {
            return response()->json(['message' => 'Product is not found'], 404);
        }
        if(!$product->parent_product_id && $product->child_products_count === 0) {
            return response()->json(['message' => 'Product is not a merged product'], 400);
        }

        if($product->brand_id != $brand_id) {
            return response()->json(['message' => "Brand does not match"], 400);
        }

        if($product->parent_product_id != null) {
            // Step 2: Goto the parent product if it has a parent
            if(!$product->parentProduct) {
                return response()->json(['message' => "This product is not merged"], 400);
            }
            $product = $product->parentProduct;
        }

        $childProducts = $product->childProducts->load('defaultProductVariants');

        $frontendVariantData = $this->prepareProductVariantArray($childProducts,'defaultProductVariants');

        return response()->json([
            'product_name' => $product->name,
            'child_products' => $frontendVariantData,
        ],200);
    }

    /**
     * NOT RELEVANT FOR CHALLENGE - ONLY FOR VIEWING (but might help you understand the data structure)
     * Returns the array that the frontend expects
     * @param Collection $products
     * @param string $productVariantsKey = 'productVariants' (can be set to defaultProductVariants to return the "original" child Product Variants)
     */
    public function prepareProductVariantArray(Collection $products, string $productVariantsKey = 'productVariants'):array {
        $productVariantsArray = [];

        // Extract globalFields from productVariants
        $globalFieldIds = [];
        foreach($products as $product) {

            foreach($product->$productVariantsKey as $productVariant) {
                for($i = 1; $i<=4; $i++) {
                    if(!empty($productVariant->{'option_'.$i.'_global_field_id'})) {
                        $globalFieldIds[] = $productVariant->{'option_'.$i.'_global_field_id'};
                    }
                }
            }
        }
        $globalFieldIds = array_unique($globalFieldIds);

        $globalFields = GlobalField::whereIn('id', $globalFieldIds)
        ->get();

        foreach($products as $product) {
            $variantData = [];
            foreach($product->productVariants as $productVariant) {
                $filledFieldIds = []; // to make sure all global fields are present in all variants, also with null

                $arrayData = [
                    'id' => $productVariant->id,
                    'sku' => $productVariant->sku,
                    'gtin' => $productVariant->gtin,
                    'option_values' => $productVariant->option_values,
                ];

                for($i = 1; $i<=4; $i++) {
                    $optionFieldKey = "option_".$i."_global_field_id";
                    $optionValueKey = "option_".$i."_value";

                    // Find globalField?
                    $globalField = $globalFields->firstWhere('id', $productVariant->{$optionFieldKey});
                    if($globalField) {
                        $arrayData["option_".$i."_global_field"] = [
                            'id' => $globalField->id,
                            'label' => $globalField->label,
                            'mutable' => $globalField->mutable,
                            'value' => $productVariant->{$optionValueKey}
                        ];
                        $filledFieldIds[] = $globalField->id;
                    }
                    else {
                        $arrayData["option_".$i."_global_field"] = null;
                    }

                    $arrayData[$optionValueKey] = $productVariant->{$optionValueKey};
                }

                if($filledFieldIds != $globalFields->count()) {
                    $missingFieldIds = array_diff($globalFieldIds, $filledFieldIds);
                    foreach($missingFieldIds as $missingFieldId) {
                        $globalField = $globalFields->firstWhere('id', $missingFieldId);
                        for($i = 1; $i<=4; $i++) {
                            $optionKey = "option_".$i."_global_field";
                            $optionValueKey = "option_".$i."_value";
                            if(empty($arrayData[$optionKey])) {
                                $arrayData[$optionKey] = [
                                    'id' => $globalField->id,
                                    'label' => $globalField->label,
                                    'mutable' => $globalField->mutable,
                                    'value' => null
                                ];
                                $arrayData[$optionValueKey] = null;
                            }
                        }
                    }
                }

                $variantData[$productVariant->id] = $arrayData;
            }
            $productVariantsArray[$product->id] = [
                'id' => $product->id,
                'name' => $product->name,
                'variants' => $variantData
            ];
        }
        return $productVariantsArray;
    }
}
