<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Order matters: identity → taxonomy → content → governance → business.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,   // M01 roles & granular permissions
            UserSeeder::class,             // staff + listener accounts (password: 123456)
            SettingSeeder::class,          // central app & theme configuration
            TaxonomySeeder::class,         // stations, departments, vocabularies
            ArtistSeeder::class,           // person/entity records
            ProgrammeSeeder::class,        // programme hierarchy + seasons
            AudioAssetSeeder::class,       // archive assets + version families
            MusicSeeder::class,            // albums, songs, artist links
            EpisodeStorySeeder::class,     // Bhoot FM episodes, stories, submissions
            PodcastSeeder::class,          // podcast channels & episodes
            WorkflowSeeder::class,         // approval workflows + live instances
            RightsSeeder::class,           // rights holders & records
            QcAndDigitizationSeeder::class,// media items + QC reports
            TranscriptAiSeeder::class,     // transcripts + AI suggestions
            AudioMarkerSeeder::class,      // studio content markers & chapters
            PlanSeeder::class,             // Free/Premium plans + promo codes
            SubscriptionSeeder::class,     // subscriptions + payments
            CurationSeeder::class,         // home sections, banners, collections
            EngagementSeeder::class,       // favourites, follows, comments, ratings
            AdvertisementSeeder::class,    // advertisers, campaigns, impressions
            AnalyticsSeeder::class,        // play events + daily stats/heat maps
            SystemSeeder::class,           // backups + integrity checks
        ]);
    }
}
