<?php

use App\Models\GlobalField;
use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            // The product_id is set to the parent product if the product is merged.
            $table->foreignIdFor(Product::class,'product_id');
            // The default_product_id is set to the "actual" product if the product is merged. If the product is not merged, it is set to null.
            $table->foreignIdFor(Product::class,'default_product_id')->nullable();

            $table->foreignIdFor(GlobalField::class,'option_1_global_field_id')->nullable();
            $table->string('option_1_value')->nullable();
            $table->foreignIdFor(GlobalField::class,'option_2_global_field_id')->nullable();
            $table->string('option_2_value')->nullable();
            $table->foreignIdFor(GlobalField::class,'option_3_global_field_id')->nullable();
            $table->string('option_3_value')->nullable();
            $table->string('sku');
            $table->string('gtin')->nullable();
            $table->integer('stock_quantity')->default(0);
            $table->integer('price')->default(0);
            $table->integer('compare_at_price')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_variants');
    }
};
