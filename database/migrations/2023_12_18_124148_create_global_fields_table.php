<?php

use App\Models\Brand;
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
        Schema::create('global_fields', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignIdFor(Brand::class);
            $table->string('label');
            $table->string('shop_reference')->nullable();
            $table->jsonb('default_value')->nullable();
            $table->boolean('mutable')->default(false); // it set to true, this field has been created by the user and its values can be edited. If not, it is synced with the shop.
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('global_fields');
    }
};
