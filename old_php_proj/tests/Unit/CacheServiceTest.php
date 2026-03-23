<?php
// =============================================================
// tests/Unit/CacheServiceTest.php
// =============================================================

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\CacheService;

class CacheServiceTest extends TestCase
{
    private CacheService $cache;
    private string $testDir;

    protected function setUp(): void
    {
        $this->testDir = sys_get_temp_dir() . '/attendance_cache_test_' . uniqid();
        mkdir($this->testDir, 0755, true);
        $this->cache = new CacheService($this->testDir);
    }

    protected function tearDown(): void
    {
        $this->cache->flush();
        if (is_dir($this->testDir)) {
            rmdir($this->testDir);
        }
    }

    public function testSetAndGet(): void
    {
        $this->cache->set('test_key', 'hello', 60);
        $this->assertEquals('hello', $this->cache->get('test_key'));
    }

    public function testGetMissing(): void
    {
        $this->assertNull($this->cache->get('nonexistent'));
    }

    public function testGetDefault(): void
    {
        $result = $this->cache->get('nonexistent', 'default_val');
        $this->assertEquals('default_val', $result);
    }

    public function testGetExpired(): void
    {
        $this->cache->set('expiring', 'value', -1);
        $this->assertNull($this->cache->get('expiring'));
    }

    public function testForget(): void
    {
        $this->cache->set('to_forget', 'value', 60);
        $this->cache->forget('to_forget');
        $this->assertNull($this->cache->get('to_forget'));
    }

    public function testRemember(): void
    {
        $called = 0;
        $callback = function () use (&$called) {
            $called++;
            return 'computed_value';
        };

        $result1 = $this->cache->remember('remember_key', 60, $callback);
        $result2 = $this->cache->remember('remember_key', 60, $callback);

        $this->assertEquals('computed_value', $result1);
        $this->assertEquals('computed_value', $result2);
        $this->assertEquals(1, $called); // Callback only called once
    }

    public function testFlush(): void
    {
        $this->cache->set('key1', 'val1', 60);
        $this->cache->set('key2', 'val2', 60);
        $this->cache->flush();

        $this->assertNull($this->cache->get('key1'));
        $this->assertNull($this->cache->get('key2'));
    }

    public function testArrayValue(): void
    {
        $data = ['name' => 'test', 'count' => 42];
        $this->cache->set('array_key', $data, 60);
        $this->assertEquals($data, $this->cache->get('array_key'));
    }

    public function testForgetByPrefix(): void
    {
        $this->cache->set('prefix_one', 'a', 60);
        $this->cache->set('prefix_two', 'b', 60);
        $this->cache->set('other_key', 'c', 60);

        $this->cache->forgetByPrefix('prefix_');

        $this->assertNull($this->cache->get('prefix_one'));
        $this->assertNull($this->cache->get('prefix_two'));
        $this->assertEquals('c', $this->cache->get('other_key'));
    }
}
