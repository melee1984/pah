<?php

namespace App\Services;

use App\BlogPost;
use App\BlogPostImage;
use App\BlogTemporaryUpload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class BlogImageManager
{
    public function storeTemporary(UploadedFile $file, ?int $userId): BlogTemporaryUpload
    {
        $this->cleanupExpired();

        $id = (string) Str::uuid();
        $extension = strtolower($file->extension() ?: $file->getClientOriginalExtension());
        $path = $file->storeAs('blog/temporary', $id.'.'.$extension, 'local');

        if (! $path) {
            throw new RuntimeException('The temporary blog image could not be stored.');
        }

        return BlogTemporaryUpload::query()->create([
            'id' => $id,
            'user_id' => $userId,
            'disk' => 'local',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
            'size' => $file->getSize(),
            'expires_at' => now()->addDay(),
        ]);
    }

    public function attachTemporary(BlogPost $post, array $uploadIds, ?int $userId): void
    {
        if ($uploadIds === []) {
            return;
        }

        $uploads = BlogTemporaryUpload::query()
            ->whereIn('id', $uploadIds)
            ->where('user_id', $userId)
            ->where('expires_at', '>', now())
            ->get()
            ->keyBy('id');

        $sortOrder = (int) $post->images()->max('sort_order');

        foreach ($uploadIds as $uploadId) {
            $upload = $uploads->get($uploadId);

            if (! $upload || ! Storage::disk($upload->disk)->exists($upload->path)) {
                continue;
            }

            $extension = pathinfo($upload->path, PATHINFO_EXTENSION);
            $destination = 'blog/posts/'.$post->id.'/'.Str::uuid().'.'.$extension;
            $stream = Storage::disk($upload->disk)->readStream($upload->path);

            if (! is_resource($stream) || ! Storage::disk('public')->put($destination, $stream)) {
                if (is_resource($stream)) {
                    fclose($stream);
                }

                throw new RuntimeException('A blog image could not be moved into permanent storage.');
            }

            if (is_resource($stream)) {
                fclose($stream);
            }

            $post->images()->create([
                'disk' => 'public',
                'path' => $destination,
                'original_name' => $upload->original_name,
                'mime_type' => $upload->mime_type,
                'size' => $upload->size,
                'alt_text' => $post->title,
                'sort_order' => ++$sortOrder,
            ]);

            Storage::disk($upload->disk)->delete($upload->path);
            $upload->delete();
        }
    }

    public function deleteImage(BlogPostImage $image): void
    {
        Storage::disk($image->disk)->delete($image->path);
        $image->delete();
    }

    public function deleteTemporary(BlogTemporaryUpload $upload): void
    {
        Storage::disk($upload->disk)->delete($upload->path);
        $upload->delete();
    }

    public function cleanupExpired(): void
    {
        BlogTemporaryUpload::query()
            ->where('expires_at', '<=', now())
            ->limit(100)
            ->get()
            ->each(fn (BlogTemporaryUpload $upload) => $this->deleteTemporary($upload));
    }
}
