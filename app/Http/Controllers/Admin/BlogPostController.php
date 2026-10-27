<?php

namespace App\Http\Controllers\Admin;

use App\BlogPost;
use App\BlogTemporaryUpload;
use App\Http\Controllers\Controller;
use App\Http\Requests\BlogPostRequest;
use App\Services\BlogImageManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BlogPostController extends Controller
{
    public function __construct(private readonly BlogImageManager $images) {}

    public function index(): View
    {
        $posts = BlogPost::query()
            ->withCount('images')
            ->with('author')
            ->latest()
            ->paginate(20);

        return view('dashboard.pages.blog.index', compact('posts'));
    }

    public function create(): View
    {
        return $this->form(new BlogPost(['status' => BlogPost::STATUS_DRAFT]));
    }

    public function store(BlogPostRequest $request): RedirectResponse
    {
        $post = DB::transaction(function () use ($request) {
            $data = Arr::except($request->validated(), [
                'action',
                'temporary_upload_ids',
                'remove_image_ids',
            ]);
            $publishing = $request->validated('action') === 'publish';
            $data['created_by'] = auth()->id();
            $data['status'] = $publishing ? BlogPost::STATUS_PUBLISHED : BlogPost::STATUS_DRAFT;
            $data['published_at'] = $publishing ? now() : null;

            $post = BlogPost::query()->create($data);
            $this->images->attachTemporary(
                $post,
                $request->validated('temporary_upload_ids', []),
                auth()->id(),
            );

            return $post;
        });

        return redirect()->route('dashboard.blog.index')->with(
            'success',
            $post->isPublished() ? 'Blog post published successfully.' : 'Blog draft saved successfully.',
        );
    }

    public function edit(BlogPost $blogPost): View
    {
        $blogPost->load('images');

        return $this->form($blogPost);
    }

    public function update(BlogPostRequest $request, BlogPost $blogPost): RedirectResponse
    {
        $imagesToDelete = collect();

        DB::transaction(function () use ($request, $blogPost, &$imagesToDelete) {
            $data = Arr::except($request->validated(), [
                'action',
                'temporary_upload_ids',
                'remove_image_ids',
            ]);
            $action = $request->validated('action');

            if ($action === 'publish') {
                $data['status'] = BlogPost::STATUS_PUBLISHED;
                $data['published_at'] = $blogPost->published_at ?? now();
            } elseif ($action === 'draft') {
                $data['status'] = BlogPost::STATUS_DRAFT;
                $data['published_at'] = null;
            }

            $blogPost->update($data);
            $this->images->attachTemporary(
                $blogPost,
                $request->validated('temporary_upload_ids', []),
                auth()->id(),
            );

            $imagesToDelete = $blogPost->images()
                ->whereIn('id', $request->validated('remove_image_ids', []))
                ->get();
        });

        $imagesToDelete->each(fn ($image) => $this->images->deleteImage($image));

        return redirect()->route('dashboard.blog.index')->with(
            'success',
            $blogPost->fresh()->isPublished() ? 'Blog post updated and published.' : 'Blog draft updated.',
        );
    }

    public function publish(BlogPost $blogPost): RedirectResponse
    {
        $blogPost->update([
            'status' => BlogPost::STATUS_PUBLISHED,
            'published_at' => $blogPost->published_at ?? now(),
        ]);

        return back()->with('success', 'Blog post published successfully.');
    }

    public function unpublish(BlogPost $blogPost): RedirectResponse
    {
        $blogPost->update([
            'status' => BlogPost::STATUS_DRAFT,
            'published_at' => null,
        ]);

        return back()->with('success', 'Blog post moved back to drafts.');
    }

    public function destroy(BlogPost $blogPost): RedirectResponse
    {
        $images = $blogPost->images()->get();
        $blogPost->delete();
        $images->each(fn ($image) => Storage::disk($image->disk)->delete($image->path));

        return redirect()->route('dashboard.blog.index')->with('success', 'Blog post deleted successfully.');
    }

    private function form(BlogPost $post): View
    {
        $this->images->cleanupExpired();

        $temporaryUploads = BlogTemporaryUpload::query()
            ->where('user_id', auth()->id())
            ->where('expires_at', '>', now())
            ->latest()
            ->get();

        return view('dashboard.pages.blog.form', compact('post', 'temporaryUploads'));
    }
}
