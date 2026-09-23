<?php

declare(strict_types=1);

use Database\Seeders\WatchMetadataSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(WatchMetadataSeeder::class)->run();
    }

    public function down(): void
    {
        // Demo metadata is retained as catalogue content on rollback.
    }
};
