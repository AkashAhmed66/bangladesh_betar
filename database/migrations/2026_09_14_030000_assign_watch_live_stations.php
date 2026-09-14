<?php

declare(strict_types=1);

use Database\Seeders\BroadcastChannelSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(BroadcastChannelSeeder::class)->run();
    }

    public function down(): void
    {
        // Seed corrections are retained as catalogue content.
    }
};
