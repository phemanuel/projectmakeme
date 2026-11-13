
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('makeme_participants', function (Blueprint $table) {

            // Remove relationship with NYFEW users
            $table->dropForeign(['user_id']);
            $table->dropUnique(['user_id']);
            $table->dropColumn('user_id');

            // Participant identification
            $table->string('first_name')->nullable();
            $table->string('surname')->nullable();
            $table->string('other_name')->nullable();

            // Contact information
            $table->string('email')->nullable()->unique();
            $table->string('mobile_no')->nullable();
            $table->string('mobile_no1')->nullable();
            $table->text('address')->nullable();

            // Personal details
            $table->string('gender')->nullable();
            $table->date('dob')->nullable();
            $table->string('occupation')->nullable();

            // Skill information
            // Existing skill_category column remains unchanged

            // Participant profile
            $table->string('image')->nullable();

            // Social media
            $table->string('instagram')->nullable();
            $table->string('facebook')->nullable();
            $table->string('tiktok')->nullable();
            $table->string('twitter')->nullable();

            // Authentication
            $table->string('password')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();

            // Account activity
            $table->timestamp('last_login_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('makeme_participants', function (Blueprint $table) {

            $table->dropColumn([
                'first_name',
                'surname',
                'other_name',
                'email',
                'mobile_no',
                'mobile_no1',
                'address',
                'gender',
                'dob',
                'occupation',
                'image',
                'instagram',
                'facebook',
                'tiktok',
                'twitter',
                'password',
                'email_verified_at',
                'remember_token',
                'last_login_at',
            ]);

            $table->foreignId('user_id')
                ->unique()
                ->constrained('users')
                ->restrictOnDelete();
        });
    }
};
