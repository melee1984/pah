@extends('dashboard.template.main2')

@section('content')
<div class="content-wrapper admin-content-wrapper">
    <section class="content-header admin-page-header">
        <div class="container-fluid">
            <div class="admin-page-heading">
                <div><span class="admin-eyebrow">Website content</span><h1>Blog posts</h1><p>Create useful stories, optimize their search previews, and control when they appear publicly.</p></div>
                <a class="btn admin-btn-primary" href="{{ route('dashboard.blog.create') }}"><i class="fas fa-plus mr-2"></i>New post</a>
            </div>
        </div>
    </section>

    <section class="content"><div class="container-fluid">
        @if (session('success'))<div class="alert admin-alert-success"><i class="fas fa-check-circle mr-2"></i>{{ session('success') }}</div>@endif
        @if ($errors->any())<div class="alert admin-alert-error"><i class="fas fa-exclamation-circle mr-2"></i>{{ $errors->first() }}</div>@endif

        <div class="card admin-card">
            <div class="admin-card-header"><div><h2>All posts</h2><p>{{ number_format($posts->total()) }} {{ Str::plural('post', $posts->total()) }}</p></div></div>
            @if ($posts->isEmpty())
                <div class="admin-empty-state"><span><i class="fas fa-newspaper"></i></span><h3>No blog posts yet</h3><p>Create your first post and save it as a draft or publish it immediately.</p><a class="btn admin-btn-primary mt-2" href="{{ route('dashboard.blog.create') }}">Create a post</a></div>
            @else
                <div class="table-responsive"><table class="table admin-table blog-admin-table">
                    <thead><tr><th>Post</th><th>Status</th><th>Images</th><th>Published</th><th>Author</th><th>Actions</th></tr></thead>
                    <tbody>@foreach ($posts as $post)<tr>
                        <td><strong>{{ $post->title }}</strong><small>/blog/{{ $post->slug }}</small></td>
                        <td><span class="admin-status {{ $post->isPublished() ? 'admin-status-active' : 'admin-status-pending' }}">{{ $post->isPublished() ? 'Published' : 'Draft' }}</span></td>
                        <td>{{ $post->images_count }}</td>
                        <td>{{ $post->published_at?->format('M j, Y g:i A') ?? '—' }}</td>
                        <td>{{ trim($post->author?->full_name ?? '') ?: ($post->author?->email ?? '—') }}</td>
                        <td><div class="blog-admin-actions">
                            @if ($post->isPublished())<a class="btn admin-btn-secondary btn-sm" href="{{ route('blog.show', $post) }}" target="_blank" rel="noopener"><i class="fas fa-external-link-alt"></i><span class="sr-only">View</span></a>@endif
                            <a class="btn admin-btn-secondary btn-sm" href="{{ route('dashboard.blog.edit', $post) }}"><i class="fas fa-pen"></i><span class="sr-only">Edit</span></a>
                            <form method="POST" action="{{ $post->isPublished() ? route('dashboard.blog.unpublish', $post) : route('dashboard.blog.publish', $post) }}">@csrf @method('PATCH')<button class="btn admin-btn-secondary btn-sm" type="submit" title="{{ $post->isPublished() ? 'Move to drafts' : 'Publish' }}"><i class="fas {{ $post->isPublished() ? 'fa-eye-slash' : 'fa-paper-plane' }}"></i></button></form>
                            <form method="POST" action="{{ route('dashboard.blog.destroy', $post) }}">@csrf @method('DELETE')<button class="btn admin-btn-danger btn-sm" type="submit" onclick="return confirm('Delete this post and all of its images?')"><i class="fas fa-trash"></i><span class="sr-only">Delete</span></button></form>
                        </div></td>
                    </tr>@endforeach</tbody>
                </table></div>
                <div class="admin-pagination">{{ $posts->links('pagination::bootstrap-4') }}</div>
            @endif
        </div>
    </div></section>
</div>
@endsection
