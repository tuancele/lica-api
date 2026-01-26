<?php

namespace Tests\Feature\ApiAdmin;

use Tests\TestCase;
use App\Modules\Brand\Models\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class BrandApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
    }

    public function test_can_get_brands_list(): void
    {
        Brand::factory()->count(5)->create(['status' => '1']);

        $response = $this->getJson('/admin/api/brands?limit=5');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data',
                     'pagination' => [
                         'current_page',
                         'per_page',
                         'total',
                         'last_page'
                     ]
                 ])
                 ->assertJson(['success' => true]);
    }

    public function test_can_filter_brands(): void
    {
        Brand::factory()->create(['name' => 'Test Brand', 'status' => '1']);

        $response = $this->getJson('/admin/api/brands?keyword=Test&status=1');

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);
    }

    public function test_can_get_single_brand(): void
    {
        $brand = Brand::factory()->create();

        $response = $this->getJson("/admin/api/brands/{$brand->id}");

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'id',
                         'name',
                         'slug',
                         'status'
                     ]
                 ])
                 ->assertJson([
                     'success' => true,
                     'data' => ['id' => $brand->id]
                 ]);
    }

    public function test_returns_404_for_nonexistent_brand(): void
    {
        $response = $this->getJson('/admin/api/brands/99999');

        $response->assertStatus(404)
                 ->assertJson(['success' => false]);
    }

    public function test_can_create_brand(): void
    {
        $brandData = [
            'name' => 'New Test Brand',
            'slug' => 'new-test-brand',
            'status' => '1',
            'sort' => 0
        ];

        $response = $this->postJson('/admin/api/brands', $brandData);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data'
                 ])
                 ->assertJson(['success' => true]);

        $this->assertDatabaseHas('brands', [
            'name' => 'New Test Brand',
            'slug' => 'new-test-brand'
        ]);
    }

    public function test_validation_error_on_create_brand(): void
    {
        $response = $this->postJson('/admin/api/brands', []);

        $response->assertStatus(422);
    }

    public function test_can_update_brand(): void
    {
        $brand = Brand::factory()->create(['name' => 'Original Name']);

        $response = $this->putJson("/admin/api/brands/{$brand->id}", [
            'name' => 'Updated Name',
            'status' => '1'
        ]);

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);

        $this->assertDatabaseHas('brands', [
            'id' => $brand->id,
            'name' => 'Updated Name'
        ]);
    }

    public function test_can_delete_brand(): void
    {
        $brand = Brand::factory()->create();

        $response = $this->deleteJson("/admin/api/brands/{$brand->id}");

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('brands', ['id' => $brand->id]);
    }

    public function test_can_update_brand_status(): void
    {
        $brand = Brand::factory()->create(['status' => '1']);

        $response = $this->patchJson("/admin/api/brands/{$brand->id}/status", [
            'status' => '0'
        ]);

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);

        $this->assertDatabaseHas('brands', [
            'id' => $brand->id,
            'status' => '0'
        ]);
    }

    public function test_can_perform_bulk_action(): void
    {
        $brands = Brand::factory()->count(3)->create(['status' => '1']);
        $brandIds = $brands->pluck('id')->toArray();

        $response = $this->postJson('/admin/api/brands/bulk-action', [
            'ids' => $brandIds,
            'action' => 0
        ]);

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);

        foreach ($brandIds as $id) {
            $this->assertDatabaseHas('brands', [
                'id' => $id,
                'status' => '0'
            ]);
        }
    }
}

