<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
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
    use Auditable, SoftDeletes;

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

    protected static function booted(): void
    {
        // When the approval workflow completes (status → 'approved'), auto-
        // provision a pending rights record so it lands in the Rights Records
        // table immediately. The rights team completes + clears it, which is
        // what unlocks publishing (the publish gate still requires cleared).
        static::updated(function (self $asset): void {
            if ($asset->wasChanged('status') && $asset->status === 'approved') {
                $asset->ensureRightsRecord();
            }
        });
    }

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
            ->whereIn('rights_status', ['cleared']);
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
            && $this->rights_status === 'cleared';
    }

    /**
     * Ensure the asset has at least one rights record. Auto-provisioned as
     * 'pending' when the asset is published, so the rights team only has to
     * fill in the details and clear it (rather than create it from scratch).
     */
    public function ensureRightsRecord(): void
    {
        if ($this->rightsRecords()->exists()) {
            return;
        }

        $this->rightsRecords()->create([
            'rights_types' => [],
            'territory' => 'Bangladesh',
            'royalty_required' => false,
            'status' => 'pending',
            'created_by' => auth()->id(),
            'notes' => 'Auto-created on approval — add the rights holder and details, then set the status to Cleared to allow publishing.',
        ]);
    }

    public static function nextArchiveNo(): string
    {
        $next = (int) (static::withTrashed()->max('id') ?? 0) + 1;

        return sprintf('BB-%s-%06d', now()->format('Y'), $next);
    }
}
