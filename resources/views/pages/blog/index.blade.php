@extends('templates.inside')

@section('title', 'Blog')
@section('description', 'Read practical delivery guides, food discoveries, partner stories, and updates from Pahatud across the Philippines.')

@section('content')
    @include('pages.partials.public-page-header', ['title' => 'The Pahatud Blog', 'pageName' => 'Blog', 'kicker' => 'Stories, tips, and local discoveries', 'description' => 'Helpful delivery guides, food discoveries, partner stories, and updates from communities across the Philippines.'])

    <section class="public-blog-page"><div class="container">
        @if ($posts->isEmpty())
            <div class="public-blog-empty"><i class="icofont-newspaper"></i><h2>Stories are on the way</h2><p>Check back soon for Pahatud news, useful guides, and local food discoveries.</p></div>
        @else
            <div class="public-blog-grid">
                @foreach ($posts as $post)
                    <article class="public-blog-card">
                        <a class="public-blog-card-image" href="{{ route('blog.show', $post) }}" aria-label="Read {{ $post->title }}">
                            @if ($post->images->first())<img src="{{ $post->images->first()->url }}" alt="{{ $post->images->first()->alt_text ?: $post->title }}" loading="lazy">@else<span><i class="icofont-newspaper"></i></span>@endif
                        </a>
                        <div class="public-blog-card-copy">
                            <time datetime="{{ $post->published_at->toDateString() }}">{{ $post->published_at->format('F j, Y') }}</time>
                            <h2><a href="{{ route('blog.show', $post) }}">{{ $post->title }}</a></h2>
                            <p>{{ $post->excerpt ?: Str::limit(strip_tags($post->body), 150) }}</p>
                            <a class="public-blog-read" href="{{ route('blog.show', $post) }}">Read article <i class="icofont-arrow-right"></i></a>
                        </div>
                    </article>
                @endforeach
            </div>
            <div class="public-blog-pagination">{{ $posts->links('pagination::bootstrap-4') }}</div>
        @endif
    </div></section>
@endsection
