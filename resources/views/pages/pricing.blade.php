@extends('layouts.public')

@section('title', 'Pricing')
@section('description', 'Simple Linkora pricing. Start free, upgrade to Pro or Business when you need more.')

@section('content')
    <section class="page-hero pb-4">
        <div class="aurora" aria-hidden="true"><b></b><b></b><b></b></div>
        <div class="container">
            <span class="lk-eyebrow"><i class="fa-solid fa-tag"></i> Pricing</span>
            <h1>Simple plans. No surprises.</h1>
            <p class="lead">Start for free and upgrade when you’re ready. Cancel any time.</p>
            <div class="billing-toggle mt-4" role="group" aria-label="Billing period">
                <button type="button" class="active" data-cycle="monthly" aria-pressed="true">Monthly</button>
                <button type="button" data-cycle="yearly" aria-pressed="false">Yearly <span class="save">2 months free</span></button>
            </div>
        </div>
    </section>

    <section class="lk-section-sm pt-4">
        <div class="container">
            <div class="row g-4 justify-content-center pt-3">
                @foreach ($plans as $plan)
                    @php
                        $monthly = $plan['monthly'] > 0 ? '₹' . number_format($plan['monthly']) : '₹0';
                        $yearly  = $plan['yearly'] > 0 ? '₹' . number_format($plan['yearly']) : '₹0';
                        $noteM   = $plan['monthly'] > 0 ? 'Billed monthly' : 'Free forever';
                        $noteY   = $plan['yearly'] > 0 ? 'Just ₹' . number_format(round($plan['yearly'] / 12)) . '/month, billed yearly' : 'Free forever';
                    @endphp
                    <div class="col-md-6 col-lg-4">
                        <div class="price-card rv {{ $plan['featured'] ? 'featured' : '' }}">
                            @if ($plan['featured'])<span class="badge-pop"><i class="fa-solid fa-star me-1"></i>Most popular</span>@endif
                            <h3>{{ $plan['name'] }}</h3>
                            <p class="text-muted mb-0">{{ $plan['tagline'] }}</p>
                            <div class="price" data-price-monthly="{{ $monthly }}" data-price-yearly="{{ $yearly }}"
                                 data-note-monthly="{{ $noteM }}" data-note-yearly="{{ $noteY }}" @if ($plan['monthly'] == 0) data-free @endif>
                                <span class="amt">{{ $monthly }}</span><small class="per">/month</small>
                            </div>
                            <div class="price-note">{{ $noteM }}</div>
                            <ul>
                                @foreach ($plan['features'] as [$feature, $included])
                                    <li class="{{ $included ? '' : 'off' }}">
                                        <i class="fa-solid {{ $included ? 'fa-circle-check' : 'fa-circle-xmark' }}"></i>
                                        <span>{{ $feature }}<span class="visually-hidden">{{ $included ? ' (included)' : ' (not included)' }}</span></span>
                                    </li>
                                @endforeach
                            </ul>
                            <a href="{{ route('register') }}" class="btn {{ $plan['featured'] ? 'btn-primary' : 'btn-outline-primary' }} w-100">{{ $plan['cta'] }}</a>
                        </div>
                    </div>
                @endforeach
            </div>
            <p class="text-center text-muted small mt-4 mb-0">Prices in Indian Rupees and include GST.</p>
        </div>
    </section>

    {{-- Comparison --}}
    <section class="lk-section-sm">
        <div class="container">
            <div class="lk-head text-center rv"><h2>Compare plans</h2></div>
            <div class="compare-table rv">
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr><th scope="col">Feature</th>@foreach ($plans as $plan)<th scope="col" class="text-center">{{ $plan['name'] }}</th>@endforeach</tr>
                        </thead>
                        <tbody>
                            @foreach (config('linkora.comparison') as $row)
                                <tr>
                                    <th scope="row" class="fw-normal">{{ $row[0] }}</th>
                                    @foreach (array_slice($row, 1) as $cell)
                                        <td class="text-center">
                                            @if ($cell === true)
                                                <i class="fa-solid fa-check" aria-hidden="true"></i><span class="visually-hidden">Included</span>
                                            @elseif ($cell === false)
                                                <i class="fa-solid fa-minus" aria-hidden="true"></i><span class="visually-hidden">Not included</span>
                                            @else
                                                {{ $cell }}
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    {{-- FAQ --}}
    <section class="lk-section" id="faq">
        <div class="container" style="max-width:820px">
            <div class="lk-head text-center rv"><h2>Questions? Answers.</h2></div>
            <div class="accordion lk-accordion rv" id="faqAccordion">
                @foreach ([
                    ['Is the Free plan really free?', 'Yes. The Free plan costs nothing, has no time limit and doesn’t need a card. Upgrade only if you want more themes, customisation or analytics.'],
                    ['Can I change plans later?', 'Of course. You can upgrade, downgrade or cancel at any time from your dashboard. Changes apply from your next billing date.'],
                    ['Which payment methods do you accept?', 'We accept UPI, debit and credit cards, and net banking.'],
                    ['Can I keep my username if I downgrade?', 'Yes. Your username belongs to you on every plan, including Free.'],
                    ['Do you offer refunds?', 'If you’re not happy within the first 7 days of a paid plan, contact us and we’ll refund you in full.'],
                ] as $i => [$q, $a])
                    <div class="accordion-item">
                        <h3 class="accordion-header" id="faq-h{{ $i }}">
                            <button class="accordion-button {{ $i ? 'collapsed' : '' }}" type="button" data-bs-toggle="collapse" data-bs-target="#faq-c{{ $i }}" aria-expanded="{{ $i ? 'false' : 'true' }}" aria-controls="faq-c{{ $i }}">{{ $q }}</button>
                        </h3>
                        <div id="faq-c{{ $i }}" class="accordion-collapse collapse {{ $i ? '' : 'show' }}" aria-labelledby="faq-h{{ $i }}" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">{{ $a }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
            <p class="text-center text-muted mt-4">Still unsure? <a class="fw-semibold" href="{{ route('contact') }}">Talk to us</a></p>
        </div>
    </section>

    @include('partials.cta', ['title' => 'Start free today.'])
@endsection
