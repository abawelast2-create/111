<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\TwoFactorService;

class TwoFactorServiceTest extends TestCase
{
    private TwoFactorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TwoFactorService();
    }

    public function test_generates_valid_secret()
    {
        $secret = $this->service->generateSecret();
        $this->assertNotEmpty($secret);
        $this->assertEquals(32, strlen($secret));
    }

    public function test_generates_valid_qr_uri()
    {
        $uri = $this->service->getQrUri('JBSWY3DPEHPK3PXP', 'admin@test.com');
        $this->assertStringStartsWith('otpauth://totp/', $uri);
        $this->assertStringContainsString('secret=JBSWY3DPEHPK3PXP', $uri);
        $this->assertStringContainsString('admin%40test.com', $uri);
    }

    public function test_generates_recovery_codes()
    {
        $codes = $this->service->generateRecoveryCodes();
        $this->assertCount(8, $codes);
        foreach ($codes as $code) {
            $this->assertMatchesRegularExpression('/^[A-Za-z0-9]+-[A-Za-z0-9]+$/', $code);
        }
    }

    public function test_verify_code_with_known_secret()
    {
        // This tests the TOTP algorithm is correctly implemented
        $secret = 'JBSWY3DPEHPK3PXP';
        // Generate a code for current time and verify it
        $code = $this->generateTOTP($secret);
        $this->assertTrue($this->service->verifyCode($secret, $code));
    }

    public function test_invalid_code_fails_verification()
    {
        $secret = 'JBSWY3DPEHPK3PXP';
        $this->assertFalse($this->service->verifyCode($secret, '000000'));
    }

    /**
     * Helper to generate TOTP code for testing
     */
    private function generateTOTP(string $secret): string
    {
        $key = $this->base32Decode($secret);
        $time = pack('N*', 0, floor(time() / 30));
        $hash = hash_hmac('sha1', $time, $key, true);
        $offset = ord(substr($hash, -1)) & 0xF;
        $code = (
            ((ord($hash[$offset]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF)
        ) % 1000000;
        return str_pad((string) $code, 6, '0', STR_PAD_LEFT);
    }

    private function base32Decode(string $input): string
    {
        $map = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $input = strtoupper($input);
        $buffer = 0;
        $bitsLeft = 0;
        $result = '';
        for ($i = 0; $i < strlen($input); $i++) {
            $val = strpos($map, $input[$i]);
            if ($val === false) continue;
            $buffer = ($buffer << 5) | $val;
            $bitsLeft += 5;
            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $result .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }
        return $result;
    }
}
