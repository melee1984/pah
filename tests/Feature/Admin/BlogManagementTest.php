<?php

namespace Tests\Feature\Admin;

use App\BlogPost;
use App\BlogPostImage;
use App\BlogTemporaryUpload;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BlogManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('manager', 'web');
        Storage::fake('local');
        Storage::fake('public');
    }

    public function test_administrator_can_upload_a_temporary_image_and_publish_a_post(): void
    {
        $admin = $this->user('admin@example.test', 'admin');

        $this->actingAs($admin)
            ->get(route('dashboard.blog.create'))
            ->assertOk()
            ->assertSee('Create blog post');

        $uploadResponse = $this->postJson(route('dashboard.blog.uploads.store'), [
            'image' => UploadedFile::fake()->image('pahatud-guide.jpg', 1200, 630),
        ]);

        $uploadResponse->assertCreated()->assertJsonStructure(['id', 'name', 'url', 'delete_url']);
        $upload = BlogTemporaryUpload::query()->findOrFail($uploadResponse->json('id'));
        Storage::disk('local')->assertExists($upload->path);

        $this->post(route('dashboard.blog.store'), [
            'title' => 'A Guide to Easier Local Deliveries',
            'slug' => 'a-guide-to-easier-local-deliveries',
            'excerpt' => 'Simple ways to prepare for a smooth delivery.',
            'body' => "Plan ahead and confirm your address.\n\nKeep your phone nearby for rider updates.",
            'meta_description' => 'Learn simple ways to make every local Pahatud delivery smooth and convenient.',
            'temporary_upload_ids' => [$upload->id],
            'action' => 'publish',
        ])->assertRedirect(route('dashboard.blog.index'))->assertSessionHas('success');

        $post = BlogPost::query()->where('slug', 'a-guide-to-easier-local-deliveries')->firstOrFail();
        $image = $post->images()->firstOrFail();
        $this->assertTrue($post->isPublished());
        $this->assertSame($admin->id, $post->created_by);
        $this->assertNull(BlogTemporaryUpload::query()->find($upload->id));
        Storage::disk('local')->assertMissing($upload->path);
        Storage::disk('public')->assertExists($image->path);

        $this->get(route('dashboard.blog.index'))
            ->assertOk()
            ->assertSee($post->title);
        $this->get(route('dashboard.blog.edit', $post))
            ->assertOk()
            ->assertSee('Edit blog post');

        $this->get(route('blog.index'))
            ->assertOk()
            ->assertSee($post->title);

        $this->get(route('blog.show', $post))
            ->assertOk()
            ->assertSee($post->meta_description, false)
            ->assertSee('<meta property="og:type" content="article">', false)
            ->assertSee('<meta property="og:title" content="'.$post->title.' | PahatudFood">', false)
            ->assertSee($image->url, false)
            ->assertSee('Plan ahead and confirm your address.');

        $this->get(route('blog.images.show', $image))->assertOk();
    }

    public function test_administrator_can_edit_change_publication_status_and_delete_a_post(): void
    {
        $admin = $this->user('admin@example.test', 'admin');
        $post = BlogPost::query()->create([
            'created_by' => $admin->id,
            'title' => 'Draft story',
            'slug' => 'draft-story',
            'body' => 'A private draft.',
            'status' => BlogPost::STATUS_DRAFT,
        ]);

        $this->get(route('blog.show', $post))->assertNotFound();

        $this->actingAs($admin)->put(route('dashboard.blog.update', $post), [
            'title' => 'Published story',
            'slug' => 'published-story',
            'excerpt' => 'The updated summary.',
            'body' => 'The updated article.',
            'meta_description' => 'An updated description for search engines and social sharing.',
            'action' => 'publish',
        ])->assertRedirect(route('dashboard.blog.index'));

        $post->refresh();
        $this->assertTrue($post->isPublished());
        $this->assertSame('published-story', $post->slug);
        $this->get(route('blog.show', $post))->assertOk();

        $this->patch(route('dashboard.blog.unpublish', $post))->assertRedirect();
        $this->assertFalse($post->fresh()->isPublished());
        $this->get(route('blog.show', $post))->assertNotFound();

        Storage::disk('public')->put('blog/posts/'.$post->id.'/cover.jpg', 'image');
        $image = BlogPostImage::query()->create([
            'blog_post_id' => $post->id,
            'disk' => 'public',
            'path' => 'blog/posts/'.$post->id.'/cover.jpg',
            'original_name' => 'cover.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 5,
        ]);

        $this->delete(route('dashboard.blog.destroy', $post))->assertRedirect(route('dashboard.blog.index'));
        $this->assertDatabaseMissing('blog_posts', ['id' => $post->id]);
        $this->assertDatabaseMissing('blog_post_images', ['id' => $image->id]);
        Storage::disk('public')->assertMissing($image->path);
    }

    public function test_city_manager_cannot_manage_blog_posts(): void
    {
        $manager = $this->user('manager@example.test', 'manager');

        $this->actingAs($manager)
            ->get(route('dashboard.blog.index'))
            ->assertForbidden();
    }

    private function user(string $email, string $role): User
    {
        $user = User::query()->forceCreate([
            'name' => strtok($email, '@'),
            'email' => $email,
            'password' => Hash::make('password'),
        ]);
        $user->assignRole($role);

        return $user;
    }
}
