<?php

declare(strict_types=1);

namespace Tests\Feature\ApiAdmin;

use App\Modules\Page\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
    }

    public function test_can_get_pages_list(): void
    {
        Page::factory()->count(5)->create(['status' => '1']);

        $response = $this->getJson('/admin/api/pages?limit=5');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_can_get_single_page(): void
    {
        $page = Page::factory()->create();

        $response = $this->getJson("/admin/api/pages/{$page->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_can_create_page(): void
    {
        $pageData = [
            'title' => 'New Page',
            'slug' => 'new-page',
            'content' => 'Page content',
            'status' => '1',
        ];

        $response = $this->postJson('/admin/api/pages', $pageData);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);
    }

    public function test_can_update_page(): void
    {
        $page = Page::factory()->create();

        $response = $this->putJson("/admin/api/pages/{$page->id}", [
            'title' => 'Updated Page',
            'status' => '1',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_can_delete_page(): void
    {
        $page = Page::factory()->create();

        $response = $this->deleteJson("/admin/api/pages/{$page->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }
}
