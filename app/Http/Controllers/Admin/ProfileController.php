<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Artist;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Self-service account & profile editing, available to every portal user
 * (staff and artists). Not permission-gated — a user may always manage their
 * own account. Artists additionally manage their public artist profile here;
 * the verified badge stays admin-only (set via the Users / Artists modules).
 */
class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();
        $artist = $user->isArtist() ? $user->artist()->firstOrNew(['user_id' => $user->id]) : null;

        return view('admin.profile.edit', [
            'user' => $user,
            'artist' => $artist,
            'artistTypes' => Artist::TYPES,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'locale' => ['required', Rule::in(['en', 'bn'])],
            'bio' => ['nullable', 'string', 'max:2000'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            // Password change is optional; when set, the current password must match.
            'current_password' => ['nullable', 'required_with:password', 'current_password'],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
            // Artist-only fields (validated for everyone but only applied to artists).
            'name_bn' => ['nullable', 'string', 'max:255'],
            'bio_bn' => ['nullable', 'string', 'max:2000'],
            'artist_type' => ['nullable', Rule::in(Artist::TYPES)],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'social' => ['nullable', 'array'],
            'social.website' => ['nullable', 'url', 'max:255'],
            'social.facebook' => ['nullable', 'url', 'max:255'],
            'social.instagram' => ['nullable', 'url', 'max:255'],
            'social.youtube' => ['nullable', 'url', 'max:255'],
            'social.twitter' => ['nullable', 'url', 'max:255'],
            'social.spotify' => ['nullable', 'url', 'max:255'],
        ]);

        // Account avatar — for artists this is also their public artist photo.
        $photoPath = $this->storeImage($request, 'photo', 'avatars', $user->avatar_path);

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'locale' => $data['locale'],
            'bio' => $data['bio'] ?? null,
            'avatar_path' => $photoPath,
        ]);

        if (! empty($data['password'])) {
            $user->password = $data['password']; // 'hashed' cast handles hashing
        }

        $user->save();

        if ($user->isArtist()) {
            $this->updateArtistProfile($request, $user, $data, $photoPath);
        }

        AuditLog::record('profile_updated', $user, null, null, $user->name.' updated their profile');

        return redirect()->route('admin.profile.edit')->with('success', 'Your profile has been updated.');
    }

    /** Sync the linked artist profile (never touches the admin-only verified flag). */
    private function updateArtistProfile(Request $request, User $user, array $data, ?string $photoPath): void
    {
        $artist = $user->artist()->firstOrNew(['user_id' => $user->id]);

        if (! $artist->exists) {
            $artist->slug = Str::slug($data['name']).'-'.Str::lower(Str::random(4));
            $artist->is_published = true;
        }

        $social = array_filter($data['social'] ?? []); // drop empty links

        $artist->fill([
            'user_id' => $user->id,
            'name' => $data['name'],              // display name mirrors the account name
            'name_bn' => $data['name_bn'] ?? null,
            'artist_type' => $data['artist_type'] ?? $artist->artist_type ?? 'singer',
            'bio' => $data['bio'] ?? null,
            'bio_bn' => $data['bio_bn'] ?? null,
            'photo_path' => $photoPath,           // same image as the account avatar
            'cover_path' => $this->storeImage($request, 'cover', 'artists/covers', $artist->cover_path),
            'social_links' => $social ?: null,
        ])->save();
    }

    /**
     * Store an uploaded image on the public disk, replacing (and deleting) any
     * previous file. Returns the new path, or the existing one when no file was
     * uploaded.
     */
    private function storeImage(Request $request, string $field, string $dir, ?string $existing): ?string
    {
        if (! $request->hasFile($field)) {
            return $existing;
        }

        if ($existing) {
            Storage::disk('public')->delete($existing);
        }

        return $request->file($field)->store($dir, 'public');
    }
}
