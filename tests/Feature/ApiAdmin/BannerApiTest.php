<?php

namespace Tests\Feature\ApiAdmin;

use Tests\TestCase;
use App\Modules\Banner\Models\Banner;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BannerApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
    }

    public function test_can_get_banners_list(): void
    {
        Banner::factory()->count(5)->create(['status' => '1']);

        $response = $this->getJson('/admin/api/banners?limit=5');

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);
    }

    public function test_can_get_single_banner(): void
    {
        $banner = Banner::factory()->create();

        $response = $this->getJson("/admin/api/banners/{$banner->id}");

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);
    }

    public function test_can_create_banner(): void
    {
        $bannerData = [
            'name' => 'New Banner',
            'image' => '/uploads/image/banner.jpg',
            'link' => 'https://example.com',
            'status' => '1',
            'sort' => 0
        ];

        $response = $this->postJson('/admin/api/banners', $bannerData);

        $response->assertStatus(201)
                 ->assertJson(['success' => true]);
    }

    public function test_can_update_banner(): void
    {
        $banner = Banner::factory()->create();

        $response = $this->putJson("/admin/api/banners/{$banner->id}", [
            'name' => 'Updated Banner',
            'status' => '1'
        ]);

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);
    }

    public function test_can_delete_banner(): void
    {
        $banner = Banner::factory()->create();

        $response = $this->deleteJson("/admin/api/banners/{$banner->id}");

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);
    }
}

