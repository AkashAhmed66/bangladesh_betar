<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Episode;
use App\Models\Language;
use App\Models\Story;
use App\Models\StorySubmission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * M10 — individually addressable stories inside an episode, plus the
 * listener submissions review queue (FR-EVT-05) on the same page. The
 * submissions section only appears for users who can view it, and the
 * review action inside it only for users who can act on it.
 */
class StoryController extends Controller
{
    public function index(Request $request): View
    {
        $stories = Story::query()
            ->with(['episode.programme', 'category', 'language'])
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->where('title', 'like', '%'.$request->string('q').'%')
                ->orWhere('storyteller_name', 'like', '%'.$request->string('q').'%')))
            ->when($request->filled('episode'), fn ($q) => $q->where('episode_id', $request->integer('episode')))
            ->orderByDesc('id')
            ->paginate(12, ['*'], 'stories_page')
            ->withQueryString();

        $submissions = null;
        if ($request->user()->can('submissions.view')) {
            $submissions = StorySubmission::query()
                ->with(['user', 'reviewer'])
                ->when($request->filled('submission_status'), fn ($q) => $q->where('status', $request->string('submission_status')))
                ->orderByDesc('id')
                ->paginate(10, ['*'], 'submissions_page')
                ->withQueryString();
        }

        return view('admin.stories.index', compact('stories', 'submissions'));
    }

    public function create(): View
    {
        $this->authorize('stories.manage');

        return view('admin.stories.form', ['story' => null] + $this->options());
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('stories.manage');

        $data = $this->validated($request);
        $data['slug'] = Str::slug($data['title'].'-'.uniqid());

        Story::query()->create($data);

        return redirect()->route('admin.stories.index')->with('success', 'Story created.');
    }

    public function edit(Story $story): View
    {
        $this->authorize('stories.manage');

        return view('admin.stories.form', ['story' => $story] + $this->options());
    }

    public function update(Request $request, Story $story): RedirectResponse
    {
        $this->authorize('stories.manage');

        $story->update($this->validated($request));

        return redirect()->route('admin.stories.index')->with('success', 'Story updated.');
    }

    public function destroy(Story $story): RedirectResponse
    {
        $this->authorize('stories.manage');

        $story->delete();

        return redirect()->route('admin.stories.index')->with('success', 'Story removed.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'episode_id' => ['required', 'exists:episodes,id'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'language_id' => ['nullable', 'exists:languages,id'],
            'title' => ['required', 'string', 'max:255'],
            'title_bn' => ['nullable', 'string', 'max:255'],
            'summary' => ['nullable', 'string'],
            'storyteller_name' => ['nullable', 'string', 'max:255'],
            'is_anonymous' => ['boolean'],
            'narrator' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:60'],
            'start_seconds' => ['required', 'integer', 'min:0'],
            'end_seconds' => ['required', 'integer', 'min:0'],
            'content_warning' => ['nullable', 'string', 'max:255'],
            'is_published' => ['boolean'],
        ]);
    }

    private function options(): array
    {
        return [
            'episodes' => Episode::query()->with('programme')->orderByDesc('id')->take(300)->get()
                ->mapWithKeys(fn (Episode $e) => [$e->id => trim(($e->programme?->title ? $e->programme->title.' — ' : '').$e->title)]),
            'categories' => Category::query()->where('type', 'story')->orderBy('name')->pluck('name', 'id'),
            'languages' => Language::query()->orderBy('name')->pluck('name', 'id'),
        ];
    }
}
