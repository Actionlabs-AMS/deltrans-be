<?php

namespace Tests\Unit\Support;

use App\Support\AdminAppUrl;
use Tests\TestCase;

class AdminAppUrlTest extends TestCase
{
    public function test_empty_app_url_still_produces_absolute_admin_links(): void
    {
        config(['app.url' => '']);
        config(['app.admin_app_url' => null]);

        $url = AdminAppUrl::to('login');

        $this->assertMatchesRegularExpression('#^https?://#', $url);
        $this->assertStringEndsWith('/login', $url);
    }

    public function test_admin_app_url_override_is_used_when_valid(): void
    {
        config(['app.url' => 'http://api.example.test']);
        config(['app.admin_app_url' => 'https://admin.example.test']);

        $this->assertSame('https://admin.example.test/login', AdminAppUrl::to('login'));
    }
}
