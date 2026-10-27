<?php

namespace App\Http\Controllers;

use App\BlogPost;
use App\BlogPostImage;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BlogController extends Controller
{
    public function index(): View
    {
        $posts = BlogPost::query()
            ->published()
            ->with('images')
            ->latest('published_at')
            ->paginate(12);

        return view('pages.blog.index', compact('posts'));
    }

    public function show(BlogPost $blogPost): View
    {
        abort_unless($blogPost->isPublished(), 404);
        $blogPost->load('images');

        return view('pages.blog.show', ['post' => $blogPost]);
    }

    public function image(BlogPostImage $image): StreamedResponse
    {
        $image->loadMissing('post');
        abort_unless($image->post?->isPublished(), 404);
        abort_unless(Storage::disk($image->disk)->exists($image->path), 404);

        return Storage::disk($image->disk)->response(
            $image->path,
            $image->original_name,
            ['Cache-Control' => 'public, max-age=86400'],
        );
    }
}
