<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_reactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('reactionable');
            $table->string('reaction', 10);
            $table->timestamps();

            $table->unique(
                ['user_id', 'reactionable_type', 'reactionable_id'],
                'content_reactions_unique'
            );
            $table->index(
                ['reactionable_type', 'reactionable_id', 'reaction'],
                'content_reactions_summary'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_reactions');
    }
};
