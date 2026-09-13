<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\NewsArticle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Replaces the repeated legacy News demo artwork with a modern editorial
 * image set. Paths uploaded through the admin portal are intentionally left
 * untouched; only seeded portal/demo artwork (or empty lead images) is
 * eligible for replacement.
 */
final class NewsArtworkExpansionSeeder extends Seeder
{
    private const DIRECTORY = 'news-artwork';

    private const ARTWORK = [
        'train.webp',
        'agriculture.webp',
        'dhaka-mobility.webp',
        'education.webp',
        'health.webp',
        'climate.webp',
        'technology.webp',
        'business.webp',
        'market.webp',
        'world.webp',
        'sports.webp',
        'culture.webp',
        'environment.webp',
    ];

    /** @var array<string, string> */
    private const CATEGORY_ARTWORK = [
        'bangladesh-national' => 'train.webp',
        'bangladesh-dhaka' => 'dhaka-mobility.webp',
        'bangladesh-chattogram' => 'business.webp',
        'bangladesh-sylhet' => 'environment.webp',
        'politics-government' => 'technology.webp',
        'politics-parliament' => 'world.webp',
        'politics-elections' => 'technology.webp',
        'politics-parties' => 'world.webp',
        'world-asia' => 'world.webp',
        'world-europe' => 'world.webp',
        'world-americas' => 'world.webp',
        'world-middle-east' => 'world.webp',
        'business-economy' => 'business.webp',
        'business-banking' => 'technology.webp',
        'business-markets' => 'market.webp',
        'business-industry' => 'business.webp',
        'sports-cricket' => 'sports.webp',
        'sports-football' => 'sports.webp',
        'sports-local' => 'sports.webp',
        'sports-international' => 'sports.webp',
        'entertainment-television' => 'culture.webp',
        'entertainment-ott' => 'culture.webp',
        'entertainment-films' => 'culture.webp',
        'entertainment-music' => 'culture.webp',
        'entertainment-drama' => 'culture.webp',
        'entertainment-interviews' => 'culture.webp',
        'lifestyle-health' => 'health.webp',
        'lifestyle-education' => 'education.webp',
        'lifestyle-travel' => 'environment.webp',
        'lifestyle-food' => 'market.webp',
        'lifestyle-fashion' => 'business.webp',
        'video-news' => 'train.webp',
        'video-interviews' => 'culture.webp',
        'video-explainers' => 'technology.webp',
        'video-documentaries' => 'environment.webp',
        'economy' => 'business.webp',
        'climate' => 'climate.webp',
        'culture' => 'culture.webp',
        'science' => 'technology.webp',
        'environment' => 'environment.webp',
        'media' => 'technology.webp',
    ];

    /** @var array<string, string> */
    private const KEYWORD_ARTWORK = [
        'dhaka|metro|transit|commut' => 'dhaka-mobility.webp',
        'train|rail' => 'train.webp',
        'river|delta|coast|haor|mangrove|wetland|flood|climate|storm' => 'climate.webp',
        'farm|agri|crop|harvest|rice|tea|jute|farmer|irrigation' => 'agriculture.webp',
        'school|student|university|education|research|scholar|campus' => 'education.webp',
        'health|clinic|hospital|medical|mental|medicine|wellbeing' => 'health.webp',
        'solar|renewable|energy|digital|ai|smart|cyber|software|robot|technology' => 'technology.webp',
        'market|bazaar|retail|shop' => 'market.webp',
        'market|bank|remittance|export|trade|econom|industry|business|startup' => 'business.webp',
        'cricket|football|badminton|archery|sport|champion|stadium' => 'sports.webp',
        'film|music|radio|drama|culture|heritage|craft|festival|fashion|food' => 'culture.webp',
    ];

    public function run(): void
    {
        $this->installArtworkFiles();

        $updated = 0;
        NewsArticle::query()
            ->with('portalCategory')
            ->orderBy('id')
            ->get()
            ->each(function (NewsArticle $article, int $index) use (&$updated): void {
                if (! $this->shouldReplace($article->image_path)) {
                    return;
                }

                $article->image_path = $this->path($this->artworkFor($article, $index));
                $article->saveQuietly();
                $updated++;
            });

        $this->command?->info("Modern News artwork installed ({$updated} lead images updated).");
    }

    private function installArtworkFiles(): void
    {
        $sourceDirectory = base_path('database/seeders/assets/'.self::DIRECTORY);

        if (! File::isDirectory($sourceDirectory)) {
            throw new RuntimeException("News artwork directory is missing: {$sourceDirectory}");
        }

        $disk = Storage::disk('public');
        foreach (self::ARTWORK as $file) {
            $source = $sourceDirectory.DIRECTORY_SEPARATOR.$file;

            if (! File::isFile($source) || File::size($source) === 0) {
                throw new RuntimeException("News artwork source is missing or empty: {$source}");
            }

            $disk->put($this->path($file), File::get($source), 'public');
        }
    }

    private function artworkFor(NewsArticle $article, int $index): string
    {
        $text = mb_strtolower(implode(' ', array_filter([
            (string) $article->slug,
            (string) $article->title,
            (string) $article->summary,
        ])));

        $category = $article->portalCategory?->slug ?: (string) $article->category;

        if (str_starts_with($category, 'world-')) {
            return 'world.webp';
        }

        if ($category === 'business-markets') {
            return 'market.webp';
        }

        foreach (self::KEYWORD_ARTWORK as $keywords => $file) {
            if (preg_match('/(?:'.$keywords.')/u', $text) === 1) {
                return $file;
            }
        }

        return self::CATEGORY_ARTWORK[$category]
            ?? self::ARTWORK[$index % count(self::ARTWORK)];
    }

    private function shouldReplace(?string $path): bool
    {
        $normalized = str_replace('\\', '/', (string) $path);

        return blank($path)
            || str_starts_with($normalized, 'portal/demo/')
            || (str_starts_with($normalized, self::DIRECTORY.'/') && str_ends_with($normalized, '.png'));
    }

    private function path(string $file): string
    {
        return self::DIRECTORY.'/'.$file;
    }
}
