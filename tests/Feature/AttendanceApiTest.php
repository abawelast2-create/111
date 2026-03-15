<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Employee;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AttendanceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_check_in()
    {
        $branch = Branch::factory()->create([
            'latitude' => 24.7136,
            'longitude' => 46.6753,
            'geofence_radius' => 1000,
        ]);

        $employee = Employee::factory()->create([
            'branch_id' => $branch->id,
            'device_uuid' => 'test-device-123',
        ]);

        $response = $this->postJson('/api/check-in', [
            'employee_id' => $employee->id,
            'latitude' => 24.7136,
            'longitude' => 46.6753,
            'device_uuid' => 'test-device-123',
        ]);

        $response->assertStatus(200);
    }

    public function test_check_in_fails_without_required_fields()
    {
        $response = $this->postJson('/api/check-in', []);
        $response->assertStatus(422);
    }

    public function test_employee_can_check_out()
    {
        $branch = Branch::factory()->create([
            'latitude' => 24.7136,
            'longitude' => 46.6753,
            'geofence_radius' => 1000,
        ]);

        $employee = Employee::factory()->create([
            'branch_id' => $branch->id,
            'device_uuid' => 'test-device-123',
        ]);

        // First check in
        $this->postJson('/api/check-in', [
            'employee_id' => $employee->id,
            'latitude' => 24.7136,
            'longitude' => 46.6753,
            'device_uuid' => 'test-device-123',
        ]);

        $response = $this->postJson('/api/check-out', [
            'employee_id' => $employee->id,
            'latitude' => 24.7136,
            'longitude' => 46.6753,
            'device_uuid' => 'test-device-123',
        ]);

        $response->assertStatus(200);
    }
}
