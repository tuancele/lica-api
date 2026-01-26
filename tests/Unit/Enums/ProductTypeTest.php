<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\ProductType;
use Tests\TestCase;

class ProductTypeTest extends TestCase
{
    /** @test */
    public function it_has_product_taxonomy_and_post_types()
    {
        $this->assertEquals('product', ProductType::PRODUCT->value);
        $this->assertEquals('taxonomy', ProductType::TAXONOMY->value);
        $this->assertEquals('post', ProductType::POST->value);
    }

    /** @test */
    public function it_can_get_label_for_type()
    {
        $this->assertEquals('Sản phẩm', ProductType::PRODUCT->label());
        $this->assertEquals('Danh mục', ProductType::TAXONOMY->label());
        $this->assertEquals('Bài viết', ProductType::POST->label());
    }

    /** @test */
    public function it_can_convert_to_array()
    {
        $array = ProductType::toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('product', $array);
        $this->assertArrayHasKey('taxonomy', $array);
        $this->assertArrayHasKey('post', $array);
    }
}
