
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('makeme_wallets')) {
            throw new RuntimeException(
                'The makeme_wallets table does not exist.'
            );
        }

        if (!Schema::hasTable('makeme_participants')) {
            throw new RuntimeException(
                'The makeme_participants table does not exist.'
            );
        }

        // If already corrected, do not change the table.
        if (Schema::hasColumn('makeme_wallets', 'participant_id')) {
            return;
        }

        if (!Schema::hasColumn('makeme_wallets', 'user_id')) {
            throw new RuntimeException(
                'Expected user_id column was not found.'
            );
        }

        // Existing wallet records cannot be automatically
        // mapped from admin users to participants.
        // Stop instead of losing balances or history.
        $existingWallets = DB::table('makeme_wallets')->count();

        if ($existingWallets > 0) {
            throw new RuntimeException(
                "Found {$existingWallets} existing wallet records. " .
                'Map these wallets to participant IDs before ' .
                'running the corrective migration.'
            );
        }

        // Remove the incorrect user relationship.
        Schema::table('makeme_wallets', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('makeme_wallets', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });

        // Create the correct participant relationship.
        Schema::table('makeme_wallets', function (Blueprint $table) {
            $table->foreignId('participant_id')
                ->unique()
                ->constrained('makeme_participants')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('makeme_wallets', 'participant_id')) {
            return;
        }

        // Do not silently discard participant wallet data
        // during rollback.
        if (DB::table('makeme_wallets')->exists()) {
            throw new RuntimeException(
                'Cannot roll back participant wallets containing data.'
            );
        }

        Schema::table('makeme_wallets', function (Blueprint $table) {
            $table->dropForeign(['participant_id']);
        });

        Schema::table('makeme_wallets', function (Blueprint $table) {
            $table->dropColumn('participant_id');
        });

        Schema::table('makeme_wallets', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->unique()
                ->constrained('users')
                ->restrictOnDelete();
        });
    }
};
