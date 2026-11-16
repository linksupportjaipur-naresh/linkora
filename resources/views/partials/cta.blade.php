<div class="container mb-5">
    <div class="cta rv">
        <h2>{{ $title ?? 'Ready to make your link?' }}</h2>
        <p>{{ $text ?? 'Set up your page in minutes. Free forever, no card needed.' }}</p>
        <a class="btn btn-white btn-lg" href="{{ route('register') }}">Create my page</a>
    </div>
</div>
