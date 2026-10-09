<?php

namespace Tests\Unit;

use App\Support\R2;
use PHPUnit\Framework\TestCase;

class R2Test extends TestCase
{
    public function test_site_folders_are_public(): void
    {
        foreach (['cars/a.jpg', 'blog/x.png', 'services/s.webp', 'testimonials/t.jpg', 'site/logo.svg', '/cars/a.jpg'] as $path) {
            $this->assertTrue(R2::isPublic($path), $path);
        }
    }

    public function test_contracts_and_everything_else_are_private(): void
    {
        foreach ([
            'car-manager/contract-images/a.jpg', 'car-manager/return-images/b.jpg', 'customers/license.jpg',
            'carsX/a.jpg', 'secret.txt', '', 'cars', '../cars/a.jpg',
        ] as $path) {
            $this->assertFalse(R2::isPublic($path), $path);
        }
    }
}
