<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Station;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StationController extends Controller
{
    public function index(): View
    {
        $stations = Station::query()->withCount(['departments', 'programmes', 'audioAssets'])->orderBy('name')->get();

        return view('admin.stations.index', compact('stations'));
    }

    public function create(): View
    {
        $this->authorize('stations.manage');

        return view('admin.stations.form', ['station' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('stations.manage');

        Station::query()->create($this->validated($request));

        return redirect()->route('admin.stations.index')->with('success', 'Station created.');
    }

    public function edit(Station $station): View
    {
        $this->authorize('stations.manage');

        return view('admin.stations.form', compact('station'));
    }

    public function update(Request $request, Station $station): RedirectResponse
    {
        $this->authorize('stations.manage');

        $station->update($this->validated($request, $station->id));

        return redirect()->route('admin.stations.index')->with('success', 'Station updated.');
    }

    public function destroy(Station $station): RedirectResponse
    {
        $this->authorize('stations.manage');

        if ($station->audioAssets()->exists()) {
            return back()->with('error', 'This station still owns archive assets and cannot be deleted.');
        }

        $station->delete();

        return redirect()->route('admin.stations.index')->with('success', 'Station deleted.');
    }

    private function validated(Request $request, ?int $ignore = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'name_bn' => ['nullable', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20', 'unique:stations,code'.($ignore ? ",$ignore" : '')],
            'location' => ['nullable', 'string', 'max:255'],
            'frequency' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);
    }
}
