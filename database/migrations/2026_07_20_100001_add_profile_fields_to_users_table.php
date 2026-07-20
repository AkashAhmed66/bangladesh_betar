<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('user_type', 20)->default('staff')->after('id')->index(); // staff | listener
            $table->string('phone', 30)->nullable()->after('email');
            $table->string('avatar_path')->nullable()->after('password');
            $table->string('locale', 5)->default('en');
            $table->string('status', 20)->default('active')->index(); // active | inactive | banned | muted
            $table->text('bio')->nullable();
            $table->json('preferences')->nullable(); // notification prefs, personalization opt-out, theme
            $table->timestamp('last_login_at')->nullable();
            $table->timestamp('tos_accepted_at')->nullable();
            $table->string('tos_version', 20)->nullable();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'user_type', 'phone', 'avatar_path', 'locale', 'status', 'bio',
                'preferences', 'last_login_at', 'tos_accepted_at', 'tos_version', 'deleted_at',
            ]);
        });
    }
};
