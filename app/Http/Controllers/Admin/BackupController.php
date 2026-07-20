<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BackupRun;
use App\Models\IntegrityCheck;
use Illuminate\View\View;

/**
 * M22 — digital preservation: three-copy backups + fixity/integrity checks.
 */
class BackupController extends Controller
{
    public function index(): View
    {
        $backups = BackupRun::query()
            ->orderByDesc('started_at')
            ->orderByDesc('id')
            ->take(25)
            ->get();

        $checks = IntegrityCheck::query()
            ->with('audioAsset')
            ->orderByDesc('checked_at')
            ->take(25)
            ->get();

        $stats = [
            'last_success' => BackupRun::query()
                ->where('status', 'success')
                ->orderByDesc('finished_at')
                ->first(),
            'failed' => BackupRun::query()->where('status', 'failed')->count(),
            'corrupt' => IntegrityCheck::query()->where('result', 'corrupt')->count(),
        ];

        return view('admin.backups.index', compact('backups', 'checks', 'stats'));
    }
}
