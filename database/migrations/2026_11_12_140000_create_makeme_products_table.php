<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('makeme_products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sku', 80)->unique();
            $table->text('description')->nullable();
            $table->string('category', 60);
            $table->unsignedBigInteger('credit_price');
            $table->unsignedInteger('stock_quantity')->default(0);
            $table->unsignedSmallInteger('max_per_participant')->nullable();
            $table->string('image_path')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
            $table->index(['category', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('makeme_products');
    }
};
