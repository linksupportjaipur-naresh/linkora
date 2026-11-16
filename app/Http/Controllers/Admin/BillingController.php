<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function subscriptions(Request $request)
    {
        $status = $request->query('status');

        $subscriptions = Subscription::query()
            ->with(['user:id,username,name,email', 'plan:id,name,key'])
            ->when($status === 'current', fn ($q) => $q->current())
            ->when($status === 'cancelled', fn ($q) => $q->where('status', Subscription::CANCELLED))
            ->when($status === 'expired', fn ($q) => $q->where('status', Subscription::ACTIVE)->whereNotNull('ends_at')->where('ends_at', '<=', now()))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.billing.subscriptions', [
            'subscriptions' => $subscriptions,
            'status'        => $status,
            'currentCount'  => Subscription::current()->count(),
        ]);
    }

    public function cancelSubscription(Subscription $subscription)
    {
        $subscription->update(['status' => Subscription::CANCELLED, 'ends_at' => now()]);

        return back()->with('status', 'Subscription cancelled. The user is back on the Free plan.');
    }

    public function payments(Request $request)
    {
        $status = $request->query('status');

        $payments = Payment::query()
            ->with('user:id,username,name', 'subscription.plan:id,name')
            ->when(in_array($status, Payment::STATUSES, true), fn ($q) => $q->where('status', $status))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.billing.payments', [
            'payments' => $payments,
            'status'   => $status,
            'revenue'  => (int) Payment::where('status', 'paid')->sum('amount'),
            'month'    => (int) Payment::where('status', 'paid')->where('paid_at', '>=', now()->startOfMonth())->sum('amount'),
        ]);
    }

    public function updatePayment(Request $request, Payment $payment)
    {
        $data = $request->validate(['status' => ['required', 'in:' . implode(',', Payment::STATUSES)]]);
        $payment->update($data);

        return back()->with('status', 'Payment marked as ' . $data['status'] . '.');
    }
}
