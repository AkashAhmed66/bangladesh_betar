<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\NewsArticle;
use App\Models\User;
use App\Models\WatchShow;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class PortalContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->installImages();
        $creatorId = User::query()->where('email', 'admin@betar.gov.bd')->value('id');

        $news = [
            ['rail-link-connects-river-regions', 'New rail link brings river communities closer to the capital', 'The expanded route is expected to shorten journeys, improve regional trade and give more passengers access to reliable public transport.', 'Bangladesh', 'news-hero.png', 4, 12, [
                "A newly expanded rail connection has begun carrying passengers across one of the country's busiest river corridors, creating a faster link between regional towns and Dhaka.",
                'Transport planners say the service is designed to reduce road pressure while making education, healthcare and markets easier to reach. More services are expected to be added after the first operating review.',
                'Local businesses welcomed the opening and said predictable journey times could make it easier to move fresh produce and small manufactured goods between districts.',
            ]],
            ['aman-harvest-reaches-local-markets', 'Strong Aman harvest begins reaching local markets', 'Farmers across several northern districts report healthy yields after a season of careful water management.', 'Economy', 'news-rice.png', 3, 35, [
                'Freshly harvested Aman rice is arriving at regional markets as growers complete work across the northern districts.',
                'Agriculture officers said local irrigation planning and timely field advice helped many farmers protect their crops through changing weather conditions.',
                'Market observers are monitoring transport and storage costs as the harvest moves from farms to mills and retail centres.',
            ]],
            ['coastal-volunteers-complete-shelter-drill', 'Coastal volunteers complete early-season shelter drill', 'Community teams checked first-aid supplies, evacuation routes and communications before the next period of severe weather.', 'Climate', 'news-coast.png', 5, 48, [
                'Volunteer groups in coastal communities have completed a coordinated readiness exercise focused on cyclone shelter access and household communication.',
                'Teams inspected emergency supplies and practised supporting older residents, children and people with disabilities during an evacuation.',
                'Organisers said the exercise will be repeated in remote areas where travel becomes difficult during heavy rain.',
            ]],
            ['student-robotics-team-heads-to-regional-final', 'Student robotics team heads to regional innovation final', 'The university team built a low-cost inspection rover using locally available components and open-source tools.', 'Science', 'news-tech.png', 4, 60, [
                'A student engineering team has qualified for a regional innovation final with a compact rover designed to inspect difficult indoor spaces.',
                'The prototype combines affordable sensors with locally sourced parts, allowing the students to repair and adapt it without specialist equipment.',
                'The team hopes the project will encourage more schools and universities to create practical robotics clubs.',
            ]],
            ['community-radio-expands-agriculture-bulletins', 'Community radio expands daily agriculture bulletins', 'New regional segments will share market prices, weather guidance and advice from agricultural extension officers.', 'Media', 'news-rice.png', 3, 120, [
                'Regional radio bulletins are expanding to provide farmers with more frequent weather, crop and market information.',
                'The short programmes will be broadcast at times chosen with local listeners and repeated for people working away from home during the day.',
                'Producers said listeners will also be able to submit questions for future episodes.',
            ]],
            ['river-research-maps-seasonal-change', 'Researchers map how seasonal rivers are changing', 'A new public dataset combines satellite observations with reports from people living beside major waterways.', 'Environment', 'watch-river.png', 6, 180, [
                'Researchers have released an open dataset showing how river channels and nearby settlements change across the seasons.',
                'The project combines satellite imagery with observations contributed by schools and community groups.',
                'Planners hope the information can support safer local infrastructure and better decisions about erosion-prone areas.',
            ]],
        ];

        foreach ($news as $position => [$slug, $title, $summary, $category, $image, $readTime, $minutesAgo, $body]) {
            NewsArticle::query()->updateOrCreate(['slug' => $slug], [
                'created_by' => $creatorId,
                'title' => $title,
                'summary' => $summary,
                'category' => $category,
                'body' => $body,
                'image_path' => 'portal/demo/'.$image,
                'read_time_minutes' => $readTime,
                'position' => $position,
                'is_featured' => $position === 0,
                'is_published' => true,
                'published_at' => now()->subMinutes($minutesAgo),
            ]);
        }

        $shows = [
            ['the-last-transmission', 'The Last Transmission', 'New original drama', 'In a radio studio during the final weeks of 1971, a young broadcaster discovers that one carefully chosen message can travel farther than fear.', 'Drama', 'watch-hero.png', 2026, 'PG', [
                ['The Signal', 46, 'Maya arrives for a night shift that will change the course of the station.'],
                ['Between Frequencies', 44, 'A hidden message forces the team to decide who they can trust.'],
                ['The Last Transmission', 52, 'The studio prepares one final broadcast as dawn approaches.'],
            ]],
            ['rivers-that-remember', 'Rivers That Remember', 'Documentary series', 'Travel with the boat communities whose stories, livelihoods and songs follow the changing waterways of Bangladesh.', 'Documentary', 'watch-river.png', 2026, 'G', [
                ['Morning Tide', 28, 'A fishing family reads the river before sunrise.'],
                ['Moving Banks', 31, 'Communities adapt as familiar channels shift.'],
                ['Songs Downstream', 29, 'Music carries memory from one generation to the next.'],
            ]],
            ['songs-of-the-courtyard', 'Songs of the Courtyard', 'Live performance', 'An intimate evening of folk and classical traditions, recorded with artists from across the country.', 'Culture', 'watch-music.png', 2026, 'G', [
                ['Folk Roads', 42, 'Songs shaped by travel, rivers and village life.'],
                ['Poetry in Raga', 39, 'Voices and instruments meet in a new arrangement.'],
            ]],
            ['little-field-guides', 'Little Field Guides', 'New for young explorers', 'Curious children discover the plants, insects and wildlife living just beyond their classroom.', 'Kids', 'watch-kids.png', 2026, 'G', [
                ['Life on a Lily Pad', 14, 'Meet the tiny neighbours of a village pond.'],
                ['The Busy Banyan', 13, 'A single tree becomes a home for many species.'],
                ['After the Rain', 15, 'Young explorers follow the clues left by monsoon weather.'],
            ]],
            ['voices-of-betar', 'Voices of Betar', 'Archive documentary', 'Presenters, engineers and performers revisit the moments that made public radio part of everyday life.', 'Documentary', 'watch-hero.png', 2025, 'G', [
                ['Behind the Microphone', 48, 'The people who gave a national service its voice.'],
            ]],
            ['monsoon-kitchen', 'The Monsoon Kitchen', 'Food and culture', 'Home cooks share seasonal recipes and the family histories that travel with them.', 'Culture', 'news-rice.png', 2026, 'G', [
                ['First Rain', 24, 'A menu built around the arrival of the monsoon.'],
            ]],
            ['tomorrows-builders', "Tomorrow's Builders", 'Factual series', 'Young inventors turn classroom ideas into practical tools for their communities.', 'Documentary', 'news-tech.png', 2026, 'G', [
                ['Small Machines, Big Ideas', 26, 'A robotics club prepares for its first national showcase.'],
            ]],
            ['ready-together', 'Ready Together', 'Community stories', 'Meet the volunteers strengthening local resilience before severe weather arrives.', 'Documentary', 'news-coast.png', 2026, 'G', [
                ['The Shelter Team', 27, 'Neighbours turn preparedness into a shared routine.'],
            ]],
        ];

        foreach ($shows as $position => [$slug, $title, $eyebrow, $description, $category, $image, $year, $rating, $episodes]) {
            $show = WatchShow::query()->updateOrCreate(['slug' => $slug], [
                'created_by' => $creatorId,
                'title' => $title,
                'eyebrow' => $eyebrow,
                'description' => $description,
                'category' => $category,
                'image_path' => 'portal/demo/'.$image,
                'year' => $year,
                'rating' => $rating,
                'position' => $position,
                'is_featured' => $position === 0,
                'is_published' => true,
                'published_at' => now()->subDays($position + 1),
            ]);

            foreach ($episodes as $episodePosition => [$episodeTitle, $duration, $episodeDescription]) {
                $show->episodes()->updateOrCreate(['title' => $episodeTitle], [
                    'description' => $episodeDescription,
                    'duration_minutes' => $duration,
                    'position' => $episodePosition + 1,
                    'is_published' => true,
                ]);
            }
        }

        $this->command?->info('News and Watch portal demo content seeded.');
    }

    private function installImages(): void
    {
        $disk = Storage::disk('public');
        $sourceDirectory = base_path('database/seeders/assets/portal');

        foreach (File::files($sourceDirectory) as $file) {
            $contents = File::get($file->getPathname());
            if ($contents === '') {
                throw new RuntimeException('Portal demo image is empty: '.$file->getFilename());
            }

            $disk->put('portal/demo/'.$file->getFilename(), $contents, 'public');
        }
    }
}
