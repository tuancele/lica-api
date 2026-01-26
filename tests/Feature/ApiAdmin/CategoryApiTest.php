<?php

declare(strict_types=1);
namespace Tests\Feature\ApiAdmin;

use Tests\TestCase;
use App\Modules\Category\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CategoryApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
    }

    public function test_can_get_categories_list(): void
    {
        Category::factory()->count(5)->create(['status' => '1']);

        $response = $this->getJson('/admin/api/categories?limit=5');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data',
                     'pagination'
                 ])
                 ->assertJson(['success' => true]);
    }

    public function test_can_get_single_category(): void
    {
        $category = Category::factory()->create();

        $response = $this->getJson("/admin/api/categories/{$category->id}");

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);
    }

    public function test_can_create_category(): void
    {
        $categoryData = [
            'name' => 'New Category',
            'slug' => 'new-category',
            'status' => '1',
            'sort' => 0
        ];

        $response = $this->postJson('/admin/api/categories', $categoryData);

        $response->assertStatus(201)
                 ->assertJson(['success' => true]);
    }

    public function test_can_update_category(): void
    {
        $category = Category::factory()->create();

        $response = $this->putJson("/admin/api/categories/{$category->id}", [
            'name' => 'Updated Category',
            'status' => '1'
        ]);

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);
    }

    public function test_can_delete_category(): void
    {
        $category = Category::factory()->create();

        $response = $this->deleteJson("/admin/api/categories/{$category->id}");

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);
    }

    public function test_can_get_category_tree(): void
    {
        Category::factory()->count(3)->create();

        $response = $this->getJson('/admin/api/categories/tree');

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);
    }
}

