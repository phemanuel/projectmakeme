
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('makeme_stock_movements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')
                ->constrained('makeme_products')
                ->restrictOnDelete();

            $table->unsignedBigInteger('performed_by')->nullable();

            $table->string('type', 30);
            $table->unsignedInteger('quantity_before');
            $table->unsignedInteger('quantity_change');
            $table->unsignedInteger('quantity_after');

            $table->string('reason', 255)->nullable();

            $table->timestamps();

            $table->index(['product_id', 'created_at']);
            $table->index('performed_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('makeme_stock_movements');
    }
};
