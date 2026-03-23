<?php
// =============================================================
// tests/Unit/RateLimiterTest.php
// =============================================================

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Middleware\RateLimiter;

class RateLimiterTest extends TestCase
{
    private RateLimiter $limiter;
    private string $testDir;

    protected function setUp(): void
    {
        $this->testDir = sys_get_temp_dir() . '/attendance_rate_test_' . uniqid();
        mkdir($this->testDir, 0755, true);
        $this->limiter = new RateLimiter($this->testDir);
    }

    protected function tearDown(): void
    {
        $this->limiter->cleanup(0); // Force cleanup all
        if (is_dir($this->testDir)) {
            @rmdir($this->testDir);
        }
    }

    public function testAllowedWithinLimit(): void
    {
        // Allow 10 requests per minute
        $result = $this->limiter->checkByIP('192.168.1.1', 10, 60);
        $this->assertTrue($result);
    }

    public function testBlockedAfterLimit(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->limiter->checkByIP('192.168.1.2', 5, 60);
        }
        // 6th request should be blocked
        $result = $this->limiter->checkByIP('192.168.1.2', 5, 60);
        $this->assertFalse($result);
    }

    public function testDifferentIPsIndependent(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->limiter->checkByIP('192.168.1.3', 5, 60);
        }
        // Different IP should still be allowed
        $result = $this->limiter->checkByIP('192.168.1.4', 5, 60);
        $this->assertTrue($result);
    }

    public function testTokenRateLimit(): void
    {
        $result = $this->limiter->checkByToken('test-token-123', 10, 60);
        $this->assertTrue($result);
    }
}
