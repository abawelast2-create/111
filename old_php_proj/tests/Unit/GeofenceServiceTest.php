<?php
// =============================================================
// tests/Unit/GeofenceServiceTest.php
// =============================================================

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\GeofenceService;

class GeofenceServiceTest extends TestCase
{
    private GeofenceService $geo;

    protected function setUp(): void
    {
        $this->geo = new GeofenceService();
    }

    public function testCalculateDistanceSamePoint(): void
    {
        $distance = $this->geo->calculateDistance(24.7136, 46.6753, 24.7136, 46.6753);
        $this->assertEquals(0, $distance);
    }

    public function testCalculateDistanceKnownPoints(): void
    {
        // Riyadh (24.7136, 46.6753) to Jeddah (21.4858, 39.1925)
        $distance = $this->geo->calculateDistance(24.7136, 46.6753, 21.4858, 39.1925);
        // ~850 km
        $this->assertGreaterThan(800000, $distance);
        $this->assertLessThan(900000, $distance);
    }

    public function testCalculateDistanceShort(): void
    {
        // Two points ~100m apart
        $lat1 = 24.7136;
        $lng1 = 46.6753;
        $lat2 = 24.7145; // ~100m north
        $lng2 = 46.6753;

        $distance = $this->geo->calculateDistance($lat1, $lng1, $lat2, $lng2);
        $this->assertGreaterThan(80, $distance);
        $this->assertLessThan(120, $distance);
    }

    public function testIsWithinGeofenceInside(): void
    {
        $result = $this->geo->isWithinGeofence(24.7136, 46.6753, 24.7136, 46.6753, 100);
        $this->assertTrue($result);
    }

    public function testIsWithinGeofenceOutside(): void
    {
        // 1km away, 100m radius
        $result = $this->geo->isWithinGeofence(24.7136, 46.6753, 24.7236, 46.6753, 100);
        $this->assertFalse($result);
    }

    public function testIsWithinGeofenceBoundary(): void
    {
        // ~90m away, 100m radius
        $result = $this->geo->isWithinGeofence(24.71360, 46.67530, 24.71440, 46.67530, 100);
        $this->assertTrue($result);
    }
}
