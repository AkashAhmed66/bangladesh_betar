<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Approval;
use App\Models\ApprovalAction;
use App\Models\AudioAsset;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowStage;
use Illuminate\Database\Seeder;

/**
 * M13 — configurable approval workflows per content type + live instances.
 */
class WorkflowSeeder extends Seeder
{
    public function run(): void
    {
        $definitions = [
            'default' => ['Default Content Workflow', [
                ['Technical Quality Check', 'Archive Administrator'],
                ['Editorial Review', 'Archive Administrator'],
                ['Copyright Review', 'Copyright Officer'],
                ['Management Approval', 'Approver'],
            ]],
            'song' => ['Song Publication Workflow', [
                ['Technical Quality Check', 'Archive Administrator'],
                ['Music Library Review', 'Music Library Manager'],
                ['Copyright Review', 'Copyright Officer'],
                ['Management Approval', 'Approver'],
            ]],
            'podcast' => ['Podcast Publication Workflow', [
                ['Technical Quality Check', 'Archive Administrator'],
                ['Editorial Review', 'Podcast Manager'],
                ['Management Approval', 'Approver'],
            ]],
            'advert' => ['Advertisement Approval Workflow', [
                ['Content Review', 'Advertisement Manager'],
                ['Copyright Review', 'Copyright Officer'],
                ['Management Approval', 'Approver'],
            ]],
            'historical' => ['Historical Recording Workflow', [
                ['Restoration QC', 'Archive Administrator'],
                ['Archival Review', 'Archive Administrator'],
                ['Rights Verification', 'Copyright Officer'],
                ['Management Approval', 'Approver'],
            ]],
        ];

        foreach ($definitions as $contentType => [$name, $stages]) {
            $workflow = Workflow::query()->updateOrCreate(
                ['content_type' => $contentType],
                ['name' => $name, 'is_active' => true, 'escalation_hours' => 72],
            );

            foreach ($stages as $index => [$stageName, $role]) {
                WorkflowStage::query()->updateOrCreate(
                    ['workflow_id' => $workflow->id, 'sequence' => $index + 1],
                    ['name' => $stageName, 'approver_role' => $role],
                );
            }
        }

        // Live approval instances for the in-pipeline assets.
        $archivist = User::query()->where('email', 'archivist@betar.gov.bd')->first();
        $pending = AudioAsset::query()->whereIn('status', ['pending_qc', 'in_review'])->get();

        foreach ($pending as $asset) {
            $workflow = Workflow::forContentType($asset->content_type === 'drama' ? 'default' : 'historical');
            if (! $workflow) {
                continue;
            }
            $firstStage = $workflow->stages()->first();

            $approval = Approval::query()->firstOrCreate(
                ['approvable_type' => 'audio_asset', 'approvable_id' => $asset->id],
                [
                    'workflow_id' => $workflow->id,
                    'current_stage_id' => $firstStage?->id,
                    'status' => 'pending',
                    'submitted_by' => $archivist?->id,
                    'submitted_at' => now()->subDays(random_int(1, 5)),
                ],
            );

            ApprovalAction::query()->firstOrCreate(
                ['approval_id' => $approval->id, 'action' => 'submitted'],
                [
                    'workflow_stage_id' => $firstStage?->id,
                    'user_id' => $archivist?->id,
                    'comments' => 'Submitted for review.',
                ],
            );
        }

        $this->command?->info('Workflows: '.count($definitions).' definitions + '.$pending->count().' live approvals seeded');
    }
}
