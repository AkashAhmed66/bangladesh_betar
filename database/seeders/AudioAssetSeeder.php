<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Programme;
use App\Models\Tag;
use Illuminate\Database\Seeder;
use Database\Seeders\Concerns\CreatesAudioAssets;

/**
 * M02/M04 — standalone archive assets: news, speeches, interviews,
 * drama, historical recordings and public-service announcements.
 */
class AudioAssetSeeder extends Seeder
{
    use CreatesAudioAssets;

    public function run(): void
    {
        $category = fn (string $slug) => Category::query()->where('slug', $slug)->value('id');

        $assets = [
            ['7th March Speech — Archival Restoration', 'speech', 'historical-recordings', ['Liberation War', '1971', 'Rare Recording'], true],
            ['Victory Day Special Broadcast 1971', 'historical', 'historical-recordings', ['Liberation War', 'Victory Day', 'Archive Gem'], true],
            ['Swadhin Bangla Betar Kendra — Charampatra Episode', 'historical', 'historical-recordings', ['Liberation War', '1971'], true],
            ['Ekushey February Dawn Programme 1985', 'historical', 'historical-recordings', ['Ekushey February', 'Golden Era'], false],
            ['Interview: Freedom Fighter Recollections', 'interview', 'interviews', ['Liberation War'], false],
            ['Interview: Folk Legend on Village Music', 'interview', 'interviews', ['Golden Era'], false],
            ['Morning News Bulletin — 16 December 2025', 'news', 'news', [], false],
            ['Evening News Bulletin — 26 March 2026', 'news', 'news', [], false],
            ['Radio Drama: Kobor', 'drama', 'drama', ['Golden Era'], false],
            ['Radio Drama: Ora Egaro Jon', 'drama', 'drama', ['Liberation War'], false],
            ['Cyclone Preparedness PSA', 'psa', 'public-service', [], false],
            ['Vaccination Awareness Announcement', 'psa', 'public-service', [], false],
            ['Pahela Baishakh Live Celebration 2025', 'programme', 'radio-programmes', ['Pahela Baishakh', 'Live Performance'], false],
            ['Eid Special Magazine Programme 2025', 'programme', 'radio-programmes', ['Eid Special'], false],
            ['Monsoon Melodies — Studio Session', 'programme', 'songs', ['Monsoon', 'Studio Session'], false],
        ];

        foreach ($assets as [$title, $type, $catSlug, $tagNames, $historic]) {
            $overrides = [
                'content_type' => $type,
                'category_id' => $category($catSlug),
                'description' => "$title — preserved in the Bangladesh Betar national audio archive.",
            ];

            if ($type === 'news' || $type === 'psa') {
                $overrides['is_public_service'] = true; // FR-SUB-11: always free, never ads
                $overrides['duration_seconds'] = random_int(120, 600);
            }

            if ($historic) {
                $overrides['first_broadcast_on'] = now()->subYears(random_int(40, 55))->toDateString();
                $overrides['source'] = 'digitization';
            }

            $asset = $this->makeAsset($title, $overrides);

            $tagIds = Tag::query()->whereIn('name', $tagNames)->pluck('id');
            $asset->tags()->sync($tagIds);
        }

        // A couple of unpublished/in-workflow assets so admin queues are not empty.
        $this->makeAsset('Rescued Reel: Unidentified Folk Session', [
            'content_type' => 'historical',
            'status' => 'pending_qc',
            'access_level' => 'internal',
            'rights_status' => 'pending',
            'published_at' => null,
            'play_count' => 0,
        ]);

        $this->makeAsset('New Drama Pilot: Shesh Bikeler Rod', [
            'content_type' => 'drama',
            'status' => 'in_review',
            'access_level' => 'internal',
            'rights_status' => 'pending',
            'published_at' => null,
            'play_count' => 0,
        ]);

        $this->makeAsset('Restricted: Court Proceedings Recording', [
            'content_type' => 'historical',
            'status' => 'approved',
            'access_level' => 'restricted',
            'rights_status' => 'restricted',
            'published_at' => null,
            'play_count' => 0,
        ]);

        // Link programme-typed assets to programmes where sensible.
        $programme = Programme::query()->where('slug', 'shonar-bangla-magazine')->first();
        if ($programme) {
            \App\Models\AudioAsset::query()
                ->where('content_type', 'programme')
                ->whereNull('programme_id')
                ->update(['programme_id' => $programme->id]);
        }

        $this->command?->info('Audio assets: '.count($assets).' published + 3 in-pipeline seeded');
    }
}
