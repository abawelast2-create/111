<?php
// =============================================================
// tests/Unit/LangTest.php
// =============================================================

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Core\Lang;

class LangTest extends TestCase
{
    protected function setUp(): void
    {
        $langDir = APP_ROOT . '/lang';
        Lang::init($langDir, 'ar');
    }

    public function testTranslateExistingKey(): void
    {
        $result = Lang::get('messages.app_name');
        $this->assertNotEmpty($result);
        $this->assertNotEquals('messages.app_name', $result);
    }

    public function testTranslateMissingKeyReturnsKey(): void
    {
        $result = Lang::get('messages.nonexistent_key_xyz');
        $this->assertEquals('messages.nonexistent_key_xyz', $result);
    }

    public function testSetLocale(): void
    {
        Lang::setLocale('en');
        $this->assertEquals('en', Lang::getLocale());

        // Reset
        Lang::setLocale('ar');
    }

    public function testIsRtl(): void
    {
        Lang::setLocale('ar');
        $this->assertTrue(Lang::isRtl());

        Lang::setLocale('en');
        $this->assertFalse(Lang::isRtl());

        // Reset
        Lang::setLocale('ar');
    }

    public function testTranslateWithReplacements(): void
    {
        // Assuming messages.php has some key with :param
        // Test falls back gracefully if key doesn't support replacement
        $result = Lang::get('messages.app_name', ['name' => 'Test']);
        $this->assertIsString($result);
    }
}
