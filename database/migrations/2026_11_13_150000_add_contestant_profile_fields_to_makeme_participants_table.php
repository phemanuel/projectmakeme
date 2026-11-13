<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | ADD CONTESTANT PROFILE FIELDS
        |--------------------------------------------------------------------------
        |
        | Only missing columns will be added.
        | Existing participant data will remain unchanged.
        |
        */

        $fields = [
            // Business Information
            'business_name' => fn (Blueprint $table) =>
                $table->string('business_name', 180)->nullable(),

            'profession' => fn (Blueprint $table) =>
                $table->string('profession', 150)->nullable(),

            // Personal Information
            'age' => fn (Blueprint $table) =>
                $table->unsignedTinyInteger('age')->nullable(),

            'city' => fn (Blueprint $table) =>
                $table->string('city', 120)->nullable(),

            'state_of_origin' => fn (Blueprint $table) =>
                $table->string('state_of_origin', 120)->nullable(),

            'alternative_email' => fn (Blueprint $table) =>
                $table->string('alternative_email', 255)->nullable(),

            // Social Media Profiles
            'instagram_handle' => fn (Blueprint $table) =>
                $table->string('instagram_handle', 150)->nullable(),

            'tiktok_handle' => fn (Blueprint $table) =>
                $table->string('tiktok_handle', 150)->nullable(),

            'facebook_handle' => fn (Blueprint $table) =>
                $table->string('facebook_handle', 255)->nullable(),

            'x_handle' => fn (Blueprint $table) =>
                $table->string('x_handle', 255)->nullable(),

            'linkedin_handle' => fn (Blueprint $table) =>
                $table->string('linkedin_handle', 255)->nullable(),

            // Contestant Photograph
            'photo_path' => fn (Blueprint $table) =>
                $table->string('photo_path', 255)->nullable(),
        ];

        /*
        |--------------------------------------------------------------------------
        | CREATE ONLY MISSING COLUMNS
        |--------------------------------------------------------------------------
        */

        foreach ($fields as $column => $addColumn) {
            if (!Schema::hasColumn('makeme_participants', $column)) {
                Schema::table('makeme_participants', $addColumn);
            }
        }
    }

    public function down(): void
    {
        /*
        |--------------------------------------------------------------------------
        | NON-DESTRUCTIVE ROLLBACK
        |--------------------------------------------------------------------------
        |
        | Some columns may already have existed before this
        | migration, so we do not automatically delete them.
        |
        */
    }
};
