<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Genre;
use App\Models\Language;
use App\Models\Mood;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * M05 — controlled vocabularies: one screen per vocabulary type
 * (categories, genres, moods, languages, tags). FR-MET-03.
 */
class VocabularyController extends Controller
{
    private const TYPES = [
        'categories' => ['model' => Category::class, 'label' => 'Categories', 'hasType' => true],
        'genres' => ['model' => Genre::class, 'label' => 'Genres', 'hasType' => false],
        'moods' => ['model' => Mood::class, 'label' => 'Moods', 'hasType' => false],
        'languages' => ['model' => Language::class, 'label' => 'Languages', 'hasType' => false],
        'tags' => ['model' => Tag::class, 'label' => 'Tags', 'hasType' => false],
    ];

    public function index(string $type): View
    {
        $config = self::TYPES[$type];
        $items = $config['model']::query()->orderBy('name')->get();

        return view('admin.vocabularies.index', [
            'type' => $type,
            'label' => $config['label'],
            'hasType' => $config['hasType'],
            'isLanguage' => $type === 'languages',
            'items' => $items,
            'types' => array_keys(self::TYPES),
        ]);
    }

    public function store(Request $request, string $type): RedirectResponse
    {
        $this->authorize('taxonomies.manage');

        $data = $this->validated($request, $type);
        $model = self::TYPES[$type]['model'];

        $model::query()->create($data);

        return back()->with('success', Str::singular(self::TYPES[$type]['label']).' added.');
    }

    public function update(Request $request, string $type, int $id): RedirectResponse
    {
        $this->authorize('taxonomies.manage');

        $item = $this->find($type, $id);
        $item->update($this->validated($request, $type, $id));

        return back()->with('success', 'Updated.');
    }

    public function destroy(string $type, int $id): RedirectResponse
    {
        $this->authorize('taxonomies.manage');

        $this->find($type, $id)->delete();

        return back()->with('success', 'Removed.');
    }

    private function find(string $type, int $id): Model
    {
        return self::TYPES[$type]['model']::query()->findOrFail($id);
    }

    private function validated(Request $request, string $type, ?int $id = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'name_bn' => ['nullable', 'string', 'max:120'],
            'code' => ['nullable', 'string', 'max:10'],
            'type' => ['nullable', 'in:content,story,ad'],
        ]);

        if ($type === 'languages') {
            $data['code'] = $data['code'] ?? Str::lower(substr($data['name'], 0, 2));
        } else {
            unset($data['code']);
            $data['slug'] = Str::slug($data['name']).($id ? '' : '');
        }

        if ($type !== 'categories') {
            unset($data['type']);
        }

        if ($type === 'languages') {
            unset($data['slug']);
        }

        return $data;
    }
}
