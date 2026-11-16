{{-- Search + filter bar. $action (url), $q, $selects: [name => [current, [value => label]]] --}}
<form method="GET" action="{{ $action }}" class="p-card mb-3 py-3">
    <div class="row g-2 align-items-center">
        <div class="col-md">
            <div class="input-group">
                <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                <input type="search" name="q" value="{{ $q ?? '' }}" class="form-control" placeholder="{{ $placeholder ?? 'Search…' }}" aria-label="Search">
            </div>
        </div>
        @foreach ($selects ?? [] as $name => [$current, $options])
            <div class="col-6 col-md-auto">
                <select name="{{ $name }}" class="form-select" aria-label="Filter by {{ $name }}" onchange="this.form.submit()">
                    @foreach ($options as $value => $label)
                        <option value="{{ $value }}" {{ (string) $current === (string) $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        @endforeach
        <div class="col-6 col-md-auto d-flex gap-2">
            <button class="btn btn-primary" type="submit">Search</button>
            @if (request()->hasAny(array_merge(['q'], array_keys($selects ?? []))))
                <a class="btn btn-ghost" href="{{ $action }}">Clear</a>
            @endif
        </div>
    </div>
</form>
