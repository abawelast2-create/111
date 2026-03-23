<?php
// =============================================================
// src/Middleware/RateLimiter.php - Rate Limiting متقدم (Token Bucket)
// =============================================================

namespace App\Middleware;

class RateLimiter
{
    private string $storageDir;

    public function __construct(?string $storageDir = null)
    {
        $this->storageDir = $storageDir ?? sys_get_temp_dir() . '/attendance_rate_limit';
        if (!is_dir($this->storageDir)) {
            @mkdir($this->storageDir, 0700, true);
        }
    }

    /**
     * Token Bucket Algorithm
     * أكثر مرونة من العد البسيط - يسمح بدفعات قصيرة (bursts)
     *
     * @param string $key معرف فريد (IP + endpoint)
     * @param int $maxTokens الحد الأقصى للرموز (السعة)
     * @param float $refillRate معدل إعادة التعبئة (رموز/ثانية)
     * @return array ['allowed' => bool, 'remaining' => int, 'retry_after' => int]
     */
    public function check(string $key, int $maxTokens = 60, float $refillRate = 1.0): array
    {
        $file = $this->storageDir . '/' . $this->sanitizeKey($key) . '.json';
        $now = microtime(true);

        $bucket = $this->loadBucket($file);

        if ($bucket === null) {
            $bucket = [
                'tokens' => $maxTokens - 1,
                'last_refill' => $now,
                'blocked_until' => 0,
            ];
            $this->saveBucket($file, $bucket);
            return ['allowed' => true, 'remaining' => $bucket['tokens'], 'retry_after' => 0];
        }

        // هل محظور؟
        if ($bucket['blocked_until'] > $now) {
            $retryAfter = (int)ceil($bucket['blocked_until'] - $now);
            return ['allowed' => false, 'remaining' => 0, 'retry_after' => $retryAfter];
        }

        // إعادة تعبئة الرموز بناءً على الوقت المنقضي
        $elapsed = $now - $bucket['last_refill'];
        $newTokens = $elapsed * $refillRate;
        $bucket['tokens'] = min($maxTokens, $bucket['tokens'] + $newTokens);
        $bucket['last_refill'] = $now;

        if ($bucket['tokens'] < 1) {
            // محظور - قفل لمدة 60 ثانية
            $bucket['blocked_until'] = $now + 60;
            $this->saveBucket($file, $bucket);
            return ['allowed' => false, 'remaining' => 0, 'retry_after' => 60];
        }

        // استهلاك رمز
        $bucket['tokens'] -= 1;
        $bucket['blocked_until'] = 0;
        $this->saveBucket($file, $bucket);

        return [
            'allowed' => true,
            'remaining' => (int)$bucket['tokens'],
            'retry_after' => 0,
        ];
    }

    /**
     * Rate limit بالـ IP + مسار
     */
    public function checkByIP(string $endpoint, int $maxRequests = 60, int $windowSeconds = 60): array
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $key = $endpoint . '_' . $ip;
        $refillRate = $maxRequests / $windowSeconds;
        return $this->check($key, $maxRequests, $refillRate);
    }

    /**
     * Rate limit بالـ Token (per employee)
     */
    public function checkByToken(string $token, string $endpoint, int $maxRequests = 30, int $windowSeconds = 60): array
    {
        $key = $endpoint . '_token_' . hash('sha256', $token);
        $refillRate = $maxRequests / $windowSeconds;
        return $this->check($key, $maxRequests, $refillRate);
    }

    /**
     * إرسال رد 429
     */
    public function denyResponse(int $retryAfter = 60): void
    {
        http_response_code(429);
        header('Content-Type: application/json; charset=utf-8');
        header("Retry-After: {$retryAfter}");
        echo json_encode([
            'success' => false,
            'message' => 'تم تجاوز الحد المسموح من الطلبات. حاول مرة أخرى بعد ' . $retryAfter . ' ثانية.',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * تنظيف الملفات القديمة
     */
    public function cleanup(int $maxAgeSeconds = 3600): void
    {
        if (!is_dir($this->storageDir)) return;
        $now = time();
        foreach (glob($this->storageDir . '/*.json') as $file) {
            if (($now - filemtime($file)) > $maxAgeSeconds) {
                @unlink($file);
            }
        }
    }

    private function sanitizeKey(string $key): string
    {
        return preg_replace('/[^a-zA-Z0-9._:-]/', '_', $key);
    }

    private function loadBucket(string $file): ?array
    {
        if (!file_exists($file)) return null;
        $content = @file_get_contents($file);
        if (!$content) return null;
        return json_decode($content, true);
    }

    private function saveBucket(string $file, array $bucket): void
    {
        @file_put_contents($file, json_encode($bucket), LOCK_EX);
    }
}
