<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Admin;
use App\Models\Employee;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class PublicApiTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = Admin::factory()->create();
    }

    public function test_unauthenticated_api_returns_401()
    {
        $response = $this->getJson('/api/v1/attendance');
        $response->assertStatus(401);
    }

    public function test_authenticated_api_can_list_attendance()
    {
        Sanctum::actingAs($this->admin, ['*']);

        $response = $this->getJson('/api/v1/attendance');
        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
    }

    public function test_authenticated_api_can_list_employees()
    {
        Sanctum::actingAs($this->admin, ['*']);

        $response = $this->getJson('/api/v1/employees');
        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
    }

    public function test_authenticated_api_can_list_branches()
    {
        Sanctum::actingAs($this->admin, ['*']);

        $response = $this->getJson('/api/v1/branches');
        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
    }

    public function test_authenticated_api_can_list_leaves()
    {
        Sanctum::actingAs($this->admin, ['*']);

        $response = $this->getJson('/api/v1/leaves');
        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
    }

    public function test_can_create_employee_via_api()
    {
        Sanctum::actingAs($this->admin, ['*']);

        $branch = Branch::factory()->create();

        $response = $this->postJson('/api/v1/employees', [
            'name' => 'Test Employee',
            'branch_id' => $branch->id,
            'pin' => '1234',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('employees', ['name' => 'Test Employee']);
    }
}
