<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('makeme_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('makeme_orders')->restrictOnDelete();
            $table->foreignId('product_id')->constrained('makeme_products')->restrictOnDelete();
            $table->string('product_name');
            $table->string('sku')->nullable();
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('unit_price');
            $table->unsignedBigInteger('line_total');
            $table->timestamps();
            $table->unique(['order_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('makeme_order_items');
    }
};
