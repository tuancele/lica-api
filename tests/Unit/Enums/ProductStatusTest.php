<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\ProductStatus;
use Tests\TestCase;

class ProductStatusTest extends TestCase
{
    /** @test */
    public function it_has_active_and_inactive_statuses()
    {
        $this->assertEquals('1', ProductStatus::ACTIVE->value);
        $this->assertEquals('0', ProductStatus::INACTIVE->value);
    }

    /** @test */
    public function it_can_get_label_for_status()
    {
        $this->assertEquals('Hoạt động', ProductStatus::ACTIVE->label());
        $this->assertEquals('Không hoạt động', ProductStatus::INACTIVE->label());
    }

    /** @test */
    public function it_can_get_badge_class_for_status()
    {
        $this->assertEquals('badge-success', ProductStatus::ACTIVE->badgeClass());
        $this->assertEquals('badge-danger', ProductStatus::INACTIVE->badgeClass());
    }

    /** @test */
    public function it_can_convert_to_array()
    {
        $array = ProductStatus::toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('1', $array);
        $this->assertArrayHasKey('0', $array);
        $this->assertEquals('Hoạt động', $array['1']);
        $this->assertEquals('Không hoạt động', $array['0']);
    }
}
