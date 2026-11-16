@extends('layouts.admin')

@section('title', 'Plans')
@section('page_title', 'Plans')

@section('content')
    <div class="page-head">
        <div>
            <h2>Plans &amp; pricing</h2>
            <p>These plans appear on the <a href="{{ route('pricing') }}" target="_blank" rel="noopener">pricing page</a>. Prices are in INR.</p>
        </div>
        <a href="{{ route('admin.plans.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i>New plan</a>
    </div>

    <div class="row g-3">
        @foreach ($plans as $plan)
            <div class="col-md-6 col-xl-4">
                <div class="p-card h-100 d-flex flex-column">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <h3 class="h5 mb-0">{{ $plan->name }}</h3>
                        @if ($plan->is_featured)<span class="badge-soft"><i class="fa-solid fa-star me-1"></i>Featured</span>@endif
                        @unless ($plan->is_active)<span class="badge-soft red">Hidden</span>@endunless
                    </div>
                    <div class="text-muted small mb-3">{{ $plan->tagline }}</div>
                    <div class="mb-3"><span class="display-font fs-2 fw-bold">₹{{ number_format($plan->price_monthly) }}</span><span class="text-muted">/month</span> <span class="text-muted small">· ₹{{ number_format($plan->price_yearly) }}/year</span></div>
                    <ul class="list-unstyled small mb-3">
                        @foreach (array_slice($plan->features ?: [], 0, 7) as [$text, $inc])
                            <li class="{{ $inc ? '' : 'text-muted text-decoration-line-through' }}"><i class="fa-solid {{ $inc ? 'fa-check text-brand' : 'fa-xmark' }} me-2"></i>{{ $text }}</li>
                        @endforeach
                    </ul>
                    <div class="small text-muted mb-3">{{ $plan->active_subscriptions_count }} active {{ \Illuminate\Support\Str::plural('subscriber', $plan->active_subscriptions_count) }}</div>
                    <div class="mt-auto d-flex gap-2">
                        <a href="{{ route('admin.plans.edit', $plan) }}" class="btn btn-sm btn-outline-primary flex-fill">Edit</a>
                        @if ($plan->key !== \App\Models\Plan::FREE)
                            <form method="POST" action="{{ route('admin.plans.destroy', $plan) }}" data-confirm="Delete the {{ $plan->name }} plan?">@csrf @method('DELETE')<button class="btn btn-sm btn-ghost" title="Delete"><i class="fa-regular fa-trash-can"></i><span class="visually-hidden">Delete</span></button></form>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection
