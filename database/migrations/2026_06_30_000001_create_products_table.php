<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->string('name');
            $table->string('sku');
            $table->unsignedBigInteger('variation_id');
            $table->timestamps();

            $table->unique(['business_id', 'sku', 'variation_id']);
            $table->index(['business_id', 'id', 'variation_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
