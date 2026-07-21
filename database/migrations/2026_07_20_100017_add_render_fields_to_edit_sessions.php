<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('edit_sessions', function (Blueprint $table): void {
            // Server-side render pipeline state (M12 editing → ffmpeg render).
            $table->string('render_status', 15)->default('draft')->after('status');
            // draft | queued | rendering | done | failed
            $table->unsignedTinyInteger('progress')->default(0)->after('render_status');
            $table->text('error')->nullable()->after('progress');
            $table->text('ffmpeg_cmd')->nullable()->after('error'); // audit / debugging
        });
    }

    public function down(): void
    {
        Schema::table('edit_sessions', function (Blueprint $table): void {
            $table->dropColumn(['render_status', 'progress', 'error', 'ffmpeg_cmd']);
        });
    }
};
