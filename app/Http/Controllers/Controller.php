<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;

abstract class Controller
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * 403 unless the record is visible to the current user under the
     * record-level visibility rules (see HasRecordVisibility).
     */
    protected function authorizeRecordVisibility(Model $record): void
    {
        abort_unless(
            $record->isVisibleTo(auth()->user()),
            403,
            'You can only access records you created. Ask an administrator for the "records: view-all" permission.',
        );
    }
}
