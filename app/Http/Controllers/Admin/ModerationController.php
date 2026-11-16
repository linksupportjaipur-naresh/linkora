<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Link;
use App\Models\Profile;
use App\Models\ProfileView;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Profiles and links moderation.
 */
class ModerationController extends Controller
{
    public function profiles(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $state = $request->query('state');

        $profiles = Profile::query()
            ->with('user')
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x
                ->where('display_name', 'like', "%{$q}%")
                ->orWhere('bio', 'like', "%{$q}%")
                ->orWhereHas('user', fn ($u) => $u->where('username', 'like', "%{$q}%")->orWhere('name', 'like', "%{$q}%"))))
            ->when($state === 'published', fn ($w) => $w->where('is_published', true))
            ->when($state === 'hidden', fn ($w) => $w->where('is_published', false))
            ->latest('updated_at')
            ->paginate(15)
            ->withQueryString();

        $ids = $profiles->pluck('user_id');
        $linkCounts = Link::whereIn('user_id', $ids)->selectRaw('user_id, COUNT(*) as c')->groupBy('user_id')->pluck('c', 'user_id');
        $viewCounts = ProfileView::whereIn('user_id', $ids)->selectRaw('user_id, COUNT(*) as c')->groupBy('user_id')->pluck('c', 'user_id');

        return view('admin.profiles.index', compact('profiles', 'q', 'state', 'linkCounts', 'viewCounts'));
    }

    public function unpublishProfile(Profile $profile)
    {
        $profile->forceFill(['is_published' => false])->save();

        return back()->with('status', 'Page @' . $profile->user->username . ' is now hidden.');
    }

    public function publishProfile(Profile $profile)
    {
        $profile->forceFill(['is_published' => true, 'published_at' => $profile->published_at ?? now()])->save();

        return back()->with('status', 'Page @' . $profile->user->username . ' is live again.');
    }

    /** Remove inappropriate content from a profile: photo and/or bio. */
    public function clearProfile(Request $request, Profile $profile)
    {
        $request->validate(['field' => ['required', 'in:avatar,bio,bg_image']]);

        if ($request->input('field') === 'bg_image') {
            if ($profile->bg_image_path) {
                Storage::disk('public')->delete($profile->bg_image_path);
            }
            $profile->forceFill(['bg_image_path' => null])->save();
            $msg = 'Background image removed';
        } elseif ($request->input('field') === 'avatar') {
            if ($profile->avatar_path) {
                Storage::disk('public')->delete($profile->avatar_path);
            }
            $profile->forceFill(['avatar_path' => null])->save();
            $profile->user->forceFill(['avatar' => null])->save();
            $msg = 'Photo removed';
        } else {
            $profile->forceFill(['bio' => null])->save();
            $msg = 'Bio cleared';
        }

        return back()->with('status', $msg . ' for @' . $profile->user->username . '.');
    }

    public function links(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $state = $request->query('state');

        $links = Link::query()
            ->with('user:id,username,name')
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('title', 'like', "%{$q}%")->orWhere('url', 'like', "%{$q}%")))
            ->when($state === 'blocked', fn ($w) => $w->where('is_blocked', true))
            ->when($state === 'hidden', fn ($w) => $w->where('is_active', false))
            ->when($state === 'live', fn ($w) => $w->where('is_active', true)->where('is_blocked', false))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.links.index', compact('links', 'q', 'state'));
    }

    public function blockLink(Request $request, Link $link)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:200']], ['reason.required' => 'Please give a short reason the user will see.']);
        $link->forceFill(['is_blocked' => true, 'blocked_reason' => $data['reason']])->save();

        return back()->with('status', 'Link “' . $link->title . '” is blocked and hidden from the public page.');
    }

    public function unblockLink(Link $link)
    {
        $link->forceFill(['is_blocked' => false, 'blocked_reason' => null])->save();

        return back()->with('status', 'Link “' . $link->title . '” is unblocked.');
    }

    public function destroyLink(Link $link)
    {
        $title = $link->title;
        $link->delete();

        return back()->with('status', 'Link “' . $title . '” was deleted.');
    }
}
