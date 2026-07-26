<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Public view of a listener's own Community Inbox submission — the status a
 * moderator has set is surfaced so the listener can track their report /
 * issue / feedback.
 */
class CommunitySubmissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,                 // content_report | issue_report | feedback
            'type_label' => $this->typeLabel(),    // Abuse report / Content issue / Feedback
            'category' => $this->category,
            'category_label' => $this->category ? ucwords(str_replace('_', ' ', $this->category)) : null,
            'subject_line' => $this->subject_line,
            'message' => $this->message,
            'status' => $this->status,             // new | in_progress | resolved | dismissed
            'resolution_notes' => $this->resolution_notes,
            'target' => $this->when((bool) $this->subject_type, fn () => [
                'type' => $this->subject_type,
                'label' => data_get($this->subject, 'title')
                    ?? data_get($this->subject, 'name')
                    ?? data_get($this->subject, 'body'),
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
            'handled_at' => $this->handled_at?->toIso8601String(),
        ];
    }
}
