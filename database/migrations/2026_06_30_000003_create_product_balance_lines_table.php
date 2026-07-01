<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_balance_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->foreignId('product_balance_id')->constrained('product_balances')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->unsignedBigInteger('variation_id');
            $table->string('sku');
            $table->string('name');
            $table->decimal('quantity', 15, 3);
            $table->timestamps();

            $table->unique(['business_id', 'product_balance_id', 'product_id', 'variation_id'], 'balance_line_unique_item');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_balance_lines');
    }
};
