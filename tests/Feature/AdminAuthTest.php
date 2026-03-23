<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\LoginAttempt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    // ─── صفحة تسجيل الدخول ───────────────────────────────────────

    public function test_login_page_is_accessible(): void
    {
        $this->get(route('admin.login'))->assertStatus(200);
    }

    public function test_already_authenticated_redirected_from_login(): void
    {
        $admin = Admin::factory()->create();
        $this->withSession(['admin_id' => $admin->id])
             ->get(route('admin.login'))
             ->assertRedirect(route('admin.dashboard'));
    }

    // ─── تسجيل الدخول الصحيح ─────────────────────────────────────

    public function test_admin_can_login_with_valid_credentials(): void
    {
        $admin = Admin::factory()->withPassword('Secret@123')->create();

        $this->post(route('admin.login.submit'), [
            'username' => $admin->username,
            'password' => 'Secret@123',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertEquals($admin->id, session('admin_id'));
    }

    public function test_session_set_after_login(): void
    {
        $admin = Admin::factory()->withPassword('Pass@123')->create();

        $this->post(route('admin.login.submit'), [
            'username' => $admin->username,
            'password' => 'Pass@123',
        ]);

        $this->assertNotNull(session('admin_id'));
        $this->assertEquals($admin->username, session('admin_username'));
        $this->assertNotNull(session('last_activity'));
    }

    // ─── تسجيل دخول فاشل ─────────────────────────────────────────

    public function test_login_fails_with_wrong_password(): void
    {
        $admin = Admin::factory()->withPassword('Secret@123')->create();

        $this->post(route('admin.login.submit'), [
            'username' => $admin->username,
            'password' => 'WrongPassword',
        ])->assertSessionHasErrors('login');

        $this->assertNull(session('admin_id'));
    }

    public function test_login_fails_with_nonexistent_username(): void
    {
        $this->post(route('admin.login.submit'), [
            'username' => 'ghost_user_xyz',
            'password' => 'anything',
        ])->assertSessionHasErrors('login');
    }

    public function test_login_requires_username_field(): void
    {
        $this->post(route('admin.login.submit'), [
            'password' => 'Secret@123',
        ])->assertSessionHasErrors('username');
    }

    public function test_login_requires_password_field(): void
    {
        $this->post(route('admin.login.submit'), [
            'username' => 'admin',
        ])->assertSessionHasErrors('password');
    }

    // ─── حماية ضد القوة الغاشمة ──────────────────────────────────

    public function test_login_blocked_after_max_failed_attempts(): void
    {
        $admin = Admin::factory()->withPassword('Secret@123')->create();

        for ($i = 0; $i < 5; $i++) {
            LoginAttempt::create([
                'ip_address'   => '127.0.0.1',
                'username'     => $admin->username,
                'attempted_at' => now(),
            ]);
        }

        $this->post(route('admin.login.submit'), [
            'username' => $admin->username,
            'password' => 'Secret@123',
        ])->assertSessionHasErrors('login');

        $this->assertNull(session('admin_id'));
    }

    public function test_failed_login_records_attempt(): void
    {
        $admin = Admin::factory()->create();

        $this->post(route('admin.login.submit'), [
            'username' => $admin->username,
            'password' => 'wrong_password',
        ]);

        $this->assertDatabaseHas('login_attempts', [
            'username' => $admin->username,
        ]);
    }

    // ─── تسجيل الخروج ────────────────────────────────────────────

    public function test_admin_can_logout(): void
    {
        $admin = Admin::factory()->create();
        $this->withSession([
            'admin_id'       => $admin->id,
            'admin_username' => $admin->username,
        ])->post(route('admin.logout'))
          ->assertRedirect(route('admin.login'));

        $this->assertNull(session('admin_id'));
    }

    // ─── حماية المسارات ──────────────────────────────────────────

    public function test_unauthenticated_redirected_to_login(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
    }

    public function test_multiple_protected_routes_redirect_unauthenticated(): void
    {
        $routes = [
            route('admin.employees.index'),
            route('admin.branches.index'),
            route('admin.leaves.index'),
        ];

        foreach ($routes as $url) {
            $this->get($url)->assertRedirect(route('admin.login'));
        }
    }

    // ─── لوحة التحكم ─────────────────────────────────────────────

    public function test_authenticated_admin_can_access_dashboard(): void
    {
        $admin = Admin::factory()->superAdmin()->create();

        $this->withSession([
            'admin_id'      => $admin->id,
            'admin_username'=> $admin->username,
            'admin_name'    => $admin->full_name,
            'last_activity' => time(),
        ])->get(route('admin.dashboard'))->assertStatus(200);
    }
}
