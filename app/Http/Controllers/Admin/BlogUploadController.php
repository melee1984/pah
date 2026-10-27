<?php

namespace App\Http\Controllers\Admin;

use App\BlogTemporaryUpload;
use App\Http\Controllers\Controller;
use App\Services\BlogImageManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BlogUploadController extends Controller
{
    public function __construct(private readonly BlogImageManager $images) {}

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $upload = $this->images->storeTemporary($request->file('image'), auth()->id());

        return response()->json([
            'id' => $upload->id,
            'name' => $upload->original_name,
            'url' => route('dashboard.blog.uploads.show', $upload),
            'delete_url' => route('dashboard.blog.uploads.destroy', $upload),
        ], 201);
    }

    public function show(BlogTemporaryUpload $upload): StreamedResponse
    {
        $this->authorizeUpload($upload);
        abort_unless(Storage::disk($upload->disk)->exists($upload->path), 404);

        return Storage::disk($upload->disk)->response(
            $upload->path,
            $upload->original_name,
            ['Cache-Control' => 'private, no-store'],
        );
    }

    public function destroy(BlogTemporaryUpload $upload): JsonResponse
    {
        $this->authorizeUpload($upload);
        $this->images->deleteTemporary($upload);

        return response()->json([], 204);
    }

    private function authorizeUpload(BlogTemporaryUpload $upload): void
    {
        abort_unless($upload->user_id === auth()->id() && $upload->expires_at->isFuture(), 404);
    }
}
