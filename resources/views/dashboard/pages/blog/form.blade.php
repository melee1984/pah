@extends('dashboard.template.main2')

@section('content')
@php($editing = $post->exists)
<div class="content-wrapper admin-content-wrapper">
    <section class="content-header admin-page-header"><div class="container-fluid"><div class="admin-page-heading">
        <div><span class="admin-eyebrow">Website content</span><h1>{{ $editing ? 'Edit blog post' : 'Create blog post' }}</h1><p>Write the article, add its search description, and upload one or more images.</p></div>
        <a class="btn admin-btn-secondary" href="{{ route('dashboard.blog.index') }}"><i class="fas fa-arrow-left mr-2"></i>Back to posts</a>
    </div></div></section>

    <section class="content"><div class="container-fluid">
        @if ($errors->any())<div class="alert admin-alert-error"><i class="fas fa-exclamation-circle mr-2"></i><strong>Please review the form.</strong><ul class="mb-0 mt-2">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

        <form method="POST" action="{{ $editing ? route('dashboard.blog.update', $post) : route('dashboard.blog.store') }}" id="blogPostForm">
            @csrf
            @if ($editing) @method('PUT') @endif
            <div class="blog-editor-layout">
                <div class="card admin-card blog-editor-main"><div class="card-body">
                    <div class="form-group"><label for="blogTitle">Post title</label><input class="form-control" id="blogTitle" name="title" value="{{ old('title', $post->title) }}" maxlength="160" required></div>
                    <div class="form-group"><label for="blogSlug">URL slug</label><div class="blog-slug-field"><span>{{ url('/blog') }}/</span><input class="form-control" id="blogSlug" name="slug" value="{{ old('slug', $post->slug) }}" maxlength="190" pattern="[a-z0-9]+(?:-[a-z0-9]+)*" required></div><small class="form-text text-muted">Use lowercase words separated by hyphens.</small></div>
                    <div class="form-group"><label for="blogExcerpt">Summary</label><textarea class="form-control" id="blogExcerpt" name="excerpt" rows="3" maxlength="500" placeholder="A short introduction shown on the blog listing.">{{ old('excerpt', $post->excerpt) }}</textarea></div>
                    <div class="form-group mb-0"><label for="blogBody">Article</label><textarea class="form-control blog-body-input" id="blogBody" name="body" rows="18" required placeholder="Write the full article here...">{{ old('body', $post->body) }}</textarea><small class="form-text text-muted">Paragraph breaks are preserved on the public article page.</small></div>
                </div></div>

                <aside class="blog-editor-sidebar">
                    <div class="card admin-card"><div class="admin-card-header"><div><h2>Search preview</h2><p>Keep the description clear and under 160 characters.</p></div></div><div class="card-body">
                        <div class="form-group mb-1"><label for="blogMeta">SEO meta description</label><textarea class="form-control" id="blogMeta" name="meta_description" rows="4" maxlength="160" placeholder="Describe this article for search results.">{{ old('meta_description', $post->meta_description) }}</textarea></div>
                        <small class="blog-character-count"><span id="metaCount">0</span>/160 characters</small>
                    </div></div>

                    <div class="card admin-card"><div class="admin-card-header"><div><h2>Post images</h2><p>The first image is used as the cover and social preview.</p></div></div><div class="card-body">
                        <label class="blog-upload-control" for="blogImages"><i class="fas fa-cloud-upload-alt"></i><strong>Upload images</strong><span>JPG, PNG, or WebP · up to 5 MB each</span></label>
                        <input class="sr-only" id="blogImages" type="file" accept="image/jpeg,image/png,image/webp" multiple>
                        <div id="uploadStatus" class="blog-upload-status" role="status" aria-live="polite"></div>
                        <div class="blog-image-grid" id="blogImageGrid">
                            @foreach ($post->images ?? [] as $image)
                                <label class="blog-image-card"><img src="{{ $image->url }}" alt="{{ $image->alt_text ?: $post->title }}"><span>{{ $image->original_name }}</span><span class="blog-remove-check"><input type="checkbox" name="remove_image_ids[]" value="{{ $image->id }}"> Remove on save</span></label>
                            @endforeach
                            @foreach ($temporaryUploads as $upload)
                                <div class="blog-image-card blog-temp-card" data-upload-id="{{ $upload->id }}"><img src="{{ route('dashboard.blog.uploads.show', $upload) }}" alt="Temporary upload preview"><span>{{ $upload->original_name }}</span><input type="hidden" name="temporary_upload_ids[]" value="{{ $upload->id }}"><button type="button" class="blog-remove-temp" data-delete-url="{{ route('dashboard.blog.uploads.destroy', $upload) }}"><i class="fas fa-times"></i> Remove</button></div>
                            @endforeach
                        </div>
                    </div></div>

                    <div class="card admin-card"><div class="card-body blog-publish-actions">
                        <p><strong>Current status:</strong> <span class="admin-status {{ $post->isPublished() ? 'admin-status-active' : 'admin-status-pending' }}">{{ $post->isPublished() ? 'Published' : 'Draft' }}</span></p>
                        @if ($post->isPublished())
                            <button class="btn admin-btn-primary btn-block" name="action" value="save" type="submit"><i class="fas fa-save mr-2"></i>Save changes</button>
                        @else
                            <button class="btn admin-btn-primary btn-block" name="action" value="publish" type="submit"><i class="fas fa-paper-plane mr-2"></i>Publish post</button>
                            <button class="btn admin-btn-secondary btn-block" name="action" value="draft" type="submit"><i class="fas fa-save mr-2"></i>Save draft</button>
                        @endif
                    </div></div>
                </aside>
            </div>
        </form>
    </div></section>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const title = document.getElementById('blogTitle');
    const slug = document.getElementById('blogSlug');
    const meta = document.getElementById('blogMeta');
    const metaCount = document.getElementById('metaCount');
    const input = document.getElementById('blogImages');
    const grid = document.getElementById('blogImageGrid');
    const status = document.getElementById('uploadStatus');
    const token = document.querySelector('meta[name="csrf-token"]').content;
    let slugChanged = Boolean(slug.value);

    const slugify = value => value.toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
    title.addEventListener('input', () => { if (!slugChanged) slug.value = slugify(title.value); });
    slug.addEventListener('input', () => { slugChanged = true; slug.value = slugify(slug.value); });
    const countMeta = () => { metaCount.textContent = meta.value.length; };
    meta.addEventListener('input', countMeta); countMeta();

    async function removeTemporary(button) {
        button.disabled = true;
        const response = await fetch(button.dataset.deleteUrl, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' } });
        if (response.ok) button.closest('.blog-temp-card').remove();
        else { button.disabled = false; status.textContent = 'That image could not be removed. Please try again.'; }
    }
    grid.querySelectorAll('.blog-remove-temp').forEach(button => button.addEventListener('click', () => removeTemporary(button)));

    input.addEventListener('change', async function () {
        const files = Array.from(input.files);
        if (!files.length) return;
        status.textContent = `Uploading ${files.length} image${files.length === 1 ? '' : 's'}…`;
        let uploaded = 0;
        for (const file of files) {
            const data = new FormData(); data.append('image', file);
            try {
                const response = await fetch(@json(route('dashboard.blog.uploads.store')), { method: 'POST', headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' }, body: data });
                const payload = await response.json();
                if (!response.ok) throw new Error(payload.message || Object.values(payload.errors || {}).flat()[0] || 'Upload failed.');
                const card = document.createElement('div');
                card.className = 'blog-image-card blog-temp-card'; card.dataset.uploadId = payload.id;
                card.innerHTML = `<img src="${payload.url}" alt="Temporary upload preview"><span></span><input type="hidden" name="temporary_upload_ids[]" value="${payload.id}"><button type="button" class="blog-remove-temp"><i class="fas fa-times"></i> Remove</button>`;
                card.querySelector('span').textContent = payload.name;
                const button = card.querySelector('button'); button.dataset.deleteUrl = payload.delete_url;
                button.addEventListener('click', () => removeTemporary(button)); grid.appendChild(card); uploaded++;
            } catch (error) { status.textContent = error.message; }
        }
        if (uploaded === files.length) status.textContent = `${uploaded} image${uploaded === 1 ? '' : 's'} ready. Images become permanent when you save the post.`;
        input.value = '';
    });
});
</script>
@endsection
