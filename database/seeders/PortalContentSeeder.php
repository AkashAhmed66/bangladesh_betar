<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\NewsArticle;
use App\Models\NewsCategory;
use App\Models\User;
use App\Models\WatchCategory;
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

        $newsBn = [
            'rail-link-connects-river-regions' => ['নতুন রেলপথে নদীবেষ্টিত অঞ্চলের সঙ্গে রাজধানীর যোগাযোগ সহজ', 'সম্প্রসারিত রুটটি যাত্রার সময় কমাবে, আঞ্চলিক বাণিজ্য বাড়াবে এবং আরও যাত্রীকে নির্ভরযোগ্য গণপরিবহনের আওতায় আনবে।', ['দেশের ব্যস্ততম নদী করিডরের একটি দিয়ে নতুন সম্প্রসারিত রেল যোগাযোগে যাত্রী পরিবহন শুরু হয়েছে। এতে আঞ্চলিক শহরগুলোর সঙ্গে ঢাকার দ্রুত যোগাযোগ তৈরি হয়েছে।', 'পরিবহন পরিকল্পনাবিদরা বলছেন, সড়কের চাপ কমানোর পাশাপাশি শিক্ষা, স্বাস্থ্যসেবা ও বাজারে যাতায়াত সহজ করতেই এই সেবা চালু হয়েছে।', 'স্থানীয় ব্যবসায়ীরা আশা করছেন, নির্ভরযোগ্য যাত্রাসময় জেলার মধ্যে কৃষিপণ্য ও ক্ষুদ্র শিল্পপণ্য পরিবহন সহজ করবে।']],
            'aman-harvest-reaches-local-markets' => ['আমনের ভালো ফলন স্থানীয় বাজারে পৌঁছাতে শুরু করেছে', 'সেচ ব্যবস্থাপনার সুফলে উত্তরের কয়েকটি জেলার কৃষকেরা ভালো ফলনের কথা জানিয়েছেন।', ['উত্তরের জেলাগুলোতে আমন ধান কাটা শেষ হওয়ার সঙ্গে সঙ্গে নতুন চাল আঞ্চলিক বাজারে আসছে।', 'কৃষি কর্মকর্তারা জানান, স্থানীয় সেচ পরিকল্পনা ও সময়মতো পরামর্শ পরিবর্তনশীল আবহাওয়ায় ফসল রক্ষায় সহায়তা করেছে।', 'খামার থেকে মিল ও খুচরা বাজারে ধান পৌঁছানোর সময় পরিবহন ও সংরক্ষণ ব্যয় পর্যবেক্ষণ করা হচ্ছে।']],
            'coastal-volunteers-complete-shelter-drill' => ['উপকূলীয় স্বেচ্ছাসেবকদের আশ্রয়কেন্দ্র মহড়া সম্পন্ন', 'দুর্যোগ মৌসুমের আগে প্রাথমিক চিকিৎসা, সরিয়ে নেওয়ার পথ ও যোগাযোগব্যবস্থা পরীক্ষা করেছে স্থানীয় দলগুলো।', ['উপকূলীয় স্বেচ্ছাসেবকেরা ঘূর্ণিঝড় আশ্রয়কেন্দ্রে পৌঁছানো ও পরিবারের সঙ্গে যোগাযোগের প্রস্তুতি নিয়ে সমন্বিত মহড়া শেষ করেছেন।', 'মহড়ায় বয়স্ক, শিশু ও প্রতিবন্ধী মানুষকে সরিয়ে নেওয়ার অনুশীলন এবং জরুরি সরঞ্জাম পরীক্ষা করা হয়।', 'ভারী বৃষ্টিতে যেসব প্রত্যন্ত এলাকায় চলাচল কঠিন হয়, সেখানেও এই মহড়া আয়োজন করা হবে।']],
            'student-robotics-team-heads-to-regional-final' => ['শিক্ষার্থী রোবটিক্স দল আঞ্চলিক উদ্ভাবন প্রতিযোগিতার ফাইনালে', 'স্থানীয় উপকরণ ও উন্মুক্ত প্রযুক্তিতে তৈরি স্বল্পমূল্যের পরিদর্শন রোভার দলটিকে ফাইনালে নিয়েছে।', ['একটি শিক্ষার্থী প্রকৌশল দল সংকীর্ণ স্থান পরিদর্শনের জন্য ছোট রোভার তৈরি করে আঞ্চলিক উদ্ভাবন প্রতিযোগিতার ফাইনালে উঠেছে।', 'স্বল্পমূল্যের সেন্সর ও স্থানীয় যন্ত্রাংশে তৈরি হওয়ায় বিশেষ সরঞ্জাম ছাড়াই রোভারটি মেরামত ও পরিবর্তন করা যায়।', 'এই উদ্যোগ আরও স্কুল ও বিশ্ববিদ্যালয়কে ব্যবহারিক রোবটিক্স ক্লাব গড়তে উৎসাহিত করবে বলে দলটি আশা করছে।']],
            'community-radio-expands-agriculture-bulletins' => ['কমিউনিটি রেডিওর দৈনিক কৃষি বুলেটিন সম্প্রসারণ', 'নতুন আঞ্চলিক পর্বে বাজারদর, আবহাওয়া ও কৃষি সম্প্রসারণ কর্মকর্তাদের পরামর্শ প্রচার করা হবে।', ['কৃষকদের আরও নিয়মিত আবহাওয়া, ফসল ও বাজারের তথ্য দিতে আঞ্চলিক রেডিও বুলেটিন বাড়ানো হচ্ছে।', 'স্থানীয় শ্রোতাদের সঙ্গে আলোচনা করে সম্প্রচারের সময় নির্ধারণ করা হবে এবং দিনের কাজে বাইরে থাকা মানুষের জন্য তা পুনঃপ্রচার করা হবে।', 'শ্রোতারা ভবিষ্যৎ পর্বের জন্য প্রশ্নও পাঠাতে পারবেন বলে প্রযোজকেরা জানিয়েছেন।']],
            'river-research-maps-seasonal-change' => ['মৌসুমি নদীর পরিবর্তন মানচিত্রে তুলে ধরছেন গবেষকেরা', 'উপগ্রহ পর্যবেক্ষণ ও নদীপারের মানুষের তথ্য মিলিয়ে একটি নতুন উন্মুক্ত উপাত্তভান্ডার তৈরি হয়েছে।', ['গবেষকেরা মৌসুমভেদে নদীর গতিপথ ও আশপাশের বসতি কীভাবে বদলায় তার উন্মুক্ত উপাত্ত প্রকাশ করেছেন।', 'প্রকল্পটিতে উপগ্রহচিত্রের সঙ্গে স্কুল ও স্থানীয় সংগঠনের পর্যবেক্ষণ যুক্ত করা হয়েছে।', 'এই তথ্য ভাঙনপ্রবণ এলাকায় নিরাপদ অবকাঠামো ও উন্নত পরিকল্পনায় সহায়তা করবে বলে আশা করা হচ্ছে।']],
        ];

        foreach ($news as $position => [$slug, $title, $summary, $category, $image, $readTime, $minutesAgo, $body]) {
            [$titleBn, $summaryBn, $bodyBn] = $newsBn[$slug];
            $categoryId = NewsCategory::query()->where('name', $category)->value('id');
            NewsArticle::query()->updateOrCreate(['slug' => $slug], [
                'created_by' => $creatorId,
                'news_category_id' => $categoryId,
                'title' => $title,
                'title_bn' => $titleBn,
                'summary' => $summary,
                'summary_bn' => $summaryBn,
                'category' => $category,
                'body' => $body,
                'body_bn' => $bodyBn,
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

        $showsBn = [
            'the-last-transmission' => ['শেষ সম্প্রচার', 'নতুন মৌলিক নাটক', '১৯৭১ সালের শেষ সপ্তাহে একটি বেতারকেন্দ্রে এক তরুণ সম্প্রচারক আবিষ্কার করে—সঠিকভাবে বেছে নেওয়া একটি বার্তা ভয়কেও অতিক্রম করতে পারে।'],
            'rivers-that-remember' => ['স্মৃতিবাহী নদী', 'প্রামাণ্যচিত্র সিরিজ', 'বাংলাদেশের পরিবর্তনশীল জলপথ ঘিরে নৌকা সম্প্রদায়ের গল্প, জীবিকা ও গানের সঙ্গে ভ্রমণ করুন।'],
            'songs-of-the-courtyard' => ['উঠানের গান', 'সরাসরি পরিবেশনা', 'দেশের বিভিন্ন প্রান্তের শিল্পীদের লোক ও শাস্ত্রীয় ঐতিহ্যের অন্তরঙ্গ সন্ধ্যা।'],
            'little-field-guides' => ['ছোট্ট প্রকৃতি নির্দেশিকা', 'কিশোর অভিযাত্রীদের নতুন আয়োজন', 'কৌতূহলী শিশুরা শ্রেণিকক্ষের বাইরের গাছপালা, পোকামাকড় ও বন্যপ্রাণী আবিষ্কার করে।'],
            'voices-of-betar' => ['বেতারের কণ্ঠ', 'আর্কাইভ প্রামাণ্যচিত্র', 'উপস্থাপক, প্রকৌশলী ও শিল্পীরা জনজীবনের অংশ হয়ে ওঠা বেতারের স্মরণীয় মুহূর্তগুলো ফিরে দেখেন।'],
            'monsoon-kitchen' => ['বর্ষার রান্নাঘর', 'খাবার ও সংস্কৃতি', 'ঘরের রাঁধুনিরা মৌসুমি রেসিপি ও প্রজন্ম ধরে বহমান পারিবারিক ইতিহাস ভাগ করে নেন।'],
            'tomorrows-builders' => ['আগামীর নির্মাতা', 'তথ্যভিত্তিক সিরিজ', 'তরুণ উদ্ভাবকেরা শ্রেণিকক্ষের ধারণাকে সমাজের ব্যবহারিক সরঞ্জামে রূপ দেন।'],
            'ready-together' => ['একসঙ্গে প্রস্তুত', 'মানুষের গল্প', 'দুর্যোগের আগে স্থানীয় সক্ষমতা বাড়ানো স্বেচ্ছাসেবকদের সঙ্গে পরিচিত হোন।'],
        ];
        $episodeBn = [
            'The Signal' => 'সংকেত', 'Between Frequencies' => 'তরঙ্গের মাঝে', 'The Last Transmission' => 'শেষ সম্প্রচার',
            'Morning Tide' => 'সকালের জোয়ার', 'Moving Banks' => 'বদলে যাওয়া তীর', 'Songs Downstream' => 'ভাটির গান',
            'Folk Roads' => 'লোকগানের পথ', 'Poetry in Raga' => 'রাগে কবিতা', 'Life on a Lily Pad' => 'শাপলা পাতার জীবন',
            'The Busy Banyan' => 'ব্যস্ত বটগাছ', 'After the Rain' => 'বৃষ্টির পরে', 'Behind the Microphone' => 'মাইক্রোফোনের পেছনে',
            'First Rain' => 'প্রথম বৃষ্টি', 'Small Machines, Big Ideas' => 'ছোট যন্ত্র, বড় ভাবনা', 'The Shelter Team' => 'আশ্রয়কেন্দ্র দল',
        ];

        foreach ($shows as $position => [$slug, $title, $eyebrow, $description, $category, $image, $year, $rating, $episodes]) {
            [$titleBn, $eyebrowBn, $descriptionBn] = $showsBn[$slug];
            $categoryId = WatchCategory::query()->where('name', $category)->value('id');
            $show = WatchShow::query()->updateOrCreate(['slug' => $slug], [
                'created_by' => $creatorId,
                'watch_category_id' => $categoryId,
                'title' => $title,
                'title_bn' => $titleBn,
                'eyebrow' => $eyebrow,
                'eyebrow_bn' => $eyebrowBn,
                'description' => $description,
                'description_bn' => $descriptionBn,
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
                    'title_bn' => $episodeBn[$episodeTitle] ?? null,
                    'description' => $episodeDescription,
                    'description_bn' => 'এই পর্বে '.$episodeBn[$episodeTitle].' বিষয়টি তুলে ধরা হয়েছে।',
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
