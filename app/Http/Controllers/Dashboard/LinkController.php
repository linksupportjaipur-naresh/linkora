<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Link;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Links are always looked up through $request->user()->links(), so a user can
 * never read or change someone else's link (they get a 404 instead).
 */
class LinkController extends Controller
{
    public const MAX_LINKS = 100;

    public function index(Request $request)
    {
        $user = $request->user();

        return view('dashboard.links', [
            'user'    => $user,
            'profile' => $user->profileRecord(),
            'links'   => $user->links()->ordered()->get(),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        if ($user->links()->count() >= self::MAX_LINKS) {
            return response()->json(['message' => 'You can add up to ' . self::MAX_LINKS . ' links.'], 422);
        }

        $data = $this->validated($request);
        // New links go to the top of the list.
        $user->links()->increment('position');
        $link = $user->links()->create($data + ['position' => 0, 'is_active' => true]);

        Activity::log($user, 'link.created', 'Added link “' . Str::limit($link->title, 40) . '”');

        return response()->json(['message' => 'Link added.', 'link' => $link->toDashboardArray()], 201);
    }

    public function update(Request $request, int $id)
    {
        $link = $this->find($request, $id);
        $link->update($this->validated($request));

        Activity::log($request->user(), 'link.updated', 'Edited link “' . Str::limit($link->title, 40) . '”');

        return response()->json(['message' => 'Link updated.', 'link' => $link->toDashboardArray()]);
    }

    public function destroy(Request $request, int $id)
    {
        $link = $this->find($request, $id);
        $title = $link->title;
        $link->delete();

        Activity::log($request->user(), 'link.deleted', 'Deleted link “' . Str::limit($title, 40) . '”');

        return response()->json(['message' => 'Link deleted.']);
    }

    public function toggle(Request $request, int $id)
    {
        $link = $this->find($request, $id);
        $request->validate(['is_active' => ['required', 'boolean']]);
        if ($link->is_blocked && $request->boolean('is_active')) {
            return response()->json(['message' => 'This link was blocked by our team and can’t be shown. You can edit or delete it.'], 403);
        }
        $link->update(['is_active' => $request->boolean('is_active')]);

        Activity::log(
            $request->user(),
            $link->is_active ? 'link.enabled' : 'link.disabled',
            ($link->is_active ? 'Showed' : 'Hid') . ' link “' . Str::limit($link->title, 40) . '”'
        );

        return response()->json([
            'message' => $link->is_active ? 'Link is visible on your page.' : 'Link is hidden from your page.',
            'link'    => $link->toDashboardArray(),
        ]);
    }

    public function reorder(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'order'   => ['required', 'array', 'max:' . self::MAX_LINKS],
            'order.*' => ['integer', 'distinct'],
        ]);

        $ownIds = $user->links()->pluck('id')->all();
        $ids = array_values(array_filter($data['order'], fn ($id) => in_array((int) $id, $ownIds, true)));

        if (count($ids) !== count($data['order'])) {
            abort(404);
        }

        DB::transaction(function () use ($user, $ids) {
            foreach ($ids as $position => $id) {
                $user->links()->whereKey($id)->update(['position' => $position]);
            }
        });

        Activity::log($user, 'links.reordered', 'Changed the order of your links');

        return response()->json(['message' => 'Order saved.']);
    }

    private function find(Request $request, int $id): Link
    {
        return $request->user()->links()->findOrFail($id);
    }

    private function validated(Request $request): array
    {
        $url = trim((string) $request->input('url'));
        if ($url !== '' && ! preg_match('#^[a-z][a-z0-9+.-]*:#i', $url)) {
            $url = 'https://' . ltrim($url, '/');
        }
        $request->merge(['url' => $url, 'title' => trim((string) $request->input('title')), 'subtitle' => trim((string) $request->input('subtitle')) ?: null]);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:80'],
            'subtitle' => ['nullable', 'string', 'max:100'],
            'url'   => ['required', 'string', 'max:2048', 'url', 'regex:#^https?://[^\s/$.?\#].[^\s]*$#i'],
            'icon'  => ['nullable', 'string', Rule::in(array_keys(config('linkora_design.link_icons')))],
        ], [
            'title.required' => 'Please give your link a title.',
            'url.required'   => 'Please enter the link address.',
            'url.url'        => 'That doesn’t look like a valid link. Try something like https://example.com.',
            'url.regex'      => 'Links must start with http:// or https://.',
        ]);

        $data['icon'] = $data['icon'] ?? null;
        $data['subtitle'] = $data['subtitle'] ?? null;

        return $data;
    }
}
