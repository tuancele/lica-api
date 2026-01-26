<?php

declare(strict_types=1);
namespace Tests\Feature\ApiAdmin;

use Tests\TestCase;
use App\Modules\Origin\Models\Origin;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OriginApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
    }

    public function test_can_get_origins_list(): void
    {
        Origin::factory()->count(5)->create(['status' => '1']);

        $response = $this->getJson('/admin/api/origins?limit=5');

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);
    }

    public function test_can_get_single_origin(): void
    {
        $origin = Origin::factory()->create();

        $response = $this->getJson("/admin/api/origins/{$origin->id}");

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);
    }

    public function test_can_create_origin(): void
    {
        $originData = [
            'name' => 'New Origin',
            'slug' => 'new-origin',
            'status' => '1',
            'sort' => 0
        ];

        $response = $this->postJson('/admin/api/origins', $originData);

        $response->assertStatus(201)
                 ->assertJson(['success' => true]);
    }

    public function test_can_update_origin(): void
    {
        $origin = Origin::factory()->create();

        $response = $this->putJson("/admin/api/origins/{$origin->id}", [
            'name' => 'Updated Origin',
            'status' => '1'
        ]);

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);
    }

    public function test_can_delete_origin(): void
    {
        $origin = Origin::factory()->create();

        $response = $this->deleteJson("/admin/api/origins/{$origin->id}");

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);
    }
}

