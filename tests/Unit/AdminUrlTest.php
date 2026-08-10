<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\AdminUrl;
use PHPUnit\Framework\TestCase;

class AdminUrlTest extends TestCase
{
    public function test_it_removes_a_stale_origin_and_preserves_the_admin_target(): void
    {
        $this->assertSame(
            '/admin/assets/15?tab=rights#documents',
            AdminUrl::relative('http://archive.example:8080/admin/assets/15?tab=rights#documents'),
        );
    }

    public function test_it_rejects_non_admin_targets(): void
    {
        $this->assertNull(AdminUrl::relative('https://example.com/not-an-admin-page'));
        $this->assertNull(AdminUrl::relative('//example.com/admin/users'));
    }
}
