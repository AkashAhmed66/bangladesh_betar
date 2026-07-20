<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Artist;
use App\Models\AudioAsset;
use App\Models\Comment;
use App\Models\ContentReport;
use App\Models\Favorite;
use App\Models\Feedback;
use App\Models\Follow;
use App\Models\IssueReport;
use App\Models\PlayHistory;
use App\Models\PodcastChannel;
use App\Models\Programme;
use App\Models\Rating;
use App\Models\Song;
use App\Models\TakedownRequest;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * M17/M26 — favourites, follows, history, comments, ratings,
 * reports, takedowns and feedback.
 */
class EngagementSeeder extends Seeder
{
    public function run(): void
    {
        $listeners = User::query()->where('user_type', 'listener')->get();
        $songs = Song::query()->with('audioAsset')->get();
        $assets = AudioAsset::query()->where('status', 'published')->get();

        if ($listeners->isEmpty() || $assets->isEmpty()) {
            return;
        }

        $commentBodies = [
            'অসাধারণ! ছোটবেলার স্মৃতি ফিরে এলো।',
            'This recording quality is amazing for its age.',
            'বেতারের এই আর্কাইভ সত্যিই জাতীয় সম্পদ।',
            'Listened with my family — thank you Betar!',
            'আরও পুরনো গান চাই এই অ্যাপে।',
            'The restoration work on this is incredible.',
        ];

        foreach ($listeners as $li => $listener) {
            // favourites: a few songs + assets
            foreach ($songs->random(min(4, $songs->count())) as $song) {
                Favorite::query()->firstOrCreate([
                    'user_id' => $listener->id,
                    'favoritable_type' => 'song',
                    'favoritable_id' => $song->id,
                ]);
            }

            // follows: artists, programmes, podcast channels
            foreach (Artist::query()->inRandomOrder()->take(3)->get() as $artist) {
                Follow::query()->firstOrCreate([
                    'user_id' => $listener->id,
                    'followable_type' => 'artist',
                    'followable_id' => $artist->id,
                ]);
            }
            if ($programme = Programme::query()->inRandomOrder()->first()) {
                Follow::query()->firstOrCreate([
                    'user_id' => $listener->id,
                    'followable_type' => 'programme',
                    'followable_id' => $programme->id,
                ]);
            }
            if ($channel = PodcastChannel::query()->inRandomOrder()->first()) {
                Follow::query()->firstOrCreate([
                    'user_id' => $listener->id,
                    'followable_type' => 'podcast_channel',
                    'followable_id' => $channel->id,
                ]);
            }

            // listening history / continue listening
            foreach ($assets->random(min(5, $assets->count())) as $asset) {
                $completed = (bool) random_int(0, 1);
                PlayHistory::query()->updateOrCreate(
                    ['user_id' => $listener->id, 'playable_type' => 'audio_asset', 'playable_id' => $asset->id],
                    [
                        'audio_asset_id' => $asset->id,
                        'progress_seconds' => $completed ? $asset->duration_seconds : random_int(30, max(31, $asset->duration_seconds - 60)),
                        'completed' => $completed,
                        'last_played_at' => now()->subHours(random_int(1, 240)),
                    ],
                );
            }

            // comments & ratings
            foreach ($assets->random(min(3, $assets->count())) as $asset) {
                Comment::query()->firstOrCreate(
                    ['user_id' => $listener->id, 'commentable_type' => 'audio_asset', 'commentable_id' => $asset->id],
                    [
                        'body' => $commentBodies[($li + $asset->id) % count($commentBodies)],
                        'status' => ['approved', 'approved', 'approved', 'pending'][random_int(0, 3)],
                    ],
                );

                Rating::query()->updateOrCreate(
                    ['user_id' => $listener->id, 'ratable_type' => 'audio_asset', 'ratable_id' => $asset->id],
                    ['rating' => random_int(3, 5)],
                );
            }
        }

        // Reported comment + moderation queue examples
        $spamComment = Comment::query()->create([
            'user_id' => $listeners->first()->id,
            'commentable_type' => 'audio_asset',
            'commentable_id' => $assets->first()->id,
            'body' => 'Visit my page for free downloads!!! [link removed]',
            'status' => 'pending',
        ]);

        ContentReport::query()->firstOrCreate(
            ['reportable_type' => 'comment', 'reportable_id' => $spamComment->id],
            [
                'reporter_id' => $listeners->last()->id,
                'reason' => 'spam',
                'details' => 'Advertising external pirated downloads.',
                'status' => 'pending',
            ],
        );

        // Issue reports (FR-ENG-07)
        $issues = [
            ['broken_audio', 'Playback stops at 12:40 every time.'],
            ['wrong_metadata', 'The singer credit appears to be incorrect for this song.'],
        ];
        foreach ($issues as $index => [$type, $description]) {
            IssueReport::query()->firstOrCreate(
                ['issue_type' => $type, 'audio_asset_id' => $assets[$index]->id],
                [
                    'user_id' => $listeners[$index % $listeners->count()]->id,
                    'description' => $description,
                    'status' => 'open',
                ],
            );
        }

        // Takedown request (FR-ENG-08)
        TakedownRequest::query()->firstOrCreate(
            ['complainant_name' => 'Bangla Music Rights Society'],
            [
                'complainant_email' => 'licensing@bmrs.example',
                'organization' => 'BMRS',
                'audio_asset_id' => $songs->first()?->audio_asset_id,
                'reason' => 'Streaming rights for this recording were not renewed for the current year.',
                'status' => 'investigating',
            ],
        );

        // General feedback (FR-ENG-09)
        $feedbackEntries = [
            ['suggestion', 'Sleep timer presets', 'Please add a 45-minute sleep timer preset.'],
            ['technical', 'App crash on old phone', 'The app closes when opening the equalizer on my device.'],
            ['general', 'Thank you', 'This archive is a gift to the nation. Joy Bangla!'],
        ];
        foreach ($feedbackEntries as [$category, $subject, $message]) {
            Feedback::query()->firstOrCreate(
                ['subject' => $subject],
                [
                    'user_id' => $listeners->random()->id,
                    'category' => $category,
                    'message' => $message,
                    'status' => 'new',
                ],
            );
        }

        $this->command?->info('Engagement: favourites, follows, history, comments, ratings, reports seeded');
    }
}
