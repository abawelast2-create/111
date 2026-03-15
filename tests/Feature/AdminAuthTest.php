<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Admin;
use App\Models\Employee;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_accessible()
    {
        $response = $this->get(route('admin.login'));
        $response->assertStatus(200);
    }

    public function test_admin_can_login_with_valid_credentials()
    {
        $admin = Admin::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post(route('admin.login.submit'), [
            'username' => $admin->username,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertEquals($admin->id, session('admin_id'));
    }

    public function test_admin_cannot_login_with_invalid_credentials()
    {
        $admin = Admin::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post(route('admin.login.submit'), [
            'username' => $admin->username,
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors();
    }

    public function test_unauthenticated_admin_redirected_to_login()
    {
        $response = $this->get(route('admin.dashboard'));
        $response->assertRedirect(route('admin.login'));
    }
}
