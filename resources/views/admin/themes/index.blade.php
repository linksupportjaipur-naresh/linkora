@extends('layouts.admin')

@section('title', 'Themes')
@section('page_title', 'Themes')

@section('content')
    <div class="page-head">
        <div>
            <h2>Page themes</h2>
            <p>Themes users can pick on the Appearance page. Hidden themes stay on pages that already use them until the user changes.</p>
        </div>
        <a href="{{ route('admin.themes.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i>New theme</a>
    </div>

    <div class="row g-3">
        @foreach ($themes as $t)
            <div class="col-sm-6 col-lg-4 col-xxl-3">
                <div class="p-card h-100 d-flex flex-column">
                    <div class="rounded-4 p-3 mb-3 d-flex flex-column align-items-center gap-2" style="background: {{ $t->bg }}; min-height: 150px; border: 1px solid var(--lk-line)">
                        <span class="rounded-circle" style="width:34px;height:34px;background:{{ $t->button }}"></span>
                        <span style="color:{{ $t->text }};font-weight:700;font-family:{{ config('linkora_design.fonts.' . $t->font . '.1') }}">Aa</span>
                        @foreach (range(1, 2) as $i)
                            <span class="w-100" style="height:18px;border:2px solid {{ $t->button }};background:{{ $t->button_style === 'outline' ? 'transparent' : $t->button }};border-radius:{{ ['pill' => '999px', 'rounded' => '8px', 'square' => '2px', 'outline' => '8px', 'shadow' => '4px'][$t->button_style] ?? '8px' }}"></span>
                        @endforeach
                    </div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <strong>{{ $t->name }}</strong>
                        @if ($t->isDefault())<span class="badge-soft">Default</span>@endif
                        @if ($t->is_pro)<span class="badge-soft amber"><i class="fa-solid fa-crown me-1"></i>Pro</span>@endif
                        @unless ($t->is_active)<span class="badge-soft red">Hidden</span>@endunless
                    </div>
                    <div class="small text-muted mb-3">{{ config('linkora_design.fonts.' . $t->font . '.0') }} · {{ config('linkora_design.button_styles.' . $t->button_style) }} · used by {{ $usage[$t->key] ?? 0 }}</div>
                    <div class="mt-auto d-flex gap-2">
                        <a href="{{ route('admin.themes.edit', $t) }}" class="btn btn-sm btn-outline-primary flex-fill">Edit</a>
                        @unless ($t->isDefault())
                            <form method="POST" action="{{ route('admin.themes.toggle', $t) }}">@csrf<button class="btn btn-sm btn-ghost" title="{{ $t->is_active ? 'Hide' : 'Show' }}"><i class="fa-regular {{ $t->is_active ? 'fa-eye-slash' : 'fa-eye' }}"></i><span class="visually-hidden">{{ $t->is_active ? 'Hide' : 'Show' }}</span></button></form>
                            <form method="POST" action="{{ route('admin.themes.destroy', $t) }}" data-confirm="Delete the {{ $t->name }} theme? Pages using it switch to the default theme.">@csrf @method('DELETE')<button class="btn btn-sm btn-ghost" title="Delete"><i class="fa-regular fa-trash-can"></i><span class="visually-hidden">Delete</span></button></form>
                        @endunless
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection
