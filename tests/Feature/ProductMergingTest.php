<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\GlobalField;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductMergingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_the_application_returns_a_successful_response()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    /**
     * Test can not merge products when products have different brands.
     */
    public function testCanNotMergeProductsWhenBrandsDontMatch()
    {
        // Arrange: Create a user, brand, and products with consistent option values
        $user = User::factory()->create();
        $brand = Brand::factory()->create();
        $brand->users()->attach($user);

        $products = Product::factory()->count(3)->create(); // will create a new brand for each product

        $colorGlobalField =  GlobalField::factory()->create(['brand_id' => $brand->id, 'label' => 'Color']);
        $sizeGlobalField =  GlobalField::factory()->create(['brand_id' => $brand->id, 'label' => 'Size']);

        $colorValues = ['Red','Red','Red','Green','Green','Green','Blue','Blue','Blue'];
        $sizeValues = ['Small','Medium','Large','Small','Medium','Large','Small','Medium','Large'];

        // Mock consistent option values for products
        for($i = 0; $i<min(count($colorValues),count($sizeValues)); $i++){
            $globalFieldsArray = [
                'option_1_global_field_id' => $colorGlobalField->id,
                'option_1_value' => $colorValues[$i],
                'option_2_global_field_id' => $sizeGlobalField->id,
                'option_2_value' => $sizeValues[$i],
                'option_3_global_field_id' => null,
                'option_3_value' => null,
            ];

            $productIndex = intval(ceil($i % count($products)));
            $productId = $products[$productIndex]->id;

            ProductVariant::factory()->create(array_merge(['product_id' => $productId],$globalFieldsArray));
        }

        // Act: Make a request to the endpoint
        $response = $this->actingAs($user)->json('GET', '/api/product-merger/'.$brand->id, [
            'product_ids' => $products->pluck('id')->implode(','),
        ]);

        // Assert: `can_be_grouped` should be true
        $response->assertStatus(400)
                 ->assertJson([
                     'message' => 'The products need to belong to the same brand.',
                 ]);
    }

    /**
     * Test can not merge products when products have different brands.
     */
    public function testCanNotMergeProductsThatAreAlreadyMerged()
    {
        // Arrange: Create a user, brand, and products with consistent option values
        $user = User::factory()->create();
        $brand = Brand::factory()->create();
        $brand->users()->attach($user);

        $products = Product::factory()->count(3)->create([
            'parent_product_id' => Product::factory()->create()->id,
            'brand_id' => $brand->id
        ]);

        $colorGlobalField =  GlobalField::factory()->create(['brand_id' => $brand->id, 'label' => 'Color']);
        $sizeGlobalField =  GlobalField::factory()->create(['brand_id' => $brand->id, 'label' => 'Size']);

        $colorValues = ['Red','Red','Red','Green','Green','Green','Blue','Blue','Blue'];
        $sizeValues = ['Small','Medium','Large','Small','Medium','Large','Small','Medium','Large'];

        // Mock consistent option values for products
        for($i = 0; $i<min(count($colorValues),count($sizeValues)); $i++){
            $globalFieldsArray = [
                'option_1_global_field_id' => $colorGlobalField->id,
                'option_1_value' => $colorValues[$i],
                'option_2_global_field_id' => $sizeGlobalField->id,
                'option_2_value' => $sizeValues[$i],
                'option_3_global_field_id' => null,
                'option_3_value' => null,
            ];

            $productIndex = intval(ceil($i % count($products)));
            $productId = $products[$productIndex]->id;

            ProductVariant::factory()->create(array_merge(['product_id' => $productId],$globalFieldsArray));
        }

        // Act: Make a request to the endpoint
        $response = $this->actingAs($user)->json('GET', '/api/product-merger/'.$brand->id, [
            'product_ids' => $products->pluck('id')->implode(','),
        ]);

        // Assert: `can_be_grouped` should be true
        $response->assertStatus(400)
                 ->assertJson([
                     'message' => 'The products are already merged.',
                 ]);
    }

    /**
     * Provided with consistent option values, products can be grouped.
     *
     * @return void
     */
    public function testCanGroupWithConsistentOptionValues()
    {
        // Arrange: Create a user, brand, and products with consistent option values
        $user = User::factory()->create();
        $brand = Brand::factory()->create();
        $brand->users()->attach($user);

        $products = Product::factory()->count(3)->create(['brand_id' => $brand->id]);

        $colorGlobalField =  GlobalField::factory()->create(['brand_id' => $brand->id, 'label' => 'Color']);
        $sizeGlobalField =  GlobalField::factory()->create(['brand_id' => $brand->id, 'label' => 'Size']);

        $colorValues = ['Red','Red','Red','Green','Green','Green','Blue','Blue','Blue'];
        $sizeValues = ['Small','Medium','Large','Small','Medium','Large','Small','Medium','Large'];

        // Mock consistent option values for products
        for($i = 0; $i<min(count($colorValues),count($sizeValues)); $i++){
            $globalFieldsArray = [
                'option_1_global_field_id' => $colorGlobalField->id,
                'option_1_value' => $colorValues[$i],
                'option_2_global_field_id' => $sizeGlobalField->id,
                'option_2_value' => $sizeValues[$i],
                'option_3_global_field_id' => null,
                'option_3_value' => null,
            ];

            $productIndex = intval(ceil($i % count($products)));
            $productId = $products[$productIndex]->id;

            ProductVariant::factory()->create(array_merge(['product_id' => $productId],$globalFieldsArray));
        }

        // Act: Make a request to the endpoint
        $response = $this->actingAs($user)->json('GET', '/api/product-merger/'.$brand->id, [
            'product_ids' => $products->pluck('id')->implode(','),
        ]);

        // Assert: `can_be_grouped` should be true
        $response->assertStatus(200)
                 ->assertJson([
                     'can_be_grouped' => true,
                 ]);
    }

    /**
     * Don't let users merge products if the option fields are inconsistent.
     *
     * @return void
     */
    public function testCanNotGroupWithInconsistentOptionFields()
    {
        // Arrange: Create a user, brand, and products with consistent option values
        $user = User::factory()->create();
        $brand = Brand::factory()->create();
        $brand->users()->attach($user);

        $products = Product::factory()->count(3)->create(['brand_id' => $brand->id]);

        $colorGlobalField =  GlobalField::factory()->create(['brand_id' => $brand->id, 'label' => 'Color']);
        $sizeGlobalField =  GlobalField::factory()->create(['brand_id' => $brand->id, 'label' => 'Size']);
        $sizeTwoGlobalField = GlobalField::factory()->create(['brand_id' => $brand->id, 'label' => 'Size Two']);

        $colorValues = ['Red','Red','Red','Green','Green','Green','Blue','Blue','Blue'];
        $sizeValues = ['Small','Medium','Large','XL','XXL','Small','Small','Medium','Large'];

        // Mock consistent option values for products
        for($i = 0; $i<min(count($colorValues),count($sizeValues)); $i++){
            $globalFieldsArray = [
                'option_1_global_field_id' => $colorGlobalField->id,
                'option_1_value' => $colorValues[$i],
                'option_2_global_field_id' => $i%2 == 0? $sizeGlobalField->id:$sizeTwoGlobalField->id,
                'option_2_value' => $sizeValues[$i],
                'option_3_global_field_id' => null,
                'option_3_value' => null,
            ];

            $productIndex = intval(ceil($i % count($products)));
            $productId = $products[$productIndex]->id;

            ProductVariant::factory()->create(array_merge(['product_id' => $productId],$globalFieldsArray));
        }

        // Act: Make a request to the endpoint
        $response = $this->actingAs($user)->json('GET', '/api/product-merger/'.$brand->id, [
            'product_ids' => $products->pluck('id')->implode(','),
        ]);

        // Assert: `can_be_grouped` should be true
        $response->assertStatus(200)
                 ->assertJson([
                     'can_be_grouped' => false,
                 ]);
    }

    /**
     * Products can not be grouped when there are inconsistent option values for options.
     * This test would create duplicate variants.
     *
     * @return void
     */
    public function testCanNotGroupWithInconsistentOptionValues()
    {
        // Arrange: Create a user, brand, and products with consistent option values
        $user = User::factory()->create();
        $brand = Brand::factory()->create();
        $brand->users()->attach($user);

        $products = Product::factory()->count(3)->create(['brand_id' => $brand->id]);

        $colorGlobalField =  GlobalField::factory()->create(['brand_id' => $brand->id, 'label' => 'Color']);
        $sizeGlobalField =  GlobalField::factory()->create(['brand_id' => $brand->id, 'label' => 'Size']);

        $colorValues = ['Red','Red','Red','Red','Red','Red','Blue','Blue','Blue'];
        $sizeValues = ['Small','Medium','Large','Small','Medium','Large','Small','Medium','Large'];

        // Mock consistent option values for products
        for($i = 0; $i<min(count($colorValues),count($sizeValues)); $i++){
            $globalFieldsArray = [
                'option_1_global_field_id' => $colorGlobalField->id,
                'option_1_value' => $colorValues[$i],
                'option_2_global_field_id' => $sizeGlobalField->id,
                'option_2_value' => $sizeValues[$i],
                'option_3_global_field_id' => null,
                'option_3_value' => null,
            ];

            $productIndex = intval(ceil($i % count($products)));
            $productId = $products[$productIndex]->id;

            ProductVariant::factory()->create(array_merge(['product_id' => $productId],$globalFieldsArray));
        }

        // Act: Make a request to the endpoint
        $response = $this->actingAs($user)->json('GET', '/api/product-merger/'.$brand->id, [
            'product_ids' => $products->pluck('id')->implode(','),
        ]);

        // Assert: `can_be_grouped` should be true
        $response->assertStatus(200)
                 ->assertJson([
                     'can_be_grouped' => false,
                 ]);
    }


    /**
     * Products can not be groped when there are empty option values for options
     * that are mapped to a globalField.
     *
     * @return void
     */
    public function testCanNotGroupWithEmptyOptionValues()
    {
        // Arrange: Create a user, brand, and products with consistent option values
        $user = User::factory()->create();
        $brand = Brand::factory()->create();
        $brand->users()->attach($user);

        $products = Product::factory()->count(3)->create(['brand_id' => $brand->id]);

        $colorGlobalField =  GlobalField::factory()->create(['brand_id' => $brand->id, 'label' => 'Color']);
        $sizeGlobalField =  GlobalField::factory()->create(['brand_id' => $brand->id, 'label' => 'Size']);

        $colorValues = ['Red','Red','Red','Green','Green','Green','Blue','Blue','Blue'];
        $sizeValues = ['Small','Medium','Large','Small','Medium','Large',null,null,null];

        // Mock consistent option values for products
        for($i = 0; $i<min(count($colorValues),count($sizeValues)); $i++){
            $globalFieldsArray = [
                'option_1_global_field_id' => $colorGlobalField->id,
                'option_1_value' => $colorValues[$i],
                'option_2_global_field_id' => $sizeGlobalField->id,
                'option_2_value' => $sizeValues[$i],
                'option_3_global_field_id' => null,
                'option_3_value' => null,
            ];

            $productIndex = intval(ceil($i % count($products)));
            $productId = $products[$productIndex]->id;

            ProductVariant::factory()->create(array_merge(['product_id' => $productId],$globalFieldsArray));
        }

        // Act: Make a request to the endpoint
        $response = $this->actingAs($user)->json('GET', '/api/product-merger/'.$brand->id, [
            'product_ids' => $products->pluck('id')->implode(','),
        ]);

        // Assert: `can_be_grouped` should be true
        $response->assertStatus(200)
                 ->assertJson([
                     'can_be_grouped' => false,
                 ]);
    }

}
