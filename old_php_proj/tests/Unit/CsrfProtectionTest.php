<?php
// =============================================================
// tests/Unit/CsrfProtectionTest.php
// =============================================================

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Middleware\CsrfProtection;

class CsrfProtectionTest extends TestCase
{
    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            // Suppress headers-already-sent warning in CLI
            @session_start();
        }
    }

    public function testGenerateTokenNotEmpty(): void
    {
        $token = CsrfProtection::generateToken();
        $this->assertNotEmpty($token);
        $this->assertEquals(64, strlen($token)); // 32 bytes hex = 64 chars
    }

    public function testGenerateTokenIsStored(): void
    {
        $token = CsrfProtection::generateToken();
        $this->assertArrayHasKey('csrf_tokens', $_SESSION);
        $this->assertContains($token, $_SESSION['csrf_tokens']);
    }

    public function testVerifyValidToken(): void
    {
        $token = CsrfProtection::generateToken();
        $this->assertTrue(CsrfProtection::verifyToken($token));
    }

    public function testVerifyInvalidToken(): void
    {
        $this->assertFalse(CsrfProtection::verifyToken('invalid_token'));
    }

    public function testVerifyEmptyToken(): void
    {
        $this->assertFalse(CsrfProtection::verifyToken(''));
    }

    public function testTokenIsOneTimeUse(): void
    {
        $token = CsrfProtection::generateToken();
        CsrfProtection::verifyToken($token); // Consumes token
        $this->assertFalse(CsrfProtection::verifyToken($token)); // Second use fails
    }

    public function testFieldOutputsHiddenInput(): void
    {
        $field = CsrfProtection::field();
        $this->assertStringContainsString('<input type="hidden"', $field);
        $this->assertStringContainsString('name="csrf_token"', $field);
    }

    public function testMetaTagOutputsMeta(): void
    {
        $meta = CsrfProtection::metaTag();
        $this->assertStringContainsString('<meta name="csrf-token"', $meta);
    }

    public function testMultipleTokensStored(): void
    {
        $_SESSION['csrf_tokens'] = [];
        $tokens = [];
        for ($i = 0; $i < 5; $i++) {
            $tokens[] = CsrfProtection::generateToken();
        }
        $this->assertCount(5, $_SESSION['csrf_tokens']);
    }
}
