<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Controller;
use App\Models\LinkClick;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\ProfileView;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $status = $request->query('status');
        $role = $request->query('role');

        $users = User::query()
            ->with('profile')
            ->withCount('links')
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w
                ->where('name', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%")
                ->orWhere('username', 'like', "%{$q}%")))
            ->when(in_array($status, [User::STATUS_ACTIVE, User::STATUS_SUSPENDED, User::STATUS_DEACTIVATED], true), fn ($query) => $query->where('status', $status))
            ->when(in_array($role, [User::ROLE_USER, User::ROLE_ADMIN], true), fn ($query) => $query->where('role', $role))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', compact('users', 'q', 'status', 'role'));
    }

    public function show(User $user)
    {
        return view('admin.users.show', [
            'user'          => $user->load('profile'),
            'profile'       => $user->profileRecord(),
            'links'         => $user->links()->ordered()->get(),
            'views'         => ProfileView::where('user_id', $user->id)->count(),
            'clicks'        => LinkClick::where('user_id', $user->id)->count(),
            'activities'    => $user->activities()->latest('id')->take(10)->get(),
            'subscriptions' => $user->subscriptions()->with('plan')->latest('id')->get(),
            'payments'      => $user->payments()->latest('id')->get(),
            'plans'         => Plan::orderBy('sort')->get(),
        ]);
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $request->merge([
            'username' => strtolower(trim((string) $request->input('username'))),
            'email'    => strtolower(trim((string) $request->input('email'))),
        ]);

        $usernameRules = RegisterController::usernameRules();
        $usernameRules[array_key_last($usernameRules)] = Rule::unique('users', 'username')->ignore($user->id);
        // Admin accounts may keep usernames that contain a dash (e.g. the seeded "linkora-admin").
        if ($user->isAdmin()) {
            $usernameRules = ['required', 'string', 'min:3', 'max:30', Rule::unique('users', 'username')->ignore($user->id)];
        }

        $data = $request->validate([
            'name'     => ['required', 'string', 'max:100'],
            'email'    => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'username' => $usernameRules,
            'role'     => ['required', Rule::in([User::ROLE_USER, User::ROLE_ADMIN])],
            'status'   => ['required', Rule::in([User::STATUS_ACTIVE, User::STATUS_SUSPENDED, User::STATUS_DEACTIVATED])],
        ], RegisterController::usernameMessages());

        if ($user->is($request->user()) && ($data['role'] !== User::ROLE_ADMIN || $data['status'] !== User::STATUS_ACTIVE)) {
            return back()->withInput()->withErrors(['role' => 'You can’t remove your own admin access or block yourself.']);
        }

        $user->forceFill($data)->save();

        return redirect()->route('admin.users.show', $user)->with('status', 'User updated.');
    }

    public function block(Request $request, User $user)
    {
        if ($user->is($request->user()) || $user->isAdmin()) {
            return back()->with('error', 'Administrator accounts can’t be blocked.');
        }
        $user->forceFill(['status' => User::STATUS_SUSPENDED])->save();

        return back()->with('status', '@' . $user->username . ' is blocked. They can’t log in and their page is offline.');
    }

    public function unblock(User $user)
    {
        $user->forceFill(['status' => User::STATUS_ACTIVE])->save();

        return back()->with('status', '@' . $user->username . ' is unblocked.');
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->is($request->user()) || $user->isAdmin()) {
            return back()->with('error', 'Administrator accounts can’t be deleted.');
        }

        $files = array_filter([optional($user->profile)->avatar_path, optional($user->profile)->bg_image_path]);
        $username = $user->username;
        $user->delete();
        if ($files) {
            Storage::disk('public')->delete($files);
        }

        return redirect()->route('admin.users.index')->with('status', '@' . $username . ' and all their data were deleted.');
    }

    /** Give a user a plan by hand (no payment gateway yet), optionally recording a payment. */
    public function assignPlan(Request $request, User $user)
    {
        $data = $request->validate([
            'plan_id'        => ['required', 'exists:plans,id'],
            'billing_cycle'  => ['required', Rule::in(['monthly', 'yearly', 'lifetime'])],
            'amount'         => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'method'         => ['nullable', Rule::in(array_keys(Payment::METHODS))],
            'reference'      => ['nullable', 'string', 'max:100'],
        ]);

        $plan = Plan::findOrFail($data['plan_id']);

        DB::transaction(function () use ($user, $plan, $data, $request) {
            $user->subscriptions()->current()->update(['status' => Subscription::CANCELLED, 'ends_at' => now()]);

            if ($plan->isFree()) {
                return; // Free = no paid subscription
            }

            $sub = $user->subscriptions()->create([
                'plan_id'       => $plan->id,
                'status'        => Subscription::ACTIVE,
                'billing_cycle' => $data['billing_cycle'],
                'starts_at'     => now(),
                'ends_at'       => ['monthly' => now()->addMonth(), 'yearly' => now()->addYear(), 'lifetime' => null][$data['billing_cycle']],
                'source'        => 'manual',
                'created_by'    => $request->user()->id,
            ]);

            if (! empty($data['amount'])) {
                Payment::create([
                    'user_id'         => $user->id,
                    'subscription_id' => $sub->id,
                    'amount'          => $data['amount'],
                    'method'          => $data['method'] ?? 'manual',
                    'status'          => 'paid',
                    'reference'       => $data['reference'] ?? null,
                    'notes'           => 'Recorded by admin',
                    'paid_at'         => now(),
                ]);
            }
        });

        return back()->with('status', '@' . $user->username . ' is now on the ' . $plan->name . ' plan.');
    }
}
