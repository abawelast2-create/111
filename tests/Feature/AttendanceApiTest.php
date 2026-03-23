<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceApiTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;
    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::factory()->create([
            'latitude'             => 24.7136,
            'longitude'            => 46.6753,
            'geofence_radius'      => 5000,
            'check_in_start_time'  => '00:00:00',
            'check_in_end_time'    => '23:59:00',
            'check_out_start_time' => '00:00:00',
            'check_out_end_time'   => '23:59:00',
        ]);

        $this->employee = Employee::factory()->create([
            'branch_id' => $this->branch->id,
        ]);
    }

    // ─── التحقق من الحقول المطلوبة ────────────────────────────────

    public function test_check_in_requires_token(): void
    {
        $this->postJson('/api/check-in', [
            'latitude'  => 24.7136,
            'longitude' => 46.6753,
        ])->assertStatus(422)->assertJsonValidationErrors('token');
    }

    public function test_check_in_requires_coordinates(): void
    {
        $this->postJson('/api/check-in', [
            'token' => str_repeat('a', 64),
        ])->assertStatus(422)->assertJsonValidationErrors(['latitude', 'longitude']);
    }

    public function test_check_in_token_must_be_64_chars(): void
    {
        $this->postJson('/api/check-in', [
            'token'     => 'short-token',
            'latitude'  => 24.7136,
            'longitude' => 46.6753,
        ])->assertStatus(422)->assertJsonValidationErrors('token');
    }

    // ─── رمز غير صالح ────────────────────────────────────────────

    public function test_check_in_with_invalid_token_returns_403(): void
    {
        $this->postJson('/api/check-in', [
            'token'     => str_repeat('x', 64),
            'latitude'  => 24.7136,
            'longitude' => 46.6753,
        ])->assertStatus(403)
          ->assertJson(['success' => false]);
    }

    public function test_check_out_with_invalid_token_returns_403(): void
    {
        $this->postJson('/api/check-out', [
            'token'     => str_repeat('x', 64),
            'latitude'  => 24.7136,
            'longitude' => 46.6753,
        ])->assertStatus(403)
          ->assertJson(['success' => false]);
    }

    // ─── موظف غير مفعّل ──────────────────────────────────────────

    public function test_inactive_employee_cannot_check_in(): void
    {
        $inactive = Employee::factory()->inactive()->create([
            'branch_id' => $this->branch->id,
        ]);

        $this->postJson('/api/check-in', [
            'token'     => $inactive->unique_token,
            'latitude'  => 24.7136,
            'longitude' => 46.6753,
        ])->assertStatus(403);
    }

    // ─── تسجيل الدخول الصحيح ─────────────────────────────────────

    public function test_employee_can_check_in_within_geofence(): void
    {
        $token = $this->employee->unique_token;

        $response = $this->postJson('/api/check-in', [
            'token'     => $token,
            'latitude'  => 24.7136,
            'longitude' => 46.6753,
        ]);

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $this->employee->id,
            'type'        => 'in',
        ]);
    }

    // ─── خارج النطاق الجغرافي ────────────────────────────────────

    public function test_check_in_outside_geofence_blocked(): void
    {
        $branch = Branch::factory()->create([
            'latitude'             => 24.7136,
            'longitude'            => 46.6753,
            'geofence_radius'      => 10,
            'check_in_start_time'  => '00:00:00',
            'check_in_end_time'    => '23:59:00',
            'check_out_start_time' => '00:00:00',
            'check_out_end_time'   => '23:59:00',
        ]);

        $employee = Employee::factory()->create(['branch_id' => $branch->id]);

        $this->postJson('/api/check-in', [
            'token'     => $employee->unique_token,
            'latitude'  => 25.0000,
            'longitude' => 47.0000,
        ])->assertStatus(200)
          ->assertJson(['success' => false]);
    }

    // ─── منع التسجيل المكرر ──────────────────────────────────────

    public function test_duplicate_check_in_within_5_minutes_blocked(): void
    {
        Attendance::create([
            'employee_id'     => $this->employee->id,
            'type'            => 'in',
            'timestamp'       => now()->subMinutes(2),
            'attendance_date' => today()->toDateString(),
        ]);

        $this->postJson('/api/check-in', [
            'token'     => $this->employee->unique_token,
            'latitude'  => 24.7136,
            'longitude' => 46.6753,
        ])->assertStatus(200)
          ->assertJson(['success' => false]);
    }

    // ─── تسجيل الانصراف ──────────────────────────────────────────

    public function test_employee_can_check_out_within_geofence(): void
    {
        $response = $this->postJson('/api/check-out', [
            'token'     => $this->employee->unique_token,
            'latitude'  => 24.7136,
            'longitude' => 46.6753,
        ]);

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $this->employee->id,
            'type'        => 'out',
        ]);
    }

    public function test_check_out_requires_token(): void
    {
        $this->postJson('/api/check-out', [
            'latitude'  => 24.7136,
            'longitude' => 46.6753,
        ])->assertStatus(422)->assertJsonValidationErrors('token');
    }
}
