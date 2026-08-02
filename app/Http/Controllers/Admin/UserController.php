<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Artist;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()
            ->with('roles')
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', '%'.$request->string('q').'%')
                ->orWhere('email', 'like', '%'.$request->string('q').'%')))
            ->when($request->filled('type'), fn ($q) => $q->where('user_type', $request->string('type')))
            ->when($request->filled('role'), fn ($q) => $q->role($request->string('role')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'roles' => Role::query()->orderBy('name')->pluck('name'),
        ]);
    }

    public function create(): View
    {
        $this->authorize('users.create');

        return view('admin.users.form', ['user' => null, 'roles' => Role::query()->orderBy('name')->pluck('name')]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('users.create');

        $data = $this->validateUser($request);

        $user = User::query()->create($data + ['email_verified_at' => now()]);

        $role = $this->resolveRole($data);
        $user->syncRoles([$role]);

        if ($role === 'Artist') {
            $this->syncArtistProfile($user, $data);
        }

        AuditLog::record('user_created', $user, null, ['role' => $role, 'user_type' => $user->user_type]);

        return redirect()->route('admin.users.index')->with('success', "User {$user->name} created.");
    }

    public function edit(User $user): View
    {
        $this->authorize('users.edit');

        return view('admin.users.form', ['user' => $user, 'roles' => Role::query()->orderBy('name')->pluck('name')]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorize('users.edit');

        $data = $this->validateUser($request, $user);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);
        $role = $this->resolveRole($data);
        $user->syncRoles([$role]);

        if ($role === 'Artist') {
            $this->syncArtistProfile($user, $data);
        }

        return redirect()->route('admin.users.index')->with('success', "User {$user->name} updated.");
    }

    /**
     * Shared validation for create/update. On create the password is required.
     * A `role` is required for staff and dual-app ("both") accounts; listeners
     * get the fixed Listener role. `artist_type` is required only when the
     * chosen role is Artist.
     */
    private function validateUser(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:6'],
            'user_type' => ['required', Rule::in(['staff', 'listener', 'both'])],
            'status' => ['required', Rule::in(['active', 'inactive', 'banned'])],
            'role' => ['required_unless:user_type,listener', 'nullable', 'exists:roles,name'],
            // Artist fields — required (and used) only when the role is Artist.
            'artist_type' => ['nullable', Rule::requiredIf(fn () => $request->input('role') === 'Artist'), Rule::in(Artist::TYPES)],
            'is_verified' => ['boolean'],
        ]);
    }

    /** Listeners get the fixed Listener role; staff and dual-app accounts use the chosen role. */
    private function resolveRole(array $data): string
    {
        return match ($data['user_type']) {
            'listener' => Role::findOrCreate('Listener', 'web')->name,
            default => $data['role'],
        };
    }

    /**
     * Ensure an artist account has a linked, in-sync Artist profile so it
     * appears in the Artists module immediately. The account name is the
     * canonical display name (kept mirrored); the slug is generated once.
     */
    private function syncArtistProfile(User $user, array $data): void
    {
        $artist = $user->artist()->first() ?? new Artist(['user_id' => $user->id]);

        if (! $artist->exists) {
            $artist->slug = Str::slug($user->name).'-'.Str::lower(Str::random(4));
            $artist->is_published = true;
        }

        $artist->fill([
            'user_id' => $user->id,
            'name' => $user->name,
            'artist_type' => $data['artist_type'] ?? $artist->artist_type ?? 'singer',
            'is_verified' => (bool) ($data['is_verified'] ?? $artist->is_verified),
        ])->save();
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorize('users.delete');

        if ($user->id === $request->user()->id) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();
        AuditLog::record('user_deleted', $user);

        return redirect()->route('admin.users.index')->with('success', 'User deactivated and removed.');
    }
}
