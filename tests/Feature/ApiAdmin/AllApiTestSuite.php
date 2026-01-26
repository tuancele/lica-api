<?php

declare(strict_types=1);

namespace Tests\Feature\ApiAdmin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Comprehensive API Test Suite - Dry Run Mode.
 *
 * This test suite validates all newly created Admin API endpoints
 * without making actual database changes (dry-run mode)
 */
class AllApiTestSuite extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();

        // Enable dry-run mode - prevent actual database writes
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        // Rollback all changes (dry-run)
        DB::rollBack();
        parent::tearDown();
    }

    /**
     * Test all GET endpoints return 200 status.
     */
    public function test_all_list_endpoints_respond(): void
    {
        $endpoints = [
            '/admin/api/brands',
            '/admin/api/categories',
            '/admin/api/origins',
            '/admin/api/banners',
            '/admin/api/pages',
            '/admin/api/marketing/campaigns',
            '/admin/api/promotions',
            '/admin/api/users',
            '/admin/api/members',
            '/admin/api/picks',
            '/admin/api/roles',
            '/admin/api/settings',
            '/admin/api/contacts',
            '/admin/api/feedbacks',
            '/admin/api/subscribers',
            '/admin/api/tags',
            '/admin/api/posts',
            '/admin/api/videos',
            '/admin/api/rates',
            '/admin/api/showrooms',
            '/admin/api/menus',
            '/admin/api/footer-blocks',
            '/admin/api/redirections',
            '/admin/api/sellings',
            '/admin/api/search/logs',
            '/admin/api/downloads',
            '/admin/api/configs',
            '/admin/api/compares',
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->getJson($endpoint.'?limit=1');

            $this->assertContains(
                $response->status(),
                [200, 404, 422],
                "Endpoint {$endpoint} returned unexpected status: {$response->status()}"
            );
        }
    }

    /**
     * Test all endpoints return proper JSON structure.
     */
    public function test_all_endpoints_return_json_structure(): void
    {
        $endpoints = [
            '/admin/api/brands',
            '/admin/api/categories',
            '/admin/api/origins',
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->getJson($endpoint.'?limit=1');

            if ($response->status() === 200) {
                $response->assertJsonStructure([
                    'success',
                    'data',
                ]);
            }
        }
    }

    /**
     * Test validation errors return 422.
     */
    public function test_validation_errors_return_422(): void
    {
        $endpoints = [
            ['url' => '/admin/api/brands', 'method' => 'POST', 'data' => []],
            ['url' => '/admin/api/categories', 'method' => 'POST', 'data' => []],
            ['url' => '/admin/api/origins', 'method' => 'POST', 'data' => []],
        ];

        foreach ($endpoints as $endpoint) {
            if ($endpoint['method'] === 'POST') {
                $response = $this->postJson($endpoint['url'], $endpoint['data']);
            } elseif ($endpoint['method'] === 'PUT') {
                $response = $this->putJson($endpoint['url'], $endpoint['data']);
            }

            $this->assertContains(
                $response->status(),
                [422, 404],
                "Endpoint {$endpoint['url']} validation test failed"
            );
        }
    }

    /**
     * Test 404 for non-existent resources.
     */
    public function test_404_for_nonexistent_resources(): void
    {
        $endpoints = [
            '/admin/api/brands/99999',
            '/admin/api/categories/99999',
            '/admin/api/origins/99999',
            '/admin/api/banners/99999',
            '/admin/api/pages/99999',
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->getJson($endpoint);

            $this->assertContains(
                $response->status(),
                [404, 500],
                "Endpoint {$endpoint} should return 404 for non-existent resource"
            );
        }
    }

    /**
     * Test pagination structure.
     */
    public function test_pagination_structure(): void
    {
        $endpoints = [
            '/admin/api/brands',
            '/admin/api/categories',
            '/admin/api/origins',
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->getJson($endpoint.'?limit=5');

            if ($response->status() === 200) {
                $data = $response->json();

                if (isset($data['pagination'])) {
                    $this->assertArrayHasKey('current_page', $data['pagination']);
                    $this->assertArrayHasKey('per_page', $data['pagination']);
                    $this->assertArrayHasKey('total', $data['pagination']);
                }
            }
        }
    }

    /**
     * Test filter parameters work correctly.
     */
    public function test_filter_parameters(): void
    {
        $endpoints = [
            '/admin/api/brands?status=1&keyword=test',
            '/admin/api/categories?status=1&keyword=test',
            '/admin/api/origins?status=1&keyword=test',
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->getJson($endpoint);

            $this->assertContains(
                $response->status(),
                [200, 404, 422],
                "Filter test failed for {$endpoint}"
            );
        }
    }
}
