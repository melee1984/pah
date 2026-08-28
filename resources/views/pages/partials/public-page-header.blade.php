<section class="public-page-hero">
    <div class="container">
        <nav class="public-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route('home') }}">Home</a>
            <i class="icofont-rounded-right" aria-hidden="true"></i>
            <span aria-current="page">{{ $title }}</span>
        </nav>
        <span class="public-page-kicker">{{ $kicker ?? 'Pahatud information' }}</span>
        <h1>{{ $title }}</h1>
        @isset($description)
            <p>{{ $description }}</p>
        @endisset
    </div>
</section>
