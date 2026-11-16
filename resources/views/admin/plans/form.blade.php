@extends('layouts.admin')

@section('title', $plan->exists ? 'Edit plan' : 'New plan')
@section('page_title', $plan->exists ? 'Edit plan' : 'New plan')

@section('content')
    <a href="{{ route('admin.plans.index') }}" class="small fw-semibold d-inline-block mb-3"><i class="fa-solid fa-arrow-left me-1"></i>All plans</a>
    @php($isFree = $plan->key === \App\Models\Plan::FREE)

    <div class="p-card" style="max-width:820px">
        <form method="POST" action="{{ $plan->exists ? route('admin.plans.update', $plan) : route('admin.plans.store') }}" class="row g-3" novalidate>
            @csrf
            @if ($plan->exists) @method('PUT') @endif
            <div class="col-md-6">
                <label class="form-label" for="name">Name</label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $plan->name) }}" maxlength="40" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label" for="tagline">Tagline</label>
                <input type="text" class="form-control @error('tagline') is-invalid @enderror" id="tagline" name="tagline" value="{{ old('tagline', $plan->tagline) }}" maxlength="120">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="price_monthly">Monthly price (₹)</label>
                <input type="number" min="0" class="form-control @error('price_monthly') is-invalid @enderror" id="price_monthly" name="price_monthly" value="{{ old('price_monthly', $plan->price_monthly ?? 0) }}" {{ $isFree ? 'readonly' : '' }} required>
                @error('price_monthly')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label" for="price_yearly">Yearly price (₹)</label>
                <input type="number" min="0" class="form-control @error('price_yearly') is-invalid @enderror" id="price_yearly" name="price_yearly" value="{{ old('price_yearly', $plan->price_yearly ?? 0) }}" {{ $isFree ? 'readonly' : '' }} required>
                @error('price_yearly')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label" for="cta">Button text</label>
                <input type="text" class="form-control @error('cta') is-invalid @enderror" id="cta" name="cta" value="{{ old('cta', $plan->cta) }}" maxlength="40" required>
            </div>
            <div class="col-12">
                <label class="form-label" for="features">Features</label>
                <textarea class="form-control font-monospace @error('features') is-invalid @enderror" id="features" name="features" rows="8">{{ old('features', $plan->featuresText()) }}</textarea>
                <div class="form-text">One per line. Start a line with <code>-</code> to show it as not included.</div>
            </div>
            <div class="col-12 d-flex gap-4">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $plan->is_active) ? 'checked' : '' }} {{ $isFree ? 'disabled checked' : '' }}>
                    <label class="form-check-label" for="is_active">Show on pricing page</label>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" value="1" {{ old('is_featured', $plan->is_featured) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_featured">Highlight as “Most popular”</label>
                </div>
            </div>
            <div class="col-12 d-flex gap-2">
                <button class="btn btn-primary">{{ $plan->exists ? 'Save plan' : 'Create plan' }}</button>
                <a href="{{ route('admin.plans.index') }}" class="btn btn-ghost">Cancel</a>
            </div>
        </form>
    </div>
@endsection
