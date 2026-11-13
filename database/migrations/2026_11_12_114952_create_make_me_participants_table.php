
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('makeme_participants', function (Blueprint $table) {
            $table->id();

            // Existing NYFEW account
            $table->foreignId('user_id')
                ->unique()
                ->constrained('users')
                ->restrictOnDelete();

            // Show details
            $table->string('participant_number')->unique()->nullable();
            $table->string('skill_category');

            // Access control
            $table->string('status')->default('active');

            // Administrative information
            $table->text('notes')->nullable();
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();

            $table->index(['status', 'skill_category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('makeme_participants');
    }
};
