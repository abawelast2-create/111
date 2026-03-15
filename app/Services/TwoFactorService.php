<?php

namespace App\Services;

use App\Models\Admin;

class TwoFactorService
{
    /**
     * توليد مفتاح سري جديد للمصادقة الثنائية (TOTP base32)
     */
    public static function generateSecret(): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < 32; $i++) {
            $secret .= $chars[random_int(0, 31)];
        }
        return $secret;
    }

    /**
     * توليد رموز استرداد
     */
    public static function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(bin2hex(random_bytes(4))) . '-' . strtoupper(bin2hex(random_bytes(4)));
        }
        return $codes;
    }

    /**
     * التحقق من رمز TOTP
     */
    public static function verifyCode(string $secret, string $code): bool
    {
        // نافذة زمنية: ±1 خطوة (30 ثانية)
        $timeSlice = floor(time() / 30);

        for ($i = -1; $i <= 1; $i++) {
            $calculatedCode = self::getCode($secret, $timeSlice + $i);
            if (hash_equals($calculatedCode, $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * حساب رمز TOTP لوقت محدد
     */
    private static function getCode(string $secret, int $timeSlice): string
    {
        $secretKey = self::base32Decode($secret);
        $time = pack('N*', 0, $timeSlice);
        $hash = hash_hmac('sha1', $time, $secretKey, true);
        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
        $code = (
            ((ord($hash[$offset]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF)
        ) % 1000000;

        return str_pad((string) $code, 6, '0', STR_PAD_LEFT);
    }

    /**
     * فك تشفير Base32
     */
    private static function base32Decode(string $input): string
    {
        $map = array_flip(str_split('ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'));
        $input = strtoupper(rtrim($input, '='));
        $buffer = 0;
        $bitsLeft = 0;
        $result = '';

        for ($i = 0, $len = strlen($input); $i < $len; $i++) {
            $val = $map[$input[$i]] ?? 0;
            $buffer = ($buffer << 5) | $val;
            $bitsLeft += 5;
            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $result .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }

        return $result;
    }

    /**
     * توليد URI لتطبيق Google Authenticator
     */
    public static function getQrUri(string $secret, string $accountName, string $issuer = 'Sarh Attendance'): string
    {
        $issuer = rawurlencode($issuer);
        $account = rawurlencode($accountName);
        return "otpauth://totp/{$issuer}:{$account}?secret={$secret}&issuer={$issuer}&digits=6&period=30";
    }

    /**
     * التحقق من رمز استرداد
     */
    public static function verifyRecoveryCode(Admin $admin, string $code): bool
    {
        $codes = json_decode($admin->two_factor_recovery_codes, true) ?? [];
        $index = array_search($code, $codes, true);

        if ($index === false) {
            return false;
        }

        // إزالة الرمز المستخدم
        unset($codes[$index]);
        $admin->update(['two_factor_recovery_codes' => json_encode(array_values($codes))]);

        return true;
    }

    /**
     * تفعيل 2FA للمدير
     */
    public static function enable(Admin $admin): array
    {
        $secret = self::generateSecret();
        $recoveryCodes = self::generateRecoveryCodes();

        $admin->update([
            'two_factor_secret'         => encrypt($secret),
            'two_factor_recovery_codes' => encrypt(json_encode($recoveryCodes)),
        ]);

        return [
            'secret'         => $secret,
            'qr_uri'         => self::getQrUri($secret, $admin->username),
            'recovery_codes' => $recoveryCodes,
        ];
    }

    /**
     * تأكيد تفعيل 2FA
     */
    public static function confirm(Admin $admin, string $code): bool
    {
        $secret = decrypt($admin->two_factor_secret);

        if (!self::verifyCode($secret, $code)) {
            return false;
        }

        $admin->update(['two_factor_enabled' => true]);
        return true;
    }

    /**
     * تعطيل 2FA
     */
    public static function disable(Admin $admin): void
    {
        $admin->update([
            'two_factor_enabled'        => false,
            'two_factor_secret'         => null,
            'two_factor_recovery_codes' => null,
        ]);
    }
}
