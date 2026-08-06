<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audio-book text editing: once a creator edits the extracted text, the
 * edited text becomes authoritative — regeneration must NOT re-extract from
 * the original PDF and overwrite the manual corrections. Additive + guarded
 * (migrations run on every boot).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('audio_books', 'text_edited')) {
            Schema::table('audio_books', function (Blueprint $table): void {
                $table->boolean('text_edited')->default(false)->after('used_ocr');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('audio_books', 'text_edited')) {
            Schema::table('audio_books', function (Blueprint $table): void {
                $table->dropColumn('text_edited');
            });
        }
    }
};
