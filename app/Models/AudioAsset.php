<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasRecordVisibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AudioAsset extends Model
{
    use Auditable, HasRecordVisibility, SoftDeletes;

    /** Record visibility keys on the uploader, not a created_by column. */
    public static function creatorColumn(): string
    {
        return 'uploaded_by';
    }

    /**
     * Restricted users see their own uploads plus assets awaiting their
     * action: pending approvals (for approvers) and AI-flagged items (for AI
     * reviewers) — those queues have no per-user assignment yet.
     */
    protected function applyVisibility(Builder $query, User $user): void
    {
        $query->where($this->qualifyColumn('uploaded_by'), $user->id);

        if ($user->can('approvals.act')) {
            $query->orWhereHas('approvals', fn (Builder $q) => $q->whereIn('status', ['pending', 'changes_requested']));
        }

        if ($user->can('ai-moderation.review')) {
            $query->orWhereHas('aiAnalysisJobs', fn (Builder $q) => $q->where('review_status', 'pending'));
        }
    }

    protected $guarded = [];

    protected $casts = [
        'waveform_peaks' => 'array',
        'custom_fields' => 'array',
        'is_premium' => 'boolean',
        'is_public_service' => 'boolean',
        'allow_comments' => 'boolean',
        'recorded_on' => 'date',
        'first_broadcast_on' => 'date',
        'published_at' => 'datetime',
        'loudness_lufs' => 'float',
        'peak_db' => 'float',
        'silence_percent' => 'float',
        'avg_rating' => 'float',
    ];

    // NOTE: rights records are no longer auto-provisioned on approval. After
    // the approval workflow completes, the submitter files the copyright
    // documents via "Submit for Rights" on the asset page, which creates the
    // pending rights record for the rights team to review and clear.

    /* ---------------------------- relationships ----------------------- */

    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function programme(): BelongsTo
    {
        return $this->belongsTo(Programme::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(AudioVersion::class);
    }

    public function masterVersion(): HasOne
    {
        return $this->hasOne(AudioVersion::class)->where('version_type', 'preservation_master');
    }

    public function onlineVersion(): HasOne
    {
        return $this->hasOne(AudioVersion::class)->where('version_type', 'online')->where('is_default', true);
    }

    public function previewVersion(): HasOne
    {
        return $this->hasOne(AudioVersion::class)->where('version_type', 'preview');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'audio_asset_tag');
    }

    public function artists(): BelongsToMany
    {
        return $this->belongsToMany(Artist::class, 'audio_asset_artist')->withPivot('role');
    }

    public function song(): HasOne
    {
        return $this->hasOne(Song::class);
    }

    public function podcastEpisode(): HasOne
    {
        return $this->hasOne(PodcastEpisode::class);
    }

    public function episode(): HasOne
    {
        return $this->hasOne(Episode::class);
    }

    /**
     * Recordings the PUBLIC app may surface: published in the catalogue as a
     * song, podcast episode or programme episode. Raw archive assets without
     * a public catalogue entry never appear in public listings.
     */
    public function scopeCatalogued(Builder $query): Builder
    {
        return $query->where(fn (Builder $q) => $q
            ->whereHas('song')
            ->orWhereHas('podcastEpisode', fn (Builder $e) => $e->where('status', 'published'))
            ->orWhereHas('episode', fn (Builder $e) => $e->where('is_published', true)));
    }

    public function rightsRecords(): HasMany
    {
        return $this->hasMany(RightsRecord::class);
    }

    public function transcripts(): HasMany
    {
        return $this->hasMany(Transcript::class);
    }

    public function aiSuggestions(): HasMany
    {
        return $this->hasMany(AiSuggestion::class);
    }

    public function aiAnalysisJobs(): HasMany
    {
        return $this->hasMany(AiAnalysisJob::class)->latest();
    }

    /** The most recent AI postmortem submission for this asset, if any. */
    public function latestAiAnalysisJob(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(AiAnalysisJob::class)->latestOfMany();
    }

    public function editSessions(): HasMany
    {
        return $this->hasMany(EditSession::class);
    }

    public function markers(): HasMany
    {
        return $this->hasMany(AudioMarker::class)->orderBy('start_seconds');
    }

    public function approvals(): MorphMany
    {
        return $this->morphMany(Approval::class, 'approvable');
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function ratings(): MorphMany
    {
        return $this->morphMany(Rating::class, 'ratable');
    }

    public function favorites(): MorphMany
    {
        return $this->morphMany(Favorite::class, 'favoritable');
    }

    public function playEvents(): HasMany
    {
        return $this->hasMany(PlayEvent::class);
    }

    public function dailyStats(): HasMany
    {
        return $this->hasMany(AssetStatsDaily::class);
    }

    /* ------------------------------ scopes ---------------------------- */

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->where('access_level', 'public')
            ->whereIn('rights_status', ['approved']);
    }

    public function scopeStreamable(Builder $query): Builder
    {
        return $query->published()->whereHas('versions', fn (Builder $v) => $v->whereIn('version_type', ['online', 'preview']));
    }

    /* ------------------------------ helpers --------------------------- */

    public function isPublished(): bool
    {
        return $this->status === 'published'
            && $this->access_level === 'public'
            && $this->rights_status === 'approved';
    }

    /** The rights submission currently under review (or already approved). */
    public function activeRightsRecord(): ?RightsRecord
    {
        return $this->rightsRecords->whereIn('status', ['pending', 'approved'])->first();
    }

    public static function nextArchiveNo(): string
    {
        $next = (int) (static::withTrashed()->max('id') ?? 0) + 1;

        return sprintf('BB-%s-%06d', now()->format('Y'), $next);
    }
}
