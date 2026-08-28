<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_login(): void
    {
        Admin::factory()->create([
            'email'    => 'admin@example.com',
            'password' => Hash::make('secret123'),
        ]);

        $res = $this->post('/admin/login', [
            'email'    => 'admin@example.com',
            'password' => 'secret123',
        ]);

        $res->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs(Admin::first(), 'admin');
    }

    public function test_login_is_rate_limited_after_failures(): void
    {
        Admin::factory()->create([
            'email'    => 'admin@example.com',
            'password' => Hash::make('secret123'),
        ]);

        // 连续 5 次错误密码
        for ($i = 0; $i < 5; $i++) {
            $this->post('/admin/login', [
                'email'    => 'admin@example.com',
                'password' => 'wrong',
            ]);
        }

        // 第 6 次即使密码正确也应被限流
        $res = $this->from('/admin/login')->post('/admin/login', [
            'email'    => 'admin@example.com',
            'password' => 'secret123',
        ]);

        $res->assertSessionHasErrors('email');
        $this->assertGuest('admin');
    }

    public function test_dashboard_requires_auth(): void
    {
        $this->get('/admin')->assertRedirect(route('admin.login'));
    }
}
