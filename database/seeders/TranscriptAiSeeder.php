<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AiSuggestion;
use App\Models\AudioAsset;
use App\Models\Language;
use App\Models\Transcript;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * M16 — AI transcripts (draft & verified) and metadata suggestions.
 */
class TranscriptAiSeeder extends Seeder
{
    public function run(): void
    {
        $bn = Language::query()->where('code', 'bn')->value('id');
        $archivist = User::query()->where('email', 'archivist@betar.gov.bd')->first();

        $speechAssets = AudioAsset::query()
            ->whereIn('content_type', ['speech', 'interview', 'historical', 'podcast'])
            ->take(8)
            ->get();

        foreach ($speechAssets as $index => $asset) {
            $verified = $index % 2 === 0;

            Transcript::query()->firstOrCreate(
                ['audio_asset_id' => $asset->id, 'transcript_type' => 'transcript'],
                [
                    'language_id' => $bn,
                    'lines' => [
                        ['start' => 0, 'end' => 14, 'speaker' => 'Presenter', 'text' => 'বাংলাদেশ বেতার থেকে সম্প্রচারিত এই অনুষ্ঠানে আপনাদের স্বাগতম।'],
                        ['start' => 14, 'end' => 42, 'speaker' => 'Speaker 1', 'text' => 'আজ আমরা স্মরণ করছি আমাদের ইতিহাসের এক গৌরবময় অধ্যায়।'],
                        ['start' => 42, 'end' => 75, 'speaker' => 'Speaker 1', 'text' => 'এই রেকর্ডিংটি জাতীয় আর্কাইভে সংরক্ষিত হয়েছে ভবিষ্যৎ প্রজন্মের জন্য।'],
                    ],
                    'full_text' => 'বাংলাদেশ বেতার থেকে সম্প্রচারিত এই অনুষ্ঠানে আপনাদের স্বাগতম। আজ আমরা স্মরণ করছি আমাদের ইতিহাসের এক গৌরবময় অধ্যায়। এই রেকর্ডিংটি জাতীয় আর্কাইভে সংরক্ষিত হয়েছে ভবিষ্যৎ প্রজন্মের জন্য।',
                    'is_ai_generated' => true,
                    'is_verified' => $verified,
                    'verified_by' => $verified ? $archivist?->id : null,
                    'verified_at' => $verified ? now()->subDays(random_int(5, 60)) : null,
                ],
            );
        }

        // AI suggestions pending review (FR-AIF-06 — draft until verified).
        $targets = AudioAsset::query()->inRandomOrder()->take(6)->get();
        $types = [
            ['summary', ['summary' => 'AI draft summary: this recording covers a cultural broadcast segment with music and narration.', 'confidence' => 0.87]],
            ['tags', ['tags' => ['heritage', 'culture', 'archive'], 'confidence' => 0.79]],
            ['genre', ['genre' => 'Folk', 'confidence' => 0.74]],
            ['mood', ['mood' => 'Nostalgic', 'confidence' => 0.81]],
            ['language', ['language' => 'Bangla', 'dialect' => 'Standard', 'confidence' => 0.96]],
            ['speaker', ['speakers' => [['label' => 'Speaker 1', 'suggested' => 'Russell Chowdhury', 'confidence' => 0.68]]]],
        ];

        foreach ($targets as $index => $asset) {
            [$type, $payload] = $types[$index % count($types)];
            AiSuggestion::query()->firstOrCreate(
                ['audio_asset_id' => $asset->id, 'suggestion_type' => $type],
                ['payload' => $payload, 'status' => 'pending'],
            );
        }

        $this->command?->info('AI: transcripts for '.$speechAssets->count().' assets + '.$targets->count().' pending suggestions seeded');
    }
}
