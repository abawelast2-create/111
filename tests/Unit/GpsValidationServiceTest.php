<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\GpsValidationService;
use App\Models\Employee;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GpsValidationServiceTest extends TestCase
{
    use RefreshDatabase;

    private GpsValidationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new GpsValidationService();
    }

    public function test_valid_location_passes_validation()
    {
        $branch = Branch::factory()->create([
            'latitude' => 24.7136,
            'longitude' => 46.6753,
            'geofence_radius' => 500,
        ]);

        $employee = Employee::factory()->create([
            'branch_id' => $branch->id,
        ]);

        $result = $this->service->validate($employee, [
            'latitude' => 24.7136,
            'longitude' => 46.6753,
            'accuracy' => 10,
            'mock_location' => false,
        ]);

        $this->assertGreaterThanOrEqual(50, $result['score']);
        $this->assertFalse($result['mock_location_detected']);
    }

    public function test_mock_location_detected()
    {
        $branch = Branch::factory()->create([
            'latitude' => 24.7136,
            'longitude' => 46.6753,
        ]);

        $employee = Employee::factory()->create([
            'branch_id' => $branch->id,
        ]);

        $result = $this->service->validate($employee, [
            'latitude' => 24.7136,
            'longitude' => 46.6753,
            'accuracy' => 10,
            'mock_location' => true,
        ]);

        $this->assertTrue($result['mock_location_detected']);
        $this->assertLessThan(50, $result['score']);
    }

    public function test_low_accuracy_reduces_score()
    {
        $branch = Branch::factory()->create([
            'latitude' => 24.7136,
            'longitude' => 46.6753,
        ]);

        $employee = Employee::factory()->create([
            'branch_id' => $branch->id,
        ]);

        $result = $this->service->validate($employee, [
            'latitude' => 24.7136,
            'longitude' => 46.6753,
            'accuracy' => 900,
            'mock_location' => false,
        ]);

        $this->assertLessThan(80, $result['score']);
    }

    public function test_far_location_reduces_score()
    {
        $branch = Branch::factory()->create([
            'latitude' => 24.7136,
            'longitude' => 46.6753,
            'geofence_radius' => 100,
        ]);

        $employee = Employee::factory()->create([
            'branch_id' => $branch->id,
        ]);

        // Location ~10km away
        $result = $this->service->validate($employee, [
            'latitude' => 24.8000,
            'longitude' => 46.7500,
            'accuracy' => 10,
            'mock_location' => false,
        ]);

        $this->assertLessThan(50, $result['score']);
    }
}
