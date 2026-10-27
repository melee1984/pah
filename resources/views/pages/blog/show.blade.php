@extends('templates.inside')

@php
    $description = $post->meta_description ?: ($post->excerpt ?: Str::limit(strip_tags($post->body), 155));
    $cover = $post->images->first();
@endphp
@section('title', $post->title)
@section('description', $description)
@section('canonical', route('blog.show', $post))
@section('og_type', 'article')
@if ($cover)
    @section('social_image', $cover->url)
    @section('social_image_alt', $cover->alt_text ?: $post->title)
@endif

@section('content')
    <script type="application/ld+json">{!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'BlogPosting',
        'headline' => $post->title,
        'description' => $description,
        'image' => $cover ? [$cover->url] : [asset('images/pahatud-social.jpg')],
        'datePublished' => $post->published_at?->toIso8601String(),
        'dateModified' => $post->updated_at?->toIso8601String(),
        'author' => ['@type' => 'Organization', 'name' => 'Pahatud'],
        'publisher' => ['@type' => 'Organization', 'name' => 'Pahatud', 'logo' => ['@type' => 'ImageObject', 'url' => asset('images/logo.jpg')]],
        'mainEntityOfPage' => route('blog.show', $post),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    <article class="public-blog-article">
        <header class="public-blog-article-header"><div class="container">
            <nav class="public-breadcrumb" aria-label="Breadcrumb"><a href="{{ route('home') }}">Home</a><i class="icofont-rounded-right" aria-hidden="true"></i><a href="{{ route('blog.index') }}">Blog</a><i class="icofont-rounded-right" aria-hidden="true"></i><span aria-current="page">Article</span></nav>
            <span class="public-page-kicker">Pahatud stories</span>
            <h1>{{ $post->title }}</h1>
            <div class="public-blog-byline"><time datetime="{{ $post->published_at->toDateString() }}">{{ $post->published_at->format('F j, Y') }}</time><span aria-hidden="true">•</span><span>Pahatud</span></div>
            @if ($post->excerpt)<p class="public-blog-lead">{{ $post->excerpt }}</p>@endif
        </div></header>

        <div class="container public-blog-article-layout">
            @if ($cover)<figure class="public-blog-cover"><img src="{{ $cover->url }}" alt="{{ $cover->alt_text ?: $post->title }}"></figure>@endif
            <div class="public-blog-body">{!! nl2br(e($post->body)) !!}</div>
            @if ($post->images->count() > 1)
                <div class="public-blog-gallery">@foreach ($post->images->skip(1) as $image)<figure><img src="{{ $image->url }}" alt="{{ $image->alt_text ?: $post->title }}" loading="lazy"></figure>@endforeach</div>
            @endif
            <a class="public-blog-back" href="{{ route('blog.index') }}"><i class="icofont-arrow-left"></i> Back to the blog</a>
        </div>
    </article>
@endsection
