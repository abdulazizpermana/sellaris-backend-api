<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('business_profiles', 'profile_photo')) {
                $table->string('profile_photo')->nullable()->after('description');
            }
            if (!Schema::hasColumn('business_profiles', 'dark_mode')) {
                $table->boolean('dark_mode')->default(false)->after('profile_photo');
            }
            if (!Schema::hasColumn('business_profiles', 'language')) {
                $table->string('language')->default('id')->after('dark_mode');
            }
            if (!Schema::hasColumn('business_profiles', 'notification_enabled')) {
                $table->boolean('notification_enabled')->default(true)->after('language');
            }
            if (!Schema::hasColumn('business_profiles', 'ai_tone')) {
                $table->string('ai_tone')->nullable()->after('notification_enabled');
            }
            if (!Schema::hasColumn('business_profiles', 'default_target_market')) {
                $table->string('default_target_market')->nullable()->after('ai_tone');
            }
            if (!Schema::hasColumn('business_profiles', 'default_platform')) {
                $table->string('default_platform')->nullable()->after('default_target_market');
            }
        });

        Schema::table('ai_contents', function (Blueprint $table) {
            if (!Schema::hasColumn('ai_contents', 'user_id')) {
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade')->after('id');
            }
            if (!Schema::hasColumn('ai_contents', 'type')) {
                $table->string('type')->nullable()->after('user_id');
            }
            if (!Schema::hasColumn('ai_contents', 'generated_content')) {
                $table->text('generated_content')->nullable()->after('type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('business_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'profile_photo',
                'dark_mode',
                'language',
                'notification_enabled',
                'ai_tone',
                'default_target_market',
                'default_platform',
            ]);
        });

        Schema::table('ai_contents', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'type', 'generated_content']);
        });
    }
};
