<?php

namespace Tests\Unit;

use App\Services\GeofenceService;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeofenceServiceTest extends TestCase
{
    use RefreshDatabase;

    // ─── حساب المسافة ────────────────────────────────────────────────────────

    public function test_same_point_returns_zero_distance(): void
    {
        $distance = GeofenceService::calculateDistance(24.7136, 46.6753, 24.7136, 46.6753);
        $this->assertEquals(0.0, round($distance, 2));
    }

    public function test_distance_between_known_points(): void
    {
        // الرياض → جدة: ~860 كم
        $distance = GeofenceService::calculateDistance(24.7136, 46.6753, 21.4858, 39.1925);
        $this->assertGreaterThan(800000, $distance);
        $this->assertLessThan(920000, $distance);
    }

    public function test_distance_is_symmetric(): void
    {
        $d1 = GeofenceService::calculateDistance(24.7136, 46.6753, 21.4858, 39.1925);
        $d2 = GeofenceService::calculateDistance(21.4858, 39.1925, 24.7136, 46.6753);

        $this->assertEqualsWithDelta($d1, $d2, 0.001);
    }

    public function test_distance_within_same_city_is_reasonable(): void
    {
        // نقطتان في الرياض (~2 كم)
        $distance = GeofenceService::calculateDistance(24.7136, 46.6753, 24.7300, 46.6900);
        $this->assertGreaterThan(1000, $distance);
        $this->assertLessThan(5000, $distance);
    }

    public function test_distance_is_positive(): void
    {
        $d = GeofenceService::calculateDistance(24.0, 46.0, 25.0, 47.0);
        $this->assertGreaterThan(0, $d);
    }

    // ─── التحقق من الجيوفينس ─────────────────────────────────────────────────

    public function test_within_geofence_returns_allowed_true(): void
    {
        $branch = Branch::factory()->create([
            'latitude'        => 24.7136,
            'longitude'       => 46.6753,
            'geofence_radius' => 500,
        ]);

        $result = GeofenceService::isWithinGeofence(24.7136, 46.6753, $branch->id);

        $this->assertTrue($result['allowed']);
        $this->assertEquals(0, $result['distance']);
    }

    public function test_outside_geofence_returns_allowed_false(): void
    {
        $branch = Branch::factory()->create([
            'latitude'        => 24.7136,
            'longitude'       => 46.6753,
            'geofence_radius' => 100,
        ]);

        // نقطة بعيدة ~2 كم
        $result = GeofenceService::isWithinGeofence(24.7300, 46.6900, $branch->id);

        $this->assertFalse($result['allowed']);
        $this->assertGreaterThan(100, $result['distance']);
    }

    public function test_geofence_result_contains_required_keys(): void
    {
        $branch = Branch::factory()->create();

        $result = GeofenceService::isWithinGeofence(24.7136, 46.6753, $branch->id);

        $this->assertArrayHasKey('allowed', $result);
        $this->assertArrayHasKey('distance', $result);
        $this->assertArrayHasKey('radius', $result);
        $this->assertArrayHasKey('message', $result);
    }

    public function test_null_branch_id_falls_back_to_settings(): void
    {
        // بدون branch_id وبدون settings، يُسمح دائماً
        $result = GeofenceService::isWithinGeofence(24.7136, 46.6753, null);

        $this->assertTrue($result['allowed']);
    }

    public function test_geofence_edge_case_exactly_on_radius(): void
    {
        $branch = Branch::factory()->create([
            'latitude'        => 24.7136,
            'longitude'       => 46.6753,
            'geofence_radius' => 500,
        ]);

        // نقطة قريبة جداً (~100م)
        $result = GeofenceService::isWithinGeofence(24.7136, 46.6800, $branch->id);
        // ~400 متر → داخل النطاق
        $this->assertTrue($result['allowed']);
    }

    public function test_inactive_branch_falls_back_to_no_location(): void
    {
        $branch = Branch::factory()->create([
            'latitude'        => 24.7136,
            'longitude'       => 46.6753,
            'geofence_radius' => 100,
            'is_active'       => false,
        ]);

        // الفرع غير نشط → يرجع إلى Settings (التي تكون 0,0 في الاختبارات) → مسموح
        $result = GeofenceService::isWithinGeofence(25.0000, 47.0000, $branch->id);

        $this->assertTrue($result['allowed']);
    }

    public function test_message_contains_distance_info(): void
    {
        $branch = Branch::factory()->create([
            'latitude'        => 24.7136,
            'longitude'       => 46.6753,
            'geofence_radius' => 100,
        ]);

        $result = GeofenceService::isWithinGeofence(25.0000, 47.0000, $branch->id);

        $this->assertStringContainsString('متر', $result['message']);
    }
}
