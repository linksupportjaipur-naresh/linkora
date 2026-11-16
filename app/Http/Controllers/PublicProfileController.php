<?php

namespace App\Http\Controllers;

use App\Models\Link;
use App\Models\User;
use App\Support\ProfileDesign;
use App\Support\VisitTracker;
use Illuminate\Http\Request;

class PublicProfileController extends Controller
{
    /** linkora.in/{username} */
    public function show(Request $request, string $username)
    {
        $user = User::where('username', strtolower($username))->first();
        $isOwner = $user && optional($request->user())->id === $user->id;

        if (! $user || ! $user->isActive()) {
            return $this->unavailable();
        }

        $profile = $user->profileRecord();

        // Owners can always see their own page (with a "not published" banner).
        if (! $profile->is_published && ! $isOwner) {
            return $this->unavailable();
        }

        if ($profile->is_published && ! $request->boolean('preview')) {
            if (VisitTracker::recordView($request, $user)) {
                $request->session()->put('profile_referrer.' . $user->id, VisitTracker::referrerHost($request));
            }
        }

        return response()->view('public.profile', [
            'user'      => $user,
            'profile'   => $profile,
            'links'     => $user->links()->active()->ordered()->get(),
            'design'    => ProfileDesign::resolve($profile),
            'isOwner'   => $isOwner,
            'isPreview' => $request->boolean('preview'),
        ]);
    }

    /** /l/{link}: count the click, then send the visitor to the link's URL. */
    public function click(Request $request, Link $link)
    {
        $owner = $link->user;
        $isOwner = optional($request->user())->id === $link->user_id;

        if (! $owner || ! $owner->isActive() || $link->is_blocked || (! $link->is_active && ! $isOwner)) {
            return $this->unavailable();
        }

        $profile = $owner->profileRecord();
        if (! $profile->is_published && ! $isOwner) {
            return $this->unavailable();
        }

        VisitTracker::recordClick($request, $link);

        return redirect()->away($link->url);
    }

    private function unavailable()
    {
        return response()->view('public.unavailable', [], 404);
    }
}
