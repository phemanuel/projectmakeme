<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('makeme_credit_transfers', function (Blueprint $table) {
            $table->id();

            $table->uuid('request_key')->unique();
            $table->string('reference', 50)->unique();

            $table->foreignId('sender_id')
                ->constrained('makeme_participants');

            $table->foreignId('recipient_id')
                ->constrained('makeme_participants');

            $table->unsignedBigInteger('amount');

            $table->unsignedBigInteger('sender_balance_before');
            $table->unsignedBigInteger('sender_balance_after');

            $table->unsignedBigInteger('recipient_balance_before');
            $table->unsignedBigInteger('recipient_balance_after');

            $table->timestamps();

            $table->index(['sender_id', 'created_at']);
            $table->index(['recipient_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('makeme_credit_transfers');
    }
};
