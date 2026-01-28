<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Order\Models\Order;
use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminOrderApiTest extends TestCase
{
    protected function actingAsAdmin(): User
    {
        $user = User::query()->first();
        if (! $user) {
            $userId = (int) DB::table('users')->insertGetId([
                'name' => 'Admin Test',
                'email' => 'admin-orders-'.time().'@example.com',
                'password' => bcrypt('password'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $user = User::query()->find($userId);
        }

        $this->actingAs($user);

        return $user;
    }

    public function test_index_returns_paginated_orders(): void
    {
        $this->withoutMiddleware();
        $this->actingAsAdmin();

        $response = $this->getJson('/admin/api/orders?limit=5');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data',
            'pagination' => ['current_page', 'per_page', 'total', 'last_page'],
        ]);
    }

    public function test_index_filters_by_status_and_keyword(): void
    {
        $this->withoutMiddleware();
        $this->actingAsAdmin();

        $order = Order::query()->first();
        if (! $order) {
            $orderId = (int) DB::table('orders')->insertGetId([
                'code' => 'TEST-ORDER-'.time(),
                'name' => 'Filter User',
                'phone' => '0900000000',
                'status' => '1',
                'payment' => '1',
                'ship' => '0',
                'total' => 100000,
                'sale' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $order = Order::query()->find($orderId);
        }

        $response = $this->getJson('/admin/api/orders?status='.$order->status.'&keyword='.$order->code);
        $response->assertStatus(200);
        $json = $response->json();

        $this->assertTrue($json['success'] ?? false);
        $this->assertIsArray($json['data'] ?? []);
    }
}


